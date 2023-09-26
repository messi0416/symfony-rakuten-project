/**
 * 管理画面 ロジ用 JS
 */
$(function() {
});


// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentLogisticsPurchaseListItem = {
    template: '#templateLogisticsPurchaseListTableRow'
  , props: [
      'item'
    , 'index'
  ]
  , data: function() {

    return {
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
      displayDeliveryDate: function() {
      return this.item.deliveryDate ? $.Plusnao.Date.getDateString(this.item.deliveryDate) : '';
    }
    , displayQuantityPrice: function() {
      return $.Plusnao.String.numberFormat(this.item.quantityPrice);
    }
  }
  , methods: {

    /// input欄 enter で1行次へ
    onInputEnter: function(event) {
      var $nextTr = $(event.target).closest('tr').next('tr');
      if ($nextTr.size() > 0) {
        var $targetInput = $nextTr.find('input[type="text"]').first();
        if ($targetInput.size() > 0) {
          $targetInput.focus();
        }
      }
    }

    //openVoucherWindow: function() {
    //  $.Plusnao.NextEngine.openVoucherWindow(this.item.voucherNumber, 'concierge-list');
    //  if (! this.isVoucherOpened) {
    //    this.$emit('voucher-opened', this.item.voucherNumber);
    //  }
    //  return false;
    //}



    //, processUpdateReminderDate: function(date) {
    //
    //  var self = this;
    //
    //  var data = {
    //      voucher_number: self.item.voucherNumber
    //    , date: date
    //  };
    //
    //  $.Vendor.WaitingDialog.show();
    //
    //  $.ajax({
    //      type: "POST"
    //    , url: self.updateUrl
    //    , dataType: "json"
    //    , data: data
    //  })
    //    .done(function(result) {
    //
    //      if (result.status == 'ok') {
    //
    //        // itemデータ更新
    //        self.$emit('update-item', self.item, result);
    //
    //      } else {
    //        var message = result.message.length > 0 ? result.message : '入金催促日を更新できませんでした。';
    //        self.messageState.setMessage(message, 'alert alert-danger');
    //      }
    //    })
    //    .fail(function(stat) {
    //      self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
    //    })
    //    . always(function() {
    //
    //      // Show loading
    //      $.Vendor.WaitingDialog.hide();
    //    });
    //
    //}
  }
};


// 一覧画面 一覧表
var vmRealShopProductStockListTable = new Vue({
    el: '#purchaseList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , pageItemNum: 50
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , totalItemNum: 0

    , listUrl: null
    , updateUrl: null

    , orders: {
        orderNumber: null
      , sireName: null
      , deliveryDate: null
      , syohinCode: null
      , remainNum: null
    }

    , searchConditions: {
        orderNumber: null
      , syohinCode: null
      , remainNum: 1
      , shippingNumber: null
      , sireCode: null
      , agentCode: null
      , agentStatus: null
      , comment: null
    }
    , defaultSearchConditions: {
        orderNumber: null
      , syohinCode: null
      , remainNum: 1
      , shippingNumber: null
      , sireCode: null
      , agentCode: null
      , agentStatus: null
      , comment: null
    }

    , messageState: {}

  }
  , components: {
      'result-item': vmComponentLogisticsPurchaseListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.listUrl = $(this.$el).data('listUrl');
      this.updateUrl = $(this.$el).data('updateUrl');

      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      this.loadListData();
    });
  }

  , computed: {

    // sort, filter済みデータ
      listData: function() {
      var self = this;
      return self.list.slice();
    }

    , pageData: function() {
      return this.listData;
    }

    , sortField: function() {
      var keys = Object.keys(this.orders);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (this.orders[key] !== null) {
          return key;
        }
      }
      return null;
    }
    , sortDirection: function() {
      var sortField = this.sortField;
      if (sortField) {
        switch (this.orders[sortField]) {
          case 1:
            return 'ASC';
            break;
          case -1:
            return 'DESC';
            break;
        }
      }

      return null;
    }
  }
  , methods: {

    loadListData: function() {
      var self = this;

      // データ読み込み処理
      var data = {
          page: self.page
        , limit: self.pageItemNum
        , o: self.sortField
        , od: self.sortDirection
        , search: self.searchConditions
      };

      // Show loading
      $.Vendor.WaitingDialog.show('Loading', {
        backdrop: false
      });

      $.ajax({
          type: "GET"
        , url: self.listUrl
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
                  orderNumber   : item['発注伝票番号']
                , sireName      : item['仕入先名']
                , deliveryDate  : (item['予定納期'] ? new Date(item['予定納期'].replace(/-/g, "/")) : null) // replace for firefox, IE
                , syohinCode    : item['商品コード']
                , orderNum      : Number(item['発注数'])
                , regularNum    : Number(item['regular'])
                , defectiveNum  : Number(item['defective'])
                , shortageNum   : Number(item['shortage'])
                , remainNum     : Number(item['注残計'])
                , comment       : item['備考']
                , quantityPrice : Number(item['quantity_price'])
                , agentName     : item['依頼先名']
                , agentStatus   : item['依頼先状態']
                , supportColName: item['support_colname']
                , supportRowName: item['support_rowname']
                , vendorComment : item['vendor_comment']
                , shippingNumber: item['shipping_number']

                // TODO ブラウザ保存値から補完
                , inputOrderNum: null
                , inputRegularNum: null
                , inputDefectiveNum: null
                , inputShortageNum: null
                , orderStop: null

                //, voucherOpened : false
              };

              // TODO 既存入力値 このへんでセット

              self.list.push(row);
            }

            self.totalItemNum = result.pageInfo.totalCount;

            self.$nextTick(function() {
              FixedMidashi.create(); // ヘッダ固定テーブル 再計算
            });


          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });

    }

    , showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;

      this.loadListData();
    }

    /// 直接 loadListData() よりはワンクッション置いてみる
    , search: function() {
      this.page = 1;
      return this.loadListData();
    }

    /// 絞込クリア
    , resetSearchConditions: function() {
      var fields = Object.keys(this.defaultSearchConditions);
      for (var i = 0; i < fields.length; i++) {
        var key = fields[i];
        this.searchConditions[key] = this.defaultSearchConditions[key];
      }

      this.loadListData();
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

      this.loadListData();
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

    , updateItem: function(item, result) {
      //var date = result.date ?  new Date(result.date.replace(/-/g, "/")) : null;
      //item.sunPaymentReminder = date;
    }

  }

});

