/**
 * 実店舗管理画面用 JS
 */

// 全体メッセージ
var vmGlobalMessage = new Vue({
    el: '#globalMessage'
  , delimiters: ['(%', '%)']
  , data: {
      message: ''
    , messageCssClass: ''
    , loadingImageUrl: null
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    });
  }
  , methods: {
    setMessage: function(message, cssClass, autoHide) {
      cssClass = cssClass || 'alert alert-info';
      autoHide = autoHide || true;

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
      }
    }
    , setCssClass: function(cssClass) {
      this.messageCssClass = cssClass;
    }
    , clear: function() {
      this.message = '';
      this.messageCssClass = '';
    }
    , closeWindow: function() {
      window.close();
    }
  }
});

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentRealShopProductStockItem = {
    template: '#templateRealShopProductStockListTableRow'
  , props: [
      'item'
    , 'labelTypeOptions'
    , 'taxRate'
  ]
  , data: function() {

    return {
        daihyoSyohinCode      : this.item.daihyoSyohinCode
      , image                 : this.item.image
      , freeStock             : this.item.shopStock
      , shopStock             : this.item.shopStock
      , orderNum              : this.item.orderNum
      , pickingNum            : this.item.pickingNum
      , cost                  : this.item.cost
      , basePrice             : this.item.basePrice
      , currentPrice          : this.item.currentPrice
      , labelType             : this.item.labelType

      , editingTaxedPrice: null
      , inEditCurrentPrice : false
      , inEditLabelType : false
    };
  }
  , computed: {
    displayCurrentPrice: function() {
      if (this.inEditCurrentPrice) {
        return !this.editingTaxedPrice  ? '-' : this.editingUntaxedPrice;
      } else {
        return !this.item.currentPrice ? '-' : this.item.currentPrice;
      }
    }

    , taxedPrice: function() {
      if (this.item.currentPrice) {
        return Math.floor(this.item.currentPrice * ((100 + this.taxRate) / 100));
      } else {
        return '-';
      }
    }

    , editingUntaxedPrice: function() {

      if (!this.editingTaxedPrice) {
        return 0;
      }

      var price = Math.ceil((this.editingTaxedPrice * 100) / (100 + this.taxRate));
      var taxed = Math.floor(price * (100 + this.taxRate) / 100);
      if (taxed % 10 !== 0) {
        price--;
      }

      return price;
    }

    , displayLabelType: function() {
      var self = this;
      var type = this.labelTypeOptions.filter(function(item) {
        return item.code == self.item.labelType;
      });

      return type.length ? type[0].name : '';
    }
  }
  , methods: {
    edit: function() {
      this.$emit('edit-item', this.item);
    }

    , editPrice: function() {
      this.editingTaxedPrice = this.item.currentPrice ?  this.taxedPrice : 0;
      this.inEditCurrentPrice = true;

      this.$nextTick(function() {
        // ここのthisもComponent自身なので大丈夫
        if (this.$refs.input) {
          this.$refs.input.focus();
        }
      });
    }

    , editPriceCancel: function(event) {
      if (event) {
        event.stopPropagation(); // これがないと、価格表示のdivのイベントが残っていて動いてしまう。
      }

      this.inEditCurrentPrice = false;
    }

    , savePrice: function(event) {
      if (event) {
        event.stopPropagation();
      }

      this.$emit('save-price', this.item, this.editingUntaxedPrice);
      this.editPriceCancel(event);
    }

    , keyPressEditPrice: function(event) {
      if (event.which === 13) {
        return this.savePrice();
      } else {
        return false;
      }
    }

    , editLabelType: function() {
      this.labelType = this.item.labelType;
      this.inEditLabelType = true;
    }

    , editLabelTypeCancel: function(event) {
      if (event) {
        event.stopPropagation(); // これがないと、価格表示のdivのイベントが残っていて動いてしまう。
      }

      this.labelType = this.item.labelType;
      this.inEditLabelType = false;
    }

    , saveLabelType: function(event) {
      if (event) {
        event.stopPropagation();
      }

      this.$emit('save-label-type', this.item, this.labelType);
      this.editLabelTypeCancel(event);
    }


  }
};

// 登録フォーム
var vmComponentModalRealShopProductStockForm = {
  template: '#templateModalRealShopProductStockForm'
  , props: [
      'item'
    , 'skuList'
    , 'show'
    , 'taxRate'
  ]
  , data: function() {
    return {
        messageClass: 'alert-success'
      , message: null

      , noticeHidden: true
      , noticeClass: 'alert-info'
      , notices: []

      , dataUrl: null
      , saveUrl: null
      , labelCsvUrl: null

      , list: [] // SKU一覧

      , doReload: false
      , editingTaxedPrice: 0
    };
  }
  , computed: {
    caption: function() {
      var caption = 'SKU';
      if (this.item.daihyoSyohinCode) {
        caption = this.item.daihyoSyohinCode;
      }
      return caption;
    }

    , taxedPrice: function() {
      var price = this.item.currentPrice ? this.item.currentPrice : this.item.basePrice;
      return Math.floor(price * ((100 + this.taxRate) / 100));
    }

    , editingUntaxedPrice: function() {

      if (!this.editingTaxedPrice) {
        return 0;
      }

      var price = Math.ceil((this.editingTaxedPrice * 100) / (100 + this.taxRate));
      var taxed = Math.floor(price * (100 + this.taxRate) / 100);
      if (taxed % 10 !== 0) {
        price--;
      }

      return price;
    }

    , labelTypeCss: function() {
      var ret = null;
      switch (this.item.labelType) {
        case 'tag':
          ret = 'fa-tag';
          break;
        case 'sticker':
          ret = 'fa-sticky-note-o';
          break;
      }

      return ret;
    }
  }



  , watch : {
    show: function() {
      var modal = $(this.$el);
      if (this.show && modal.is(':hidden')) {
        modal.modal('show');
      } else if (!this.show && !modal.is(':hidden')) {
        modal.modal('hide');
      }
    }
    //, item: function() {
    //  console.log('item changed');
    //  console.log(this.item);
    //}
  }

  , mounted: function() {
    this.$nextTick(function (){
      var self = this;

      self.dataUrl = $(this.$el).data('dataUrl');
      self.saveUrl = $(this.$el).data('saveUrl');
      self.labelCsvUrl = $(this.$el).data('labelCsvUrl');

      // イベント登録
      // -- open前
      $(self.$el).on('show.bs.modal', function(e) {

        self.reset();

        self.editingTaxedPrice = self.taxedPrice;

        var data = {
          daihyo_syohin_code: self.item.daihyoSyohinCode
        };

        self.list = [];

        $.ajax({
            type: "GET"
          , url: self.dataUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {

            if (result.status == 'ok') {
              self.list = [];
              var i;

              for (i = 0; i < result.list.length; i++) {
                var item = result.list[i];
                var row = {
                    neSyohinSyohinCode    : item.ne_syohin_syohin_code
                  , daihyoSyohinCode      : item.daihyo_syohin_code
                  , colname               : item.colname
                  , rowname               : item.rowname
                  , freeStock             : Number(item.free_stock)
                  , shopStock             : Number(item.shop_stock)
                  , orderNum              : Number(item.order_num)
                  , orderRemain           : Number(item.order_remain)
                  , lastOrdered           : (item.last_ordered ? new Date(item.last_ordered.replace(/-/g, "/")) : null) // replace for firefox, IE
                  , barcode               : item.barcode
                  , barcodeSVG            : item.barcodeSVG

                  , editShopStock : false /* 店舗在庫編集フラグ */
                };

                self.list.push(row);
              }

            } else {
              self.messageCssClass = 'alert-danger';
              self.message = result.message.length > 0 ? result.message : 'SKU情報が取得できませんでした。';
            }
          })
          .fail(function(stat) {
            self.messageCssClass = 'alert-danger';
            self.message = 'エラーが発生しました';
          })
          . always(function() {

          });

      });

      // -- close後
      $(self.$el).on('hidden.bs.modal', function(e) {
        if (self.doReload) {
          self.$emit('reload-current-page');
        }
      })
    });
  }
  , methods: {
    hideForm: function() {
      this.$emit('hide-form');
    }

    /// 編集結果保存
    , save: function() {
      var self = this;

      // Show loading
      // $.Vendor.WaitingDialog.show('保存中 ...');

      // データ保存処理

      // 添字変換
      var fixedList = [];
      for(var i = 0; i < self.list.length; i++) {
        var sku = self.list[i];
        fixedList.push({
            ne_syohin_syohin_code : sku.neSyohinSyohinCode
          , daihyo_syohin_code    : sku.daihyoSyohinCode
          , stock                 : sku.shopStock
          , order_num             : sku.orderNum
          , last_ordered          : sku.lastOrdered ? $.Plusnao.Date.getDateString(sku.lastOrdered) : ''
        });
      }

      var data = {
          item: self.item
        , list: fixedList
        , new_price: self.editingUntaxedPrice
      };

      $.ajax({
        type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            self.messageCssClass = 'alert-success';
            self.message = 'SKUデータを保存しました。';

            self.doReload = true;

          } else {
            self.messageCssClass = 'alert-danger';
            self.message = result.message.length > 0 ? result.message : '保存に失敗しました。';

          }
        })
        .fail(function(stat) {
          self.messageCssClass = 'alert-danger';
          self.message = result.message.length > 0 ? result.message : 'エラーが発生しました。。';
        })
        . always(function() {

          $('#modalRealShopProductStockForm').animate({
            scrollTop: 0
          }, 200);

          //  loading
          // $.Vendor.WaitingDialog.hide();
        });

    }

    , reset: function() {
      this.messageCssClass = 'alert-success';
      this.message = null;

      this.doReload = false;
    }

    , displayLastOrdered: function(date) {
      return date ? $.Plusnao.Date.getDateString(date) : '';
    }

    , toggleEditShopStock: function(sku) {
        sku.editShopStock = ! sku.editShopStock;
    }

    , getLabelCsvUrl: function(sku) {
      var url = this.labelCsvUrl;
      url = url + '?code=' + sku.neSyohinSyohinCode + '&num=' + sku.shopStock + '&type=' + this.item.labelType
      return url;
    }

    , getLabelCsvFilename: function(sku) {
      return 'real_shop_label_' + sku.neSyohinSyohinCode + '_' + this.item.labelType  + '.csv';
    }
  }
};


// 一覧画面 一覧表
var vmRealShopProductStockListTable = new Vue({
    el: '#realShopProductStockListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    , pageItemNum: 100
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , url: null
    , savePriceUrl: null
    , saveLabelTypeUrl: null
    , currentItem: {}
    , editFormShown: false

    , taxRate: 0

    , filterSyohinCode: null
    , filterShopStockOnly: 0
    , filterForestStockOnly: 1
    , filterStockOrderOnly: 0
    , filterIncludeFinished: 0
    , filterLabelType: ""

    , orders: {
        syohinCode: null
      , shopStock: null
      , freeStock: null
      , orderNum: null
      , cost: null
      , basePrice: null
      , currentPrice: null
      , labelType: null
    }

    , labelTypeOptions: [
        { code: 'tag', name: '下げ札', order: 1 }
      , { code: 'sticker', name: 'シール', order: 2 }
    ]
  }
  , components: {
      'result-item': vmComponentRealShopProductStockItem // 一覧テーブル
    , 'modal-form': vmComponentModalRealShopProductStockForm // 編集フォーム
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.savePriceUrl = $(this.$el).data('savePriceUrl');
      this.saveLabelTypeUrl = $(this.$el).data('saveLabelTypeUrl');
      this.showFirstPage();
    });
  }

  , computed: {
    filterLabelTypeCss: function() {
      return (this.filterLabelType == "") ? 'placeholder' : null;
    }
  }
  , methods: {
    showPage: function(pageInfo) {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('loading ...');

      var page = pageInfo.page;

      // データ読み込み処理
      var data = {
          page: page
        , limit: pageInfo.pageItemNum
        , conditions: {
            daihyo_syohin_code : this.filterSyohinCode
          , shop_stock_only    : (this.filterShopStockOnly   ? 1 : 0)
          , forest_stock_only  : (this.filterForestStockOnly ? 1 : 0)
          , stock_order_only   : (this.filterStockOrderOnly  ? 1 : 0)
          , include_finished   : (this.filterIncludeFinished ? 1 : 0)
          , label_type         : this.filterLabelType
        }
        , orders: {
            daihyo_syohin_code: this.orders.syohinCode
          , shop_stock    : this.orders.shopStock
          , free_stock    : this.orders.freeStock
          , order_num     : this.orders.orderNum
          , cost          : this.orders.cost
          , base_price    : this.orders.basePrice
          , current_price : this.orders.currentPrice
          , label_type    : this.orders.labelType
        }
      };

      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            // 税率
            self.taxRate = Number(result.tax_rate);

            // データ
            self.list = [];
            var i;

            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  daihyoSyohinCode      : item.daihyo_syohin_code
                , image                 : item.image
                , freeStock             : Number(item.free_stock)
                , shopStock             : Number(item.shop_stock)
                , orderNum              : Number(item.order_num)
                , pickingNum            : Number(item.picking_num)

                , cost                  : Number(item.cost)
                , basePrice             : Number(item.base_price)
                , currentPrice          : Number(item.current_price)
                , labelType             : item.label_type ? item.label_type : 'tag'
              };

              self.list.push(row);
            }

            self.totalItemNum = Number(result.count);
            self.page = page;

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });
    }

    , showFirstPage: function() {
      var pageInfo = {
          page: 1
        , pageItemNum: this.pageItemNum
      };
      this.showPage(pageInfo);
    }

    , searchFormKeyPress: function(event) {
      if (event.which === 13) {
        return this.showFirstPage();
      } else {
        return false;
      }
    }

    , toggleOrder: function(key) {
      if (this.orders[key]) {
        if (this.orders[key] == 1) {
          this.orders[key] = -1;
        } else {
          this.orders[key] = null;
        }
      } else {
        var k;
        for (k in this.orders) {
          this.orders[k] = null;
        }
        this.orders[key] = 1;
      }

      // リロード
      this.showFirstPage();
    }

    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function(key) {
      if (!this.orders[key]) {
        return '';
      }
      return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------

    /// 編集フォームを開く
    , editItem: function(item) {
      this.currentItem = item;
      this.editFormShown = true;
    }

    /// 編集フォームを閉じる
    , hideForm: function() {
      this.editFormShown = false;
    }

    /// 現在のページを再読込（編集後など）
    , reloadCurrentPage: function() {
      var pageInfo = {
          page: this.page
        , pageItemNum: this.pageItemNum
      };
      this.showPage(pageInfo);
    }

    /// 店舗価格更新
    , saveItemCurrentPrice: function(item, price) {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('保存中 ...');

      // データ保存処理

      var data = {
          daihyo_syohin_code: item.daihyoSyohinCode
        , price: price
      };

      $.ajax({
          type: "POST"
        , url: self.savePriceUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            vmGlobalMessage.setMessage('価格を保存し、スマレジの一括更新を完了しました。', 'alert-success');
            item.currentPrice = price;

          } else {
            vmGlobalMessage.setMessage('価格更新に失敗しました。', 'alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert-danger');
        })
        . always(function() {

          //  loading
          $.Vendor.WaitingDialog.hide();
        });
    }

    /// ラベル種別更新
    , saveItemLabelType: function(item, labelType) {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('保存中 ...');

      // データ保存処理

      var data = {
          daihyo_syohin_code: item.daihyoSyohinCode
        , label_type: labelType
      };

      $.ajax({
          type: "POST"
        , url: self.saveLabelTypeUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            vmGlobalMessage.setMessage('ラベル種別を変更しました。', 'alert-success');
            item.labelType = labelType;

          } else {
            vmGlobalMessage.setMessage('ラベル種別変更に失敗しました。', 'alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert-danger');
        })
        . always(function() {

          //  loading
          $.Vendor.WaitingDialog.hide();
        });


    }

  }

});

