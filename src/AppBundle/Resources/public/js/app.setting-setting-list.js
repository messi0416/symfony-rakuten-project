/**
 * 管理画面 各種設定 JS
 */

// 登録・編集フォーム モーダル コンポーネント

/**
 * メインブロック
 */
Vue.component('setting-form-modal', {
    template: '#templateSettingForm'
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
      var caption = '各種設定編集';
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
        self.item = $.extend(true, { settingKey: '' }, newValue);
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
var vmComponentSettingListItem = {
    template: '#templateSettingListTableRow'
  , props: [
     'item'
  ]
  , methods: {
    showEditForm: function() {
      this.$emit('show-edit-form', this.item);
    }
  }
};


// 一覧画面 一覧表
var vmSettingList = new Vue({
    el: '#settingList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentSettingListItem // 一覧テーブル
  }

  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];

      if (SETTING_DATA) {
        for (var i = 0; i < SETTING_DATA.length; i++) {
          var item = SETTING_DATA[i];
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

    // 更新
    , updateItem: function (item) {
      var row = this.convertItem(item);

      for (var i = 0; i < this.list.length; i++) {
        var compare = this.list[i];
        if (compare.settingKey == item.settingKey) {
          this.list.splice(i, 1, row); // 更新トリガのためにspliceでないとダメ
          return;
        }
      }

      // 一致するitemが無かった。=> 新規追加
      this.list.push(row);
    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
         settingKey           : item.settingKey
         , settingVal         : item.settingVal
         , settingDesc        : item.settingDesc
      };
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------


  }

});

