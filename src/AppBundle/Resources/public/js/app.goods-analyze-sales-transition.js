const setInventoryConstant = new Vue({
  el: "#analyzeSalesTransition",
  data: {
    messageState: {},
    productData: PRODUCT_DATA,
    displayFlg: false,
  },

  mounted: function () {
    const self = this;
    self.messageState = new PartsGlobalMessageState();

    if (!self.productData.hasOwnProperty("name")) {
      self.messageState.setMessage(
        `代表商品コード「 ${self.productData.code} 」は存在しません。`,
        "alert alert-danger"
      );
      return;
    }

    self.formatData();
    self.displayFlg = true;
  },

  computed: {},

  methods: {
    // データを整形する
    formatData() {
      const self = this;
      self.productData.salesTransition.map((record) => {
        // 受注年月の年-月を区切る
        let orderDate = String(record.orderYm);
        record.orderYm = orderDate.slice(0, 4) + "-" + orderDate.slice(4);

        // 3桁区切りにする前に粗利率を算出
        const detailGrossProfitRate = self.calcGrossProfitRate(
          record.detailGrossProfit,
          record.detailAmountIncludingCost
        );
        // 受注日以外を3桁区切りにする
        for (key of Object.keys(record)) {
          if (key !== "orderYm") {
            record[key] = record[key].toLocaleString();
          }
        }
        // 粗利率もリストに追加
        record.detailGrossProfitRate = detailGrossProfitRate;

        return record;
      });
    },

    // 粗利率を計算
    calcGrossProfitRate(detailGrossProfit, detailAmountIncludingCost) {
      if (detailAmountIncludingCost === 0) {
        return 0;
      }
      return (
        (detailGrossProfit * 100) /
        detailAmountIncludingCost
      ).toLocaleString(undefined, {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
      });
    },
  },
});
