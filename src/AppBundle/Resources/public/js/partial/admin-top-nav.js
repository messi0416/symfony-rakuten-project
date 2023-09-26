/**
 * 管理画面 ヘッダナビ
 * Vue.js >= 2.0.0
 */
/*
Vue.component('parts-admin-top-nav', {
    template: '#partsAdminTopNav'
*/
var vmAdminTopNav = new Vue({
    el: '#adminTopNav'
  , delimiters: ['(%', '%)']
  , data: {
      labelPrintModalState: {
        show: false
      , showRealShopButton: true
    }

    , goodsImageEditCodeModalState: {
        show: false
      , eventOnChoiceProduct: 'submit-product'
    }

    , goodsImageEditUrl: null
  }
  , mounted: function() {
    //this.$nextTick(function () {
    //});
  }
  , methods: {
    openMailTemplateWindow: function(url) {
      var width = 640;
      var height = 530;

      var options = [
          'menubar=no'
        , 'toolbar=no'
        , 'width=' + width
        , 'height=' + height
        , 'resizable=yes'
        , 'scrollbars=yes'
      ];

      window.open(
          url
        , 'concierge-mail-template'
        , options.join(',')
      );

      return false;
    }

    , openRakutenInquiryCountWindow: function(url) {
      var width = 540;
      var height = 250;

      var options = [
          'menubar=no'
        , 'toolbar=no'
        , 'width=' + width
        , 'height=' + height
        , 'resizable=yes'
        , 'scrollbars=yes'
      ];

      window.open(
          url
        , 'concierge-rakuten-inquiry'
        , options.join(',')
      );

      return false;
    }

    /// ラベルダウンロードモーダル表示
    , showLabelModal: function() {
      this.labelPrintModalState.show = true;
    }

    /// 画像編集 商品選択モーダル表示
    , showGoodsImageEditCodeModal: function(event) {
      this.goodsImageEditUrl = $(event.target).attr('href');
      // console.log(this.goodsImageEditUrl);
      this.goodsImageEditCodeModalState.show = true;
    }

    , goImageEdit: function(item) {
      if (item.daihyoSyohinCode) {
        if (this.goodsImageEditUrl) {
          window.location.href = this.goodsImageEditUrl.replace(/__DUMMY__/, item.daihyoSyohinCode);
        } else {
          alert('遷移に失敗しました。(URL取得エラー)');
        }

      } else {
        alert('代表商品コードが取得できません。');
      }
    }
  }
});
