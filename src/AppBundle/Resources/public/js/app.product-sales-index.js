// 一覧画面 一覧表
const productSales = new Vue({
  el: "#productSales",
  data: {
    typeList: [
      { id: "account", name: "アカウント別" },
      { id: "team", name: "チーム別" },
    ],
    selectType: "account",
    searchItem: {
      targetDateFrom: $.Plusnao.Date.getDateString(
        $.Plusnao.Date.getAddMonth(null, -1),
        false,
        false
      ),
      targetDateTo: "",
      selectTask: [],
      applyStartDateFrom: "",
      applyStartDateTo: "",
    },
    dispSearchItem: {},
    searchUrl: null, // 検索URL
    detailUrl: null, // 担当者別売上明細URL
    checkUrl: null, // コマンド実行状況チェックURL
    totalInfo: {}, // 全体・フォレスト売上情報
    productCountList: {}, // 期間内関連商品数
    targetReviews: {}, // 担当者orチーム毎のレビュー情報
    score: [],
    dispTotal: {},
    dispList: [],
    messageState: {},
    tasks: TASKS_DATA,
    salesAccounts: SALES_ACCOUNTS_DATA,
    defaultDisplayUsers: DEFAULT_DISPLAYS_DATA,
    sortKey: "",
    sortDesc: true,
    selectedUsers: DEFAULT_DISPLAYS_DATA,
  },
  mounted: function () {
    const self = this;
    // URL取得
    this.searchUrl = $(this.$el).data("searchUrl");
    this.checkUrl = $(this.$el).data("checkUrl");
    // datetimepickerの設定
    $(".datepicker")
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on("change", function (e) {
        self.$set(self.searchItem, e.target.name, e.target.value);
      });

    const $selectTask = $("#selectTask");
    $selectTask.selectpicker("refresh");

    this.messageState = new PartsGlobalMessageState();
    self.messageState.setMessage("検索条件を指定して、検索を押下してください", "alert alert-info");
  },
  computed: {
    dispTargetName: function () {
      const self = this;
      switch (self.selectType) {
        case "account":
          return "担当者名";
        case "team":
          return "チーム名";
        default:
          return "";
      }
    },
    noAccountTitle: function () {
      if (this.dispSearchItem.applyStartDateFrom || this.dispSearchItem.applyStartDateTo) {
        return "期間内販売開始の\n担当者なし商品";
      } else {
        return "担当者なし全商品";
      }
    },
    sortList: function () {
      let sortList = JSON.parse(JSON.stringify(this.dispList));
      if (this.selectType === "account") {
        sortList = sortList.filter(item => {
          return this.selectedUsers.includes(item.id);
        });
      }
      if (this.sortKey !== "") {
        let set = 1;
        this.sortDesc ? (set = 1) : (set = -1);
        sortList.sort((a, b) => {
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
      }
      return sortList;
    },
  },
  methods: {
    // 検索
    search: function () {
      $.Vendor.WaitingDialog.show('loading ...');
      const self = this;
      self.totalInfo = {};
      self.productCountList = {};
      self.targetReviews = {};
      self.score = [];
      self.dispTotal = {};
      self.dispList = [];
      self.messageState.clear();
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          form: self.searchItem,
          selectType: self.selectType,
        },
      })
        .done(function (result) {
          if (result.success) {
            self.checkCommand();
            self.totalInfo = result.total;
            self.productCountList = result.productCountList;
            self.targetReviews = result.targetReviews;
            self.score = result.score;
            self.dispSearchItem = Object.assign({}, self.searchItem);
            self.createDispTotal();
            self.createDispList();
          } else {
            const message = result.error ? result.error : "検索でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function (stat) {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
          console.log(stat);
        })
        .always(function () {
          $.Vendor.WaitingDialog.hide();
        });
    },
    createDispTotal: function () {
      const self = this;
      const totalStockAmount = Number(self.totalInfo.stockAmount);
      const totalProductCount = Number(self.totalInfo.productCount);
      const totalImmediateProductCount = Number(self.totalInfo.immediateProductCount);
      const totalSales = Number(self.totalInfo.totalSales);
      const totalGrossProfit = Number(self.totalInfo.totalGrossProfit);
      const totalShoplistSales = Number(self.totalInfo.totalShoplistSales);
      const totalShoplistProfit = Number(self.totalInfo.totalShoplistProfit);
      const totalReviewPoints = Number(self.totalInfo.reviews.totalScore);
      const totalReviewNum = Number(self.totalInfo.reviews.count);
      const totalReviewAve = totalReviewNum
        ? Math.floor(totalReviewPoints / totalReviewNum * 100) / 100
        : '-';
      const noAccountStockAmount = Number(self.totalInfo.noAccountStockAmount);
      const noAccountProductCount = Number(self.totalInfo.noAccountProductCount);
      const noAccountImmediateProductCount = Number(self.totalInfo.noAccountImmediateProductCount);
      const noAccountSales = Number(self.totalInfo.noAccountSales);
      const noAccountGrossProfit = Number(self.totalInfo.noAccountGrossProfit);
      const noAccountShoplistSales = Number(self.totalInfo.noAccountShoplistSales);
      const noAccountShoplistProfit = Number(self.totalInfo.noAccountShoplistProfit);
      const noAccountReviewPoints = Number(self.totalInfo.noAccountReviews.totalScore);
      const noAccountReviewNum = Number(self.totalInfo.noAccountReviews.count);
      const noAccountReviewAve = noAccountReviewNum
        ? Math.floor(noAccountReviewPoints / noAccountReviewNum * 100) / 100
        : '-';
      self.dispTotal = {
        stockDate: self.totalInfo.stockDate,
        stockQuantity: Number(self.totalInfo.stockQuantity).toLocaleString(),
        stockAmount: totalStockAmount.toLocaleString(),
        remainQuantity: Number(self.totalInfo.remainQuantity).toLocaleString(),
        remainAmount: Number(self.totalInfo.remainAmount).toLocaleString(),
        stockQuantityAvg: Number(self.totalInfo.stockQuantityAvg).toLocaleString(),
        stockAmountAvg: Number(self.totalInfo.stockAmountAvg).toLocaleString(),
        remainQuantityAvg: Number(self.totalInfo.remainQuantityAvg).toLocaleString(),
        remainAmountAvg: Number(self.totalInfo.remainAmountAvg).toLocaleString(),
        noAccountStockQuantity: Number(self.totalInfo.noAccountStockQuantity).toLocaleString(),
        noAccountStockAmount: noAccountStockAmount.toLocaleString(),
        noAccountRemainQuantity: Number(self.totalInfo.noAccountRemainQuantity).toLocaleString(),
        noAccountRemainAmount: Number(self.totalInfo.noAccountRemainAmount).toLocaleString(),
        productCount: totalProductCount.toLocaleString(),
        immediateProductCount: totalImmediateProductCount.toLocaleString(),
        noAccountProductCount: noAccountProductCount.toLocaleString(),
        noAccountImmediateProductCount: noAccountImmediateProductCount.toLocaleString(),
        totalSales: totalSales.toLocaleString(),
        totalGrossProfit: totalGrossProfit.toLocaleString(),
        totalSalesProfitRate:
          totalSales === 0
            ? "-"
            : ((totalGrossProfit / totalSales) * 100).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        totalShoplistSales: totalShoplistSales.toLocaleString(),
        totalShoplistProfit: totalShoplistProfit.toLocaleString(),
        totalShoplistSalesProfitRate:
          totalShoplistSales === 0
            ? "-"
            : ((totalShoplistProfit / totalShoplistSales) * 100).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        totalInventoryProfitRate:
          totalStockAmount === 0
            ? "-"
            : (((totalGrossProfit + totalShoplistProfit) / totalStockAmount) * 100).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        totalProfitPerItem:
          totalProductCount === 0
            ? "-"
            : ((totalGrossProfit + totalShoplistProfit) / totalProductCount).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        totalProfitPerItemOfImmediate:
          totalImmediateProductCount === 0
            ? "-"
            : ((totalGrossProfit + totalShoplistProfit) / totalImmediateProductCount).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        totalReviews: {
          reviewStar: totalReviewNum ? self.createReviewStar(totalReviewAve) : '-----',
          reviewAve: totalReviewAve,
          reviewNum: totalReviewNum,
          reviewNumStr: totalReviewNum.toLocaleString(),
        },
        noAccountSales: noAccountSales.toLocaleString(),
        noAccountGrossProfit: noAccountGrossProfit.toLocaleString(),
        noAccountSalesProfitRate:
          noAccountSales === 0
            ? "-"
            : ((noAccountGrossProfit / noAccountSales) * 100).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        noAccountShoplistSales: noAccountShoplistSales.toLocaleString(),
        noAccountShoplistProfit: noAccountShoplistProfit.toLocaleString(),
        noAccountShoplistSalesProfitRate:
          noAccountShoplistSales === 0
            ? "-"
            : ((noAccountShoplistProfit / noAccountShoplistSales) * 100).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        noAccountInventoryProfitRate:
          noAccountStockAmount === 0
            ? "-"
            : (((noAccountGrossProfit + noAccountShoplistProfit) / noAccountStockAmount) * 100).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        noAccountProfitPerItem:
          noAccountProductCount === 0
            ? "-"
            : ((noAccountGrossProfit + noAccountShoplistProfit) / noAccountProductCount).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        noAccountProfitPerItemOfImmediate:
          noAccountImmediateProductCount === 0
            ? "-"
            : ((noAccountGrossProfit + noAccountShoplistProfit) / noAccountImmediateProductCount).toLocaleString(undefined, {
              minimumFractionDigits: 1,
              maximumFractionDigits: 1,
            }),
        noAccountReviews: {
          reviewStar: noAccountReviewNum ? self.createReviewStar(noAccountReviewAve) : '-----',
          reviewAve: noAccountReviewAve,
          reviewNum: noAccountReviewNum,
          reviewNumStr: noAccountReviewNum.toLocaleString(),
        },
      };
    },
    createDispList: function () {
      const self = this;
      switch (self.selectType) {
        case "account":
          // 売上・在庫リストに存在するユーザIDを格納
          const salesStockUserIds = [];
          // 同じuserIdの売上額、利益額、利益率を計算し、リストで返す
          const accountList = self.score.map((item) => {
            salesStockUserIds.push(item.userId);
            const productCount = self.productCountList[item.userId]?.productCount;
            const reviews = self.targetReviews[item.userId];
            const reviewNum = reviews?.count ? Number(reviews.count) : 0;
            const reviewAve = reviewNum ? Math.floor(reviews.totalScore / reviewNum * 100) / 100 : '-';

            return {
              target: item.userName,
              id: item.userId,
              userDetailUrl: self.getUserDetailUrl(item.userId),
              productCount: productCount ? Number(productCount).toLocaleString() : 0,
              salesAmount: item.salesAmount.toLocaleString(),
              profitAmount: item.profitAmount.toLocaleString(),
              salesProfitRate:
                item.salesAmount === 0
                  ? "-"
                  : ((item.profitAmount / item.salesAmount) * 100).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
              shoplistSalesAmount: item.shoplistSalesAmount.toLocaleString(),
              shoplistProfitAmount: item.shoplistProfitAmount.toLocaleString(),
              shoplistSalesProfitRate:
                item.shoplistSalesAmount === 0
                  ? "-"
                  : ((item.shoplistProfitAmount / item.shoplistSalesAmount) * 100).toLocaleString(
                    undefined,
                    { minimumFractionDigits: 1, maximumFractionDigits: 1 }
                  ),
              inventoryProfitRate:
                item.stockAmount === 0
                  ? "-"
                  : (((item.profitAmount + item.shoplistProfitAmount) / item.stockAmount) * 100).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
              profitPerItem:
                productCount === 0
                  ? "-"
                  : ((item.profitAmount + item.shoplistProfitAmount) / productCount).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
              stockQuantity: item.stockQuantity.toLocaleString(),
              stockAmount: item.stockAmount.toLocaleString(),
              remainQuantity: item.remainQuantity.toLocaleString(),
              remainAmount: item.remainAmount.toLocaleString(),
              reviewStar: reviewNum ? self.createReviewStar(reviewAve) : '-----',
              reviewAve,
              reviewNum,
              reviewNumStr: reviewNum.toLocaleString(),
            };
          });

          // 売上・在庫データが無く、期間内関連商品数のみ有るユーザIDを取得
          if (self.productCountList) {
            const onlyProductCountUserIds = Object.keys(self.productCountList).filter(
              (id) => {
                return salesStockUserIds.indexOf(id) == -1;
              }
            );
            // 期間内関連商品数のみのデータを表示用リストに追加
            onlyProductCountUserIds.forEach((id) => {
              const reviews = self.targetReviews[id];
              const reviewNum = reviews?.count ? Number(reviews.count) : 0;
              const reviewAve = reviewNum ? Math.floor(reviews.totalScore / reviewNum * 100) / 100 : '-';
              accountList.push({
                target: self.productCountList[id].userName,
                id,
                userDetailUrl: self.getUserDetailUrl(id),
                productCount: self.productCountList[id].productCount,
                salesAmount: 0,
                profitAmount: 0,
                salesProfitRate: "-",
                shoplistSalesAmount: 0,
                shoplistProfitAmount: 0,
                shoplistSalesProfitRate: "-",
                inventoryProfitRate: "-",
                profitPerItem: 0,
                stockQuantity: 0,
                stockAmount: 0,
                remainQuantity: 0,
                remainAmount: 0,
                reviewStar: reviewNum ? self.createReviewStar(reviewAve) : '-----',
                reviewAve,
                reviewNum,
                reviewNumStr: reviewNum.toLocaleString(),
              });
            });
          }
          self.dispList = accountList;
          $.Vendor.WaitingDialog.hide();
          break;
        case "team":
          // 売上・在庫リストに存在するチームIDを格納
          const salesStockTeamIds = [];
          // 同じteamIdの売上額、利益額、利益率を計算し、リストで返す
          const teamList = self.score.map((item) => {
            salesStockTeamIds.push(item.teamId);
            const productCount = self.productCountList[item.teamId]?.productCount;
            const reviews = self.targetReviews[item.teamId];
            const reviewNum = reviews?.count ? Number(reviews.count) : 0;
            const reviewAve = reviewNum ? Math.floor(reviews.totalScore / reviewNum * 100) / 100 : '-';

            return {
              target: item.teamName,
              productCount: productCount ? Number(productCount).toLocaleString() : 0,
              salesAmount: item.salesAmount.toLocaleString(),
              profitAmount: item.profitAmount.toLocaleString(),
              salesProfitRate:
                item.salesAmount === 0
                  ? "-"
                  : ((item.profitAmount / item.salesAmount) * 100).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
              shoplistSalesAmount: item.shoplistSalesAmount.toLocaleString(),
              shoplistProfitAmount: item.shoplistProfitAmount.toLocaleString(),
              shoplistSalesProfitRate:
                item.shoplistSalesAmount === 0
                  ? "-"
                  : ((item.shoplistProfitAmount / item.shoplistSalesAmount) * 100).toLocaleString(
                    undefined,
                    {
                      minimumFractionDigits: 1,
                      maximumFractionDigits: 1,
                    }
                  ),
              inventoryProfitRate:
                item.stockAmount === 0
                  ? "-"
                  : (((item.profitAmount + item.shoplistProfitAmount) / item.stockAmount) * 100).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
              profitPerItem:
                productCount === 0
                  ? "-"
                  : ((item.profitAmount + item.shoplistProfitAmount) / productCount).toLocaleString(undefined, {
                    minimumFractionDigits: 1,
                    maximumFractionDigits: 1,
                  }),
              stockQuantity: item.stockQuantity.toLocaleString(),
              stockAmount: item.stockAmount.toLocaleString(),
              remainQuantity: item.remainQuantity.toLocaleString(),
              remainAmount: item.remainAmount.toLocaleString(),
              reviewStar: reviewNum ? self.createReviewStar(reviewAve) : '-----',
              reviewAve,
              reviewNum,
              reviewNumStr: reviewNum.toLocaleString(),
            };
          });

          // 売上・在庫データが無く、期間内関連商品数のみ有るチームIDを取得
          if (self.productCountList) {
            const onlyProductCountTeamIds = Object.keys(self.productCountList).filter((id) => {
              return salesStockTeamIds.indexOf(id) == -1;
            });
            // 期間内関連商品数のみのデータを表示用リストに追加
            onlyProductCountTeamIds.forEach((id) => {
              const reviews = self.targetReviews[id];
              const reviewNum = reviews?.count ? Number(reviews.count) : 0;
              const reviewAve = reviewNum ? Math.floor(reviews.totalScore / reviewNum * 100) / 100 : '-';

              teamList.push({
                target: self.productCountList[id].teamName,
                productCount: self.productCountList[id].productCount,
                salesAmount: 0,
                profitAmount: 0,
                salesProfitRate: "-",
                shoplistSalesAmount: 0,
                shoplistProfitAmount: 0,
                shoplistSalesProfitRate: "-",
                inventoryProfitRate: "-",
                profitPerItem: 0,
                stockQuantity: 0,
                stockAmount: 0,
                remainQuantity: 0,
                remainAmount: 0,
                reviewStar: reviewNum ? self.createReviewStar(reviewAve) : '-----',
                reviewAve,
                reviewNum,
                reviewNumStr: reviewNum.toLocaleString(),
              });
            });
          }
          self.dispList = teamList;
          $.Vendor.WaitingDialog.hide();
          break;
        default:
          self.dispList = [];
          $.Vendor.WaitingDialog.hide();
          break;
      }
    },
    createReviewStar: function (score) {
      if (score === 5) {
        return '★★★★★';
      } else if (score >= 4) {
        return '★★★★☆';
      } else if (score >= 3) {
        return '★★★☆☆';
      } else if (score >= 2) {
        return '★★☆☆☆';
      } else if (score >= 1) {
        return '★☆☆☆☆';
      } else {
        return '☆☆☆☆☆';
      }
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
        .fail(function (stat) {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
        });
    },
    sortBy(key) {
      this.sortKey === key ? (this.sortDesc = !this.sortDesc) : (this.sortDesc = true);
      this.sortKey = key;
    },
    changeTarget: function (typeId) {
      if (this.selectType === typeId) {
        return;
      }
      this.selectType = typeId;
      this.dispList = [];
      this.search();
    },
    getUserDetailUrl: function (userId) {
      // URL取得
      this.detailUrl = $(this.$el).data("detailUrl");
      const self = this;
      self.messageState.clear();
      return (
        self.detailUrl +
        "?userId=" +
        userId +
        "&stockDate=" +
        self.totalInfo.stockDate +
        "&targetDateFrom=" +
        self.dispSearchItem.targetDateFrom +
        "&targetDateTo=" +
        self.dispSearchItem.targetDateTo +
        "&selectTask=" +
        self.dispSearchItem.selectTask +
        "&applyStartDateFrom=" +
        self.dispSearchItem.applyStartDateFrom +
        "&applyStartDateTo=" +
        self.dispSearchItem.applyStartDateTo
      );
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
    updateSelectedUsers: function (type) {
      if (type === 'all') {
        this.selectedUsers = Object.keys(this.salesAccounts);
      }
      if (type === 'none') {
        this.selectedUsers = [];
      }
      if (type === 'default') {
        this.selectedUsers = this.defaultDisplayUsers;
      }
    }
  },
});
