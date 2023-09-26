const setInventoryConstant = new Vue({
  el: "#goodsList",
  data: {
    deliverycodeList: DELIVERY_CODE_LIST,
    listUrl: null,
    searchUrl: null,
    rakutenAttributeUrl: null,
    mallProductUrl: null,
    weightSizeEditUrl: null,
    goodsImageEditUrl: null,
    inventoryConstantUrl: null,
    seasonSettingUrl: null,
    thumbnailUrl: null,
    messageState: {},
    conditions: {
      daihyoSyohinCode: "",
      daihyoSyohinName: "",
      category: "",
      sireAdress: "",
      salesStartDateFrom: null,
      salesStartDateTo: null,
      registrationDateFrom: null,
      registrationDateTo: null,
      deliverycodes: ["0", "1", "2"],
      sireAddressNecessity: "",
      configurable: false,
      isMyProduct: false,
      orderable: false,
      zaikoTeisuZero: false,
    },
    sireAddressNecessityList: [
      "登録済・登録不要",
      "要登録",
    ],
    dispConditions: {}, // 現在表示している検索結果の条件
    list: [],

    sortKey: "daihyoSyohinCode",
    sortDesc: false,
    paginationObj: {
      initPageItemNum: 100, // 1ページに表示する件数
      page: 1, // 現在ページ数
      itemNum: 0, // データ件数
    },
  },
  mounted: function () {
    const self = this;
    this.listUrl = $(this.$el).data("listUrl");
    this.searchUrl = $(this.$el).data("searchUrl");
    this.rakutenAttributeUrl = $(this.$el).data("rakutenAttributeUrl");
    this.mallProductUrl = $(this.$el).data("mallProductUrl");
    this.weightSizeEditUrl = $(this.$el).data("weightSizeEditUrl");
    this.goodsImageEditUrl = $(this.$el).data("goodsImageEditUrl");
    this.axisCodeIndexUrl = $(this.$el).data("axisCodeIndexUrl");
    this.inventoryConstantUrl = $(this.$el).data("inventoryConstantUrl");
    this.seasonSettingUrl = $(this.$el).data("seasonSettingUrl");
    this.thumbnailUrl = $(this.$el).data("thumbnailUrl");
    this.goodsInfoIndexUrl = $(this.$el).data("goodsInfoIndexUrl");

    this.messageState = new PartsGlobalMessageState();
    this.dispConditions = { ...this.conditions };

    $("#salesStartDateFrom", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate: function () {
          self.conditions.salesStartDateFrom = $(this).val();
        },
        clearDate: function () {
          self.conditions.salesStartDateFrom = null;
        },
      });
    $("#salesStartDateTo", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate: function () {
          self.conditions.salesStartDateTo = $(this).val();
        },
        clearDate: function () {
          self.conditions.salesStartDateTo = null;
        },
      });
    $("#registrationDateFrom", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate: function () {
          self.conditions.registrationDateFrom = $(this).val();
        },
        clearDate: function () {
          self.conditions.registrationDateFrom = null;
        },
      });
    $("#registrationDateTo", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate: function () {
          self.conditions.registrationDateTo = $(this).val();
        },
        clearDate: function () {
          self.conditions.registrationDateTo = null;
        },
      });
    $("#deliverycodes", this.$el).on(
      "changed.bs.select",
      self.deliverycodesChanged
    );

    // パラメータが有る場合、その条件で検索
    if (location.href.indexOf("?") === -1) {
      return;
    }
    const params = new URL(document.location).searchParams;
    for (const key of Object.keys(self.conditions)) {
      let value = params.get(key);
      if (!value) {
        continue;
      }
      if (key === "deliverycodes") {
        value = value.split(",");
      }
      if (
        key === "configurable" ||
        key === "isMyProduct" ||
        key === "orderable" ||
        key === "zaikoTeisuZero"
      ) {
        value = value === "0" ? false : true;
      }
      self.conditions[key] = value;
    }
    self.sortKey = params.get("sortKey");
    self.sortDesc = params.get("sortDesc") === "0" ? false : true;
    self.paginationObj.page = Number(params.get("page"));
    self.search();
  },

  computed: {},

  methods: {
    deliverycodesChanged: function () {
      this.conditions.deliverycodes = $("#deliverycodes").val();
    },

    search: function (reset = false) {
      $.Vendor.WaitingDialog.show("loading ...");

      const self = this;
      self.messageState.clear();
      self.list = [];

      const dateList = [
        self.conditions.salesStartDateFrom,
        self.conditions.salesStartDateTo,
        self.conditions.registrationDateFrom,
        self.conditions.registrationDateTo,
      ];
      const regex = /^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/;
      for (i = 0; i < dateList.length; i++) {
        if (dateList[i]) {
          if (!regex.test(dateList[i])) {
            self.messageState.setMessage(
              "日付の入力がyyyy-mm-dd形式ではありません",
              "alert alert-danger"
            );
            return;
          }
        }
      }

      if (reset) {
        self.paginationObj.page = 1;
      }
      self.conditions.configurable = self.conditions.configurable ? 1 : 0;
      self.conditions.isMyProduct = self.conditions.isMyProduct ? 1 : 0;
      self.conditions.orderable = self.conditions.orderable ? 1 : 0;
      self.conditions.zaikoTeisuZero = self.conditions.zaikoTeisuZero ? 1 : 0;
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          conditions: self.conditions,
          paginationObj: self.paginationObj,
          sortKey: self.sortKey,
          sortDesc: self.sortDesc ? 1 : 0,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.paginationObj.itemNum = result.count;
            if (result.count === 0) {
              self.messageState.setMessage(
                "商品データが取得できませんでした",
                "alert alert-warning"
              );
              return;
            }
            self.list = result.list;
            self.formatList();
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

          self.createParameter();

          $.Vendor.WaitingDialog.hide();
        });
    },
    formatList: function () {
      this.list.map((item) => {
        item.mallProductUrl = this.mallProductUrl.replace(
          "__DUMMY__",
          item.daihyoSyohinCode
        )
        item.goodsImageEditUrl = this.goodsImageEditUrl.replace(
          "__DUMMY__",
          item.daihyoSyohinCode
        )

        item.thumbnail = this.thumbnailUrl.replace(
          "dir/file",
          `${item.imageDir}/${item.imageFile}`
        );

        switch (item.orderableFlg) {
          case 1:
            item.orderable = "可能";
            item.orderableCss = "text-primary";
            break;
          case 0:
            item.orderable = "不可";
            item.orderableCss = "text-danger";
            break;
          default:
        }

        item.baikaTankaStr = item.baikaTanka.toLocaleString();

        // セット商品なら更新不可用の表示にする
        if (item.setFlg !== 0) {
          item.inventoryConstantCss = "text-muted";
        }
        // 自分が担当者でなく、他に担当者がいるか稼働中の場合も更新不可用の表示にする
        if (item.staffFlg === 0
          && (item.salesAccounts.length > 0 || item.activeFlg !== 0)
        ) {
          item.inventoryConstantCss = "text-muted";
        }

        item.registrationDate = $.Plusnao.Date.getDateString(
          new Date(item.registrationDate)
        );
      });
    },
    createParameter() {
      const params = [];
      for (let [key, value] of Object.entries(this.dispConditions)) {
        if (value === "" || value === null) {
          continue;
        }
        if (key === "deliverycodes") {
          value = value.join(",");
        }
        if (
          key === "configurable" ||
          key === "isMyProduct" ||
          key === "orderable"
        ) {
          value = value ? 1 : 0;
        }
        params.push(key + "=" + value);
      }
      params.push("sortKey=" + this.sortKey);
      params.push("sortDesc=" + (this.sortDesc ? 1 : 0));
      params.push("page=" + this.paginationObj.page);
      if (params.length > 0) {
        history.pushState({}, "", this.listUrl + "?" + params.join("&"));
      }
    },
    sortBy(key) {
      this.sortKey === key
        ? (this.sortDesc = !this.sortDesc)
        : (this.sortDesc = true);
      this.sortKey = key;
      this.conditions = { ...this.dispConditions };
      // 複数件検索結果がある場合のみ、データ再取得
      // 1件以下の時は、次回検索ボタン押下の時のソート予約のみ
      if (this.paginationObj.itemNum > 1) {
        this.search();
      }
    },
    addSortArrow: function (key) {
      return {
        asc: this.sortKey === key && !this.sortDesc,
        desc: this.sortKey === key && this.sortDesc,
      };
    },
    changePage: function (pageInfo) {
      this.paginationObj.page = pageInfo.page;
      this.conditions = { ...this.dispConditions };
      this.search();
    },
    scrollTop: function () {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    },
  },
});
