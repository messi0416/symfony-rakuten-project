const seasonSetting = new Vue({
  el: "#season-setting",
  data: {
    adjustWorkPeriod: 14, // 新規登録商品の調整作業期間(日数)
    isUpdatable: false,
    thumbnailUrl: null,
    daihyoSyohinCode: null,
    mainProduct: {},
    monthsOriginal: {},
    months: {},
    settingTargets: {
      hattyuten: "発注点設定",
      nesage: "値下設定",
      kisetsuzaikoteisu: "季節在庫定数設定",
    },
    batchChangeItems: {
      ON: {
        すべて: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        "春(2〜5月)": [2, 3, 4, 5],
        "夏(5〜8月)": [5, 6, 7, 8],
        "秋(8〜11月)": [8, 9, 10, 11],
        "冬(11〜2月)": [11, 12, 1, 2],
      },
      OFF: {
        すべて: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        "春(3〜5月)": [3, 4, 5],
        "夏(6〜8月)": [6, 7, 8],
        "秋(9〜11月)": [9, 10, 11],
        "冬(12〜2月)": [12, 1, 2],
      },
    },
    messageState: {},
  },
  components: {},
  mounted: function () {
    this.thumbnailUrl = $(this.$el).data("thumbnailUrl");
    this.searchUrl = $(this.$el).data("searchUrl");
    this.updateUrl = $(this.$el).data("updateUrl");

    this.messageState = new PartsGlobalMessageState();

    // codeパラメータが有る場合、そのコードで検索
    const code = new URL(document.location).searchParams.get("code");
    if (code !== undefined) {
      this.daihyoSyohinCode = code;
      this.search();
    }
  },
  computed: {},
  methods: {
    search: function () {
      const self = this;
      self.messageState.clear();
      self.isUpdatable = false;
      self.mainProduct = {};
      self.monthsOriginal = {};
      self.months = {};
      self.thumbnailUrl = $(this.$el).data("thumbnailUrl");
      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "GET",
        url: self.searchUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.mainProduct = result.list.mainProduct;
            if (self.mainProduct.daihyoSyohinCode === null) {
              const message = "商品データが取得できませんでした";
              self.messageState.setMessage(message, "alert alert-warning");
              return;
            }
            self.monthsOriginal = result.list.months;
            self.months = { ...self.monthsOriginal };
            const { imageDir, imageFile } = self.mainProduct;
            self.thumbnailUrl = self.thumbnailUrl.replace(
              "dir/file",
              `${imageDir}/${imageFile}`
            );
            self.checkUpdateAvailability();
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
          $.Vendor.WaitingDialog.hide();
        });
    },
    checkUpdateAvailability: function () {
      const self = this;
      // セット商品は無条件に更新不可
      if (self.mainProduct.setFlg !== 0) {
        self.messageState.setMessage(
          "セット商品の為、更新できません",
          "alert alert-warning"
        );
        return;
      }
      // 自分が担当者であれば、更新可
      if (self.mainProduct.isStaff) {
        self.isUpdatable = true;
        return;
      }
      // 自分が担当者ではなく、他に担当者がいれば、更新不可
      if (self.mainProduct.hasStaff) {
        self.messageState.setMessage(
          "この商品は担当者が存在します。商品売上担当者以外は更新できません",
          "alert alert-warning"
        );
        return;
      }
      // 誰も担当者がいなければ、更新可（但し、アラートは表示する）
      // #219525_2 シーズン設定は、（在庫定数設定と異なり、）稼働中でも更新可とする
      const registrationDate = new Date(self.mainProduct.registrationDate);
      const ms = new Date().getTime() - registrationDate.getTime();
      // 特に、新規登録商品の調整作業期間内の商品は、その旨を強く表示する
      if (Math.floor(ms / (1000 * 60 * 60 * 24)) <= self.adjustWorkPeriod) {
        self.messageState.setMessage(
          `商品登録から${self.adjustWorkPeriod}日以内です。商品売上担当者の依頼商品の可能性があります。更新可能かご確認ください`,
          "alert alert-warning"
        );
      } else {
        self.messageState.setMessage(
          "現在、この商品の担当者はいません。商品売上担当者画面で担当者登録が可能です",
          "alert alert-warning"
        );
      }
      self.isUpdatable = true;
    },
    update: function () {
      const self = this;
      self.messageState.clear();
      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.mainProduct.daihyoSyohinCode,
          months: self.months,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.messageState.setMessage("更新しました", "alert alert-success");
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
    batchChange: function (settingKey, months, type) {
      if (type === "ON") {
        months.forEach((item) => {
          if (!this.months[settingKey].includes(item)) {
            this.months[settingKey].push(item);
          }
        });
      } else if (type === "OFF") {
        this.months[settingKey] = this.months[settingKey].filter((item) => {
          return !months.includes(item);
        });
      }
    },
  },
});
