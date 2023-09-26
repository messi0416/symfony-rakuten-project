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
var vmComponentConciergeShippingDelayListItem = {
    template: '#templateConciergeShippingDelayListTableRow'
  , props: [
      'item'
    , 'copyComment'
    , 'openedVoucherNumbers'
  ]
  , data: function() {

    return {
        voucherNumber  : this.item.voucherNumber
      , shopName       : this.item.shopName
      , orderDate      : this.item.orderDate
      , inputShippingDate : this.item.inputShippingDate
      , shippingDate   : this.item.shippingDate
      , daysBefore     : this.item.daysBefore
      , syohinCodes    : this.item.neSyohinSyohinCode

      // , inEditLabelType : false
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

    , displayInputShippingDate: function() {
      return this.item.inputShippingDate ? $.Plusnao.Date.getDateString(this.item.inputShippingDate) : '';
    }

    , displayShippingDate: function() {
      return this.item.shippingDate ? $.Plusnao.Date.getDateString(this.item.shippingDate) : '';
    }

    , displaySyohinCodeList: function() {
      return this.item.syohinCodes.join(' / ');
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

  }
};


// 一覧画面 一覧表
var vmRealShopProductStockListTable = new Vue({
    el: '#shippingDelayListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    , pageItemNum: 50
    , pageItemNumList: [20, 50, 100 ]
    , page: 1

    , url: null
    , updateUrl: null

    , filterBorderDate: $.Plusnao.Date.getDateString(new Date())
    , filterShopNeIds: null

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

    , alertMessage: ''

  }
  , components: {
      'result-item': vmComponentConciergeShippingDelayListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.updateUrl = $(this.$el).data('updateUrl');

      // 2017/4/5 入金催促
      this.copyComment = $.Plusnao.Date.getDateString(new Date(), false, true).replace(/-/g, '/')
                       + ' 出荷遅延';

      var self = this;
      $('#filterBorderDate', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          var date = $(this).datepicker('getDate');
          if (date) {
            self.filterBorderDate = $.Plusnao.Date.getDateString(date);
          } else {
            self.filterBorderDate = '';
          }
        }
        , clearDate: function() {
          self.filterBorderDate = '';
        }
      });

      var $filterShopNeIds = $('#filterShopNeIds', this.$el);
      $filterShopNeIds.on('changed.bs.select', self.shopSelectChanged);
      $filterShopNeIds.on('hidden.bs.select', self.showFirstPage);

      $filterShopNeIds.selectpicker('selectAll');

      this.showFirstPage();
    });
  }
  , watch: {
    filterBorderDate: function() {
      var self = this;
      this.$nextTick(function() {
        self.showFirstPage();
      });
    }
  }

  , computed: {
  }
  , methods: {
    showPage: function(pageInfo) {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('loading ...');

      var page = pageInfo.page;

      // データ読み込み処理
      var data = {
          page: page
        , limit: pageInfo.pageItemNum
        , conditions: {
            'border_date': self.filterBorderDate
          , 'shop_ne_ids': self.filterShopNeIds
        }
        , orders: {
          //  daihyo_syohin_code: this.orders.syohinCode
        }
      };

      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            self.list = [];
            for (var i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  voucherNumber      : item.voucher_number
                , shopName           : item.shop_name
                , orderDate          : (item.order_date ? new Date(item.order_date.replace(/-/g, "/")) : null) // replace for firefox, IE
                , inputShippingDate  : (item.input_shipping_date ? new Date(item.input_shipping_date.replace(/-/g, "/")) : null) // replace for firefox, IE
                , shippingDate       : (item.shipping_date ? new Date(item.shipping_date.replace(/-/g, "/")) : null) // replace for firefox, IE
                , daysBefore         : Number(item.days_before)
                , syohinCodes        : item.syohin_codes ? item.syohin_codes.split(',') : []
              };

              self.list.push(row);
            }

            self.totalItemNum = Number(result.count);
            self.page = page;

            // リセットされてしまうので再セット
            self.pageItemNum = pageInfo.pageItemNum;
            // self.filterBorderDate = data.conditions.border_date;

            // 多過ぎ警告
            self.alertMessage = result.alert_message;

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

    , showFirstPage: function() {
      var pageInfo = {
          page: 1
        , pageItemNum: this.pageItemNum
      };

      this.showPage(pageInfo);
    }

    , searchFormKeyPress: function(event) {
      if (event.which === 13) {
        return this.showFirstPage();
      } else {
        return false;
      }
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

      // リロード
      this.showFirstPage();
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

    , shopSelectChanged: function(e) {
      this.filterShopNeIds = $('#filterShopNeIds').val();
    }


    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------

    /// 現在のページを再読込（編集後など）
    , reloadCurrentPage: function() {

      var pageInfo = {
          page: this.page
        , pageItemNum: this.pageItemNum
      };
      this.showPage(pageInfo);
    }

    , addOpenedVoucherNumber: function(voucherNumber) {
      this.openedVoucherNumbers.push(voucherNumber);
    }
  }

});

