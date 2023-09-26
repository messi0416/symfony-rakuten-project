/**
 * 商品ロケーション画面用 JS
 */
$(function() {

  /**
   * 検索フォーム
   */
  var vmSearchProduct = new Vue({
      el: "#searchProduct"
    , data: {
        searchKeywordProduct: ''
      , searchNoStockProduct: false
      , searchLikeMode: 'forward'
    }
    , ready: function() {
      this.url = $(this.$el).data('url');

      this.searchNoStockProduct = $(this.$el).data('searchNoStockProduct') == '1';
      this.searchLikeMode = $(this.$el).data('searchLikeMode');
    }
    , methods: {
      onKeyDown: function($event) {
        if ($event.keyCode == 13) {
          this.search();
        }
      }
      , search: function() {
        if (this.searchKeywordProduct.length == 0) {
          vmGlobalMessage.setMessage('検索キーを入力してください。', 'alert-warning');
          return;
        }
        vmGlobalMessage.clear();
        vmGlobalMessage.clearFlashMessage();

        var url = this.url.replace(/\/$/, "") + '/' + this.searchKeywordProduct;
        url += '?no_stock=' + (this.searchNoStockProduct ? '1' : '0');
        url += '&like_mode=' + this.searchLikeMode;

        window.location.href = url;
      }

      , toggleSearchNoStockProduct: function() {
        this.searchNoStockProduct = ! this.searchNoStockProduct;
      }

    }
  });



});
