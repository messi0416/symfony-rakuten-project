// 登録・編集モーダル
Vue.component('setting-maintenance-schedule-form-modal', {
    template: '#templateMaintenanceScheduleForm'
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
      const caption = 'メンテナンススケジュール編集';
      return caption;
    }
    , maintenanceTypeOptions: function() {
      const options = [];
      for (key in MAINTENANCE_TYPE_LIST) {
        const option = { value : key, text : MAINTENANCE_TYPE_LIST[key],}
        options.push(option);
      }
      return options;
    }
  }

  , watch : {
  }

  , mounted: function() {
    this.$nextTick(function (){
      const self = this;
      const modal = $(self.$el);

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
        self.item = $.extend(true, { id: '', maintenanceType: '', note: '' }, newValue);
      });
      
      // 日時フォームの初期化
      self.initializeDatePicker();

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
    
    /**
     * datetimePickerを初期化する。 
     */
    , initializeDatePicker: function() {
      const self = this;
      $('#start_datetime').datetimepicker({
        locale: 'ja',
        format : 'YYYY-MM-DD HH:mm',
      // datetimePickerが閉じられたときに、その時点の値を取得して vueへ書き戻す e.dateは秒 https://getdatepicker.com/4/Events/
      }).on('dp.hide', function(e) { 
        self.item.startDatetime = $.Plusnao.Date.getDateString(new Date(e.date), true);
      });

      $('#end_datetime').datetimepicker({
        locale: 'ja',
        format : 'YYYY-MM-DD HH:mm',
      }).on('dp.hide', function(e) { 
        self.item.endDatetime = $.Plusnao.Date.getDateString(new Date(e.date), true);
      });
    }
    , save: function() {
      const self = this;

      self.messageState.clear();
      self.nowLoading = true;

      const sendItem = {...self.item};
      sendItem.deleteFlg = sendItem.deleteFlg ? 1 : 0;
      const data = {
        item: sendItem,
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
            const message = result.message.length > 0 ? result.message : '更新できませんでした。';
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
const vmComponentMaintenanceScheduleListItem = {
    template: '#templateMaintenanceScheduleListTableRow'
  , data: function() {
    return {
      maintenanceTypeList : MAINTENANCE_TYPE_LIST
    };
  }
  , props: [
     'item'
  ]
  , computed: {
    rowCssClass: function() {
      let cssClass = '';

      // 使用終了ならばshadow
      if (this.item.deleteFlg != 0) {
        cssClass = 'shadow';
        return cssClass;
      }
      return cssClass;
    },
  }
  , methods: {
    showEditForm: function() {
      this.$emit('show-edit-form', this.item);
    }
  }
};

// 一覧画面 一覧表
const vmMaintenanceScheduleList = new Vue({
    el: '#maintenanceScheduleList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , url: null
    , initialized: false
    
    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentMaintenanceScheduleListItem // 一覧テーブル
  }

  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.url = $(this.$el).data('url');
      this.initialized = true;
      this.showPage();
    });
  }

  , computed: {
    }
  , methods: {

    showPage: function(pageInfo) {

      // 初期化が済んでいない場合にはreturn
      if (!this.initialized) {
        return;
      }
      this.initialized = false;
      const self = this;
      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
      })
        .done(function(result) {
          if (result.status == 'ok') {
            self.list = [];
            for (let i = 0; i < result.list.length; i++) {
              self.list.push(result.list[i]);
            }
          } else {
            const message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
        })
        . always(function() {
          self.initialized = true;
        });
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
      for (let i = 0; i < this.list.length; i++) {
        const compare = this.list[i];
        if (compare.id == item.id) {
          this.list.splice(i, 1, item); // 更新トリガのためにspliceでないとダメ
          return;
        }
      }

      // 一致するitemが無かった。=> 新規追加
      this.list.push(item);
    }
  }
});
