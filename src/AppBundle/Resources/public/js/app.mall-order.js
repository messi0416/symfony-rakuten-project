/**
 * モール受注CSV 画面
 */
$(function() {
// 変換済み一覧テーブル
  var vmMallOrderList = new Vue({
    el: '#mallOrderList'
    , data: {
        convertUrl: null
      , neUploadUrlBase: null
      , convertEcCubeUrl: null
      , result: null

      , messageState: new PartsGlobalMessageState()
    }
    , mounted: function() {
      this.$nextTick(function () {
        var self = this;
        self.convertUrl = $(self.$el).data('convertUrl');
        self.neUploadUrlBase = $(self.$el).data('neUploadUrlBase');
        self.convertEcCubeUrl = $(self.$el).data('convertEcCubeUrl');
      });
    }
    , methods: {
      enqueueConvert: function (shopCode) {
        var self = this;

        if (!confirm("このモールの未処理データの変換を実行してもよいですか？\n\n※受注明細が存在しなくとも変換を実行します。")) {
          return;
        }

        var data = {
          shop_code: shopCode
          , force: 1 // 強制変換
        };

        $.ajax({
          type: "POST"
          , url: self.convertUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.status == 'ng') {
              self.messageState.setMessage(result.message, 'alert-danger');

            } else {
              self.messageState.setMessage(result.message, 'alert-success');

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function (stat) {
            self.messageState.setMessage.setMessage('処理を開始できませんでした。', 'alert-danger');
          })
          .always(function () {
            self.noticeHidden = true;
          });
      }

      , neUpload: function(shopCode, converted) {
        var self = this;

        var message = "受注データをNextEngineにアップロードします。よろしいですか？"
        if (!confirm(message)) {
          return;
        }

        var data = {
          shop_code: shopCode
          , converted: converted
        };

        var url = self.neUploadUrlBase;
        url = url.replace(/___SHOP_CODE___/g, shopCode);
        url = url.replace(/___CONVERTED___/g, converted);

        $.ajax({
          type: "POST"
          , url: url
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.status == 'ng') {
              self.messageState.setMessage(result.message, 'alert-danger');

            } else {
              self.messageState.setMessage(result.message, 'alert-success');
            }
          })
          .fail(function (stat) {
            self.messageState.setMessage.setMessage('処理を開始できませんでした。', 'alert-danger');
          })
          .always(function () {
            self.noticeHidden = true;
          });
      }

      /**
       * 受注番号 ポップアップ
       */
      , openOrderNumbersModal: function (event) {
        var target = event.target;
        vmMallOrderNumbersModal.open($(target).data('noSalesDetailVoucherNumber'));
      }

      /**
       * EC-CUBE 受注変換
       */
      , convertEcCubeOrder: function() {
        var self = this;

        if (!confirm("EC-CUBE 2サイト(club-plusnao.jp, club-forest.shop) の受注を取り込みます。よろしいですか？")) {
          return;
        }

        var data = {};

        $.ajax({
            type: "POST"
          , url: self.convertEcCubeUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.status == 'ng') {
              self.messageState.setMessage(result.message, 'alert-danger');

            } else {
              self.messageState.setMessage(result.message, 'alert-success');
            }
          })
          .fail(function (stat) {
            self.messageState.setMessage.setMessage('処理を実行できませんでした。', 'alert-danger');
          })
          .always(function () {
            self.noticeHidden = true;
          });
      }
    }
  });

// 受注CSVアップロードモーダル
  var vmUploadCsvModal = new Vue({
    el: '#modalUploadCsv'
    , data: {
      uploadUrl: null
      , result: null
    }
    , mounted: function() {
      var self = this;
      this.$nextTick(function () {

        self.uploadUrl = $(self.$el).data('uploadUrl');

        // アップロードフォーム
        $('#mallOrderCsvUpload').fileinput({
          uploadUrl: self.uploadUrl
          , language: 'ja'
          , showPreview: true
          , uploadAsync: false

          , fileActionSettings: {
            showZoom: false
            , showUpload: false
          }
          // , allowedFileTypes: ['csv', 'text']
          , allowedFileExtensions: ['csv', 'txt']
        })

          .on('filebatchuploadsuccess', function (event, data, previewId, index) {

            if (data.response && data.response.info) {
              self.result = data.response.info;
            } else {
              self.result = null;
            }

            $('#mallOrderCsvUpload').fileinput('clear');
          })
        ;
      });

    }
    , methods: {
    }

  });


// Q10配送処理CSV出力 アップロードモーダル
  var vmUploadQ10DeliveryCsvModal = new Vue({
    el: '#modalQ10DeliveryCsv'
    , data: {
      uploadUrl: null
      , result: null
    }

    , computed: {
      downloadHrefDelivery: function() {
        if (!this.result || !this.result.delivery) {
          return null;
        }

        var bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
        var blob = new Blob([ bom, this.result.delivery.data ], { "type" : "text/csv" });
        return window.URL.createObjectURL(blob);
      }
      , downloadHrefTracking: function() {
        if (!this.result || !this.result.tracking) {
          return null;
        }

        var bom = new Uint8Array([0xEF, 0xBB, 0xBF]);
        var blob = new Blob([ bom, this.result.tracking.data ], { "type" : "text/csv" });
        return window.URL.createObjectURL(blob);
      }
    }

    , mounted: function() {
      var self = this;
      this.$nextTick(function () {

        self.uploadUrl = $(self.$el).data('uploadUrl');

        // アップロードフォーム
        $('#mallOrderCsvQ10DeliveryUpload').fileinput({
          uploadUrl: self.uploadUrl
          , language: 'ja'
          , showPreview: true
          , uploadAsync: false

          , fileActionSettings: {
            showZoom: false
            , showUpload: false
          }
          // , allowedFileTypes: ['csv', 'text']
          , allowedFileExtensions: ['csv', 'txt']
        })

          .on('filebatchuploadsuccess', function (event, data, previewId, index) {

            if (data.response && data.response.csv) {
              self.result = data.response.csv;
            } else {
              self.result = null;
            }

            $('#mallOrderCsvQ10DeliveryUpload').fileinput('clear');
          })
        ;
      });

    }
    , methods: {
    }

  });

// 店舗受注番号 モーダル
  var vmMallOrderNumbersModal = new Vue({
    el: '#modalOrderNumbers'
    , data: {
      numbers: ''
    }

    , computed: {
    }

    , mounted: function() {
      var self = this;
      this.$nextTick(function () {
      });
    }
    , methods: {
      open: function(data) {
        this.numbers = data && data.length > 0 ? data.split(",").join("\n") : '';
        $(this.$el).modal('show');
      }

    }

  });

});
