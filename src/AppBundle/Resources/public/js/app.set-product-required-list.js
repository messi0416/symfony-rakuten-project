/**
 * 管理画面 セット商品 作成要求一覧 JS
 */

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentSetProductRequiredListItem = {
    template: '#templateSetProductRequiredListTableRow'
  , props: [
        'item'
  ]
  , data: function() {
    return {
    };
  }
  /* もし、this.item の直接参照がいやなら、下記のwatchでインスタンスのプロパティを更新する必要がある。
  , watch: {
    item: function() {
      this.orderDate          = this.item.orderDate;
      this.paymentMethod      = this.item.paymentMethod;
      this.paymentType        = this.item.paymentType;
      this.purchaseQuantity   = this.item.purchaseQuantity;
      this.sunPaymentReminder = this.item.sunPaymentReminder;
      this.voucherNumber      = this.item.voucherNumber;
    }
  }
  */
  , computed: {
    creatable: function() {
      return this.item.creatableNum > 0;
    }
  }
  , methods: {
  }
};


// 一覧画面 一覧表
var vmSetProductRequiredList = new Vue({
    el: '#setProductRequiredList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , pageItemNum: 50
    , pageItemNumList: [ 2, 20, 50, 100 ]
    , page: 1

    , createUrl: null

    , orders: {
    }

    , messageState: {}

    , allCheck: false

    , filterSyohinCode: ''
    , filterCreatableOnly: false
  }
  , components: {
      'result-item': vmComponentSetProductRequiredListItem // 一覧テーブル
  }

  , watch: {
    allCheck: function(newValue, oldValue) {
      for (var i = 0; i < this.pageData.length; i++) {
        this.pageData[i].setCheck(newValue);
      }
    }
  }

  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      this.createUrl = $(this.$el).data('createUrl');

      this.list = [];

      if (SET_PRODUCT_REQUIRED_LIST_DATA) {
        for (var i = 0; i < SET_PRODUCT_REQUIRED_LIST_DATA.length; i++) {
          var item = SET_PRODUCT_REQUIRED_LIST_DATA[i];
          var row = {
              daihyoSyohinCode    : item.daihyo_syohin_code
            , daihyoSyohinName    : item.daihyo_syohin_name
            , setSku              : item.set_sku
            , colname             : item.colname
            , rowname             : item.rowname
            , requiredStock       : Number(item.required_stock)
            , setFreeStock        : Number(item.set_free_stock)
            , shortNum            : Number(item.short_num)
            , creatableNum        : Number(item.creatable_num)
            , listNum             : Number(item.list_num)
            , createNum           : Number(item.create_num)
            , detail              : item.detail ? item.detail : []

            , checked             : false

            , setCheck: function(flag) {
              if (flag && (this.creatableNum == 0 || this.createNum == 0)) {
                return;
              }
              this.checked = flag;
            }

          };

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }
    });
  }

  , computed: {

    totalItemNum: function() {
      return this.listData.length;
    }

    // sort, filter済みデータ
    , listData: function() {
      var self = this;
      var list = self.list.slice(); // 破壊防止

      //// 絞込: 商品コード
      if (self.filterSyohinCode && self.filterSyohinCode.length > 0) {
        var pattern = new RegExp($.Plusnao.String.regexQuote(self.filterSyohinCode), 'i');
        list = list.filter(function(item, i) {
          return item.setSku.match(pattern) !== null;
        });
      }
      if (self.filterCreatableOnly) {
        list = list.filter(function(item, i) {
          return item.creatableNum > 0;
        });
      }

      return list;
    }

    , pageData: function() {
      var startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    /// チェックした商品を作成リストに追加
    , submitCheckProducts: function() {
      const self = this;

      self.messageState.clear();

      var data = {};

      var list = self.list.filter(function(item){
        return item.checked;
      });

      if (!list.length) {
        self.messageState.setMessage('商品が選択されていません。', 'alert-warning');
        return;
      }
      if (!confirm("セット商品" + list.length.toString() + "件の作成リストを追加します。よろしいですか？")) {
        return;
      }

      data.list = list;

      $.ajax({
          type: "POST"
        , url: self.createUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            self.resetForm();
            self.messageState.setMessage(result.message, 'alert-success');
            window.location.reload();

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

        });
    }

    /// チェックを全て外して初期状態
    , resetForm: function() {

      this.messageState.clear();
      this.allCheck = false; // watch によりこれで全てチェックが外れる
    }


    //
    //, toggleOrder: function(key) {
    //  if (this.orders[key]) {
    //    if (this.orders[key] == 1) {
    //      this.orders[key] = -1;
    //    } else {
    //      this.orders[key] = null;
    //    }
    //  } else {
    //    var k;
    //    for (k in this.orders) {
    //      this.orders[k] = null;
    //    }
    //    this.orders[key] = 1;
    //  }
    //
    //}
    //
    ///**
    // * ソートアイコンCSSクラス
    // */
    //, getSortMarkCssClass: function(key) {
    //  if (!this.orders[key]) {
    //    return '';
    //  }
    //  return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    //}


    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------


  }

});

