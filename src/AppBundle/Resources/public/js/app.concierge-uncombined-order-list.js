/**
 * 管理画面 コンシェルジュ用 JS
 */
$(function() {
  var clipboard = new Clipboard('.btnCopyVoucherNumber');
  clipboard.on('success', function(e) {
    e.clearSelection();
  });
});



// 一覧画面 一覧表
var vmConciergeUncombinedOrderList = new Vue({
    el: '#conciergeUncombinedOrderList'
  , delimiters: ['(%', '%)']
  , data: {

    clickedNumbers: []

  }
  , components: {
  }
  , mounted: function() {
    this.$nextTick(function () {

    });
  }
  , watch: {
  }

  , computed: {
  }
  , methods: {
    submitSearchForm: function() {
      $('#conciergeUncombinedOrderListSearchForm', this.$el).submit();
    }

    , copyClicked: function(number) {
      if (this.clickedNumbers.indexOf(number) == -1) {
        this.clickedNumbers.push(number);
      }
    }

    , getCopyButtonClickedCss: function(number) {
      return this.clickedNumbers.indexOf(number) == -1 ? 'btn-default' : 'btn-info';
    }
  }

});

