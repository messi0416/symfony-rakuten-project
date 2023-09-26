var deliveryCreateShippingInfo = new Vue({
  el: '#deliveryCreateShippingInfo'
    , data: {
      availableShippingMethods: DELIVERY_METHOD // 配送方法リスト

      , messageState: {}
      , modalState: {
          message: ''
        , messageCssClass: ''
        , currentItem: {}
        , show: false
      }
      , urlCsv: null
      , urlPdf: null
      , deliveryMethod : null
    }
  , mounted: function() {
    this.$nextTick(function () {
      this.urlCsv = $(this.$el).data('urlCsv');
      this.urlPdf = $(this.$el).data('urlPdf');
    });
  }

  , methods: {

    downloadCsv: function(isYuupuriR = false) {
      var self = this;
      if (isYuupuriR) {
        const message = `ゆうプリR用のCSVをダウンロードします。
現在紐づいている自社管理の発送伝票番号が破棄されます。
よろしいですか？`;

        if (!confirm(message)) {
          return;
        }
      }
      var $form = $('#shippingInfoDownloadForm');
      $form.attr('target', "_self");
      $form.attr('action', self.urlCsv);
      $form.submit();
    }
    
    ,downloadPdf: function(assignOwnTrackingNumber = false) {
      var self = this;
      if (assignOwnTrackingNumber) {
        const message = `自社管理の発送ラベルを生成します。
発送伝票番号がない伝票は、発送伝票番号を発行します。
よろしいですか？`;

        if (!confirm(message)) {
          return;
        }
      }
      var $form = $('#shippingInfoDownloadForm');
      $form.attr('target', "_blank");
      $form.attr('action', self.urlPdf);
      $form.submit();
    }
  }
});
