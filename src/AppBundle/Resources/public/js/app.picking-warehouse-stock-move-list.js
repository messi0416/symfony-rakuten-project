/**
 * 倉庫在庫ピッキングリスト一覧画面 JS
 */

/**
 * 一覧
 */
var vmWarehouseStockMoveListSearchForm = new Vue({
  el: '#warehouseStockMoveListSearchForm'
  , data: {
    showDeleteButtons: false
  }
  , computed: {
    deleteButtonCss: function() {
      return this.showDeleteButtons ? 'btn-danger' : 'btn-default'
    }
  }

  , mounted: function() {
    this.$nextTick(function() {
      const self = this;

      $('#searchDate', this.$el).datepicker({
        language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.search();
        }
        , clearDate: function() {
          self.search();
        }
      });
    });

  }
  , methods: {
    search: function() {
      $(this.$el).submit();
    }
    , toggleDeleteButtons: function() {
      this.showDeleteButtons = ! (this.showDeleteButtons);
    }
  }
});

var vmWarehouseStockMoveList = new Vue({
  el: '#warehouseStockMoveList'
  , data: {
    removeListUrl: null

  }

  , mounted: function() {
    this.$nextTick(function() {
      this.removeListUrl = $(this.$el).data('removeListUrl');
    });
  }

  , computed: {
    showDeleteButtons: function() {
      return vmWarehouseStockMoveListSearchForm.showDeleteButtons;
    }
  }
  , methods: {
    removeList: function(date, number) {
      const self = this;

      if (!confirm('このピッキングリストを削除します。よろしいですか？')) {
        return;
      }

      // Show loading
      $.Vendor.WaitingDialog.show('削除中 ...');

      const data = {
          date: date
        , number: number
      };
      console.log(data);

      $.ajax({
          type: "POST"
        , url: self.removeListUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          let message;
          if (result.status === 'ok') {
            window.location.reload();

            // エラー
          } else {
            message = result.message && result.message.length > 0 ? result.message : 'ピッキングリストの削除に失敗しました。';
            alert(message);
          }

        })
        .fail(function(stat) {
          console.log(stat);
          alert('エラーが発生しました。');

        })
        . always(function() {
          $.Vendor.WaitingDialog.hide();
        });
    }
  }
});

