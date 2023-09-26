/**
 * 商品管理画面用 JS
 */
$(function() {

  /**
   * ポップアップ Ajax バリデーションコールバック
   *
   * vmSelf 必須プロパティ
   *   $el
   *   message
   *   messageClass
   *   notices
   *   noticeHidden
   *
   * vmSelf 必須メソッド
   *   resetDialog()
   */
  function generateVerifyCallback(vmSelf, url, verifyData, callbacks)
  {
    return function() {
      vmSelf.resetDialog();

      $.ajax({
          type: "GET"
        , url: url
        , dataType: "json"
        , data: verifyData
      })
        .done(function(result) {

          if (result.valid) {
            vmSelf.message = result.message;
            vmSelf.messageClass = 'alert alert-success';

            if (result.notices.length > 0) {
              vmSelf.notices = result.notices;
              vmSelf.noticeHidden = false;
            }

            $('.modal-footer button.btn-primary', vmSelf.$el).show();

            // 成功コールバック
            if (callbacks && callbacks.success) {
              callbacks.success(result);
            }

          } else {
            vmSelf.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmSelf.messageClass = 'alert alert-danger';
            if (result.notices.length > 0) {
              vmSelf.notices = result.notices;
              vmSelf.noticeHidden = false;
            }
            $('.modal-footer button.btn-primary', vmSelf.$el).hide();

            // 失敗コールバック
            if (callbacks && callbacks.error) {
              callbacks.error(result);
            }
          }
        })
        .fail(function(stat) {
          vmSelf.message = 'エラーが発生しました。';
          vmSelf.messageClass = 'alert alert-danger';
          $('.modal-footer button.btn-primary', vmSelf.$el).hide();

          // 失敗コールバック
          if (callbacks && callbacks.error) {
            callbacks.error(stat);
          }
        })
        . always(function() {
          // 完了コールバック
          if (callbacks && callbacks.finally) {
            callbacks.finally();
          }
        });
    };
  }


  // 機能画面 受注明細差分更新処理 モーダル
  var vmDeliveryMethodConversionModal = new Vue({
    el: '#modalDeliveryMethodConversion',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
    },
    computed: {
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "delivery_method_conversion"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
            
            setTimeout("location.reload()",5000);
            
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

});
