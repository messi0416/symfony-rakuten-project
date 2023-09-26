/**
 * 商品在庫数 手動変更履歴画面 JS
 */

// 除外商品設定モーダル
Vue.component("setting-form-modal", {
  template: "#templateExcludeProductsSettingForm",
  data: function () {
    return {
      findUrl: null,
      saveUrl: null,
      messageState: {},
      nowLoading: false,
      excludeProductsStr: "",
    };
  },
  mounted: function () {
    this.$nextTick(function () {
      const self = this;
      const modal = $(self.$el);
      self.messageState = new PartsGlobalMessageState();
      self.findUrl = $(self.$el).data("findUrl");
      self.saveUrl = $(self.$el).data("saveUrl");

      modal.on("show.bs.modal", function (e) {
        self.messageState.clear();
        self.find();
      });
    });
  },

  methods: {
    find: function () {
      const self = this;
      self.nowLoading = true;
      $.ajax({
        type: "POST",
        url: self.findUrl,
        dataType: "json",
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.excludeProductsStr = result.excludeProducts.join("\n");
          } else {
            const message =
              result.message.length > 0
                ? result.message
                : "除外商品を取得できませんでした";
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
          self.nowLoading = false;
        });
    },
    save: function () {
      const self = this;
      self.messageState.clear();
      self.nowLoading = true;

      const excludeProducts = self.excludeProductsStr
        .split("\n")
        .map((product) => product.trim())
        .filter((product) => product !== "");

      $.ajax({
        type: "POST",
        url: self.saveUrl,
        dataType: "json",
        data: { excludeProducts },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.messageState.setMessage("更新しました", "alert-success", true);
          } else {
            const message =
              result.message.length > 0
                ? result.message
                : "除外商品を更新できませんでした";
            self.messageState.setMessage(message, "alert alert-danger");
          }
          self.find();
        })
        .fail(function (stat) {
          console.log(stat);
          self.messageState.setMessage(
            "エラーが発生しました",
            "alert alert-danger"
          );
        })
        .always(function () {
          self.nowLoading = false;
        });
    },
  },
});

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentStockManualChangeHistory = {
  template: "#templateStockManualChangeHistoriesTableRow",
  props: ["history"],
  computed: {
    displayBaikaGenka: function () {
      return Number(this.history.baika_genka).toLocaleString();
    },
    displayDiff: function () {
      let diff = Number(this.history.diff).toLocaleString();
      if (this.history.diff > 0) {
        diff = "+" + diff;
      }
      return diff;
    },
    displayTotalBaikaGenka: function () {
      let totalBaikaGenka = (
        this.history.baika_genka * this.history.diff
      ).toLocaleString();
      if (this.history.diff > 0) {
        totalBaikaGenka = "+" + totalBaikaGenka;
      }
      return totalBaikaGenka;
    },
    diffCss: function () {
      if (this.history.diff >= 0) {
        return "";
      } else {
        return "text-danger";
      }
    },
  },
};

// 一覧画面 一覧表
const stockManualChangeHistories = new Vue({
  el: "#stock-manual-change-histories",
  data: {
    conditions: {
      targetDateFrom: $.Plusnao.Date.getDateString(
        $.Plusnao.Date.getAddMonth(null, -1)
      ),
      targetDateTo: null,
      warehouseId: "",
      sku: "",
      reason: "",
      exceptNoDiff: true,
      exceptExcludeProducts: true,
    },
    sortKey: "sku",
    sortDesc: false,
    dispConditions: {}, // 現在表示している検索結果の条件
    warehouses: [],
    histories: [],
    diffSum: 0,
    totalBaikaGenkaSum: 0,
    messageState: {},
    findWarehousesUrl: null,
    searchUrl: null,
  },

  components: {
    "result-history": vmComponentStockManualChangeHistory, // 一覧テーブル
  },

  computed: {
    displayWarehouses: function () {
      const displayWarehouses = [...this.warehouses];
      displayWarehouses.unshift({ id: "", name: "全て" });
      return displayWarehouses;
    },
    calcTotalValue: function () {
      const totalValue = {
        diffSum: 0,
        totalBaikaGenkaSum: 0,
      };
      this.histories.forEach((history) => {
        totalValue.diffSum += Number(history.diff);
        totalValue.totalBaikaGenkaSum += history.baika_genka * history.diff;
      });
      return totalValue;
    },
    displayDiffSum: function () {
      if (this.calcTotalValue.diffSum > 0) {
        return "+" + this.calcTotalValue.diffSum.toLocaleString();
      } else {
        return this.calcTotalValue.diffSum.toLocaleString();
      }
    },
    displayTotalBaikaGenkaSum: function () {
      if (this.calcTotalValue.totalBaikaGenkaSum > 0) {
        return "+" + this.calcTotalValue.totalBaikaGenkaSum.toLocaleString();
      } else {
        return this.calcTotalValue.totalBaikaGenkaSum.toLocaleString();
      }
    },
    diffSumCss: function () {
      if (this.calcTotalValue.diffSum >= 0) {
        return "";
      } else {
        return "text-danger";
      }
    },
    totalBaikaGenkaSumCss: function () {
      if (this.calcTotalValue.totalBaikaGenkaSum >= 0) {
        return "";
      } else {
        return "text-danger";
      }
    },
  },

  mounted: function () {
    const self = this;
    self.searchUrl = $(self.$el).data("searchUrl");
    self.findWarehousesUrl = $(self.$el).data("findWarehousesUrl");
    self.messageState = new PartsGlobalMessageState();
    self.findWarehouses();
    $("#targetDateFrom", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate: function () {
          self.conditions.targetDateFrom = $(this).val();
        },
        clearDate: function () {
          self.conditions.targetDateFrom = null;
        },
      });
    $("#targetDateTo", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate: function () {
          self.conditions.targetDateTo = $(this).val();
        },
        clearDate: function () {
          self.conditions.targetDateTo = null;
        },
      });
  },

  methods: {
    findWarehouses: function () {
      const self = this;
      self.warehouses = [];
      self.messageState.clear();

      $.ajax({
        type: "GET",
        url: self.findWarehousesUrl,
        dataType: "json",
        data: {},
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.warehouses = result.warehouses;
          } else {
            const message = result.message
              ? result.message
              : "倉庫一覧取得処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage(
            "エラーが発生しました",
            "alert alert-danger"
          );
        });
    },

    search: function () {
      const self = this;
      self.histories = [];
      self.messageState.clear();
      const conditions = { ...self.conditions };
      conditions.exceptNoDiff = conditions.exceptNoDiff ? 1 : 0;
      conditions.exceptExcludeProducts = conditions.exceptExcludeProducts ? 1 : 0;

      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          conditions,
          sortKey: self.sortKey,
          sortDesc: self.sortDesc ? 1 : 0,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            if (result.histories.length === 0) {
              self.messageState.setMessage(
                "データがありません",
                "alert alert-warning"
              );
              return;
            }
            self.histories = result.histories;
          } else {
            const message = result.message
              ? result.message
              : "検索処理でエラーが発生しました";
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
          self.dispConditions = { ...self.conditions };
          $.Vendor.WaitingDialog.hide();
        });
    },

    sortBy: function (key) {
      this.sortDesc = this.sortKey === key ? !this.sortDesc : false;
      this.sortKey = key;
      this.conditions = { ...this.dispConditions };
      this.search();
    },

    addSortArrow: function (key) {
      return {
        asc: this.sortKey === key && !this.sortDesc,
        desc: this.sortKey === key && this.sortDesc,
      };
    },
  },
});
