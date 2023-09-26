/**
 * 楽天ExpressCSVファイル変換画面 JS
 */

$(function () {
  Vue.component("list-body", {
    template : "#list-body",
    props : [
      "row"
    ],
    data: function () {
      return {
        conversionDate : this.row.conversionDate,
        convertedCSVName : this.row.convertedCSVName,
        downloadCount : this.row.downloadCount,
        errorVoucherNumbers : this.row.errorVoucherNumbers
      }
    },
    computed: {
      convertedCSVPath : function () {
        // JSだとダウンロードファイルをS-JISにエンコードするのが上手くいかないためPHPで処理
        return BASE_URL + "/logistics/rakuten_express/download"
        + "?base=" +  this.row.convertedCSVName
        + "&date=" + this.row.conversionDate;
      }
    },
    methods: {
      download: function() {
        const self = this;
        var data = {
          base: self.row.convertedCSVName
          , date: self.row.conversionDate
        };
        setTimeout(function(){
          $.ajax({
            url : BASE_URL + '/logistics/rakuten_express/get/download_count',
            type : "POST",
            data: data,
            dataType : "json"
          })
          .done(function (result) {
            self.downloadCount = result.downloadCount;
          })
        }, 1000);
      }
    }
  });

  const vmClickpostCsvList = new Vue({
    el: "#rakutenExpressCsvList",
    data: {
      list : [],
      convertCSVURL     : "",
      messageCssClass : "alert alert-info mb0",
      message: ""
    },
    mounted: function () {
      const self = this;
      self.convertCSVURL      = $(self.$el).data("convertCsvUrl");

      const dateOptions = {
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true
      };

      self.appendDataToList(LIST_DATA);

      $("#expectedShippingDate").datepicker(dateOptions);
      $("#conversionDateFrom").datepicker(dateOptions);
      $("#conversionDateTo").datepicker(dateOptions);
      let stringParam = location.href.split("?")[1];
      if (stringParam === undefined) {
        $("#conversionDateFrom").datepicker("setDate", new Date());
        $("#conversionDateTo").datepicker("setDate", new Date());
      }
      else {
        stringParam += "&"; // 正規表現簡易化のため&をつけておく
        const params = stringParam.match(/=(.{10,10})&/g);
        $("#conversionDateFrom").datepicker("setDate", params[0]);
        $("#conversionDateTo").datepicker("setDate", params[1]);
      }
    },
    methods: {
      appendDataToList: function (listData) {
        const self = this;
        for (const row of listData) {
          self.list.push(self.convertJsonToObject(row));
        }
      },
      convertJsonToObject: function (row) {
        return {
          conversionDate         : row["変換日"],
          convertedCSVName       : row["変換後csvファイル名"],
          downloadCount          : row["保存回数"] ? row["保存回数"] : 0,
          errorVoucherNumbers    : row["エラー伝票番号"]
        };
      },
      convertCSV: function () {
        const self = this;
        const formdata = new FormData($("#convert-csv-form").get(0));

        $.Vendor.WaitingDialog.show("loading ..."); // Show loading

        $.ajax({
          url : self.convertCSVURL,
          type : "POST",
          data : formdata,
          contentType : false,
          processData : false,
          dataType : "json"
        })
        .done(function (result) {
          if (result.status === "ok") {
            self.appendDataToList(result.data);
            self.messageCssClass = "alert alert-success";
            self.message = "正常に変換しました。";
          } else if (result.status === "warn") {
            self.appendDataToList(result.data);
            self.messageCssClass = "alert alert-warning";
            self.message = "一部の住所の変換に失敗しました。\n";
          } else {
            self.messageCssClass = "alert alert-danger";
            self.message = "変換に失敗しました。\n";
            self.message += result.message;
          }
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        })
      },
      // 取得
      fetchListByDate: function () {
        const self = this;
        const from = $("#conversionDateFrom").val();
        if (!from) {
          alert("Fromが入力されていません。");
          return;
        }
        const to = $("#conversionDateTo").val();
        if (!to) {
          alert("Toが入力されていません。");
          return;
        }
        const url = location.href;
        window.location.href = url.split("?")[0] + "?from=" + from + "&to=" + to;
      }
    }
  });
  });
