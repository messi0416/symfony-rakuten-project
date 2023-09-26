const rakutenAttribute = new Vue({
  el: "#rakutenAttribute",

  data: {
    thumbnailUrl: null,
    searchUrl: null,
    updateUrl: null,
    messageState: {},

    daihyoSyohinCode: "",
    daihyoSyohin: "",
    axis: "",
    genreAttributes: [],
    skuAttributes: [],
    copySkuAttributes: [],

    currentTabIndex: 0,
    batchAllChangeValue: "",

    modifiedList: [],
    modifiedAttributesIds: [],
  },

  mounted: function () {
    const self = this;

    self.thumbnailUrl = $(self.$el).data("thumbnailUrl");
    self.searchUrl = $(self.$el).data("searchUrl");
    self.updateUrl = $(self.$el).data("updateUrl");

    self.messageState = new PartsGlobalMessageState();

    // codeパラメータが有る場合、そのコードで検索
    const code = new URL(document.location).searchParams.get("code");
    if (code !== undefined) {
      self.daihyoSyohinCode = code;
      self.search();
    }
  },

  methods: {
    search: function (skipUpdateProcess = false) {
      const self = this;
      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "GET",
        url: self.searchUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode,
          skipUpdateProcess: skipUpdateProcess ? 1 : 0,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            if (result.isAutoUpdated) {
              self.messageState.setMessage(
                "未登録の必須属性があったため、自動設定しました",
                "alert alert-warning"
              );
            }
            self.daihyoSyohin = result.list.daihyoSyohin;
            self.thumbnailUrl = self.thumbnailUrl.replace(
              "dir/file",
              `${result.list.daihyoSyohin.dir}/${result.list.daihyoSyohin.file}`
            );
            self.axis = result.list.axis;
            self.axis.col.map((element) => (element.batchChangeValue = ""));
            self.axis.row.map((element) => (element.batchChangeValue = ""));
            self.genreAttributes = result.list.genreAttributes;
            self.skuAttributes = result.list.skuAttributes;
            self.copySkuAttributes = self.clone(self.skuAttributes);
          } else {
            self.daihyoSyohin = result.list?.daihyoSyohin;
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
    clone: function (obj) {
      return JSON.parse(JSON.stringify(obj));
    },
    changeAttributeValue: function (event, attributeId, sku) {
      this.skuAttributes[attributeId][sku] = event.target.value;
      this.checkModified(attributeId, sku, event.target.value);
    },
    changeAllValue: function (attributeId) {
      const self = this;
      for (const sku of Object.keys(self.skuAttributes[attributeId])) {
        self.skuAttributes[attributeId][sku] = self.batchAllChangeValue;
        self.checkModified(attributeId, sku, self.batchAllChangeValue);
      }
    },
    changeSameAxisValue: function (attributeId, axis, code, value) {
      const self = this;
      switch (axis) {
        case "col":
          self.axis["row"].forEach((element) => {
            const sku = self.daihyoSyohin.code + code + element.rowcode;
            self.skuAttributes[attributeId][sku] = value;
            self.checkModified(attributeId, sku, value);
          });
          break;
        case "row":
          self.axis["col"].forEach((element) => {
            const sku = self.daihyoSyohin.code + element.colcode + code;
            self.skuAttributes[attributeId][sku] = value;
            self.checkModified(attributeId, sku, value);
          });
          break;
      }
    },
    checkModified: function (attributeId, sku, value) {
      const self = this;
      const id = self.genreAttributes[attributeId].id;
      // 未登録の時はundefined。undefinedと空文字を比較して、（見た目上変化無しなのに）差分扱いになるのを防ぐ。
      const copyValue = self.copySkuAttributes[attributeId][sku];
      const baseValue =
        typeof copyValue === "undefined" ? "" : String(copyValue);
      if (value === baseValue) {
        self.modifiedList = self.modifiedList.filter((element) => {
          return !(element.sku === sku && element.id === id);
        });
      } else {
        // 属性単位での差異有無判定の為に、attributeIdもpush
        self.modifiedList.push({ attributeId, sku, id, value });
      }
      self.modifiedAttributesIds = self.modifiedList
        .map((element) => element["attributeId"])
        .filter((element, index, array) => array.indexOf(element) === index);
    },
    update: function () {
      const self = this;
      $.Vendor.WaitingDialog.show("loading ...");
      $.ajax({
        type: "GET",
        url: self.updateUrl,
        dataType: "json",
        data: {
          modifiedList: self.modifiedList,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.modifiedList = [];
            self.modifiedAttributesIds = [];
            self.messageState.setMessage(
              "更新しました",
              "alert alert-success",
              true
            );
            self.search(true);
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
  },
});
