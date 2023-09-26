const mallProductByShopTable = {
  template: "#mallProductByShopTable",
  props: ["item", "index", "setFlg", "size"],
  data: function () {
    return {
      copyItem: {},
      modifiedClasses: {},
      descriptionModifiedClass: "",
      hasUpdateRole: HAS_UPDATE_ROLE,
      taxRate: TAX_RATE,

      // S&L出荷フラグのサイズ条件。単位は重さ(weight)がグラム(g)、他はミリメートル(mm)
      snlTerms: {
        maxBaikaTanka: 800,
        maxWeight: 900, // 重量：950g まで（内、50gは梱包重量として900gで判定）
        maxSizes: [
          { maxDepth: 300, maxWidth: 235, maxHeight: 23 },
          { maxDepth: 200, maxWidth: 170, maxHeight: 33 },
        ],
      },
    };
  },
  mounted: function () {
    if (!this.hasUpdateRole) {
      document.querySelectorAll("input").forEach((el) => {
        el.disabled = true;
      });
    }
    this.copyItem = { ...this.item };
  },
  computed: {
    hasSizeSetting: function () {
      const { weight, depth, width, height } = this.size;
      if (weight <= 0 || depth <= 0 || width <= 0 || height <= 0) {
        return false;
      }
      return true;
    },
    displaySnlSizeTerms: function () {
      const sizeTerms = this.snlTerms.maxSizes.map((size) => {
        const { maxDepth, maxWidth, maxHeight } = size;
        return `「${maxDepth / 10}cm × ${maxWidth / 10}cm × ${
          maxHeight / 10
        }cm 以内」`;
      });
      return sizeTerms.join(" または ");
    },
    canChangeShoplistRegistrationFlg: function () {
      // セット商品の場合、SHOPLISTでは現在出品でないものを出品には変更できない
      if (!this.setFlg) return true;
      if (this.item.mall !== "shoplist") return true;
      if (this.copyItem.registrationFlg) return true;
      return false;
    },
    displayMallName: function () {
      return this.findDisplayMallName(this.item.mall);
    },
    displaySellingPrice: function () {
      return Math.floor(this.item.baikaTanka * this.taxRate).toLocaleString();
    },
    displayFbaBaika: function () {
      return this.item.mall === "amazon"
        ? this.item.fbaBaika.toLocaleString()
        : null;
    },
    displayFbaBaikaTaxed: function () {
      return this.item.mall === "amazon"
        ? Math.floor(this.item.fbaBaika * this.taxRate).toLocaleString()
        : null;
    },
    displaySnlBaika: function () {
      return this.item.mall === "amazon"
        ? this.item.snlBaika.toLocaleString()
        : null;
    },
    displaySnlBaikaTaxed: function () {
      return this.item.mall === "amazon"
        ? Math.floor(this.item.snlBaika * this.taxRate).toLocaleString()
        : null;
    },
  },
  methods: {
    findDisplayMallName: function (mall) {
      switch (mall) {
        case "rakuten":
          return "楽天";
        case "yahoo":
          return "Yahoo!";
        case "wowma":
          return "Wowma [au PAY ﾏｰｹｯﾄ]";
        case "shoplist":
          return "SHOPLIST";
        case "ppm":
          return "ﾎﾟﾝﾊﾟﾚﾓｰﾙ";
        case "amazon":
          return "Amazon";
        case "cube":
          return "EC-CUBE";
        case "q10":
          return "Q10";
      }
    },
    // 現在の登録内容から変更されている説明文が1件でもあるか確認する
    checkDescriptionModified: function () {
      const descriptions = this.findDescriptionObj();
      const copyDescriptions = this.findDescriptionObj(true);
      const hasModified = Object.entries(descriptions).some(
        ([key, value]) => value !== copyDescriptions[key]
      );
      this.descriptionModifiedClass = hasModified ? "shadow-modified" : "";
    },
    // 値が変更されたタイミングで、値の更新と同時に、現在の登録内容との比較も行う
    changeValue: function (event, key) {
      const { type, checked } = event.target;
      // バリデーション
      if (!this.validateCheckBox(event, key)) {
        return;
      }
      if (type === "text" || type === "number") {
        // 入力補正
        let value = event.target.value.trim().replace(/　/g, " ");
        value = this.inputCorrection(key, value);
        event.target.value = value;

        this.item[key] = value;
        this.checkTextDiff(key, value);
      }
      if (type === "checkbox") {
        this.item[key] = checked;
        this.checkBoolValueDiff(key, checked);
      }
      // Amazon・売価単価の特別処理
      if (this.item.mall === "amazon" && key === "baikaTanka") {
        const maxBaikaTanka = this.snlTerms.maxBaikaTanka;
        if (this.item.snlFlg && this.item.baikaTanka > maxBaikaTanka) {
          event.target.value = maxBaikaTanka;
          this.item[key] = maxBaikaTanka;
          alert(
            `S&L出荷がONの場合、売価単価は${maxBaikaTanka}円より高くできません`
          );
          return false;
        }
      }
      // 価格非連動変更時の特別処理
      if (key === "originalPriceFlg") {
        // 値変更後、親メソッドで、価格非連動にチェックの有る店舗が存在するかのチェックを行う
        this.$emit("change-original-price-flg");
        // チェックを外した場合、売価単価は編集を元に戻し更新対象から外す
        if (!checked) {
          this.item.baikaTanka = this.copyItem.baikaTanka;
          this.checkTextDiff("baikaTanka", this.item.baikaTanka);
        }
      }
    },
    // 入力補正
    inputCorrection: function (key, value) {
      // 0を含む自然数に
      if (["baikaTanka"].includes(key)) {
        const valueNum = Math.round(Number(value));
        return valueNum >= 0 ? valueNum : 0;
      }
      // 楽天
      // 表示価格: 半角数字か'open'以外は空文字に
      if (key === "displayPrice") {
        const convertValue = value.match(/^[1-9][0-9]{0,8}$/) ? value : "";
        return ["0", "open"].includes(value) ? value : convertValue;
      }
      // 二重価格管理番号: 0,1,2,4以外はnullに（プルダウンにすれば無くても良い）
      if (key === "dualPriceControlNumber") {
        return ["0", "1", "2", "4"].includes(value) ? value : null;
      }
      // Yahoo!
      // PR率: 0~30の小数第一位までの数値以外はnullに
      if (key === "prRate") {
        const valueNum = Math.round(Number(value) * 10) / 10;
        return value === "" || value < 0 || value > 30 ? null : valueNum;
      }
      return value;
    },
    validateCheckBox: function (event, key) {
      let result = true;
      switch (key) {
        case "fbaFlg":
          if (
            this.checkDuplicateShippingFlags(key) ||
            !this.validateSize(key)
          ) {
            result = false;
            event.target.checked = false;
          }
          break;
        case "snlFlg":
          if (
            this.checkDuplicateShippingFlags(key) ||
            !this.validateSize(key) ||
            !this.validateSnlBaikaTanka()
          ) {
            result = false;
            event.target.checked = false;
          }
          break;
      }
      return result;
    },
    checkDuplicateShippingFlags: function (key) {
      const pairKey = key === "fbaFlg" ? "snlFlg" : "fbaFlg";
      if (this.item[pairKey]) {
        alert("FBA出荷フラグ と S&L出荷フラグ は両方ONにはできません");
        return true;
      }
      return false;
    },
    validateSize: function (key) {
      if (!this.hasSizeSetting) {
        alert(
          "重量・奥行・幅・高さのいずれかが未設定のため、現在はONにはできません"
        );
        return false;
      }

      if (key === "snlFlg" && !this.validateSnlSize()) {
        return false;
      }
      return true;
    },
    validateSnlSize: function () {
      const { weight, depth, width, height } = this.size;
      const maxWeight = this.snlTerms.maxWeight;
      if (weight > maxWeight) {
        alert(`重量が${maxWeight}gより重いので、現在はONにはできません`);
        return false;
      }
      const result = this.snlTerms.maxSizes.some((size) => {
        const { maxDepth, maxWidth, maxHeight } = size;
        return depth <= maxDepth && width <= maxWidth && height <= maxHeight;
      });
      if (!result) {
        alert(
          `サイズが条件を満たしていないので、現在はONにはできません\n【条件】 ${this.displaySnlSizeTerms}`
        );
        return false;
      }
      return true;
    },
    validateSnlBaikaTanka: function () {
      const maxBaikaTanka = this.snlTerms.maxBaikaTanka;
      if (this.item.baikaTanka > maxBaikaTanka) {
        alert(
          `売価単価が${maxBaikaTanka}円より高いので、S&L出荷をONにはできません`
        );
        return false;
      }
      return true;
    },
    checkTextDiff: function (key, value) {
      // 数値が編集されて文字列になっている場合を考慮して、両方を型変換してから比較
      // 空文字とnullの差は、変更と見なさない
      if (
        (value === "" && this.copyItem[key] === null) ||
        (value === null && this.copyItem[key] === "") ||
        String(value) === String(this.copyItem[key])
      ) {
        this.$emit("change-value", this.item.entity, "delete", key);
        this.modifiedClasses[key] = "";
      } else {
        this.$emit("change-value", this.item.entity, "add", key, value);
        this.modifiedClasses[key] = "bg-modified";
      }
    },
    checkBoolValueDiff: function (key, checked) {
      if (checked === this.copyItem[key]) {
        this.$emit("change-value", this.item.entity, "delete", key);
        this.modifiedClasses[key] = "";
      } else {
        this.$emit("change-value", this.item.entity, "add", key, checked);
        this.modifiedClasses[key] = "shadow-modified";
      }
    },
    findDescriptionObj(isCopy = false) {
      const mall = this.item.mall;
      const target = isCopy ? "copyItem" : "item";
      switch (mall) {
        case "rakuten":
          return {
            productDescriptionPC: this[target].productDescriptionPC,
            productDescriptionSP: this[target].productDescriptionSP,
            salesDescriptionPC: this[target].salesDescriptionPC,
          };
        case "yahoo":
          return {
            inputCaption: this[target].inputCaption,
            inputSpAdditional: this[target].inputSpAdditional,
          };
        case "ppm":
          return {
            productDescription1: this[target].productDescription1,
            productDescription2: this[target].productDescription2,
            productDescriptionSP: this[target].productDescriptionSP,
            productDescriptionText: this[target].productDescriptionText,
          };
        case "q10":
          return {
            freeExplanation: this[target].freeExplanation,
          };
      }
    },
    /**
     * 商品説明文編集 ポップアップ
     */
    openProductDescriptionModal: function () {
      const list = {
        index: this.index,
        mall: this.item.mall,
        shop: this.item.shop,
        entity: this.item.entity,
        descriptions: this.findDescriptionObj(),
        copyDescriptions: this.findDescriptionObj(true),
      };
      vmProductDescription.open(list, this.displayMallName);
    },
  },
};

const mallProduct = new Vue({
  el: "#mall-product",
  data: {
    updateUrl: null,
    thumbnailUrl: null,
    messageState: {},
    registrationFlgAllSelected: false,
    nonLinkedPrice: "",
    originalPriceFlgAllSelected: false,
    hasUpdateRole: HAS_UPDATE_ROLE,
    info: MALL_PRODUCT,
    copyMainInfo: {},
    copyByShopInfo: {},
    mainModifiedList: {},
    byShopModifiedList: {},
    byShopModifiedNum: 0,
    modifiedClasses: {},
  },
  components: {
    "mall-product-by-shop-table": mallProductByShopTable,
  },
  mounted: function () {
    this.messageState = new PartsGlobalMessageState();
    if (!this.hasUpdateRole) {
      document.querySelectorAll("input").forEach((el) => {
        el.disabled = true;
      });
    }
    this.updateUrl = $(this.$el).data("updateUrl");
    const { imageDir, imageFile } = this.info.main;
    this.thumbnailUrl = $(this.$el)
      .data("thumbnailUrl")
      .replace("dir/file", `${imageDir}/${imageFile}`);
    this.copyMainInfo = { ...this.info.main };
    this.copyByShopInfo = this.info.byShop.map((shopInfo) => {
      return { ...shopInfo };
    });

    this.findNonLinkedPriceInitialValue();

    // モール別価格のON/OFFと、価格非連動店舗の有無が合っていない商品もあるようなので、
    // 初めに1度チェックする。
    this.checkHasOriginalPriceFlg();
  },
  computed: {
    sizeInfo: function () {
      const { weight, depth, width, height } = this.info.main;
      return { weight, depth, width, height };
    },
  },
  methods: {
    findNonLinkedPriceInitialValue: function () {
      // 非連動価格の初期値は、楽天(plusnao)の売価単価と同値とする
      const rakutenPlusnaoInfo = this.copyByShopInfo.find((info) => {
        return info.mall === "rakuten" && info.shop === "plusnao";
      });
      this.nonLinkedPrice = rakutenPlusnaoInfo.baikaTanka;
    },
    // 価格非連動にチェックのある店舗が1件でも存在するか確認
    // 有無に応じて、「モール別価格」ラベルの表示/非表示(mallPriceFlg)を切り替える
    // また、これが現在の登録から変更になる場合、この項目も保存押下時の更新対象に含める
    checkHasOriginalPriceFlg: function () {
      const key = "mallPriceFlg";
      if (this.info.byShop.some((item) => item.originalPriceFlg)) {
        this.info.main[key] = true;
        this.checkMainBoolValueDiff(key, this.info.main[key]);
      } else {
        this.info.main[key] = false;
        this.checkMainBoolValueDiff(key, this.info.main[key]);
      }
    },
    changeNonLinkedPrice: function (event) {
      let value = event.target.value.trim().replace(/　/g, " ");
      const valueNum = Math.round(Number(value));
      value = valueNum > 0 ? valueNum : 0;
      this.nonLinkedPrice = value;
    },
    // 値が変更されたタイミングで、値の更新と同時に、現在の登録内容との比較も行う
    changeMainValue: function (event, key) {
      const { type, checked } = event.target;
      if (type === "text" || type === "number") {
        let value = event.target.value.trim().replace(/　/g, " ");
        // 入力補正: 0を含む自然数に
        if (key === "genkaTnk" || key === "baikaTnk") {
          const valueNum = Math.round(Number(value));
          value = valueNum >= 0 ? valueNum : 0;
        }
        event.target.value = value;
        this.info.main[key] = value;
        this.checkMainTextDiff(key, value);
      }
      if (type === "checkbox") {
        this.info.main[key] = checked;
        this.checkMainBoolValueDiff(key, checked);
      }
      // 価格非連動変更時の特別処理
      // チェックを外した場合、基準売価(抜)は編集を元に戻し更新対象から外す
      if (key === "originalPriceFlg" && !checked) {
        this.info.main.baikaTnk = this.copyMainInfo.baikaTnk;
        this.checkMainTextDiff("baikaTnk", this.info.main.baikaTnk);
      }
    },
    checkMainTextDiff: function (key, value) {
      // 数値が編集されて文字列になっている場合を考慮して、両方を型変換してから比較
      // 空文字とnullの差は、変更と見なさない
      if (
        (value === "" && this.copyMainInfo[key] === null) ||
        (value === null && this.copyMainInfo[key] === "") ||
        String(value) === String(this.copyMainInfo[key])
      ) {
        this.changeMainModifiedList("delete", key);
        this.modifiedClasses[key] = "";
      } else {
        this.changeMainModifiedList("add", key, value);
        this.modifiedClasses[key] = "bg-modified";
      }
    },
    checkMainBoolValueDiff: function (key, checked) {
      if (checked === this.copyMainInfo[key]) {
        this.changeMainModifiedList("delete", key);
        this.modifiedClasses[key] = "";
      } else {
        this.changeMainModifiedList("add", key, checked);
        this.modifiedClasses[key] = "shadow-modified";
      }
    },
    // メイン情報の変更分リストを更新(差異が無くなった場合はプロパティ毎削除)
    changeMainModifiedList: function (ope, key, value = "") {
      if (ope === "add") {
        this.mainModifiedList[key] = value;
      }
      if (ope === "delete") {
        delete this.mainModifiedList[key];
      }
    },
    // モール・店舗毎の変更分リストを更新(差異が無くなった場合はプロパティ毎削除)
    changeByShopModifiedList: function (entity, ope, key, value = "") {
      if (ope === "add") {
        if (this.byShopModifiedList[entity] === undefined) {
          this.byShopModifiedList[entity] = {};
        }
        this.byShopModifiedList[entity][key] = value;
      }
      if (ope === "delete") {
        if (this.byShopModifiedList[entity] === undefined) {
          return;
        }
        delete this.byShopModifiedList[entity][key];
        if (Object.keys(this.byShopModifiedList[entity]).length === 0) {
          delete this.byShopModifiedList[entity];
        }
      }
      // 保存ボタンの:disabledに直接定義や、computedでは、発火しないようなので
      this.byShopModifiedNum = Object.keys(this.byShopModifiedList).length;
    },
    changeAllTitle: function () {
      this.info.byShop.forEach((item, index) => {
        item.title = this.info.main.daihyoSyohinName;
        this.$refs.byShop[index].checkTextDiff("title", item.title);
      });
    },
    selectAllRegistrationFlg: function () {
      if (this.registrationFlgAllSelected) {
        this.info.byShop.forEach((item, index) => {
          item.registrationFlg = false;
          this.$refs.byShop[index].checkBoolValueDiff("registrationFlg", false);
        });
        this.registrationFlgAllSelected = false;
      } else {
        this.info.byShop.forEach((item, index) => {
          // セット商品の場合、SHOPLISTでは現在出品でないものを出品には変更できない
          if (
            this.info.main.setFlg &&
            item.mall === "shoplist" &&
            !this.copyByShopInfo[index].registrationFlg
          ) {
            return;
          }
          item.registrationFlg = true;
          this.$refs.byShop[index].checkBoolValueDiff("registrationFlg", true);
        });
        this.registrationFlgAllSelected = true;
      }
    },
    changeAllBaikaTanka: function () {
      this.info.byShop.forEach((item, index) => {
        if (item.originalPriceFlg) {
          item.baikaTanka = this.nonLinkedPrice;
          this.$refs.byShop[index].checkTextDiff("baikaTanka", item.baikaTanka);
        }
      });
    },
    selectAllOriginalPriceFlg: function () {
      if (this.originalPriceFlgAllSelected) {
        this.info.byShop.forEach((item, index) => {
          item.originalPriceFlg = false;
          const shopInfo = this.$refs.byShop[index];
          shopInfo.checkBoolValueDiff("originalPriceFlg", false);
          // 価格非連動のチェックを外した場合、売価単価は編集を元に戻し更新対象から外す
          item.baikaTanka = this.copyByShopInfo[index].baikaTanka;
          shopInfo.checkTextDiff("baikaTanka", item.baikaTanka);
        });
        this.originalPriceFlgAllSelected = false;
      } else {
        this.info.byShop.forEach((item, index) => {
          item.originalPriceFlg = true;
          this.$refs.byShop[index].checkBoolValueDiff("originalPriceFlg", true);
        });
        this.originalPriceFlgAllSelected = true;
      }
      // 反復処理完了後に1回だけ、価格非連動にチェックの有る店舗が存在するかのチェックを行う
      this.checkHasOriginalPriceFlg();
    },
    // 真偽値の項目を「-1 / 0」に変換する(ACCESS仕様)
    // 2022/09/09現在、更新し得る全項目がtrue時殆ど-1になっているので合わせて。
    // (Amazonの出品フラグは、1のレコードも1，2割程混入しているが統一)
    convertBoolValue: function (obj) {
      const newObj = {};
      for (let [key, value] of Object.entries(obj)) {
        if (typeof value === "boolean") {
          value = value ? -1 : 0;
        }
        if (value !== null && typeof value === "object") {
          value = this.convertBoolValue(value);
        }
        newObj[key] = value;
      }
      return newObj;
    },
    save: function () {
      const self = this;
      // 念の為に更新権限があるかここでもチェエク
      if (!self.hasUpdateRole) return;
      self.messageState.clear();
      if (!self.validateByShopModified()) return;
      const mainModifiedList = self.convertBoolValue(self.mainModifiedList);
      const byShopModifiedList = self.convertBoolValue(self.byShopModifiedList);
      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.info.main.daihyoSyohinCode,
          mainModifiedList,
          byShopModifiedList,
        },
      })
        .done((result) => {
          if (result.status === "ok") {
            self.info = result.mallProduct;
            self.copyMallProductInfo();
            self.messageState.setMessage(
              "更新しました",
              "alert alert-success",
              true
            );
          } else {
            const message = result.message || "更新処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail((result) => {
          const message = result.message || "エラーが発生しました";
          self.messageState.setMessage(message, "alert alert-danger");
        });
    },
    validateByShopModified: function () {
      let result = true;
      for (const [entity, list] of Object.entries(this.byShopModifiedList)) {
        for (const [key, value] of Object.entries(list)) {
          const { mall, shop } = this.copyByShopInfo.find((info) => {
            return info.entity === entity;
          });
          const mallName = this.$refs.byShop[0].findDisplayMallName(mall);
          const shopName = ["rakuten", "yahoo"].includes(mall)
            ? `${mallName}(${shop})`
            : mallName;
          switch (key) {
            case "title":
              if (this.checkIncludeTargetString(value, "\u301c", '"', ",")) {
                alert(
                  `[ ${shopName} ] タイトル に、波ダッシュ（〜）・ダブルクォーテーション・カンマは使えません`
                );
                result = false;
              }
              break;
          }
          if (!result) break;
        }
        if (!result) break;
      }
      return result;
    },
    checkIncludeTargetString: function (value, ...targets) {
      for (let target of targets) {
        if (value.includes(target)) {
          return true;
        }
      }
      return false;
    },
    // モール商品情報を値渡しでコピーし、差分関連の情報はリセットする
    copyMallProductInfo: function () {
      this.copyMainInfo = { ...this.info.main };
      this.copyByShopInfo = this.info.byShop.map((shopInfo) => {
        return { ...shopInfo };
      });
      this.$refs.byShop.forEach((shopInfo) => {
        shopInfo.copyItem = { ...shopInfo.item };
        shopInfo.modifiedClasses = {};
        shopInfo.descriptionModifiedClass = "";
      });
      this.mainModifiedList = {};
      this.byShopModifiedList = {};
      this.byShopModifiedNum = 0;
      this.modifiedClasses = {};
    },
  },
});

/**
 * 商品説明文編集ポップアップ
 */
const vmProductDescription = new Vue({
  el: "#modal-product-description",
  data: {
    caption: "",
    message: "",
    messageClass: "info",
    list: {},
    displayMallName: "",
    modifiedClasses: {},
    nowLoading: true,
    hasUpdateRole: HAS_UPDATE_ROLE,
  },
  mounted: function () {
    const self = this;
    $(self.$el).on("show.bs.modal", function () {
      self.nowLoading = true;
      const { displayMallName, displayShopName } = self;
      self.caption = `[${displayMallName}${displayShopName}] 商品説明文編集`;
      self.nowLoading = false;
    });
  },
  updated: function () {
    if (!this.hasUpdateRole) {
      document.querySelectorAll("textarea").forEach((el) => {
        el.disabled = true;
      });
    }
  },
  components: {},
  computed: {
    displayShopName: function () {
      const { mall, shop } = this.list;
      return ["rakuten", "yahoo"].includes(mall) ? ` ${shop}` : "";
    },
  },
  methods: {
    open: function (list, displayMallName) {
      this.resetDialog();
      this.list = list;
      this.displayMallName = displayMallName;
      this.checkModified();
      $(this.$el).modal("show");
    },
    resetDialog: function () {
      this.message = "";
      this.messageClass = "";
      this.list = [];
      this.modifiedClasses = {};
    },
    findDescriptionName: function (key) {
      switch (key) {
        case "productDescriptionPC":
          return "商品説明文(PC用)";
        case "productDescriptionSP":
          return "商品説明文(スマホ用)";
        case "salesDescriptionPC":
          return "販売説明文(PC用)";
        case "inputCaption":
          return "商品説明(PCのみ)";
        case "inputSpAdditional":
          return "フリースペース(スマートフォンのみ)";
        case "productDescription1":
          return "商品説明(PC用1)";
        case "productDescription2":
          return "商品説明(PC用2)";
        case "productDescriptionSP":
          return "商品説明(スマートフォン用)";
        case "productDescriptionText":
          return "商品説明(テキストのみ)";
        case "freeExplanation":
          return "Q10商品説明(自由記述HTML可)";
      }
    },
    // 値が変更されたタイミングで、値の更新と同時に、現在の登録内容との比較も行う
    changeDescription: function (event, key) {
      const { index, entity, descriptions, copyDescriptions } = this.list;
      const value = event.target.value;
      descriptions[key] = value;
      mallProduct.info.byShop[index][key] = value;
      mallProduct.$refs.byShop[index].checkDescriptionModified();
      if (value === copyDescriptions[key]) {
        this.modifiedClasses[key] = "";
        mallProduct.changeByShopModifiedList(entity, "delete", key);
      } else {
        this.modifiedClasses[key] = "bg-modified";
        mallProduct.changeByShopModifiedList(entity, "add", key, value);
      }
    },
    checkModified: function () {
      const { descriptions, copyDescriptions } = this.list;
      for (const [key, value] of Object.entries(descriptions)) {
        if (value === copyDescriptions[key]) {
          this.modifiedClasses[key] = "";
        } else {
          this.modifiedClasses[key] = "bg-modified";
        }
      }
    },
  },
});
