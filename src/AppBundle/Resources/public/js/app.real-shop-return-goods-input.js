/**
 * 実店舗管理画面 返品入力用 JS
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
var vmComponentRealShopReturnGoodsInputItem = {
    template: '#templateRealShopReturnGoodsInputTableRow'
  , props: [
      'item'
  ]
  , data: function() {

    return {
        neSyohinSyohinCode    : this.item.neSyohinSyohinCode
      , productCode           : this.item.productCode
      , moveNum               : this.item.moveNum
      , shopStock             : this.item.shopStock
    };
  }
  , computed: {
  }
  , methods: {
    removeItem: function() {
      this.$emit('remove-item', this.item);
    }
  }
};


// 一覧画面 一覧表
var vmRealShopReturnGoodsTable = new Vue({
    el: '#realShopReturnGoodsTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , allStockList: [] // 店舗在庫全データ
    , inputCode: null
    , submitUrl: null
  }
  , components: {
      'result-item': vmComponentRealShopReturnGoodsInputItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      var self = this;

      self.submitUrl = $(self.$el).data('submitUrl');

      self.list = [];
      var i;
      for (i = 0; i < SHOP_STOCK_DATA.length; i++) {
        var item = SHOP_STOCK_DATA[i];
        var row = {
            neSyohinSyohinCode : item.ne_syohin_syohin_code
          , productCode        : item.product_code
          , shopStock          : Number(item.shop_stock) || 0
          , moveNum            : 0
        };

        self.allStockList.push(row);
      }

    });
  }

  , computed: {
  }
  , methods: {

    inputCodeOnKeyDown: function($event) {
      if ($event.keyCode == 13) {
        this.addItem();
      }
    }

    , findItem: function(productCode) {
      for (var i = 0; i < this.list.length; i++) {
        var item = this.list[i];
        if (item.productCode == productCode) {
          return item;
        }
      }
    }

    , findItemFromAll: function(productCode) {
      for (var i = 0; i < this.allStockList.length; i++) {
        var item = this.allStockList[i];
        if (item.productCode == productCode) {
          return item;
        }
      }
    }

    , addItem : function() {
      var self = this;

      if (!self.inputCode) {
        return;
      }

      var productCode = self.inputCode;
      var item = self.findItem(productCode);

      if (item) {
        item.moveNum += 1;
      } else {
        item = this.findItemFromAll(productCode);

        // 本来は、DB全データから取得するべきか
        if (!item) {
          item = {
              neSyohinSyohinCode: '-'
            , productCode       : self.inputCode
            , shopStock         : 0
          };
        }
        item.moveNum = 1;

        self.list.push(item);
      }

      self.inputCode = null;
    }

    , removeItem: function(item) {
      var index = this.list.indexOf(item);
      if (index >= 0) {
        this.list.splice(index, 1)
      }
    }

    , clearItems: function() {
      if (confirm('入力されたデータを全てクリアします。よろしいですか？')) {
        this.list = [];
      }
    }

    , submitReturn: function() {

      var self = this;

      if (!self.list.length) {
        vmGlobalMessage.setMessage('商品が入力されていません。', 'alert-danger');
        return;
      }

      if (!confirm("この内容で返品を確定してよろしいですか？")) {
        return;
      }

      var data = {
        list: []
      };
      for (var i = 0 ; i < self.list.length; i++) {
        var item = this.list[i];
        data.list.push({
            ne_syohin_syohin_code: item.neSyohinSyohinCode
          , shop_stock: item.shopStock
          , move_num: item.moveNum
        });
      }

      $.Vendor.WaitingDialog.show();

      $.ajax({
          type: "POST"
        , url: self.submitUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            alert('返品確定処理を実行しました。');

            // window.location.href = self.reportListUrl;
            window.location.href = self.submitUrl; // TODO ちゃんと一覧に戻るように。

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

