/**
 * 管理画面 コンシェルジュ用 JS
 */
$(function() {
  var clipboard = new Clipboard('.btnCopyCode');
  //clipboard.on('success', function(e) {
  //  e.clearSelection();
  //});
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
var vmComponentConciergeShortageListItem = {
    template: '#templateConciergeShortageListTableRow'
  , props: [
      'item'
  ]
  , data: function() {

    return {
        neSyohinSyohinCode : this.item.neSyohinSyohinCode
      , stock              : this.item.stock
      , orderRemainNum     : this.item.orderRemainNum
      , orderNum           : this.item.orderNum
      , assignedNum        : this.item.assignedNum
      , unassignedNum      : this.item.unassignedNum
      , voucherNum         : this.item.voucherNum
      , shortage           : this.item.shortage
      , lastOrdered        : this.item.lastOrdered
    };
  }
  //, mounted: function() {
  //  var self = this;
  //  this.$nextTick(function () {
  //  });
  //}

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
    copyCode: function() {
      var date = $.Plusnao.Date.getDateString(new Date());
      return date.replace(/-/g, '/') + ' ' + this.item.neSyohinSyohinCode + ' x';
    }

    , displayLastOrdered: function() {
      return this.item.lastOrdered ? $.Plusnao.Date.getDateString(this.item.lastOrdered) : '';
    }

  }
  , methods: {
  }
};


// 受注一覧モーダル
var vmComponentModalConciergeShortageOrderList = {
  template: '#templateModalConciergeShortageOrderList'
  , props: [
      'item'
    // , 'orderList'
    , 'show'
  ]
  , data: function() {
    return {
        messageClass: 'alert-success'
      , message: null

      , noticeHidden: true
      , noticeClass: 'alert-info'
      , notices: []

      // , orderListUrlBase   : null

      , list: [] // 受注一覧

      , doReload: false
    };
  }
  , computed: {
    caption: function() {
      var caption = '受注一覧';
      if (this.item && this.item.neSyohinSyohinCode) {
        caption += ' [' + this.item.neSyohinSyohinCode + ']';
      }
      return caption;
    }

    //, orderListUrl: function() {
    //  return this.orderListUrlBase ? this.orderListUrlBase.replace(/__CODE__/, this.item.neSyohinSyohinCode) : '';
    //}

  }

  , watch : {
    show: function() {
      var modal = $(this.$el);
      if (this.show && modal.is(':hidden')) {
        modal.modal('show');
      } else if (!this.show && !modal.is(':hidden')) {
        modal.modal('hide');
      }
    }
    //, item: function() {
    //  console.log('item changed');
    //  console.log(this.item);
    //}
  }

  , mounted: function() {
    this.$nextTick(function (){
      var self = this;

      //self.orderListUrlBase = $(this.$el).data('orderListUrlBase');

      // 2017/04/07 受注一覧の作り込みのアイディアは後から練るため、今は商品一覧までで実装ストップとする。

      return;

      //// イベント登録
      //// -- open前
      //$(self.$el).on('show.bs.modal', function(e) {
      //
      //  self.reset();
      //
      //  var data = {
      //    ne_syohin_syohin_code: self.item.ne_syohin_syohin_code
      //  };
      //
      //  self.list = [];
      //
      //  $.ajax({
      //    type: "GET"
      //    , url: self.dataUrl
      //    , dataType: "json"
      //    , data: data
      //  })
      //    .done(function(result) {
      //
      //      if (result.status == 'ok') {
      //        self.list = [];
      //        var i;
      //
      //        for (i = 0; i < result.list.length; i++) {
      //          var item = result.list[i];
      //          var row = {
      //            neSyohinSyohinCode    : item.ne_syohin_syohin_code
      //            , daihyoSyohinCode      : item.daihyo_syohin_code
      //            , colname               : item.colname
      //            , rowname               : item.rowname
      //            , freeStock             : Number(item.free_stock)
      //            , shopStock             : Number(item.shop_stock)
      //            , orderNum              : Number(item.order_num)
      //            , orderRemain           : Number(item.order_remain)
      //            , lastOrdered           : (item.last_ordered ? new Date(item.last_ordered.replace(/-/g, "/")) : null) // replace for firefox, IE
      //
      //          };
      //
      //          self.list.push(row);
      //        }
      //
      //      } else {
      //        self.messageCssClass = 'alert-danger';
      //        self.message = result.message.length > 0 ? result.message : 'SKU情報が取得できませんでした。';
      //      }
      //    })
      //    .fail(function(stat) {
      //      self.messageCssClass = 'alert-danger';
      //      self.message = 'エラーが発生しました';
      //    })
      //    . always(function() {
      //
      //    });
      //
      //});
      //
      //// -- close後
      //$(self.$el).on('hidden.bs.modal', function(e) {
      //  if (self.doReload) {
      //    self.$emit('reload-current-page');
      //  }
      //})
    });
  }
  , methods: {
    openVoucherWindow: function() {
      // $.Plusnao.NextEngine.openVoucherWindow(this.item.voucherNumber, 'concierge-list');
      return false;
    }

    //, closeOrderList: function() {
    //  this.$emit('close-order-list');
    //}
  }
};




// 一覧画面 一覧表
var vmRealConciergeShortageListTable = new Vue({
    el: '#shortageListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    , pageItemNum: 20
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , url: null

    //, filterIncludeRecentReminder: 1

    , orders: {
      //  syohinCode: null
    }

    , currentItem: null
    // , orderListShown: false
  }
  , components: {
      'result-item': vmComponentConciergeShortageListItem // 商品一覧テーブル
    , 'order-list': vmComponentModalConciergeShortageOrderList // 受注一覧モーダル
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
                  neSyohinSyohinCode : item.ne_syohin_syohin_code
                , stock          : Number(item.stock)
                , orderRemainNum : Number(item.order_remain_num)
                , orderNum       : Number(item.order_num)
                , assignedNum    : Number(item.assigned_num)
                , unassignedNum  : Number(item.unassigned_num)
                , voucherNum     : Number(item.voucher_num)
                , shortage       : Number(item.shortage)
                , lastOrdered    : (item.last_ordered ? new Date(item.last_ordered.replace(/-/g, "/")) : null) // replace for firefox, IE
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

    ///// 受注一覧モーダルを開く
    //, openOrderList: function(item) {
    //  this.currentItem = item;
    //  this.orderListShown = true;
    //}
    //
    ///// 受注一覧モーダルを閉じる
    //, closeOrderList: function() {
    //  this.orderListShown = false;
    //}


  }

});

