/**
 * Yahoo代理店 商品一覧画面JS
 */
$(function() {

  // ページ表示時処理

  //// Yahoo API利用可否判定＆認証ダイアログ表示
  // → 必須ではないため、自動表示はなし
  //if (typeof isApiEnabled != 'undefined' && isApiEnabled !== true) {
  //  $('#modalYahooAuth').modal().show();
  //}


  var vmSearchForm = new Vue({
      el: '#searchForm'
    , data: {
    }
    , methods: {
      clearSearchConditions: function() {
        $('#searchProductCode', this.$el).val("");
        $('#searchUpdateFlg', this.$el).val("");
        $('#searchRegistrationFlg', this.$el).val("");
      }
    }
  });

  var vmProductListTable = new Vue({
    el: '#productListTable'
    , data: {
        checkActionTarget: "update_flg"
      , checkActionValue: "-1"

      , checkActionUrl: null
    }
    , ready: function() {
      this.checkActionUrl = $(this.$el).data('checkActionUrl');
    }
    , methods: {
      /// 一括フラグ更新 チェック切り替え
      toggleCheckBoxes: function(event) {

        // console.log($(event.target).prop('checked'));
        var prop = $(event.target).prop('checked');
        $('input.checkTarget', this.$el).prop('checked', prop);
      }

      /// 一括フラグ更新処理
      , doCheckAction: function() {

        if (!this.checkActionTarget || this.checkActionValue.length == 0) {
          alert('一括処理が正しく選択されていません。');
          return;
        }

        var checkTargets = [];
        $('input.checkTarget', this.$el).each(function(i, check) {
          if ($(check).prop('checked')) {
            checkTargets.push($(check).val());
          }
        });

        // console.log(checkTargets);
        if (checkTargets.length == 0) {
          alert('処理対象の商品が選択されていません。');
          return;
        }

        var message = '';
        if (this.checkActionTarget == 'update_flg') {
          message = '下記商品の同期設定を';
        } else {
          message = '下記商品の出品設定を';
        }
        if (this.checkActionValue == '-1') {
          message += '「ON」に変更します。';
        } else {
          message += '「OFF」に変更します。';
        }
        message += 'よろしいですか？';

        if (! confirm(message)) {
          console.log('キャンセルされました。');
          return;
        }

        // 処理実行
        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            targets: checkTargets
          , action: this.checkActionTarget
          , value: this.checkActionValue
        };

        $.ajax({
            type: "POST"
          , url: self.checkActionUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            var message;
            if (result.status == 'ok') {

              // 成功したらリロード
              window.location.reload();

            } else {
              message = result.message;
              alert(message);
            }
          })
          .fail(function(stat) {
            var message = '更新中、エラーが発生しました。';
            alert(message);
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });
      }
    }
  });



});
