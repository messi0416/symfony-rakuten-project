/**
 * 管理画面 コンシェルジュ用 JS
 */
$(function() {
  var clipboard = new Clipboard('.btnCopyCode');
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
var vmComponentConciergeUnknownProductListItem = {
    template: '#templateConciergeUnknownProductListTableRow'
  , props: [
      'item'
    , 'updateUrl'
    , 'copyComment'
  ]
  , data: function() {

    return {
        voucherNumber      : this.item.voucherNumber
      , neSyohinSyohinCode : this.item.neSyohinSyohinCode

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
  }
  , methods: {

    openVoucherWindow: function() {
      $.Plusnao.NextEngine.openVoucherWindow(this.item.voucherNumber, 'concierge-list');
      return false;
    }
  }
};


// 一覧画面 一覧表
var vmRealShopProductStockListTable = new Vue({
    el: '#unknownProductListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    , pageItemNum: 20
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , url: null

    , orders: {
      //  syohinCode: null
    }

  }
  , components: {
      'result-item': vmComponentConciergeUnknownProductListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.showFirstPage();
    });
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
                  voucherNumber      : Number(item.voucher_number)
                , neSyohinSyohinCode : item.ne_syohin_syohin_code
              };

              self.list.push(row);
            }

            self.totalItemNum = Number(result.count);
            self.page = page;
            self.pageItemNum = pageInfo.pageItemNum; // リセットされてしまうので再セット

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

  }

});

