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

/// 登録フォーム
var vmRegisterSimpleProduct = new Vue({
    el: '#realShopRegisterSimpleProduct'
  , delimiters: ['(%', '%)']
  , data: {
      skuCols: []
    , skuRows: []
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.skuCols = SKU_DATA.cols;
      this.skuRows = SKU_DATA.rows;
    });
  }
  , methods: {
    addLine: function(type) {
      var target = type == 'col' ? this.skuCols : this.skuRows;
      target.push({
          code: ''
        , name: ''
      });
    }

    , removeLine: function(type) {
      var target = type == 'col' ? this.skuCols : this.skuRows;
      if (target.length <= 1) {
        return;
      }
      target.pop();
    }

  }
});


