/**
 * 倉庫へ画面 JS
 */
$(function() {

  /**
   * 「倉庫へ」一覧テーブル
   */
  var vmListTable = new Vue({
      el: "#warehouseList"
    , data: {
    }
    , ready: function() {
    }
    , methods: {
      toggleDetail: function($event) {
        var target = $event.target;
        var $detailTr = $(target).closest('tr').next('tr.detail');
        if ($detailTr.size() == 0) {
          return;
        }

        if ($detailTr.hasClass('hidden')) {
          $detailTr.removeClass('hidden');
        } else {
          $detailTr.addClass('hidden');
        }
      }
    }
  });

  /**
   * 「倉庫へ」再集計
   */
  var vmRecalculateForm = new Vue({
      el: "#recalculateForm"
    , data: {
      url: null
    }
    , ready: function() {
      this.url = $(this.$el).data('url');
    }
    , methods: {
      recalculate: function() {
        if (confirm("「倉庫へ」の在庫数を再集計します。\n\nよろしいですか？")) {

          var url = this.url;
          var cleanUpFlag = $('#cleanUpFlag', this.$el).prop('checked');

          if (cleanUpFlag) {
            url = url + '?cleanUpFlag=1';
          }

          window.location.href = url;
        }
      }
    }
  });


});
