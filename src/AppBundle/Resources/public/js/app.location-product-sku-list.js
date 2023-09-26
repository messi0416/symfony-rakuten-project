/**
 * 商品SKU一覧面用 JS
 */
$(function() {

// 初期処理
  // トップページ表示時 タブ選択
  var defaultTarget = '#locationProductSkuList';
  var hashTabName = document.location.hash;
  var targetNav = null;
  if (! hashTabName) {
    hashTabName = defaultTarget;
  }
  targetNav = $('.nav-tabs a[href=' + hashTabName + ']');
  if (targetNav.size() > 0) {
    targetNav.tab('show');
  }

  // タブ変更時イベント（ハッシュをつけるだけ）
  $("#indexNavTab a[data-toggle=tab]").on("shown.bs.tab", function (e) {
    document.location.hash = $(e.currentTarget).attr('href');
    $('html, body').stop().animate({
      scrollTop: 0
    }, 0);
  });

  var vmProductSkuListMain = new Vue({
      el: "#productSkuListMain"
    , data: {
    }
    , ready: function() {
    }
    , methods: {
    }
  });


});
