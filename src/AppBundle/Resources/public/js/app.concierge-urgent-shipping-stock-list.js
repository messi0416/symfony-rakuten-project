/**
 * 管理画面 コンシェルジュ用 JS
 */

// 一覧画面 一覧表
var vmConciergeUrgentShippingStockList = new Vue({
  el: '#conciergeUrgentShippingStockList'
  , delimiters: ['(%', '%)']
  , data: {
      changeVoucherUrl: null
    , voucherNumber: ''

  }
  , components: {
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.changeVoucherUrl = $(this.$el).data('changeVoucherUrl');

      if (typeof VOUCHER_NUMBER !== 'undefined') {
        this.voucherNumber = VOUCHER_NUMBER;
      }
    });
  }

  , computed: {
  }
  , methods: {
    changeVoucher: function() {
      if (this.voucherNumber.length == 0) {
        return;
      }

      window.location.href = this.changeVoucherUrl + '?num=' + this.voucherNumber;
    }

    , selectAll: function(event) {
      event.target.select();
    }

    , openVoucherWindow: function(event) {
      var voucherNumber = $.Plusnao.String.trim($(event.target).text());
      if (!voucherNumber || !voucherNumber.length || !voucherNumber.match(/^\d+$/)) {
        return;
      }

      $.Plusnao.NextEngine.openVoucherWindow(voucherNumber, 'concierge-list');
      return false;
    }

  }

});
