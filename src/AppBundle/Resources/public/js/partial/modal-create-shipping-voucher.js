$(function() {

// 機能画面 納品書CSV取込処理 モーダル
  var vmImportShippingVoucherModal = new Vue({
    el: '#modalImportShippingVoucher',
    data: {
        caption: '納品書取込 CSVアップロード'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , postUrl: null
      , deleteUrl: null
      , verifyUrl: null

      , nowLoading: false
    },
    ready: function() {
      var self = this;
      self.postUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        var data = {
            "queue": "main"
          , "command": "import_shipping_voucher"
        };

        $.ajax({
          type: "GET"
          , url: self.verifyUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {

            if (result.valid) {
              self.message = result.message.trim();
              self.messageClass = 'alert alert-success';

              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }

              $('.modal-footer button.btn-primary', self.$el).show();
              $('.modal-footer button.btn-warning', self.$el).show();

            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }
              $('.modal-footer button.btn-primary', self.$el).hide();
              $('.modal-footer button.btn-warning', self.$el).hide();
            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();
            $('.modal-footer button.btn-warning', self.$el).hide();
          })
          . always(function() {
          });
      });
    },

    methods: {
      onSubmit: function() {
        var self = this;

        var $input = $(self.$el).find('input[type="file"]');
        var files = $input.get(0).files;
        if (!files.length) {
          this.notices = ['アップロードするファイルが選択されていません。'];
          this.noticeHidden = false;
          return;
        }

        var file = files[0];
        if (!file.name.match(/\.csv$/)) {
          this.notices = ['ファイルの拡張子が .csv ではありません。'];
          this.noticeHidden = false;
          return;
        }

        var formData = new FormData();
        formData.append($input.attr('name'), file);

        this.resetDialog();

        this.nowLoading = true;
        this.caption = "アップロード中 ...";

        $.ajax({
          type: 'POST',
          timeout: 30000,
          url: self.postUrl,
          dataType: 'json',
          processData: false,
          contentType: false,
          data: formData
        }).done(function(result, textStatus, jqXHR) {

          if (result.status == 'ok') {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';
            $input.val(null);

          } else {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-danger';
          }

          if (result.warning) {
            self.notices.push(result.warning);
          }

          return false;
        }).fail(function(jqXHR, textStatus, errorThrown) {
          if (jqXHR.responseText) {

            self.$data.message = '処理を実行できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          }
          return false;
        }).always(function() {
          $('.modal-footer button.btn-primary', self.$el).show();
          $('.modal-footer button.btn-warning', self.$el).show();
          self.noticeHidden = (self.notices.length == 0);
          self.nowLoading = false;
          self.caption = "納品書CSV取込 CSVアップロード";
        });
      },

      resetDialog: function() {
        this.caption = "納品書CSV取込 CSVアップロード";
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
        $('.modal-footer button.btn-warning', self.$el).hide();

        this.nowLoading = false;
      }
    }
  });


});
