/**
 * 出荷リスト一覧用 JS
 */

// FBA納品ラベルCSV出力 アップロードモーダル
const vmUploadFbaLabelCsvModal = new Vue({
  el: '#uploadFbaLabelCsvModal'
  , data: {
      uploadUrl: null
    , result: null
  }

  , computed: {
    downloadHref: function() {
      if (!this.result) {
        return null;
      }

      const blob = $.Plusnao.Binary.toBlob(this.result.data, 'text/csv')
      return window.URL.createObjectURL(blob);
    }
  }

  , mounted: function() {
    const self = this;
    this.$nextTick(function () {

      self.uploadUrl = $(self.$el).data('uploadUrl');

      // アップロードフォーム
      $('#stockTransportFbaLabelCsvUpload').fileinput({
          uploadUrl: self.uploadUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false

        , fileActionSettings: {
            showZoom: false
          , showUpload: false
        }
        // , allowedFileTypes: ['csv', 'text']
        , allowedFileExtensions: ['csv', 'txt', 'tsv']
      })
        .on('fileloaded', function() {
          self.result = null;
        })

        .on('filebatchuploadsuccess', function (event, data, previewId, index) {

          if (data.response && data.response.csv) {
            self.result = data.response.csv;
          } else {
            self.result = null;
          }

          $('#stockTransportFbaLabelCsvUpload').fileinput('clear');
        })
      ;
    });

  }
  , methods: {
  }

});

// SHOPLISTスピード便移動伝票作成モーダル
const vmUploadShoplistSpeedBinCsvModal = new Vue({
  el: "#uploadShoplistSpeedBinCsvModal",
  data: {
    messageState: new PartsGlobalMessageState(),
    uploadUrl: null,
    formData: null,
    // nullだと、PHPに渡した時json_decode()しないと文字列の'null'になるので、空文字のほうが良さそう
    departureDate: "",
    arrivalDate: "",
    shippingMethod: "",
    transportNumber: "",
  },

  computed: {},

  mounted: function () {
    const self = this;
    this.$nextTick(function () {
      self.uploadUrl = $(self.$el).data("uploadUrl");

      $('#uploadShoplistSpeedBinCsvModal').on('shown.bs.modal', function () {
        self.messageState.clear();
        $('.modal-footer button.btn-primary', self.$el).show();
      })

      $("#departureDate", this.$el)
        .datepicker({
          language: "ja",
          format: "yyyy-mm-dd",
          autoclose: true,
        })
        .on({
          changeDate: function () {
            self.departureDate = $(this).val();
          },
          clearDate: function () {
            self.departureDate = null;
          },
        });

      $("#arrivalDate", this.$el)
        .datepicker({
          language: "ja",
          format: "yyyy-mm-dd",
          autoclose: true,
        })
        .on({
          changeDate: function () {
            self.arrivalDate = $(this).val();
          },
          clearDate: function () {
            self.arrivalDate = null;
          },
        });
    });
  },

  methods: {
    changeCsvFile: function () {
      this.formData = new FormData();
      const files = this.$refs.fileInput.files;
      if (files.length > 1) {
        const message = 'ファイルを1つだけ選択してください。';
        this.messageState.setMessage(message, "alert alert-danger");
        return;
      }
      this.formData.append('reservefile', this.$refs.fileInput.files[0]);
    },

    submit: function () {
      const self = this;
      self.messageState.clear();
      if (self.formData === null || !self.formData.has('reservefile')){
        const message = "CSVファイルが選択されていません";
        self.messageState.setMessage(message, "alert alert-danger");
        return;
      }
      self.formData.append('departureDate', self.departureDate);
      self.formData.append('arrivalDate', self.arrivalDate);
      self.formData.append('shippingMethod', self.shippingMethod);
      self.formData.append('transportNumber', self.transportNumber);
      $.ajax({
        type: "POST",
        url: self.uploadUrl,
        dataType: "json",
        processData: false,
        contentType: false,
        data: self.formData,
      })
      .done(function (result) {
        if (result.status === "ok") {
          self.messageState.setMessage(result.message, "alert alert-success");
        } else {
          self.messageState.setMessage(result.message, "alert alert-danger");
        }
      })
      .fail(function (a) {
        console.log(a);
        self.messageState.setMessage(
          "SHOPLISTスピード便移動伝票作成ができませんでした。",
          "alert alert-danger"
        );
      })
      .always(() => {
        $('.modal-footer button.btn-primary', self.$el).hide();
      });
    },
  },
});

const vmLogisticsStockTransportList = new Vue({
  el: '#logisticsStockTransportList'
  , data: {
      messageState: new PartsGlobalMessageState()

    , urlCreateFbaList: null
    , urlCreateMainWarehouseList: null
    , urlDownloadShoplistLabelCsv: null
  }
  , mounted: function() {
    this.$nextTick(function () {

      this.urlCreateFbaList = $(this.$el).data('urlCreateFbaList');
      this.urlCreateMainWarehouseList = $(this.$el).data('urlCreateMainWarehouseList');
      this.urlCreatePickingList = $(this.$el).data('urlCreatePickingList');
      this.urlDownloadShoplistLabelCsv = $(this.$el).data('urlDownloadShoplistLabelCsv');

      const dateOptions = {
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      };

      // $('#dateFrom').datepicker(dateOptions);
      // $('#dateTo').datepicker(dateOptions);
    });
  }
  , methods: {
    submitSearchForm: function() {
      $('#logisticsStockTransportListSearchForm', this.$el).submit();
    }

    , createFbaList: function() {
      const self = this;

      if (!confirm('FBA納品用の移動伝票を自動作成します。よろしいですか？')) {
        return;
      }

      const data = {
      };

      $.ajax({
          type: "POST"
        , url: self.urlCreateFbaList
        , dataType: "json"
        , data: data
      }).done(function(result) {

        if (result.status === 'ok') {
          self.messageState.setMessage(result.message, 'alert alert-success');
        } else {
          self.messageState.setMessage(result.message, 'alert alert-danger');
        }

      }).fail(function() {
        self.messageState.setMessage('FBA納品用の移動伝票の作成ができませんでした。', 'alert alert-danger');

      }).always(function() {
      });
    }

    , createMainWarehouseList: function() {
      const self = this;

      if (!confirm('南京終倉庫への移動伝票を自動作成します。よろしいですか？')) {
        return;
      }

      const data = {
      };

      // Show loading
      $.Vendor.WaitingDialog.show();

      $.ajax({
          type: "POST"
        , url: self.urlCreateMainWarehouseList
        , dataType: "json"
        , data: data
      }).done(function(result) {

        if (result.status === 'ok') {
          self.messageState.setMessage(result.message, 'alert alert-success');
          if (result.redirect) {
            window.location.href = result.redirect;
          }

        } else {
          self.messageState.setMessage(result.message, 'alert alert-danger');
        }

      }).fail(function() {
        self.messageState.setMessage('南京終倉庫への移動伝票の作成ができませんでした。', 'alert alert-danger');

      }).always(function() {
        $.Vendor.WaitingDialog.hide();
      });
    }
    /**
     * Shoplist用のラベルCSVをダウンロードする。
     */
    , getShoplistLabelCsvDownloadUrl: function(id) {
      return this.urlDownloadShoplistLabelCsv + "?id=" + id + "&type=labelFromTransport"
    }

  }
});
