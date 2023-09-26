/**
 * 管理画面 セット商品一覧 JS
 */

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentSetProductListItem = {
    template: '#templateSetProductListTableRow'
  , props: [
        'item'
      , 'detailUrlBase'
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
    detailUrl: function() {
      return this.detailUrlBase.replace('__CODE__', this.item.daihyoSyohinCode);
    }
  }
  , methods: {
  }
};


// 一覧画面 一覧表
var vmSetProductListTable = new Vue({
    el: '#setProductListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , pageItemNum: 50
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , url: null

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

    , messageState: {}

  }
  , components: {
      'result-item': vmComponentSetProductListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      this.url = $(this.$el).data('url');
      this.detailUrlBase = $(this.$el).data('detailUrlBase');

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

      //// 絞込: 今日の入金催促
      //if (! self.filterIncludeRecentReminder) {
      //  var today = new Date();
      //  list = list.filter(function(item, i) {
      //    return item.sunPaymentReminder ? $.Plusnao.Date.getDateString(item.sunPaymentReminder) != $.Plusnao.Date.getDateString(today) : true;
      //  });
      //}
      //
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
                  daihyoSyohinCode    : item.daihyo_syohin_code
                , daihyoSyohinName    : item.daihyo_syohin_name
                , setFlg              : Number(item.set_flg)
                , requiredStock       : Number(item.required_stock)
                , skuNum              : Number(item.sku_num)
                , stock               : Number(item.stock)
                , freeStock           : Number(item.free_stock)
                , imageUrl            : item.image_url
              };

              self.list.push(row);
            }

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

  }

});

