/**
 * 管理画面 複合出品設定 一覧
 */


// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentMixedProductListItem = {
    template: '#templateMixedProductListTableRow'
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
  }
  , methods: {
    reload: function() {
      this.$emit('reload', this.item);
    }
    , save: function() {
      this.$emit('submit-save', this.item);
    }
    , addChild: function() {
      this.$emit('add-child', this.item);
    }

    , removeChild: function(child) {
      this.$emit('remove-child', this.item, child);
    }

  }
};


// 一覧画面 一覧表
var vmMixedProductList = new Vue({
    el: '#mixedProductList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , page: 1
    , pageItemNum: 20
    , pageItemNumList: [ 20, 50, 100 ]

    , url: null
    , saveUrl: null

    , orders: {
      //  syohinCode: null
      //, shopStock: null
      //, freeStock: null
      //, orderNum: null
      //, cost: null
      //, basePrice: null
      //, currentPrice: null
      //, labelType: null
    }

    , messageState: {}

    , modalNewProductState: {
        show: false
      , eventOnChoiceProduct: 'submit-product'
    }
    , modalAddChildState: {
        show: false
      , eventOnChoiceProduct: 'submit-product'
    }

    , currentTargetItem: null

  }
  , components: {
      'result-item': vmComponentMixedProductListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      this.url = $(this.$el).data('url');
      this.saveUrl = $(this.$el).data('saveUrl');

      this.loadListData();
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

      //// 絞込: 今日の入金催促
      //if (! self.filterIncludeRecentReminder) {
      //  var today = new Date();
      //  list = list.filter(function(item, i) {
      //    return item.sunPaymentReminder ? $.Plusnao.Date.getDateString(item.sunPaymentReminder) != $.Plusnao.Date.getDateString(today) : true;
      //  });
      //}
      //
      return list;
    }

    , pageData: function() {
      var startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }

  , methods: {

    loadListData: function() {
      var self = this;

      // データ読み込み処理
      var data = {};

      $.ajax({
        type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var i;

            self.list = [];
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              self.list.push(item);
            }

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

    , showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }


    /// データ保存処理
    , submitSave: function(item) {

      var self = this;
      self.messageState.clear();

      if (!confirm('この商品の設定を保存してよろしいですか？')) {
        return;
      }

      // データ保存処理
      var data = {
          parent: item.parent
        , list: item.list
      };

      $.ajax({
          type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          var message;
          if (result.status == 'ok') {

            message = result.message.length > 0 ? result.message : '複合商品設定を保存しました。';
            self.messageState.setMessage(message, 'alert-success');
            self.loadListData();

          } else {
            message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
        });
    }

    /// リストへ1件追加
    , addRow: function(addItem) {
      this.messageState.clear();

      // すでに一覧に存在するかチェック
      for (var i = 0; i < this.list.length; i++) {
        var item = this.list[i];

        if (item.parent == addItem.parent) {
          this.messageState.setMessage('すでに一覧にある商品です。[' + addItem.parent + ']', 'alert-warning');
          return;
        }
      }

      this.list.unshift(this.fixRawItem(addItem));
    }

    /// リストへ追加するデータの項目そろえ（あまり意味が無い？）
    , fixRawItem: function(rawItem) {
      return {
          parent:         rawItem.parent
        , parentImageUrl: rawItem.parentImageUrl
        , list:           rawItem.list
      };
    }

    /// 親商品で検索
    , findByParent: function(parent) {
      for (var i = 0; i < this.list.length; i++) {
        if (this.list[i].parent == parent) {
          return this.list[i];
        }
      }
    }


    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------
    , openNewProductModal: function() {
      this.messageState.clear();
      this.modalNewProductState.show = true;
    }

    , submitNewProduct: function(item) {
      this.messageState.clear();
      this.modalNewProductState.show = false;

      var addItem = {
          parent: item.daihyoSyohinCode
        , parentImageUrl: item.imageUrl
        , list: []
      };

      this.addRow(addItem);
    }

    , openAddChildModal: function() {
      this.messageState.clear();
      this.modalAddChildState.show = true;
    }

    , submitAddChild: function(item) {
      this.messageState.clear();
      this.modalAddChildState.show = false;

      if (!this.currentTargetItem) { // イレギュラー。一応
        this.messageState.setMessage('追加対象商品が不明です。', 'alert-warning');
        return;
      }

      // すでに親商品ならダメ
      var exists = this.findByParent(item.daihyoSyohinCode);
      if (exists) {
        this.messageState.setMessage('すでに親商品として設定されています。 [ ' + item.daihyoSyohinCode + ']', 'alert-warning');
        return;
      }

      // 同じ親商品に重複していてもダメ
      for (var i = 0; i < this.currentTargetItem.list.length; i++) {
        var child = this.currentTargetItem.list[i];
        if (child.child == item.daihyoSyohinCode) {
          this.messageState.setMessage('すでに設定されています。 [ ' + item.daihyoSyohinCode + ']', 'alert-warning');
          return;
        }
      }

      this.currentTargetItem.list.push({
          parent: this.currentTargetItem.parent
        , child: item.daihyoSyohinCode
        , imageUrl: item.imageUrl
      });
    }

    /// 子商品1件追加（対象itemセット、モーダルOpen）
    , addChild: function(item) {
      this.messageState.clear();

      this.currentTargetItem = item;
      this.openAddChildModal();
    }

    /// 子商品1件削除（画面上）
    , removeChild: function(item, child) {
      this.messageState.clear();

      var target = this.findByParent(item.parent);
      if (target) {
        for (var i = 0; i < target.list.length; i++) {
          if (target.list[i].child == child.child) {
            target.list.splice(i, 1);
            return;
          }
        }
      }
    }

    /// 1件再読み込み
    , reloadOne: function(reloadItem) {
      this.messageState.clear();

      var self = this;

      // データ読み込み処理
      var data = {
        parent: reloadItem.parent
      };

      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            var i;
            var newInfo = null;
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              if (item.parent == reloadItem.parent) {
                newInfo = item;
                break;
              }
            }

            // データ差し替え
            if (newInfo) {
              for (i = 0; i < self.list.length; i++) {
                var target = self.list[i];
                if (target.parent == newInfo.parent) {
                  self.$set(self.list, i, self.fixRawItem(newInfo));
                  break;
                }
              }

            } else {
              self.messageState.setMessage('データが取得できませんでした。', 'alert-warning');
            }

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
        });
    }

  }

});

