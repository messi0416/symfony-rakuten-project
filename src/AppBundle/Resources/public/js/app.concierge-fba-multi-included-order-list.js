/**
 * 管理画面 コンシェルジュ用 JS
 */

// 一覧画面 一覧表
const vmConciergeFbaMultiIncludedOrderListTable = new Vue({
    el: '#conciergeFbaMultiIncludedOrderList'
  , delimiters: ['(%', '%)']
  , data: {
    openedVoucherNumbers: []
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
    openVoucherWindow: function(voucherNumber) {
      $.Plusnao.NextEngine.openVoucherWindow(voucherNumber, 'concierge-list');
      if (this.openedVoucherNumbers.indexOf(voucherNumber) === -1) {
        this.openedVoucherNumbers.push(voucherNumber);
      }
      return false;
    }

    , getVoucherOpenedCss: function(voucherNumber) {
      return this.openedVoucherNumbers.indexOf(voucherNumber) === -1 ? 'btn-default' : 'btn-info';
    }

  }

});

