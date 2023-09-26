/**
 * 管理画面 セット商品作成リスト詳細 JS
 */

// 一覧画面 一覧表
var vmSetProductCreateDetail = new Vue({
    el: '#setProductCreateDetail'
  , delimiters: ['(%', '%)']
  , data: {
      createListSubmitUrl: null
    , orders: {
    }

    , labelPrintModalState: {
        show: false
      , showRealShopButton: false
      , initialList: []
    }
  }
  , components: {
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.createListSubmitUrl = $(this.$el).data('createListSubmitUrl');

      if (LABEL_LIST) {
        this.labelPrintModalState.initialList = [];
        for (var i = 0; i < LABEL_LIST.length; i++) {
          var item = LABEL_LIST[i];
          this.labelPrintModalState.initialList.push(item);
        }
      }
    });
  }

  , computed: {
  }
  , methods: {

    /// ラベルダウンロードモーダル表示
      showLabelModal: function() {
      this.labelPrintModalState.show = true;
    }

    // 確定
    , createListSubmit: function() {

      if (!confirm('このリストの商品作成をすべて一括で確定します。よろしいですか？')) {
        return false;
      }
      window.location.href = this.createListSubmitUrl;
    }

  }

});

