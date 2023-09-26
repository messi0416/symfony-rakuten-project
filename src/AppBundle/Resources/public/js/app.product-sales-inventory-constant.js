const setInventoryConstant = new Vue({
  el: "#setInventoryConstant",
  data: {
    adjustWorkPeriod: 14, // 新規登録商品の調整作業期間(日数)
    isAdjustWorkPeriod: false,
    daihyoSyohinCode: null,
    product: {},
    list: [],
    isUpdatable: false,
    accountUrl: null,
    accountUrlSearch: "",
    searchUrl: null,
    updateResetDateUrl: null,
    updateInventoryConstantUrl: null,
    messageState: {},
    modifyFlg: false,
    modifyList: {},
    resetDateModifyFlg: false,
  },
  mounted: function () {
    const self = this;
    // URL取得
    self.accountUrl = $(self.$el).data("accountUrl");
    self.searchUrl = $(self.$el).data("searchUrl");
    self.updateResetDateUrl = $(self.$el).data("updateResetDateUrl");
    self.updateInventoryConstantUrl = $(self.$el).data("updateInventoryConstantUrl");

    self.messageState = new PartsGlobalMessageState();

    // codeパラメータが有る場合、そのコードで検索
    const code = new URL(document.location).searchParams.get("code");
    if (code != undefined) {
      self.daihyoSyohinCode = code;
      self.search();
    }
  },

  computed: {
    registrationDateCss: function () {
      let css = "";
      if (this.isAdjustWorkPeriod) {
        css = "bg-danger text-danger";
      }
      return css;
    },
  },

  methods: {
    checkUpdateAvailability: function () {
      const self = this;
      // セット商品は無条件に更新不可
      if (self.product.setFlg !== 0) {
        self.messageState.setMessage("セット商品の為、更新できません", "alert alert-warning");
        return;
      }
      // 自分が担当者であれば、更新可
      if (self.product.isStaff) {
        self.isUpdatable = true;
        return;
      }
      // 自分が担当者ではなく、他に担当者がいれば、更新不可
      if (self.product.hasStaff) {
        self.messageState.setMessage(
          "この商品は担当者が存在します。商品売上担当者以外は在庫定数を更新できません",
          "alert alert-warning"
        );
        return;
      }
      // 誰も担当者がいなくても、稼働中であれば、更新不可
      if (self.product.isOperating) {
        self.messageState.setMessage(
          "この商品は現在稼働中です。商品売上担当者以外は在庫定数を更新できません",
          "alert alert-warning"
        );
        return;
      }
      // 誰も担当者がおらず、稼働中でもなければ、更新可
      // 但し、アラートは表示する
      const registrationDate = new Date(self.product.registrationDate);
      const ms = new Date().getTime() - registrationDate.getTime();
      // 特に、新規登録商品の調整作業期間内の商品は、その旨を強く表示する
      if (Math.floor(ms / (1000 * 60 * 60 * 24)) <= self.adjustWorkPeriod) {
        self.messageState.setMessage(
          `商品登録から${self.adjustWorkPeriod}日以内です。商品売上担当者の依頼商品の可能性があります。在庫定数を登録可能かご確認ください`,
          "alert alert-warning"
        );
        self.isAdjustWorkPeriod = true;
      } else {
        self.messageState.setMessage(
          "現在、この商品の担当者はいません。商品売上担当者画面で担当者登録が可能です",
          "alert alert-warning"
        );
      }
      self.isUpdatable = true;
    },
    search: function (update = false, target = "") {
      $.Vendor.WaitingDialog.show("loading ...");

      /* @click等から呼ぶとき、@click="search"のようにメソッド名に括弧を付けないと、
          暗黙にEventオブジェクトが渡され、引数がTruthyになってしまうので注意 */
      const self = this;
      self.messageState.clear();
      self.product = {};
      self.list = [];
      self.isAdjustWorkPeriod = false;
      self.isUpdatable = false;
      self.accountUrlSearch = "";
      self.modifyFlg = false;
      self.modifyList = {};
      self.resetDateModifyFlg = false;
      $.ajax({
        type: "GET",
        url: self.searchUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode,
        },
      })
        .done(function (result) {
          if (result.status == "ok") {
            if (!result.product) {
              self.messageState.setMessage(
                "商品データが取得できませんでした",
                "alert alert-warning"
              );
              return;
            }
            self.product = result.product;
            self.product.genkaTnk = self.product.genkaTnk.toLocaleString();
            self.product.baikaTnk = self.product.baikaTnk.toLocaleString();
            self.list = result.list;
            self.copyList = JSON.parse(JSON.stringify(self.list));
            self.copyResetDate = self.product.resetDate;

            // 商品売上担当者設定画面へのパラメータ設定
            self.accountUrlSearch = "?code=" + self.product.daihyoSyohinCode;

            self.checkUpdateAvailability();
            self.list.forEach((item) => {
              // 受発注可否のCSS設定
              item.orderingAvailabilityCss = self.addOrderingAvailabilityCss(
                item.orderingAvailability
              );
              // 数値を3桁区切りに整形
              item.orderScore = item.orderScore.toLocaleString();
              item.seasonInventoryConstant = item.seasonInventoryConstant.toLocaleString();
              item.stockQuantity = item.stockQuantity.toLocaleString();
              item.freeInventoryQuantity = item.freeInventoryQuantity.toLocaleString();
              item.airOrderRemaining = item.airOrderRemaining.toLocaleString();
              item.containerOrderRemaining = item.containerOrderRemaining.toLocaleString();
            });
            if (update) {
              const message = target ? `${target}を更新しました` : '更新しました';
              self.messageState.setMessage(message, "alert alert-success");
            }
          } else {
            const message = result.message ? result.message : "検索処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました", "alert alert-danger");
        })
        .always(function() {
          scrollTo({ top: 0, behavior: "smooth" });   
          $.Vendor.WaitingDialog.hide();
        });
    },
    createDatePicker:function() {
      const self = this;
      $("#resetDate", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
        startDate: Date(),
        endDate: "+1y",
        todayBtn: 'linked',
        // 下方向に固定しないと、月選択の時上方向に表示されて選択できなくなる
        orientation: 'bottom',
        // 強制的に有効な日付にしない(既に現在日より前の日付で登録がある時に、空欄になるのを回避)
        forceParse: false,
      })
      .on({
        changeDate: function () {
          if (self.copyResetDate === $(this).val()) {
            self.resetDateModifyFlg = false;
            return;
          }
          self.product.resetDate = $(this).val();
          self.resetDateModifyFlg = true;
        },
        clearDate: function () {
          if (self.copyResetDate === null) {
            self.resetDateModifyFlg = false;
            return;
          }
          self.product.resetDate = null;
          self.resetDateModifyFlg = true;
        },
      });
    },
    addOrderingAvailabilityCss: function (orderingAvailability) {
      let css = "";
      switch (orderingAvailability) {
        case "可能":
          css = "text-primary";
          break;
        case "不可":
          css = "text-danger";
          break;
      }
      return css;
    },
    modify: function (index, value) {
      const self = this;
      value = Number(value);
      self.list[index].inventoryConstant = value;
      neSyohinCode = self.list[index].neSyohinCode;
      if (value === self.copyList[index].inventoryConstant) {
        self.list[index].addClass = "";
        delete self.modifyList[neSyohinCode];
        if (Object.keys(self.modifyList).length === 0) {
          self.modifyFlg = false;
        }
      } else {
        self.list[index].addClass = "bg-modified";
        self.modifyList[neSyohinCode] = value;
        self.modifyFlg = true;
      }
    },
    updateResetDate: function () {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      const target = "在庫定数リセット日"
      self.messageState.clear();
      $.ajax({
        type: "GET",
        url: self.updateResetDateUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode,
          resetDate: self.product.resetDate,
        },
      })
        .done(function (result) {
          if (result.status == "ok") {
            self.search(true, target);
          } else {
            const message = result.message ? result.message : `${target}更新処理でエラーが発生しました`;
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました", "alert alert-danger");
        })
        .always(function() {
          scrollTo({ top: 0, behavior: "smooth" });   
          $.Vendor.WaitingDialog.hide();
        });
    },
    updateInventoryConstant: function () {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      const target = "在庫定数"
      self.messageState.clear();
      for (const neSyohinCode in self.modifyList) {
        if (isNaN(self.modifyList[neSyohinCode])) {
          self.messageState.setMessage(
            `「 ${neSyohinCode} 」 の在庫定数が数値ではありません`,
            "alert alert-danger"
          );
          return;
        }
      }
      $.ajax({
        type: "GET",
        url: self.updateInventoryConstantUrl,
        dataType: "json",
        data: {
          modifyList: self.modifyList,
          needInterruptCheck: !(self.product.isStaff || self.product.hasStaff || self.product.isOperating),
          daihyoSyohinCode: self.daihyoSyohinCode,
        },
      })
        .done(function (result) {
          if (result.status == "ok") {
            self.search(true, target);
          } else {
            const message = result.message ? result.message : `${target}更新処理でエラーが発生しました`;
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました", "alert alert-danger");
        })
        .always(function() {
          scrollTo({ top: 0, behavior: "smooth" });   
          $.Vendor.WaitingDialog.hide();
        });
    },
  },
});
