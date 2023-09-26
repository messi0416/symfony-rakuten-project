/**
 * 商品ロケーション画面用 JS
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
        , warehouseId       : this.item.warehouseId
        , locationId        : this.item.locationId
        , locationCode      : this.item.locationCode
        , stock             : this.item.stock
        , position          : this.item.position
      };
    },
    computed: {
        isLocationChanged: function() {
        return this.locationCode !== this.item.locationCode;
      }
      , isStockChanged: function() {
        return this.stock !== this.item.stock;
      }
      , errorCss: function() {
        return this.item.hasError ? 'has-error' : '';
      }
      , locationCodeCss: function() {
        return this.isLocationChanged ? 'has-warning' : '';
      }
      , stockCss: function() {
        return this.isStockChanged ? 'has-warning' : '';
      }
    },
    ready: function() {
    },
    methods: {
        onInputFocus: function(event) {
        var input = event.target;
        input.select(0, input.value.length)
      }

      , onMoveUp: function() {
        vmProductLocationDetail.moveUp(this.item.position);
      }
      , onMoveDown: function() {
        vmProductLocationDetail.moveDown(this.item.position);
      }
      , onAddToUpper: function() {
        vmProductLocationDetail.addToUpper(this.item.position);
      }

      /**
       * ロケーション名が変更されたら、locationId をnullに更新。元に戻ればlocationIdも復帰
       * ※ロケーションが手入力の内の暫定実装
       */
      , onLocationChange: function() {

        this.item.locationCode = this.item.locationCode.trim();

        if (this.item.locationCode === this.locationCode) {
          this.item.locationId = this.locationId;
        } else {
          this.item.locationId = null;
        }
      }

      /**
       * 在庫数変更
       */
      , onStockChange: function() {}
    }
  });


  /**
   * 商品ブロック
   */
  var vmProductLocationDetail = new Vue({
      el: "#productLocationDetail"
    , data: {
        currentWarehouseId: null
      , choiceItem: {}
      , image: null
      , locations: []

      , updateUrl: null
      , dataHash: null
      , warehouseStockTotal: 0

      , stockChangeComment: null
      , stockChangeConfirmMessage: null
      , commentErrorMessage: null
    }
    , ready: function() {

      this.updateUrl = $(this.$el).data('updateUrl');

      this.$set('currentWarehouseId', CURRENT_WAREHOUSE_ID);
      this.$set('choiceItem', PRODUCT_LOCATION_DATA.choiceItem);
      this.$set('image'     , PRODUCT_LOCATION_DATA.image);
      this.$set('dataHash'  , PRODUCT_LOCATION_DATA.dataHash);
      this.$set('warehouseStockTotal', Number(PRODUCT_LOCATION_DATA.warehouseStockTotal));

      var locations = [];
      for (var i = 0; i < PRODUCT_LOCATION_DATA.locations.length; i++) {
        var item = PRODUCT_LOCATION_DATA.locations[i];
        locations.push({
            neSyohinSyohinCode: item.neSyohinSyohinCode.trim()
          , warehouseId       : Number(item.warehouseId)
          , locationId        : Number(item.locationId)
          , locationCode      : item.locationCode.trim()
          , stock             : Number(item.stock)
          , position          : Number(item.position)

          , hasError          : false
        });
      }
      this.$set('locations' , locations);
    }
    , computed: {
      inputStockTotal: function() {
          return this.locations.reduce(function(result, item) {
            return result + item.stock;
          }, 0);
      }
      , inputStockCss: function() {
        return   this.stockChanged
               ? 'text-danger bold bigger'
               : '';
      }
      , hasErrors: function() {
        return this.locations.reduce(function(result, item){
          return result || item.hasError;
        }, false);
      }
      , stockChanged: function() {
        return this.inputStockTotal != Number(this.warehouseStockTotal);
      }
      , stockDecreased: function() {
        return this.inputStockTotal < Number(this.warehouseStockTotal);
      }
      , commentChoiceOptions: function() {
        var ret = [];
        if (this.stockChanged) {
          ret = this.stockDecreased
              ? [ // 減少
                  '数量不足(国内倉庫)'
                , '不良品のため(国内倉庫)'
                , '数量不足 DHL No.'
                , '数量不足 OCS No.'
                , '数量不足 その他'
                , 'シール貼間違え DHL No.'
                , 'シール貼間違え OCS No.'
                , 'シール貼間違え その他'
                , '不良品 DHL No.'
                , '不良品 OCS No.'
                , '不良品 その他'
              ]
              : [ // 増加
                  '出荷確定戻し'
                , '数量過剰＆発見(国内倉庫)'
                , '数量過剰 DHL No.'
                , '数量過剰 OCS No.'
                , '数量過剰 その他'
                , 'シール貼間違え DHL No.'
                , 'シール貼間違え OCS No.'
                , 'シール貼間違え その他'
                , '撮影商品戻し'
              ];
        }
        return ret;
      }

    }
    , methods: {

      /// 上下入れ替え（上）
      moveUp: function(position) {
        var current = this.findLocationByPosition(position);
        var next = this.findNextLocationByPosition(position, 'prev');

        if (current !== null && next !== null) {
          var swapPosition = current.position;
          current.position = next.position;
          next.position = swapPosition;
          this.refreshPositionNumbers();
        }
      }
      /// 上下入れ替え（下）
      , moveDown: function(position) {
        var current = this.findLocationByPosition(position);
        var next = this.findNextLocationByPosition(position, 'next');

        if (current !== null && next !== null) {
          var swapPosition = current.position;
          current.position = next.position;
          next.position = swapPosition;
          this.refreshPositionNumbers();
        }
      }
      /// ひとつ前に統合
      , addToUpper: function(position) {
        var current = this.findLocationByPosition(position);
        var next = this.findNextLocationByPosition(position, 'prev');
        if (current !== null && next !== null) {

          // 同じ倉庫で無ければエラー。※念のための実装
          if (current.warehouseId != next.warehouseId) {
            alert('別倉庫のロケーションは合算できません。');
            return;
          }

          next.stock += current.stock;
          current.stock = 0;

          // current を削除 ※誤操作の場合は「リセット」してね実装
          // ※普通に$removeするとjQuery(+BootStrap?)でエラーになるため回避のsetTimeout()
          //   @see: https://github.com/aurelia/templating/issues/20
          var self = this;
          setTimeout(function() {
            self.locations.$remove(current);
            self.refreshPositionNumbers();
          }, 0);
        }

      }

      , findLocationByPosition: function(position) {
        var location = null;
        for (var i = 0; i < this.locations.length; i++) {
          if (this.locations[i].position == position) {
            location = this.locations[i];
            break;
          }
        }
        return location;
      }
      , findNextLocationByPosition: function(position, direction) {
        var location = null;
        var sortOrder = null;
        if (direction == 'prev') {
          sortOrder = -1;
        } else if (direction == 'next') {
          sortOrder = 1;
        } else {
          return null;
        }

        var list = this.$options.filters.orderBy(this.locations, 'position', sortOrder);
        for (var i = 0; i < list.length; i++) {
          if (
               (direction == 'prev' && list[i].position < position)
            || (direction == 'next' && list[i].position > position)
          ) {
            location = list[i];
            break;
          }
        }
        return location;
      }

      /// ロケーション追加
      , addLocation: function() {

        var maxPosition = 0;
        for (var i = 0; i < this.locations.length; i++) {
          if (this.locations[i].position > maxPosition) {
            maxPosition = this.locations[i].position;
          }
        }

        var location = {
            neSyohinSyohinCode: this.choiceItem.neSyohinSyohinCode
          , warehouseId       : this.currentWarehouseId
          , locationId        : null
          , locationCode      : ''
          , stock             : 0
          , position          : maxPosition + 1

          , hasError          : false
        };

        this.locations.push(location);
        this.refreshPositionNumbers();
      }

      /// position 振り直し （※ガチャガチャにならないよう、念のため毎回振り直す）
      , refreshPositionNumbers: function() {
        var position = 0;
        var list = this.$options.filters.orderBy(this.locations, 'position');
        for (var i = 0; i < list.length; i++) {
          list[i].position = position++;
        }
      }

      /// エラーリセット
      , resetErrors: function() {
        for (var i = 0; i < this.locations.length; i++) {
          this.locations[i].hasError = false;
        }
        vmGlobalMessage.clear();
      }

      /// 確定前エラーチェック処理
      , validateData: function() {

        var message = "";

        // エラーを一度全てリセット
        this.resetErrors();

        var i, j;
        for (i = 0 ; i < this.locations.length; i++) {
          var location = this.locations[i];

          // 空のロケーション
          if (location.locationCode == "" && location.stock != 0) {
            location.hasError = true;
            if (message.length == 0) {
              message = 'ロケーションコードが未入力の在庫があります。';
            }
            continue;
          }

          // 在庫数 0
          // 当初は許可して自動削除していたが、入力ミスで消えることが多いためにエラー化。
          // ただ、全て 0 にすることは許可
          if (location.stock == 0) {
            // ロケーションが1件のみであればOK。（全削除）
            // ロケーションが2件以上あればエラー。
            if (this.locations.length > 1) {
              location.hasError = true;
              if (message.length == 0) {
                message = '在庫が 0 のロケーションがあります。';
              }
              continue;
            }
          }

          // ロケーション重複チェック
          for (j = 0; j < this.locations.length; j++) {
            if (i == j) {
              continue;
            }

            if (location.locationCode.length > 0 && location.locationCode == this.locations[j].locationCode) {
              location.hasError = true;
              if (message.length == 0) {
                message = 'ロケーションコードの重複があります。';
              }
              break;
            }
          }
        }

        if (message.length > 0) {
          vmGlobalMessage.setMessage(message, 'alert-danger', false);
        }
      }

      /// 確定処理
      , submitForm: function(stockChangeComment) {

        var self = this;

        // 入力チェック
        this.validateData();

        if (this.hasErrors) {
          return;
        }

        // 在庫数チェック（確認）
        if (! stockChangeComment && ! confirm("商品ロケーション情報を更新します。\n\nよろしいですか？")) {
          return;
        }

        if (!stockChangeComment && self.stockChanged) {
          return self.onStockChangeConfirm();
        }



        // 更新処理・結果表示処理
        // Show loading
        $.Vendor.WaitingDialog.show('更新中 ...');

        vmGlobalMessage.clear();
        vmGlobalMessage.clearFlashMessage();

        // コメントモーダル リセット
        self.stockChangeComment = null;
        self.stockChangeConfirmMessage = null;
        self.commentErrorMessage = null;

        var data = {
            locations: self.locations
          , data_hash: self.dataHash
          , comment: stockChangeComment
        };

        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              var item = result.result;

              window.location.href = result.redirect; // リダイレクト

            } else {
              vmGlobalMessage.setMessage(result.message, 'alert-danger');
              // Hide loading
              $.Vendor.WaitingDialog.hide();
            }
          })
          .fail(function(stat) {
            console.log(stat);
            vmGlobalMessage.setMessage('エラー：更新に失敗しました。', 'alert-danger');
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          })
          .always(function() {
          });

      }

      , onStockChangeConfirm: function() {

        // モーダルリセット
        this.stockChangeComment = null;
        this.commentErrorMessage = null;

        this.stockChangeConfirmMessage = this.stockDecreased
                                       ? '在庫数が減っています。コメントを入力してください。'
                                       : '在庫数が増えています。コメントを入力してください';

        $('#modalStockChangeConfirm').modal().show();
      }

      , onStockChangeSubmit: function() {

        var comment = $.Plusnao.String.trim(this.stockChangeComment ? this.stockChangeComment : '');
        if (comment.length == 0) {
          this.commentErrorMessage = '合計在庫数が変更されているため、コメントが必須です。';
          return;
        }

        $('#modalStockChangeConfirm').modal().hide();

        return this.submitForm(comment);
      }


    }
  });

  /**
   * 別倉庫在庫
   */
  var vmOtherWarehouseLocations = new Vue({
      el: '#otherWarehouseLocations'
    , data: {
      showList: false
    }
    , methods: {
      toggleShow: function() {
        this.showList = this.showList ? false : true;
      }
    }
  });


});
