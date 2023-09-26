/**
 * 実店舗管理画面用 JS
 */

// 全体メッセージ
var vmGlobalMessage = new Vue({
    el: '#globalMessage'
  , delimiters: ['(%', '%)']
  , data: {
      message: ''
    , messageCssClass: ''
    , loadingImageUrl: null
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    });
  }
  , methods: {
    setMessage: function(message, cssClass, autoHide) {
      cssClass = cssClass || 'alert alert-info';
      autoHide = autoHide || true;

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
      }
    }
    , setCssClass: function(cssClass) {
      this.messageCssClass = cssClass;
    }
    , clear: function() {
      this.message = '';
      this.messageCssClass = '';
    }
    , closeWindow: function() {
      window.close();
    }
  }
});

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentRealShopPickingReportItem = {
    template: '#templateRealShopPickingReportTableRow'
  , props: [
      'item'
    , 'labelTypeOptions'
  ]
  , data: function() {

    return {
        pickingDate           : this.item.pickingDate
      , number                : this.item.number
      , neSyohinSyohinCode    : this.item.neSyohinSyohinCode
      , freeStock             : this.item.freeStock
      , orderedNum            : this.item.orderedNum
      , moveNum               : this.item.moveNum
      , labelType             : this.item.labelType

      , inEdit : this.item.inEdit
    };
  }
  , computed: {
    displayLabelType: function() {
      var self = this;
      var type = this.labelTypeOptions.filter(function(item) {
        return item.code == self.item.labelType;
      });

      return type.length ? type[0].name : '';
    }
    , labelTypeIconCss: function() {
      var self = this;
      var type = self.labelTypeOptions.filter(function(item) {
        return item.code == self.item.labelType;
      });
      return type.length > 0 ? type[0].icon : null;
    }
  }
  , methods: {
    editOn: function() {
      this.inEdit = true;
    }
    , editOff: function() {
      this.inEdit = false;
    }
    , save: function() {

      var self = this;
      var url = $(self.$el).data('saveUrl');

      // Show loading
      $.Vendor.WaitingDialog.show('保存中 ...');

      // データ保存処理

      // 添字変換
      var data = {
          ne_syohin_syohin_code : self.neSyohinSyohinCode
        , move_num              : self.moveNum
      };

      $.ajax({
          type: "POST"
        , url: url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            vmGlobalMessage.setMessage('変更を保存しました。', 'alert-success');

            self.item.moveNum = self.moveNum;
            self.editOff();

          } else {
            var message = result.message.length > 0 ? result.message : '保存に失敗しました。';
            vmGlobalMessage.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          var message = 'エラーが発生しました。';
          console.log(stat);
          console.log(message);
          vmGlobalMessage.setMessage(message, 'alert-danger');
        })
        . always(function() {

          //  loading
          $.Vendor.WaitingDialog.hide();
        });

    }
  }
};


// 一覧画面 一覧表
var vmRealShopPickingReportTable = new Vue({
    el: '#realShopPickingReportTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , submitImportUrl: null
    , reportListUrl: null

    , filterSyohinCode: null

    , labelTypeOptions: [
        { code: 'tag', name: '下げ札', order: 1, icon: 'fa-tag' }
      , { code: 'sticker', name: 'シール', order: 2, icon: 'fa-sticky-note-o' }
    ]

  }
  , components: {
      'result-item': vmComponentRealShopPickingReportItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {

      this.submitImportUrl = $(this.$el).data('submitImportUrl');
      this.reportListUrl = $(this.$el).data('reportListUrl');

      this.list = [];
      var i;
      for (i = 0; i < PICKING_REPORT_DATA.length; i++) {
        var item = PICKING_REPORT_DATA[i];
        var row = {
            pickingDate        : (item.picking_date ? new Date(item.picking_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , number             : Number(item.number) || 0
          , neSyohinSyohinCode : item.ne_syohin_syohin_code
          , freeStock          : Number(item.free_stock) || 0
          , orderedNum         : Number(item.ordered_num) || 0
          , moveNum            : Number(item.move_num) || 0
          , labelType          : item.label_type || 'tag'

          , inEdit : false
        };

        this.list.push(row);
      }
    });
  }

  , computed: {
    filteredList: function() {
      if (! this.filterSyohinCode || this.filterSyohinCode.length == 0 ) {
        return this.list;
      }
      var self = this;
      var reg = new RegExp('^' + self.filterSyohinCode);

      return this.list.filter(function(item) {
        return reg.test(item.neSyohinSyohinCode);
      });
    }
  }
  , methods: {

    submitImport: function() {
      if (!confirm("この内容で入庫を確定してよろしいですか？")) {
        return;
      }

      var self = this;

      var data = {};
      $.Vendor.WaitingDialog.show();

      $.ajax({
          type: "POST"
        , url: self.submitImportUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            alert('入庫データをスマレジへ登録しました。');

            window.location.href = self.reportListUrl;

          } else {
            var message = result.message.length > 0 ? result.message : '保存に失敗しました。';
            vmGlobalMessage.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          var message = 'エラーが発生しました。';
          console.log(stat);
          console.log(message);
          vmGlobalMessage.setMessage(message, 'alert-danger');
        })
        . always(function() {

          //  loading
          $.Vendor.WaitingDialog.hide();
        });


    }

  }

});

