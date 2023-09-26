/**
 * ロケーション詳細画面用 JS
 */
$(function() {

  // 一覧テーブル 行コンポーネント
  Vue.component('result-item', {
    template: "#result-item",
    props: [
      'item'
    ],
    data: function() {
      return {
          neSyohinSyohinCode: this.item.neSyohinSyohinCode
        , locationId        : this.item.locationId
        , stock             : this.item.stock
        , position          : this.item.position
        , image             : this.item.image
        , moveNum           : this.item.moveNum
        , isChecked         : this.item.isChecked
      };
    },
    computed: {
      rowCss: function() {
        return this.item.isChecked ? 'success' : '';
      }
      , productDetailUrl: function() {
        return vmLocationDetail.productDetailUrlBase + this.item.neSyohinSyohinCode;
      }
    },
    ready: function() {
    },
    methods: {
      toggleCheck: function() {
        this.item.isChecked = ! (this.item.isChecked);
      }
    }
  });


  /**
   * 商品ブロック
   */
  var vmLocationDetail = new Vue({
      el: "#locationDetail"
    , data: {
        location: {}
      , warehouse: {}
      , productLocations: []
      , productsNum: 0

      , productDetailUrlBase: null

      , updateUrl: null
      , deleteAllUrl: null
      , dataHash: null

      , newLocationCode: null
      , hasError: false
      , errorMessage: ''

      , stockChangeComment: null
      , stockChangeConfirmMessage: null
      , commentErrorMessage: null
      , commentChoiceOptions: [
        '出荷確定戻しの架空在庫'
      ]
    }
    , ready: function() {

      this.updateUrl = $(this.$el).data('updateUrl');
      this.deleteAllUrl = $(this.$el).data('deleteAllUrl');

      this.productDetailUrlBase = $(this.$el).data('productDetailUrl').replace(/DUMMY/, '');

      this.$set('location', LOCATION_DATA.location);
      this.$set('warehouse', LOCATION_DATA.warehouse);
      this.$set('dataHash'  , LOCATION_DATA.dataHash);

      var locations = [];
      for (var i = 0; i < LOCATION_DATA.productLocations.length; i++) {
        var item = LOCATION_DATA.productLocations[i];
        locations.push({
            neSyohinSyohinCode: item.neSyohinSyohinCode.trim()
          , locationId        : Number(item.locationId)
          , stock             : Number(item.stock)
          , position          : Number(item.position)
          , image             : item.image
          , moveNum           : Number(item.moveNum)

          , isChecked         : false
        });

        this.productsNum++;
      }
      this.$set('productLocations' , locations);

      // 移動先初期値
      this.newLocationCode = this.location.locationCode
    }
    , computed: {
        checkedProductsNum: function() {
          return this.productLocations.reduce(function(result, item) {
            return result + (item.isChecked ? 1 : 0);
          }, 0);
      }
      , checkedStockTotal: function() {
        return this.productLocations.reduce(function(result, item) {
          return result + (item.isChecked ? item.stock : 0);
        }, 0);
      }
    }
    , methods: {

      /// 全て選択
        checkAll: function() {
        for (var i = 0; i < this.productLocations.length; i++) {
          this.productLocations[i].isChecked = true;
        }
      }

      /// 全て選択解除
      , unCheckAll: function() {
        for (var i = 0; i < this.productLocations.length; i++) {
          this.productLocations[i].isChecked = false;
        }
      }

      /// エラーセット
      , setError: function(message) {
        this.errorMessage = message;
        this.hasError = (message.length != 0);
      }

      /// エラーリセット
      , clearError: function() {
        this.errorMessage = "";
        this.hasError = false;
      }

      /// 確定前エラーチェック処理
      , validateData: function() {

        this.clearError();

        if (this.checkedProductsNum <= 0) {
          this.setError('商品が選択されていません。');
        }

        if (! this.newLocationCode) {
          this.setError('移動先のロケーションコードを入力してください。');
        }

        if (this.newLocationCode == this.location.locationCode) {
          this.setError('移動元と移動先が同じロケーションです。');
        }

      }

      /// 確定処理
      , submitForm: function() {

        var self = this;

        // 入力チェック
        self.validateData();

        if (self.hasError) {
          return;
        }

        if (! confirm(
              " 【 " + self.newLocationCode + " 】 へ "
            + self.checkedProductsNum.toString()
            + " 件の商品を移動します。\n\nよろしいですか？")) {
          return;
        }

        // 更新処理・結果表示処理
        // Show loading
        $.Vendor.WaitingDialog.show('更新中 ...');

        self.clearError();

        var data = {
            data_hash: self.dataHash
          , new_location_code: self.newLocationCode
          , new_location_products: self.productLocations.filter(function(item) {
            return item.isChecked;
          }).map(function(item) {
            return {
                ne_syohin_syohin_code: item.neSyohinSyohinCode
              , location_id: item.locationId
              , stock: item.stock
              , position: item.position
            };
          })
        };

        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {

              window.location.href = result.redirect; // リダイレクト

            } else {
              self.setError(result.message);

              // Hide loading
              $.Vendor.WaitingDialog.hide();
            }
          })
          .fail(function(stat) {
            console.log(stat);
            self.setError('エラー：更新に失敗しました。');

            // Hide loading
            $.Vendor.WaitingDialog.hide();
          })
          .always(function() {
          });

      }

      /// 一括削除処理 ※確定戻し用
      , deleteAll: function(stockChangeComment) {

        if (!stockChangeComment) {
          return this.onStockChangeConfirm(); // コメントが必須。
        }

        var self = this;

        // 更新処理・結果表示処理
        // Show loading
        $.Vendor.WaitingDialog.show('削除中 ...');

        self.clearError();

        var data = {
            data_hash: self.dataHash
          , comment: stockChangeComment
        };

        $.ajax({
            type: "POST"
          , url: self.deleteAllUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {

              window.location.href = result.redirect; // リダイレクト

            } else {
              self.setError(result.message);

              // Hide loading
              $.Vendor.WaitingDialog.hide();
            }
          })
          .fail(function(stat) {
            console.log(stat);
            self.setError('エラー：削除に失敗しました。');

            // Hide loading
            $.Vendor.WaitingDialog.hide();
          })
          .always(function() {
          });
      }

      , onStockChangeConfirm: function() {

        if (!confirm('このロケーションの在庫を全て削除します。本当によろしいですか？')) {
          return;
        }

        // モーダルリセット
        this.stockChangeComment = null;
        this.commentErrorMessage = null;

        this.stockChangeConfirmMessage = 'コメントを入力してください。';
        $('#modalStockChangeConfirm').modal().show();
      }

      , onStockChangeSubmit: function() {

        var comment = $.Plusnao.String.trim(this.stockChangeComment ? this.stockChangeComment : '');
        if (comment.length == 0) {
          this.commentErrorMessage = 'ロケーションの削除はコメントが必須です。';
          return;
        }

        $('#modalStockChangeConfirm').modal().hide();

        return this.deleteAll(comment);
      }


    }
  });



});
