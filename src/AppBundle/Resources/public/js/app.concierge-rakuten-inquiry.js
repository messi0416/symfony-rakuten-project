/**
 * 管理画面 コンシェルジュ 楽天未処理お問い合わせ件数用 JS
 */
// 全体メッセージ
var vmGlobalMessage = new Vue({
  el: "#globalMessage",
  delimiters: ["(%", "%)"],
  data: {
    message: "",
    messageCssClass: "",
    loadingImageUrl: null,
  },
  mounted: function () {
    this.$nextTick(function () {
      this.loadingImageUrl = $(this.$el).data("loadingImageUrl");
    });
  },
  methods: {
    setMessage: function (message, cssClass, autoHide) {
      cssClass = cssClass || "alert-info";
      autoHide = autoHide === null ? 5000 : Number(autoHide);

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function () {
          vmGlobalMessage.clear();
        }, autoHide);
      }
    },
    setCssClass: function (cssClass) {
      this.messageCssClass = cssClass;
    },
    clear: function () {
      this.message = "";
      this.messageCssClass = "";
    },
    closeWindow: function () {
      window.close();
    },
  },
});

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentConciergeRakutenInquiryTemplateListItem = {
  template: "#templateConciergeRakutenInquiryListTableRow",
  props: ["item"],
  data: function () {
    return {};
  },
  /* もし、this.item の直接参照がいやなら、下記のwatchでインスタンスのプロパティを更新する必要がある。
  , watch: {
    item: function() {
      this.orderDate          = this.item.orderDate;
    }
  }
  */
  computed: {},
  methods: {},
};

// 一覧画面 一覧表
var vmConciergeRakutenInquiryListTable = new Vue({
  el: "#rakutenInquiryList",
  delimiters: ["(%", "%)"],
  data: {
    list: [], // データ
    listUrl: null,
  },
  components: {
    "result-item": vmComponentConciergeRakutenInquiryTemplateListItem, // 一覧テーブル
  },
  mounted: function () {
    this.$nextTick(function () {
      this.listUrl = $(this.$el).data("listUrl");
      this.loadListData("rakuten", "plusnao");
      this.loadListData("motto", "MottoMotto");
      this.loadListData("laforest", "La Forest");
      this.loadListData("dolcissimo", "ドルチッシモ");
      this.loadListData("gekipla", "激安プラネット");
    });
  },
  //
  computed: {
    listData: function () {
      return this.list;
    },
  },
  methods: {
    loadListData: function (targetShop, targetShopName) {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show();

      var data = {
        targetShop: targetShop,
      };
      $.ajax({
        type: "GET",
        url: self.listUrl,
        dataType: "json",
        data: data,
      })
        .done(function (result) {
          if (result.status == "ok") {
            var item = result.item;
            var row = {
              name: targetShopName,
              past90daysCount: item.past90daysCount,
              past1dayCount: item.past1dayCount,
            };

            self.list.push(row);
          } else {
            var message =
              result.message.length > 0
                ? result.message
                : "処理を開始できませんでした。";
            vmGlobalMessage.setMessage(message, "alert-danger");
          }
        })
        .fail(function (stat) {
          vmGlobalMessage.setMessage("エラーが発生しました。", "alert-danger");
        })
        .always(function () {
          // Show loading
          $.Vendor.WaitingDialog.hide();
        });
    },
  },
});
