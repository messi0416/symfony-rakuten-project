/**
 * ピッキングリスト 共通JS
 */
$(function() {

  /**
   * ピッキングリスト一覧
   */
  if ($('#pickingListSearchForm').size() > 0) {
    var vmPickingListSearchForm = new Vue({
        el: '#pickingListSearchForm'
      , data: {
        showDeleteButtons: false
      }
      , computed: {
        deleteButtonCss: function() {
          return this.showDeleteButtons ? 'btn-danger' : 'btn-default'
        }
      }
      , methods: {
        search: function() {
          $(this.$el).submit();
        }
        , toggleDeleteButtons: function() {
          this.showDeleteButtons = ! (this.showDeleteButtons);
        }
        , marge: function(){
          if($('#pickingListMargeForm input:checked').length > 0){
            if (confirm('選択したピッキングリストを統合します、よろしいですか？')) {
              $('#pickingListMargeForm').submit();
            } else {
              return ;
            }
          } else {
            alert('ピッキングリストが選択されていません。');
          }
        }
      }
    });
    var vmPickingList = new Vue({
        el: '#pickingList'
      , data: {
        deleteUrl: null
      }
      , ready: function() {
        this.deleteUrl = $(this.$el).data('deleteUrl');
      }
      , computed: {
        showDeleteButtons: function() {
          return vmPickingListSearchForm.showDeleteButtons;
        }
      }
      , methods: {
        deleteList: function(date, number, event) {
          var self = this;

          event.preventDefault();

          if (!confirm("このピッキングリストを削除します。よろしいですか？")) {
            return;
          }

          if (date.length > 0 && number.length > 0) {

            // Show loading
            $.Vendor.WaitingDialog.show('処理中 ...');

            var data = {
                date: date
              , number: number
            };

            $.ajax({
                type: "POST"
              , url: self.deleteUrl
              , dataType: "json"
              , data: data
            })
              .done(function (result) {
                if (result.status == 'ok') {

                  vmGlobalMessage.setMessage(result.message, 'alert-success');
                  window.location.reload();

                } else {
                  vmGlobalMessage.setMessage(result.message, 'alert-danger');
                }
              })
              .fail(function (stat) {

                vmGlobalMessage.setMessage("ピッキング削除に失敗しました。（通信エラー）", 'alert-danger');

              })
              .always(function () {
                // Hide loading
                $.Vendor.WaitingDialog.hide();
              });

          }
        }
      }
    });
  }


  /**
   * ピッキング商品詳細画面
   */
  if ($('#pickingProductDetail').size() > 0) {

    // ピッキング商品詳細画面
    var vmPickingProductDetail = new Vue({
      el: '#pickingProductDetail'
      , data: {
          urlOk: null
        , urlNg: null
        , urlPass: null

        , dataHash: null

        , pickingComment: null
        , pickingCommentButton: null

        , showOtherWarehouseLocations: false
      }
      , ready: function () {
        var self = this;

        self.urlOk = $(this.$el).data('urlOk');
        self.urlNg = $(this.$el).data('urlNg');
        self.urlPass = $(this.$el).data('urlPass');
        self.dataHash = $(this.$el).data('hash');
      }
      , computed: {
        pickingCommentButtonCss: function() {
          var css = null;
          switch (this.pickingCommentButton) {
            case 'ok':
              css = 'btn-success';
              break;
            case 'ng':
              css = 'btn-warning';
              break;
            case 'pass':
              css = 'btn-danger';
              break;
          }
          return css;
        }
        , pickingCommentButtonText: function() {
          var text = null;
          switch (this.pickingCommentButton) {
            case 'ok':
              text = 'OK';
              break;
            case 'ng':
              text = 'ロケ違い確定';
              break;
            case 'pass':
              text = '在庫無し確定';
              break;
          }
          return text;
        }

      }
      , methods: {
        /**
         * OK / NG / PASS クリック時処理
         */
        onSubmit: function (button, comment) {
          var self = this;

          // Show loading
          $.Vendor.WaitingDialog.show('処理中 ...');

          var url;
          switch (button) {
            case 'ok':
              url = self.urlOk;
              break;
            case 'ng':
              url = self.urlNg;
              break;
            case 'pass':
              url = self.urlPass;
              break;
          }
          if (!url) {
            alert('エラーが発生しました。');
            return;
          }

          var data = {
              data_hash: self.dataHash
            , comment: comment
          };

          $.ajax({
              type: "POST"
            , url: url
            , dataType: "json"
            , data: data
          })
            .done(function (result) {
              if (result.status == 'ok') {

                if (result.button == 'ok') {
                  vmGlobalMessage.setMessage(result.message, 'alert-success');
                } else {
                  vmGlobalMessage.setMessage(result.message, 'alert-warning');
                }
                if (result.redirect) {
                  window.location.href = result.redirect;
                }

              } else {

                vmGlobalMessage.setMessage(result.message, 'alert-danger');
              }
            })
            .fail(function (stat) {

              vmGlobalMessage.setMessage("ピッキング処理に失敗しました。（通信エラー）", 'alert-danger');

            })
            .always(function () {
              // Hide loading
              $.Vendor.WaitingDialog.hide();
            });
        }

        , onNoGoodConfirm: function(button) {

          var self = this;

          // モーダルリセット
          self.pickingComment = null;
          self.pickingCommentButton = button;

          $('#modalPickingConfirm').modal().show();
        }

        , onNoGoodSubmit: function() {

          var comment = $.Plusnao.String.trim(this.pickingComment ? this.pickingComment : '');
          if (comment.length == 0) {
            alert("コメントを入力してください。");
            return;
          }

          var button = this.pickingCommentButton;

          // コメントモーダル リセット
          this.pickingComment = null;
          this.pickingCommentButton = null;

          $('#modalPickingConfirm').modal().hide();

          return this.onSubmit(button, comment);
        }

        , toggleShowOtherWarehouseLocations: function() {
          console.log('moge--');
          this.showOtherWarehouseLocations = ! (this.showOtherWarehouseLocations);
        }

      }
    });
  }



});
