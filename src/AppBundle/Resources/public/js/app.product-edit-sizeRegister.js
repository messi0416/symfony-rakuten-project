/**
 * 管理画面 SKU別送料設定画面 JS
 */

/* 一覧画面 一覧テーブル 行コンポーネント */
const vmComponentSkuListItem = {
  template: "#templateSkuListTableRow",
  props: ["sku"],
};

/** メイン画面 */
const productItem = new Vue({
  el: "#sizeRegister",
  data: {
    barcode: "",
    skuCode: "",
    inputList: {
      width: "",
      height: "",
      depth: "",
      weight: "",
    },
    noParamList: [],

    item: null,
    messageState: {},

    searchUrl: null,
    updateUrl: null,
    thumbnailUrl: null,
  },

  computed: {
    addWarningClass() {
      const obj = {};
      Object.keys(this.inputList).forEach((key) => {
        obj[key] = {
          "bg-yellow": this.existParameter && this.noParamList.includes(key),
        };
      });
      return obj;
    },
    existParameter() {
      return location.href.indexOf("?") === -1 ? false : true;
    },
    displayCurrentSize() {
      // 表示用「現在のサイズ」(幅・奥行・高さは、mm → cm に変換)
      return {
        width: this.item.width / 10,
        height: this.item.height / 10,
        depth: this.item.depth / 10,
        weight: this.item.weight,
      };
    },
    displayThumbnailUrl() {
      return this.thumbnailUrl.replace(
        "dir/file",
        `${this.item.imageDir}/${this.item.imageFile}`
      );
    },
  },

  components: {
    skuList: vmComponentSkuListItem,
  },

  mounted() {
    this.searchUrl = $(this.$el).data("searchUrl");
    this.updateUrl = $(this.$el).data("updateUrl");
    this.thumbnailUrl = $(this.$el).data("thumbnailUrl");
    this.messageState = new PartsGlobalMessageState();
    this.checkCodeParameter();
  },

  methods: {
    checkCodeParameter() {
      if (!this.existParameter) {
        return;
      }
      const params = new URL(document.location).searchParams;
      this.barcode = params.get("code");
      if (this.barcode) {
        this.search();
      } else {
        this.messageState.setMessage(
          "バーコードの指定がありませんでした。手動で検索してください",
          "alert-warning"
        );
      }
    },

    getInputListParameter() {
      if (!this.existParameter) {
        return;
      }
      const params = new URL(document.location).searchParams;
      Object.keys(this.inputList).forEach((key) => {
        const value = Number(params.get(key));
        // パラメータの値が無い場合や0以下なら空文字に
        if (value <= 0) {
          this.inputList[key] = "";
          this.noParamList.push(key);
        } else {
          if (key === "weight") {
            this.inputList[key] = value;
          } else {
            // 幅・奥行・高さは、表示・編集用に単位を変換(mm → cm)
            this.inputList[key] = value / 10;
          }
        }
      });
    },

    search(type = "barcode", messageReset = true) {
      const self = this;
      self.item = null;
      if (messageReset) {
        self.messageState.clear();
      }
      self.noParamList = [];
      // パラメータから変更後サイズを取得
      self.getInputListParameter();
      // バーコードかSKUで、検索条件でない方のフォーム値は一度削除
      if (type === "sku") {
        self.barcode = "";
      } else {
        self.skuCode = "";
      }

      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          barcode: self.barcode,
          neSyohinSyohinCode: self.skuCode,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            if (!result.item) {
              self.messageState.setMessage(
                "データがありません",
                "alert alert-warning"
              );
              return;
            }
            self.item = result.item;
            // 検索結果のバーコードとSKUをフォームにも表示
            self.barcode = result.item.barcode;
            self.skuCode = result.item.neSyohinSyohinCode;

            if (
              self.messageState.message === "" &&
              self.existParameter &&
              self.noParamList.length > 0
            ) {
              self.alertParameterMissing();
            }
          } else {
            const message = result.message
              ? result.message
              : "検索処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function (stat) {
          console.log(stat);
          self.messageState.setMessage(
            "エラーが発生しました",
            "alert alert-danger"
          );
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    },

    alertParameterMissing() {
      const paramStrList = this.noParamList.map((param) => {
        switch (param) {
          case "width":
            return "「幅」";
          case "height":
            return "「高さ」";
          case "depth":
            return "「奥行」";
          case "weight":
            return "「重量」";
        }
      });
      this.messageState.setMessage(
        paramStrList.join("・") +
          "は、計測が出来ていません。正しい値を入力してください",
        "alert alert-warning"
      );
    },

    editSize(target) {
      const id = target.id;
      let value = target.value;
      if (id === "weight") {
        value = value >= 0 ? Math.floor(value) : 0;
      } else {
        value = value >= 0 ? Math.floor(value * 10) / 10 : 0;
      }
      this.inputList[id] = value;
    },

    update() {
      const self = this;
      if (!this.validateInputList()) {
        return;
      }
      self.messageState.clear();
      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: {
          skuList: self.item.skuList,
          // 幅・奥行・高さは、DB登録用に単位を変換(cm → mm)
          width: self.inputList.width * 10,
          height: self.inputList.height * 10,
          depth: self.inputList.depth * 10,
          weight: self.inputList.weight,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.messageState.setMessage("更新しました", "alert alert-success");
            // 表示中の商品のSKUで再検索。(バーコードが無い場合を考慮)
            self.skuCode = self.item.neSyohinSyohinCode;
            self.search("sku", false);
          } else {
            const message = result.message
              ? result.message
              : "更新処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage(
            "エラーが発生しました",
            "alert alert-danger"
          );
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    },

    validateInputList() {
      const inputValues = Object.values(this.inputList);
      if (inputValues.some((value) => value === "" || value < 0)) {
        this.messageState.setMessage(
          "変更後サイズには、0以上の数値を指定してください",
          "alert alert-danger"
        );
        return false;
      }
      return true;
    },
  },
});
