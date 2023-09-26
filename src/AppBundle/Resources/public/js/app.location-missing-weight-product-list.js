/**
 * ロケーション JS
 * Vue 2.x
 */

/**
 * 検索フォーム・一覧テーブル
 */
const vmLocationMissingWeightProductList = new Vue({
  el: "#locationMissingWeightProductList"
  , data: {
      url: null
  }
  , mounted: function() {
    const self = this;

    this.$nextTick(function() {
      self.url = $(self.$el).data('url');
    });

  }
  , computed: {
  }

  , methods: {
    submitSearchForm: function() {
      $('#searchForm', this.$el).submit();
    }

  }
});
