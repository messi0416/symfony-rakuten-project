/**
 * 管理画面 ロケーション在庫原価一覧
 */

// 一覧画面 一覧表
const vmGoodsLocationPurchasePriceList = new Vue({
    el: '#goodsLocationPurchasePriceList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , pageItemNum: 100
    , pageItemNumList: [ 20, 50, 100, 500, 1000 ]
    , page: 1

    , totalItemNum: 0

    , showColumns: {
        warehouseId: true
      , locationCode: true
      , purchasePrice: true
      , stock: true
    }

    , messageState: {}

  }
  , components: {
  }

  , mounted: function() {
    this.$nextTick(function () {

      // ページ情報取得
      if (typeof PAGINATION_DATA !== 'undefined') {
        this.pageItemNum = PAGINATION_DATA.pageItemNum;
        this.page = PAGINATION_DATA.page;
        this.totalItemNum = PAGINATION_DATA.totalItemNum;
      }

      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
    });
  }

  , computed: {
  }

  , methods: {

    showPage: function(pageInfo) {
      const form = $('#searchForm', this.$el);

      form.append(
        $('<input>').attr({
            type: 'hidden'
          , name: 'page'
          , value: pageInfo.page
        })
      );
      form.append(
        $('<input>').attr({
            type: 'hidden'
          , name: 'limit'
          , value: pageInfo.pageItemNum
        })
      );

      form.submit();
    }

    , omitColumn: function(column) {
      this.showColumns[column] = false;
    }


  }

});

