/**
 * 管理画面 コンシェルジュ用 JS
 */
$(function() {
  var clipboard = new Clipboard('.btnCopyComment');
  clipboard.on('success', function(e) {
    e.clearSelection();
  });
});

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
var vmComponentConciergeUnpaidListItem = {
    template: '#templateConciergeUnpaidListTableRow'
  , props: [
      'item'
    , 'updateUrl'
    , 'copyComment'
    , 'openedVoucherNumbers'
  ]
  , data: function() {

    return {
        orderDate          : this.item.orderDate
      , paymentMethod      : this.item.paymentMethod
      , paymentType        : this.item.paymentType
      , purchaseQuantity   : this.item.purchaseQuantity
      , sunPaymentReminder : this.item.sunPaymentReminder
      , voucherNumber      : this.item.voucherNumber

      , inEditReminderDate : false
      , editingReminderDate: null
    };
  }
  /* もし、this.item の直接参照がいやなら、下記のwatchでインスタンスのプロパティを更新する必要がある。
  , watch: {
    item: function() {
      this.orderDate          = this.item.orderDate;
      this.paymentMethod      = this.item.paymentMethod;
      this.paymentType        = this.item.paymentType;
      this.purchaseQuantity   = this.item.purchaseQuantity;
      this.sunPaymentReminder = this.item.sunPaymentReminder;
      this.voucherNumber      = this.item.voucherNumber;
    }
  }
  */
  , computed: {
    displayOrderDate: function() {
      return this.item.orderDate ? $.Plusnao.Date.getDateString(this.item.orderDate) : '';
    }
    , displaySunPaymentReminder: function() {
      return this.item.sunPaymentReminder ? $.Plusnao.Date.getDateString(this.item.sunPaymentReminder) : '';
    }
    , isVoucherOpened: function() {
      return this.openedVoucherNumbers.indexOf(this.item.voucherNumber) > -1;
    }
    , voucherButtonCss: function() {
      return this.isVoucherOpened ? 'btn-info' : 'btn-default';
    }
  }
  , methods: {

    openVoucherWindow: function() {
      $.Plusnao.NextEngine.openVoucherWindow(this.item.voucherNumber, 'concierge-list');
      if (! this.isVoucherOpened) {
        this.$emit('voucher-opened', this.item.voucherNumber);
      }
      return false;
    }

    /// 入金催促日 登録
    , registerReminderDate: function() {
      if (this.displaySunPaymentReminder) {
        alert('すでに入金催促日が登録されています。');
        return;
      }

      return this.processUpdateReminderDate($.Plusnao.Date.getDateString(new Date()));
    }

    /// 入金催促日 編集切り替え
    , setEditReminderDate: function(flag) {
      if (!this.item.sunPaymentReminder) {
        return;
      }

      if (flag) { // ON
        this.editingReminderDate = this.displaySunPaymentReminder;
        this.inEditReminderDate = true;

      } else { // OFF
        this.editingReminderDate = null;
        this.inEditReminderDate = false;

      }
    }

    /// 入金催促日 更新
    , updateReminderDate: function() {
      this.processUpdateReminderDate(this.editingReminderDate);
      return this.setEditReminderDate(false);
    }

    /// 入金催促日入力 キープレス
    , keyPressInputReminderDate: function(event) {
      if (event.which === 13) {
        return this.updateReminderDate();
      } else {
        return false;
      }
    }

    , processUpdateReminderDate: function(date) {

      var self = this;

      var data = {
          voucher_number: self.item.voucherNumber
        , date: date
      };

      $.Vendor.WaitingDialog.show();

      $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            // itemデータ更新
            self.$emit('update-item', self.item, result);

          } else {
            var message = result.message.length > 0 ? result.message : '入金催促日を更新できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();
        });

    }
  }
};


// 一覧画面 一覧表
var vmRealShopProductStockListTable = new Vue({
    el: '#unpaidListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , pageItemNum: 50
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , url: null
    , updateUrl: null

    , filterIncludeRecentReminder: 1

    , copyComment: ''
    , openedVoucherNumbers: []

    , orders: {
      //  syohinCode: null
      //, shopStock: null
      //, freeStock: null
      //, orderNum: null
      //, cost: null
      //, basePrice: null
      //, currentPrice: null
      //, labelType: null
    }

  }
  , components: {
      'result-item': vmComponentConciergeUnpaidListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.updateUrl = $(this.$el).data('updateUrl');

      // コピー用文言
      this.copyComment = $.Plusnao.Date.getDateString(new Date(), false, true).replace(/-/g, '/')
                       + ' 入金催促';

      this.loadListData();
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

      // 絞込: 今日の入金催促
      if (! self.filterIncludeRecentReminder) {
        var today = new Date();
        list = list.filter(function(item, i) {
          return item.sunPaymentReminder ? $.Plusnao.Date.getDateString(item.sunPaymentReminder) != $.Plusnao.Date.getDateString(today) : true;
        });
      }

      return list;
    }

    , pageData: function() {
      var startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

    loadListData: function() {
      var self = this;

      // データ読み込み処理
      var data = {};

      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var i;

            self.list = [];
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  orderDate          : (item.order_date ? new Date(item.order_date.replace(/-/g, "/")) : null) // replace for firefox, IE
                , paymentMethod      : item.payment_method
                , paymentType        : Number(item.payment_type)
                , purchaseQuantity   : Number(item.purchase_quantity)
                , sunPaymentReminder : (item.sun_payment_reminder ? new Date(item.sun_payment_reminder.replace(/-/g, "/")) : null) // replace for firefox, IE
                , voucherNumber      : Number(item.voucher_number)

                , voucherOpened : false
              };

              self.list.push(row);
            }

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });

    }

    , showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , toggleOrder: function(key) {
      if (this.orders[key]) {
        if (this.orders[key] == 1) {
          this.orders[key] = -1;
        } else {
          this.orders[key] = null;
        }
      } else {
        var k;
        for (k in this.orders) {
          this.orders[k] = null;
        }
        this.orders[key] = 1;
      }

    }

    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function(key) {
      if (!this.orders[key]) {
        return '';
      }
      return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------

    , addOpenedVoucherNumber: function(voucherNumber) {
      this.openedVoucherNumbers.push(voucherNumber);
    }

    , updateItem: function(item, result) {
      var date = result.date ?  new Date(result.date.replace(/-/g, "/")) : null;
      item.sunPaymentReminder = date;
    }

  }

});

