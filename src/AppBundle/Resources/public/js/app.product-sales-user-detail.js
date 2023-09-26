const userDetail = new Vue({
  el: "#userDetail",
  data: {
    salesAccountUrl: null,
    checkUrl: null,
    reviewUrl: null,
    oneYearAgo: '',

    messageState: {},
    message: USER_DETAIL["message"],
    conditions: USER_DETAIL["conditions"],
    total: USER_DETAIL["total"],
    list: USER_DETAIL["list"],
    disptitle: null,

    paginationObj: {
      initPageItemNum: 100, // 1ページに表示する件数
      page: 1, // 現在ページ数
    },
    sortKey: "",
    sortDesc: true,
  },

  mounted: function () {
    const self = this;

    const dt = new Date();
    const year = dt.getFullYear();
    const month = dt.getMonth() + 1;
    const date = dt.getDate();
    self.oneYearAgo = year - 1 + '-' + month + '-' + date;

    self.messageState = new PartsGlobalMessageState();

    if (self.message !== "") {
      self.messageState.setMessage(self.message, "alert alert-danger");
      return;
    }

    self.salesAccountUrl = $(self.$el).data("salesAccountUrl");
    self.checkUrl = $(self.$el).data("checkUrl");
    self.reviewUrl = $(self.$el).data("reviewUrl");

    self.checkCommand();

    self.disptitle = self.getDisptitle();

    self.total.productCount = Number(self.total.productCount).toLocaleString();
    self.total.salesProfitRate =
      self.total.salesAmount === 0
        ? "-"
        : ((self.total.profitAmount / self.total.salesAmount) * 100).toLocaleString(undefined, {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
          });
    self.total.shoplistSalesProfitRate =
      self.total.shoplistSalesAmount === 0
        ? "-"
        : ((self.total.shoplistProfitAmount / self.total.shoplistSalesAmount) * 100).toLocaleString(
            undefined,
            {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }
          );
    self.total.inventoryProfitRate =
      self.total.stockAmount === 0
        ? "-"
        : (((self.total.profitAmount + self.total.shoplistProfitAmount) / self.total.stockAmount) * 100).toLocaleString(undefined, {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1,
          });
    self.total.stockQuantity = self.total.stockQuantity.toLocaleString();
    self.total.stockAmount = self.total.stockAmount.toLocaleString();
    self.total.remainQuantity = self.total.remainQuantity.toLocaleString();
    self.total.remainAmount = self.total.remainAmount.toLocaleString();
    self.total.salesAmount = self.total.salesAmount.toLocaleString();
    self.total.profitAmount = self.total.profitAmount.toLocaleString();
    self.total.shoplistSalesAmount = self.total.shoplistSalesAmount.toLocaleString();
    self.total.shoplistProfitAmount = self.total.shoplistProfitAmount.toLocaleString();
    self.total.productCount = self.total.productCount.toLocaleString();
    if (self.total.reviewAllAve === null){
      self.total.reviewAllAve = this.displayUndefinedValue();
    } else {
      self.total.reviewAllAveStar = self.displayReviewScoreStar(1, self.total.reviewAllAve);
      self.total.reviewAllAve = (Math.floor(self.total.reviewAllAve * Math.pow(10, 2)) / Math.pow(10, 2)).toLocaleString(); // 小数点以下2桁に切り捨て
    }

    self.list.forEach((item) => self.createDispList(item));
  },

  computed: {
    sortList: function () {
      if (this.sortKey !== "") {
        let set = 1;
        this.sortDesc ? (set = 1) : (set = -1);
        this.list.sort((a, b) => {
          let targetA = a[this.sortKey];
          let targetB = b[this.sortKey];
          if (typeof targetA === "string") {
            targetA = this.convertToNum(targetA);
          }
          if (typeof targetB === "string") {
            targetB = this.convertToNum(targetB);
          }
          if (targetA < targetB) {
            return 1 * set;
          }
          if (targetA > targetB) {
            return -1 * set;
          }
          return 0;
        });
        return this.list;
      } else {
        return this.list;
      }
    },
    pageData: function () {
      const startPage = (this.paginationObj.page - 1) * this.paginationObj.initPageItemNum;
      return this.sortList.slice(startPage, startPage + this.paginationObj.initPageItemNum);
    },
    paginationInfo: function () {
      return {
        ...this.paginationObj,
        itemNum: this.sortList.length,
      };
    },
  },

  methods: {
    createDispList(item) {
      const stockAmount = Number(item.stockAmount);
      const salesAmount = Number(item.salesAmount);
      const profitAmount = Number(item.profitAmount);
      const shoplistSalesAmount = Number(item.shoplistSalesAmount);
      const shoplistProfitAmount = Number(item.shoplistProfitAmount);

      item.stockQuantityStr = Number(item.stockQuantity).toLocaleString();
      item.stockAmountStr = stockAmount.toLocaleString();
      item.remainQuantityStr = Number(item.remainQuantity).toLocaleString();
      item.remainAmountStr = Number(item.remainAmount).toLocaleString();
      item.salesAmountStr = salesAmount.toLocaleString();
      item.profitAmountStr = profitAmount.toLocaleString();
      item.shoplistSalesAmountStr = shoplistSalesAmount.toLocaleString();
      item.shoplistProfitAmountStr = shoplistProfitAmount.toLocaleString();
      item.salesProfitRate = 
        salesAmount === 0
          ? "-"
          : Math.round((profitAmount / salesAmount) * 100 * 10) / 10;
      item.salesProfitRateStr =
        item.salesProfitRate === "-"
          ? "-"
          : (item.salesProfitRate).toLocaleString();
      item.shoplistSalesProfitRate = 
        shoplistSalesAmount === 0
          ? "-"
          : Math.round((shoplistProfitAmount / shoplistSalesAmount) * 100 * 10) / 10;
      item.shoplistSalesProfitRateStr =
        item.salesProfitRate === "-"
          ? "-"
          : (item.shoplistSalesProfitRate).toLocaleString();
      item.inventorySalesProfitRate = 
        stockAmount === 0
          ? "-"
          : Math.round(((profitAmount + shoplistProfitAmount) / stockAmount) * 100 * 10) / 10;
      item.inventorySalesProfitRateStr =
        item.salesProfitRate === "-"
          ? "-"
          : (item.inventorySalesProfitRate).toLocaleString();
      item.reviewScoreStar = this.displayReviewScoreStar(item.reviewPointNum, item.reviewPointAve);
      item.reviewScoreSummary = this.displayReviewScoreSummary(item);
      item.reviewPointAve = 
        typeof item.reviewPointAve === "undefined"
          ? "-"
          : Math.round(Number(item.reviewPointAve) * 100) / 100
      item.reviewPointAveStr = 
        item.reviewPointAve === "-"
          ? "-"
          : item.reviewPointAve.toLocaleString();
    },

    downloadCSV: function() {
      // ダウンロードするCSVデータを準備する
      const headers = [
        "代表商品コード",
        "仕入先コード",
        "仕入先名",
        "在庫数量",
        "在庫金額",
        "注残数量",
        "注残金額",
        "売上額",
        "利益額",
        "利益率",
        "SHOPLIST売上額",
        "SHOPLIST利益額",
        "SHOPLIST利益率",
        "在庫利益率",
        "レビュー平均点",
        "タスク種別名",
        "タスク種別ID",
        "適用開始日",
        "適用終了日",
        "仕事量",
      ];

      let csvData = '"' + headers.join('","') + '"';
      this.list.forEach((item) => {
        csvData += "\n";
        csvData += `"${item.daihyoSyohinCode}",`;
        csvData += `"${item.sireCode}",`;
        csvData += `"${item.sireName}",`;
        csvData += `"${item.stockQuantity}",`;
        csvData += `"${item.stockAmount}",`;
        csvData += `"${item.remainQuantity}",`;
        csvData += `"${item.remainAmount}",`;
        csvData += `"${item.salesAmount}",`;
        csvData += `"${item.profitAmount}",`;
        csvData += `"${item.salesProfitRate}",`;
        csvData += `"${item.shoplistSalesAmount}",`;
        csvData += `"${item.shoplistSalesAmount}",`;
        csvData += `"${item.shoplistSalesProfitRate}",`;
        csvData += `"${item.inventorySalesProfitRate}",`;
        csvData += `"${item.reviewPointAve}",`;
        csvData += `"${item.taskName}",`;
        csvData += `"${item.taskId}",`;
        csvData += `"${item.applyStartDate}",`;
        csvData += `"${item.applyEndDate}",`;
        csvData += `"${item.workAmount}"`;
      });

      // Encoding.convert()で、直接、UTF-8からShift_JISに変換しようとすると
      // 上手くいかないので、先にUnicodeエンコードの配列を作る
      const unicodeList = [];
      for (let i = 0; i < csvData.length; i++) {
        unicodeList.push(csvData.charCodeAt(i));
      }

      // Shift_JISエンコードに変換する
      const sjisEncoded = Encoding.convert(
        unicodeList,
        "sjis",
        "unicode"
      );

      // Shift_JISエンコードされたデータをBlobオブジェクトに変換する
      const blob = new Blob([new Uint8Array(sjisEncoded)], {
        type: "text/csv;charset=SJIS",
      });

      // BlobオブジェクトからURLを生成する
      const url = URL.createObjectURL(blob);

      // ダウンロード用のリンクを作成してクリックする
      const link = document.createElement("a");
      link.setAttribute("href", url);
      const {userName, targetDateFrom, targetDateTo, sireName} = this.conditions;
      const sire = sireName ?? "全仕入先";
      const df = targetDateFrom.replace(/-/g, "");
      const dt = targetDateTo ? targetDateTo.replace(/-/g, "") : "";
      const fileName = `product_sales_user_detail_${userName}_${sire}_${df}_${dt}.csv`;
      link.setAttribute("download", fileName);
      link.style.display = "none";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      // URLを解放する
      URL.revokeObjectURL(url);
    },

    /** レビューなしの場合の表示文字列。jsとtwig両方で使用する */
    displayUndefinedValue() {
      // レビューなしの場合は ----- を表示
      return "-----";
    },

    /** レビュー平均を★で表示。小数点以下切り捨て */
    displayReviewScoreStar(reviewPointNum, reviewPointAve){
      var resultScore = '';
      if (reviewPointNum === undefined || reviewPointNum === 0) {
        return this.displayUndefinedValue();
      }
      for (var i = 1; i <= 5; i++) {
        if (i <= reviewPointAve) {
          resultScore = resultScore + '★';
        } else {
          resultScore = resultScore + '☆';
        }
      }
      return resultScore;
    }
    
    /** レビューサマリを表示。「平均(件数)」 */
    , displayReviewScoreSummary(item) {
      // レビューなしなら空
      if (item.reviewPointNum !== undefined && item.reviewPointNum > 0) {
        const ave = Math.floor(item.reviewPointAve * Math.pow(10, 2)) / Math.pow(10, 2); // 小数点以下2桁に切り捨て
        return ave + "(" + item.reviewPointNum + ")";
      }
      return null;
    },

    getDisptitle() {
      const self = this;
      const disptitleList = [];
      // 担当者名
      disptitleList.push("担当者名：" + self.conditions.userName);
      // 売上日
      if (self.conditions.targetDateFrom || self.conditions.targetDateTo) {
        let targetDateStr = "売上日：";
        if (self.conditions.targetDateFrom) {
          targetDateStr += self.conditions.targetDateFrom;
        }
        targetDateStr += " ～ ";
        if (self.conditions.targetDateTo) {
          targetDateStr += self.conditions.targetDateTo;
        }
        disptitleList.push(targetDateStr);
      }
      // タスク適用開始日
      if (self.conditions.applyStartDateFrom || self.conditions.applyStartDateTo) {
        let applyStartDateStr = "タスク適用開始日：";
        if (self.conditions.applyStartDateFrom) {
          applyStartDateStr += self.conditions.applyStartDateFrom;
        }
        applyStartDateStr += "～";
        if (self.conditions.applyStartDateTo) {
          applyStartDateStr += self.conditions.applyStartDateTo;
        }
        disptitleList.push(applyStartDateStr);
      }
      // タスク種別
      if (self.conditions.selectTaskName.length > 0) {
        let taskStr = "タスク種別：";
        taskStr += self.conditions.selectTaskName.join(", ");
        disptitleList.push(taskStr);
      }
      // 仕入先
      if (self.conditions.sireName) {
        let sireName = "仕入先：";
        sireName += self.conditions.sireName;
        disptitleList.push(sireName);
      }

      return disptitleList.join("\n");
    },
    filterBySireName: function (sireName) {
      const url = new URL(location.href);
      url.searchParams.set("sireName", sireName);
      location.href = url.href;
    },

    unfilterBySireName: function () {
      const url = new URL(location.href);
      url.searchParams.delete("sireName");
      location.href = url.href;
    },

    changePage: function (pageInfo) {
      this.paginationObj.page = pageInfo.page;
    },

    sortBy(key) {
      this.sortKey === key ? (this.sortDesc = !this.sortDesc) : (this.sortDesc = true);
      this.sortKey = key;
    },

    convertToNum(localeString) {
      const removeComma = localeString.replace(/,/g, "");
      return removeComma === "-" ? -99999 : parseFloat(removeComma);
    },

    addSortArrow: function (key) {
      return {
        asc: this.sortKey === key && !this.sortDesc,
        desc: this.sortKey === key && this.sortDesc,
      };
    },

    // 集計バッチ実行状況確認
    checkCommand: function () {
      const self = this;
      const command = "aggregate_product_sales_account_result_history";
      $.ajax({
        type: "POST",
        url: self.checkUrl,
        dataType: "json",
        data: {
          queue: "productSales",
          command: [command],
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            if (result.command[command].isRunning) {
              const message =
                "現在は集計中のため、一部、または全体の数値が正しくありません。のちほどご確認ください";
              self.messageState.setMessage(message, "alert alert-warning");
            }
          } else {
            const message = result.error
              ? result.error
              : "バッチ実行状況確認中にエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
        });
    },

    scrollTop: function () {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    },
  },
});
