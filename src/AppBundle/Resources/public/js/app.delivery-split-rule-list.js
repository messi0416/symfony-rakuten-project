/**
 * 管理画面 送料設定 JS
 */

// 登録・編集フォーム モーダル コンポーネント

/**
 * メインブロック
 */
Vue.component('delivery-split-rule-form-modal', {
    template: '#templateDeliverySplitRuleForm'
  , delimiters: ['(%', '%)']
  , props: [
    'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
  ]
  , data: function() {
    // グループオプション定義
    deliveryMethodOptions = [];
    for (key in DELIVERY_METHOD_LIST) {
      var option = { value : key, text : DELIVERY_METHOD_LIST[key],}
      deliveryMethodOptions.push(option);
    }
    return {
        saveUrl: null
      , messageState: {}
      , nowLoading: false
      , deliveryMethodOptions
      , item: {}
    };
  }
  , computed: {
    caption: function() {
      var caption = '発送方法変換設定編集';
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
        self.item = $.extend(true, { id: '' }, newValue);
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

      // 
      const item = { ...self.item };
      delete item.prefectures;
      item.sizecheck = item.sizecheck ? 1 : 0;
      item.maxflg = item.maxflg ? 1 : 0;
      console.log(item);
      var data = {
        item,
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
var vmComponentDeliverySplitRuleListItem = {
    template: '#templateDeliverySplitRuleListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
    rowCssClass: function() {
      var cssClass = '';

      // 使用終了ならばshadow
      if (this.item.terminateFlg != 0) {
//        cssClass = 'shadow';
      }
      return cssClass;
    },
    displaySize: function() {
      return (Boolean(this.item.sizecheck) ? "あり" : "なし");
    },
    displayMax: function() {
      return (Boolean(this.item.maxflg) ? "最大" : "");
    },
    displayMethod: function() {
      return (DELIVERY_METHOD_LIST[this.item.deliveryId]);
    },
  }
  , methods: {
    showEditForm: function() {
      this.$emit('show-edit-form', this.item);
    }

    , remove: function() {
      this.$emit('remove-item', this.item);
    }

    , selectAll: function(event) {
      event.target.select();
    }
  }
};


// 一覧画面 一覧表
var vmDeliverySplitRuleList = new Vue({
    el: '#deliverySplitRuleList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , removeUrl: null

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentDeliverySplitRuleListItem // 一覧テーブル
  }

  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.removeUrl = $(this.$el).data('removeUrl');
      
      if (DELIVERY_SPLIT_RULE_LIST_DATA) {
        for (var i = 0; i < DELIVERY_SPLIT_RULE_LIST_DATA.length; i++) {
          var item = DELIVERY_SPLIT_RULE_LIST_DATA[i];
          var row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }

    });
  }

  , computed: {
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

      if (!confirm('この発送方法変換ルールを削除してよろしいですか？')) {
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
      const prefectures = Array.isArray(item.prefectures)
        ? item.prefectures.join(", ")
        : item.prefectures;
      return {
          id           : Number(item.id)
        , rulename     : item.rulename
        , checkorder   : Number(item.checkorder)
        , prefectureCheckColumn : item.prefectureCheckColumn
        , prefectures
        , longlength   : Number(item.longlength)
        , middlelength : Number(item.middlelength)
        , shortlength  : Number(item.shortlength)
        , totallength  : Number(item.totallength)
        , volume       : Number(item.volume)
        , weight       : Number(item.weight)
        , sizecheck    : Number(item.sizecheck)
        , maxflg       : Number(item.maxflg)
        , deliveryId   : Number(item.deliveryId)
        , groupid      : Number(item.groupid)
        , groupname    : item.groupname
      };
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------


  }

});

