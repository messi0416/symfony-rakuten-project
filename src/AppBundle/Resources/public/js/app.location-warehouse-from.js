/**
 * 倉庫から画面 JS
 */
$(function() {
  /**
   * 「倉庫から」再集計
   */
  var vmRecalculateFromForm = new Vue({
    el: "#recalculateFromForm"
    , data: {
      url: null
    }
    , ready: function() {
      this.url = $(this.$el).data('url');
    }
    , methods: {
      recalculate: function() {
        if (confirm("「倉庫から」の在庫数を再集計します。\n\nよろしいですか？")) {
          window.location.href = this.url;
        }
      }
    }
  });

});
