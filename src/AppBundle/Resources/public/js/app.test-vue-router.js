/**
 * vue-router テスト
 */
(function() {
  var costRateTable = {
    template: '<h1>もげ！！</h1>'
  };

  var allProducts = {
    template: '<h1>ぱげ！！</h1>'
  };

  var router = new VueRouter();

  router.map({
    '/costRateTable': {
      component: costRateTable
    },
    '/allProducts': {
      component: allProducts
    }
  });

  var App = {};
  router.start(App, '#mainBlock');

})();
