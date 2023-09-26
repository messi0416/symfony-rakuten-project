/**
 * 配送ラベル再発行伝票一覧 JS
 */

/** メイン画面 */
const shippingInquiryNumberUsedCsvList = new Vue({
  el: "#shippingInquiryNumberUsedCsvList",
  data: {
    deliveryMethods: DELIVERY_METHODS,
    conditions: {
      targetFrom: null,
      targetTo: null,
      voucherNumber: null,
    },
    searchUrl: null,
    filterUrl: null,
    generateUrl: null,
    downloadUrl: null,
    messageState: {},

    reportList: [],
    filterResults: {},
    mismatchedDeliveryList: [],

    deliveryCsvName: {
      30: "yuupack",
      34: "yuupackrsl",
      35: "yuupacket",
    }
  },
  computed: {},
  mounted: function () {
    // URL取得
    this.searchUrl = $(this.$el).data("searchUrl");
    this.filterUrl = $(this.$el).data("filterUrl");
    this.generateUrl = $(this.$el).data("generateUrl");
    this.downloadUrl = $(this.$el).data("downloadUrl");

    this.messageState = new PartsGlobalMessageState();

    const self = this;
    const datetimepickerOptions = {
      locale: "ja",
      format: "YYYY-MM-DD HH:mm:ss",
    };
    $("#targetFrom", this.$el)
      .datetimepicker(datetimepickerOptions)
      .on("dp.change", function () {
        self.conditions.targetFrom = $(this).val();
      })
      .on("dp.clearDate", function () {
        self.conditions.targetFrom = null;
      });
    $("#targetTo", this.$el)
      .datetimepicker(datetimepickerOptions)
      .on("dp.change", function () {
        self.conditions.targetTo = $(this).val();
      })
      .on("dp.clearDate", function () {
        self.conditions.targetTo = null;
      });

    this.search();
  },
  methods: {
    search: function () {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      self.reportList = [];
      self.messageState.clear();
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.reportList = result.reportList;
            self.formatReportList();
          } else {
            self.messageState.setMessage(result.message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage(
            "検索時にエラーが発生しました。",
            "alert alert-danger"
          );
        })
        .always(function () {
          self.filter(true);
        });
    },
    formatReportList: function () {
      this.reportList = this.reportList.map((report) => {
        report.trackingNumberCount = Number(report.trackingNumberCount);
        report.downloadCountEdi = Number(report.downloadCountEdi);
        report.downloadCountNe = Number(report.downloadCountNe);
        report.trackingNumberCountStr =
          report.trackingNumberCount.toLocaleString();
        report.downloadCountEdiStr = report.downloadCountEdi.toLocaleString();
        report.downloadCountNeStr = report.downloadCountNe.toLocaleString();

        const method = this.deliveryCsvName[report.deliveryId];
        const createdYmdHis = report.created
          .replace(/[-:]/g, "")
          .replace(/\s+/g, "");
        report.csvNameEdi = `webedi_report_${method}_${createdYmdHis}.csv`;
        report.csvNameNe = `ne_report_${method}_${createdYmdHis}.csv`;
        return report;
      });
    },
    filter: function (isFirstProcess = false) {
      if (!isFirstProcess) {
        $.Vendor.WaitingDialog.show("loading ...");
      }
      const self = this;
      self.filterResults = {};
      self.messageState.clear();
      $.ajax({
        type: "POST",
        url: self.filterUrl,
        dataType: "json",
        data: {
          conditions: self.conditions,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.filterResults = result.filterResults;
            self.mismatchedDeliveryList = result.mismatchedDeliveryList;
          } else {
            self.messageState.setMessage(result.message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage(
            "エラーが発生しました。",
            "alert alert-danger"
          );
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    },
    generate: function (deliveryId) {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      self.messageState.clear();
      $.ajax({
        type: "POST",
        url: self.generateUrl,
        dataType: "json",
        data: {
          deliveryId,
          trackingNumbers: self.filterResults[deliveryId],
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.search();
            self.filter();
          } else {
            self.messageState.setMessage(result.message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage(
            "生成時にエラーが発生しました。",
            "alert alert-danger"
          );
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    },
    download: function (id, type, csvName) {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      self.messageState.clear();
      $.ajax({
        type: "POST",
        url: self.downloadUrl,
        dataType: "json",
        data: { id, type },
      })
        .done(function (result) {
          if (result.status === "ok") {
            // Base64エンコードされたCSVデータとファイル名を取得
            const csvData = result.csvData;
            // Base64データをBlobに変換
            const csvBlob = self.base64ToBlob(csvData, "text/csv");
            // ダウンロードリンクを作成し、クリックイベントを発火させる
            const link = document.createElement("a");
            link.href = URL.createObjectURL(csvBlob);
            link.download = csvName;
            link.click();

            self.search();
          } else {
            self.messageState.setMessage(result.message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage(
            "CSVダウンロード時にエラーが発生しました。",
            "alert alert-danger"
          );
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    },
    // Base64データをBlobに変換するメソッド
    base64ToBlob: function (base64, mimeType) {
      const binary = atob(base64.replace(/\s/g, ""));
      const len = binary.length;
      const buffer = new ArrayBuffer(len);
      const view = new Uint8Array(buffer);

      for (let i = 0; i < len; i++) {
        view[i] = binary.charCodeAt(i);
      }

      return new Blob([view], { type: mimeType });
    },
    /**
     * モーダル（出荷破棄伝票報告）表示
     */
    openCancelReportModal: function () {
      vmCancelReport.open();
    },
    /**
     * モーダル（発送方法差異）表示
     */
    openMismatchedModal: function (deliveryId) {
      const list = {
        mismatches: this.mismatchedDeliveryList[deliveryId],
        deliveryName: this.deliveryMethods[deliveryId],
      };
      vmMisMatched.open(list);
    },
  },
});

/**
 * モーダル（出荷破棄伝票報告）
 */
const vmCancelReport = new Vue({
  el: "#modal-cancel-report",
  data: {
    cancelReportUrl: "",
    messageClass: "",
    message: "",
    voucherNumbersStr: "",
  },
  mounted: function () {
    this.cancelReportUrl = $(this.$el).data("cancelReportUrl");
  },
  methods: {
    open: function () {
      this.resetMessage();
      $(this.$el). modal("show");
    },
    resetMessage: function () {
      this.messageClass = "";
      this.message = "";
    },
    report: function() {
      if (!confirm(`出荷破棄報告すると、使用済み報告CSV生成対象には戻せません。
よろしいですか。`)) {
        return;
      }

      const self = this;
      self.resetMessage();

      let voucherNumbers = self.voucherNumbersStr.split("\n")
      voucherNumbers = voucherNumbers.map(voucherNumber => voucherNumber.trim());
      self.voucherNumbersStr = voucherNumbers.join("\n");
      
      if (voucherNumbers.some(voucherNumber => isNaN(Number(voucherNumber)))) {
        self.message = "伝票番号に、数値でないものが含まれています。";
        self.messageClass = "alert alert-danger";
        return;
      };

      $.ajax({
        type: "POST",
        url: self.cancelReportUrl,
        dataType: "json",
        data: { voucherNumbers },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.message = "出荷破棄登録しました。";
            self.messageClass = "alert alert-success";
            self.voucherNumbersStr = "";
            shippingInquiryNumberUsedCsvList.filter();
          } else {
            self.message = result.message;
            self.messageClass = "alert alert-danger";
          }
        })
        .fail(function () {
          self.message = result.message;
          self.messageClass = "出荷破棄登録中にエラーが発生しました。";
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    }
  },
});

/**
 * モーダル（発送方法差異）
 */
const vmMisMatched = new Vue({
  el: "#modal-mismatched-list",
  data: {
    caption: "発送方法不一致詳細",
    message1: "",
    message2: "",
    messageClass: "alert alert-warning",
    mismatches: {},
    deliveryName: null,
  },
  methods: {
    open: function (list) {
      this.resetDialog();
      this.mismatches = list.mismatches;
      this.deliveryName = list.deliveryName;
      this.message1 = `次の伝票番号は、NextEngineで配送方法が「${this.deliveryName}」ではない可能性があります。`;
      this.message2 = `※変更後、差分更新が行われていないだけかもしれませんが、念のためご確認ください。`;
      $(this.$el).modal("show");
    },
    resetDialog: function () {
      this.mismatches = {};
      this.deliveryName = null;
      this.message1 = "";
      this.message2 = "";
    },
  },
});
