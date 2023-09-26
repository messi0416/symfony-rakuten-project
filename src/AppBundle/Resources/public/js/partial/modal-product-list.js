/**
 * 商品検索・一覧モーダル
 * for Vue2.x
 */


const productListState = {
    component: 'index'
  , currentItem: {}
  , currentItemSku: []

  , message: ''
  , messageCssClass: 'alert-info'

  , setComponent: function(component) {
    this.component = component;
  }
  , setCurrentItem: function(item) {
    this.currentItem = item;
  }
  , setCurrentItemSku: function(list) {
    this.currentItemSku = list;
  }

  , setMessage: function(message, css, autoHide) {
    if (css === undefined) {
      css = 'alert-info';
    }
    if (autoHide === undefined) {
      autoHide = true;
    }
    this.message = message;
    this.messageCssClass = css;

    if (autoHide) {
      var self = this;
      setTimeout(function(){ self.message = ''; } , 3000);
    }
  }
};


/**
 * 代表商品コード 検索・表示パネル
 * @type {{template: string, methods: {showSku: Function}}}
 */
var modalProductListBodyIndex = {
    template: '#templateModalProductListBodyIndex'
  , props: [
      'mainState'
    , 'productListState'
  ]
  , data: function() {
    return {
        searchUrl: null
      , searchCode: null
      , list: []
    };
  }

  , mounted: function() {
    this.$nextTick(function () {
      this.searchUrl = $(this.$el).data('searchUrl');
    });
  }
  , methods: {

      choiceProduct: function(item) {
        if (this.mainState.eventOnChoiceProduct && this.mainState.eventOnChoiceProduct.length > 0) {
          this.$emit('emit-parent-event', this.mainState.eventOnChoiceProduct, item);
        } else {
          return this.showSku(item);
        }
    }

    , showSku: function(item) {
       this.$emit('show-component', 'sku', item);
    }

    , search: function() {
      var self = this;

      // データ読み込み処理
      var data = {
          code: self.searchCode
        , likeMode: 'forward'
        , limit: 100
    };

      $.ajax({
          type: "GET"
        , url: self.searchUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var i;

            self.list = [];
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  daihyoSyohinCode : item.daihyo_syohin_code
                , imageUrl         : item.image_url
              };

              self.list.push(row);
            }
            // 0件
            if (self.list.length == 0) {
              self.productListState.setMessage('データがありません。', 'alert-warning');
            }


          } else {
            var message = result.message.length > 0 ? result.message : 'データが取得できませんでした。';
            self.productListState.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.productListState.setMessage('エラーが発生しました。', 'alert-danger');
        })
        . always(function() {
        });
    }

    , reset: function() {
      this.searchCode = '';
      this.list = [];
    }

  }
};

/**
 * SKU 表示パネル
 * @type {{template: string, methods: {showIndex: Function}}}
 */
var modalProductListBodySku = {
    template: '#templateModalProductListBodySku'
  , props: [
      'mainState'
    , 'productListState'
  ]
  , data: function() {
    return {
    };
  }
  , methods: {
    showIndex: function () {
      this.$emit('show-component', 'index', {});
    }

    , choiceSku: function(item) {
      this.$emit('emit-parent-event', 'submit-sku', item);
    }
  }
};

/**
 * メインブロック
 */
Vue.component('parts-modal-product-list', {
    template: '#templateModalProductList'
  , delimiters: ['(%', '%)']
  , components: {
      index: modalProductListBodyIndex
    , sku: modalProductListBodySku
  }
  , props: [
    'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
  ]
  , data: function() {
    return {
        productListState: productListState
      , searchSkuUrl: null
    };
  }
  , computed: {
    caption: function() {
      var caption = '商品検索';
      //if (this.item.daihyoSyohinCode) {
      //  caption = this.item.daihyoSyohinCode;
      //}
      return caption;
    }
  }

  , watch : {
  }

  , mounted: function() {
    this.$nextTick(function (){
      var self = this;
      var modal = $(self.$el);

      self.searchSkuUrl = $(self.$el).data('searchSkuUrl');

      // イベント登録
      self.$watch('state.show', function(newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });

      // -- open前
      modal.on('show.bs.modal', function(e) {
        self.reset();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.state.show = false; // 外部から閉じられた時のステータス手当
        }
      })
    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
    }

    , reset: function() {
      this.productListState.component = 'index';
      this.productListState.currentItem = {};
    }

    // 表示パネル切り替え
    , showComponent: function(componentName, item) {

      // SKU画面表示 データ取得
      if (componentName == 'sku') {
        if (item.daihyoSyohinCode) {

          var self = this;

          self.productListState.setCurrentItem(item);

          // データ読み込み処理
          var data = {
              code: item.daihyoSyohinCode
          };

          $.ajax({
              type: "GET"
            , url: self.searchSkuUrl
            , dataType: "json"
            , data: data
          })
            .done(function(result) {

              if (result.status == 'ok') {

                var i;

                var list = [];
                for (i = 0; i < result.list.length; i++) {
                  var item = result.list[i];
                  list.push(item);
                }

                self.productListState.setCurrentItemSku(list);

              } else {
                var message = result.message.length > 0 ? result.message : 'データが取得できませんでした。';
                self.productListState.setMessage(message, 'alert-danger');
              }
            })
            .fail(function(stat) {
              console.log(stat);
              self.productListState.setMessage('エラーが発生しました', 'alert-danger');
            })
            . always(function() {
            });

        }

      } else if (componentName == 'index') {
        this.productListState.setCurrentItem({});
        this.productListState.setCurrentItemSku([]);
      }

      this.productListState.setComponent(componentName);
    }

    /**
     * 親イベント実行
     */
    , emitParentEvent: function(event, item) {
      this.$emit(event, item);
    }
  }
});
