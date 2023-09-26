/**
 * 管理画面 発注依頼先 ユーザー一覧 JS
 */

// 登録・編集フォーム モーダル コンポーネント

/**
 * メインブロック
 */
Vue.component('purchasing-agent-user-form-modal', {
    template: '#templatePurchasingAgentUserForm'
  , delimiters: ['(%', '%)']
  , props: [
    'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
  ]
  , data: function() {
    return {
        saveUrl: null
      , messageState: {}
      , nowLoading: false
      , item: {}
    };
  }
  , computed: {
    caption: function() {
      var caption = 'ユーザー編集';
      return caption;
    }
  }

  , watch : {
  }

  , mounted: function() {
    this.$nextTick(function (){
      var self = this;
      var modal = $(self.$el);

      self.messageState = new PartsGlobalMessageState();
      self.saveUrl = $(self.$el).data('saveUrl');

      // イベント登録
      self.$watch('state.show', function(newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });
      self.$watch('state.currentItem', function(newValue) {
        self.item = $.extend(true, { id: 'new' }, newValue);
      });

      // -- open前
      modal.on('show.bs.modal', function(e) {
        self.messageState.clear();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.hideModal(); // 外部から閉じられた時の手当
        }
      })
    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
      this.reset();
    }

    , save: function() {
      const self = this;

      self.messageState.clear();
      self.nowLoading = true;

      var data = {
        item: self.item
      };

      $.ajax({
          type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert-success');
            if (result.item) {
              self.$emit('update-item', result.item);
              self.item.id = Number(result.item.id); // new の場合のため、IDだけ補完しておく（他はフォームに残っている）
            }

          } else {
            var message = result.message.length > 0 ? result.message : '更新できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
          self.nowLoading = false;
        });
    }

    , reset: function() {
      this.item = {};
      this.state.currentItem = null;
    }

    /**
     * 親イベント実行
     */
    , emitParentEvent: function(event, item) {
      this.$emit(event, item);
    }
  }
});



// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentPurchasingAgentUserListItem = {
    template: '#templatePurchasingAgentUserListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
      userListUrlBase: ""
    };
  }
  , computed: {
      displayCreated: function() {
      return this.item.createdAt ? $.Plusnao.Date.getDateString(this.item.createdAt, true) : '';
    }
    , displayUpdated: function() {
      return this.item.updatedAt ? $.Plusnao.Date.getDateString(this.item.updatedAt, true) : '';
    }
    , userListUrl: function() {
      return this.userListUrlBase.replace(/999999/g, this.item.id);
    }
  }
  , mounted: function() {
    this.$nextTick(function() {
      this.userListUrlBase = $(this.$el).data('userListUrlBase');
    });
  }
  , methods: {
    showEditForm: function() {
      this.$emit('show-edit-form', this.item);
    }

    , remove: function() {
      this.$emit('remove-item', this.item);
    }

  }
};


// 一覧画面 一覧表
var vmPurchasingAgentUserList = new Vue({
    el: '#purchasingAgentUserList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , removeUrl: null

    , pageItemNum: 50
    , pageItemNumList: [ 2, 20, 50, 100 ]
    , page: 1

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentPurchasingAgentUserListItem // 一覧テーブル
  }

  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.removeUrl = $(this.$el).data('removeUrl');

      if (PURCHASING_AGENT_USER_LIST_DATA) {
        for (var i = 0; i < PURCHASING_AGENT_USER_LIST_DATA.length; i++) {
          var item = PURCHASING_AGENT_USER_LIST_DATA[i];
          var row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }
    });
  }

  , computed: {

    totalItemNum: function() {
      return this.listData.length;
    }

    // sort, filter済みデータ
    , listData: function() {
      var self = this;
      var list = self.list.slice(); // 破壊防止

      list.sort(function(a, b) {
        return (a.displayOrder - b.displayOrder);
      });

      return list;
    }

    , pageData: function() {
      var startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , showFormModal: function (item) {
      if (!item) { // 新規作成時
        item = {};
      }

      this.modalState.currentItem = item;
      this.modalState.show = true;
    }

    // 更新 or 新規追加
    , updateItem: function (item) {
      var row = this.convertItem(item);

      for (var i = 0; i < this.list.length; i++) {
        var compare = this.list[i];
        if (compare.id == item.id) {
          this.list.splice(i, 1, row); // 更新トリガのためにspliceでないとダメ
          return;
        }
      }

      // 一致するitemが無かった。=> 新規追加
      this.list.push(row);
    }

    // 削除
    , removeItem: function (item) {
      const self = this;

      if (!confirm('このユーザー情報を削除してよろしいですか？')) {
        return;
      }

      self.messageState.clear();
      self.nowLoading = true;

      var data = {
        id: item.id
      };

      $.ajax({
          type: "POST"
        , url: self.removeUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert-success');

            if (result.id) {
              for (var i = 0; i < self.list.length; i++) {
                if (self.list[i].id == result.id) {
                  self.list.splice(i, 1);
                  break;
                }
              }
            }

          } else {
            var message = result.message.length > 0 ? result.message : '更新できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
          self.nowLoading = false;
        });

    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
          id           : Number(item.id)
        , username     : item.username
        , password     : ''
        , agentId      : Number(item.agent_id)
        , isActive     : item.is_active
        , createdAt : (item.created_at ? new Date(item.created_at.replace(/-/g, "/")) : null) // replace for firefox, IE
        , updatedAt : (item.updated_at ? new Date(item.updated_at.replace(/-/g, "/")) : null) // replace for firefox, IE
      };
    }


    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------


  }

});

