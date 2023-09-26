/**
 * 売れ筋ランキング(新)画面 JS
 */

const salesRankingTable = {
  template: "#salesRankingTable",
  props: ["item"],
  computed: {
    displayGenkaTanka: function () {
      return $.Plusnao.String.numberFormat(this.item.genkaTanka);
    },
    displayBaikaTanka: function () {
      return $.Plusnao.String.numberFormat(this.item.baikaTanka);
    },
    displayItemNum: function () {
      return $.Plusnao.String.numberFormat(this.item.itemNum);
    },
    displayVoucherNum: function () {
      return $.Plusnao.String.numberFormat(this.item.voucherNum);
    },
    displaySalesAmount: function () {
      return $.Plusnao.String.numberFormat(this.item.salesAmount);
    },
    displayItemNumA: function () {
      return $.Plusnao.String.numberFormat(this.item.itemNumA);
    },
    displayVoucherNumA: function () {
      return $.Plusnao.String.numberFormat(this.item.voucherNumA);
    },
    displaySalesAmountA: function () {
      return $.Plusnao.String.numberFormat(this.item.salesAmountA);
    },
    displayReviewNum: function () {
      return $.Plusnao.String.numberFormat(this.item.reviewNum);
    },
    displayReviewPointAverage: function () {
      if (this.item.reviewPointAverage == 0) {
        return "-";
      }
      return $.Plusnao.String.numberFormat(this.item.reviewPointAverage, 2);
    },
  },
  methods: {
    /**
     * レビュー一覧 表示ポップアップ
     */
    openProductReviewListModal: function () {
      vmProductReviewList.open(this.item.daihyoSyohinCode);
    },

    /**
     * 大カテゴリ選択変更
     */
    changeSelectBigCategory: function (bigCategory) {
      this.$emit("select-big-category", bigCategory);
    },

    /**
     * 中カテゴリ選択変更
     */
    changeSelectMidCategory: function (bigCategory, midCategory) {
      this.$emit("select-mid-category", bigCategory, midCategory);
    },
  },
};

/** メイン画面 */
const salesRanking = new Vue({
  el: "#salesRanking",
  data: {
    keyword: "",
    userId: "",
    bigCategory: "",
    midCategory: "",
    rankingTarget: "sales_amount",
    dateAStart: null, // 比較期間
    dateAEnd: null, // 比較期間
    dateBStart: null, // 取得期間
    dateBEnd: null, // 取得期間
    moveDays: 7,

    salesRankingList: [],
    totalItemNum: 0,
    totalVoucherNum: 0,
    totalSalesAmount: 0,
    totalItemNumA: 0,
    totalVoucherNumA: 0,
    totalSalesAmountA: 0,
    // 並び順指定
    sortField: "displayOrder",
    sortReverse: false,
    sortMarks: {
      rank: { cssClass: "sortable sortFree" },
      daihyoSyohinCode: { cssClass: "sortable sortFree" },
      thumbnail: { cssClass: "sortable sortFree" },
      genkaTanka: { cssClass: "sortable sortFree" },
      baikaTanka: { cssClass: "sortable sortFree" },
      itemNum: { cssClass: "sortable sortFree" },
      voucherNum: { cssClass: "sortable sortFree" },
      salesAmount: { cssClass: "sortable sortFree" },
      itemNumA: { cssClass: "sortable sortFree" },
      voucherNumA: { cssClass: "sortable sortFree" },
      salesAmountA: { cssClass: "sortable sortFree" },
      reviewNum: { cssClass: "sortable sortFree" },
      reviewPointAverage: { cssClass: "sortable sortFree" },
    },

    messageState: {},
    searchUrl: null,

    // バイヤー名取得処理を占有させる  すべての期間を一度に変更すると複数回処理が走るため
    exclusiveLock: 0,
    // カテゴリプルダウン
    allCategories: {},
    bigCategories: [],
    midCategories: [],
    // 商品売上担当者プルダウン
    productSalesAccounts: {},
  },
  computed: {
    displaySalesRankingList: function () {
      const list = [...this.salesRankingList];
      const sortField = this.sortField;
      const sortReverse = this.sortReverse;
      return list.sort(function (a, b) {
        if (sortReverse) {
          return a[sortField] < b[sortField] ? 1 : -1;
        } else {
          return a[sortField] < b[sortField] ? -1 : 1;
        }
      });
    },
    displayTotalItemNum: function () {
      return $.Plusnao.String.numberFormat(this.totalItemNum);
    },
    displayTotalVoucherNum: function () {
      return $.Plusnao.String.numberFormat(this.totalVoucherNum);
    },
    displayTotalSalesAmount: function () {
      return $.Plusnao.String.numberFormat(this.totalSalesAmount);
    },
    displayTotalItemNumA: function () {
      return $.Plusnao.String.numberFormat(this.totalItemNumA);
    },
    displayTotalVoucherNumA: function () {
      return $.Plusnao.String.numberFormat(this.totalVoucherNumA);
    },
    displayTotalSalesAmountA: function () {
      return $.Plusnao.String.numberFormat(this.totalSalesAmountA);
    },
  },
  components: {
    "sales-ranking-table": salesRankingTable,
  },
  created: function () {
    this.bigCategories = ViewVarBigCategories;
    this.bigCategories.unshift("");

    this.allCategories = ViewVarCategories;
    this.productSalesAccounts = ViewVarProductSalesAccounts;
    this.midCategories = [];
  },
  mounted: function () {
    this.searchUrl = $(this.$el).data("searchUrl");
    this.messageState = new PartsGlobalMessageState();
    this.selectOneWeek();
    // select2だけ、selectタグにCSS当てても字が灰色にならないようなので、直接初期色を灰色に。
    document.querySelector("#select2-productSalesAccount-container").style.color = '#999';
  },
  methods: {
    // select2で、初めて選択した時点で文字が黒色になるように設定
    // data-placeholderを使う方法もあるようだが、他とUIを合わせるためJSで設定。
    selectUserFirst: function () {
      document.querySelector("#select2-productSalesAccount-container").style.color = '#555';
    },
    // 中カテゴリ絞込
    updateMidCategories: function () {
      const deferred = new $.Deferred();

      // リセット
      this.midCategory = "";
      this.midCategories = [];

      if (this.bigCategory.length == 0) {
        return;
      }

      this.midCategories = this.allCategories[this.bigCategory].map((category) => {
        return category.mid_category;
      });

      // DOM更新の完了を待ってresolve
      Vue.nextTick(() => {
        deferred.resolve();
      });

      deferred.promise();
      return deferred;
    },

    searchAtBigCategory: function (bigCategory) {
      this.bigCategory = bigCategory;
      this.updateMidCategories();
      this.search();
    },

    searchAtMidCategory: function (bigCategory, midCategory) {
      this.bigCategory = bigCategory;
      this.updateMidCategories();
      this.midCategory = midCategory;
      this.search();
    },

    resetData: function () {
      this.salesRankingList = [];
      this.totalItemNum = 0;
      this.totalVoucherNum = 0;
      this.totalSalesAmount = 0;
      this.totalItemNumA = 0;
      this.totalVoucherNumA = 0;
      this.totalSalesAmountA = 0;
    },

    search: function () {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      self.resetData();
      self.messageState.clear();
      const requestData = {
        dateBStart: self.dateBStart,
        dateBEnd: self.dateBEnd,
        dateAStart: self.dateAStart,
        dateAEnd: self.dateAEnd,
        userId: self.userId,
        rankingTarget: self.rankingTarget,
        bigCategory: self.bigCategory,
        midCategory: self.midCategory,
        keyword: self.keyword,
      };
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          requestData,
        },
      })
        .done((result) => {
          if (result.status === "ok") {
            self.formatSalesRankingList(result.salesRankingList);
          } else {
            const message = result.message
              ? result.message
              : "検索処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail((result) => {
          self.messageState.setMessage(result.message, "alert alert-danger");
        })
        .always(() => {
          $.Vendor.WaitingDialog.hide();
        });
    },

    formatSalesRankingList: function (list) {
      const self = this;
      // とりあえず配列に変換しなければ始まらない
      for (const data of Object.values(list)) {
        self.salesRankingList.push({
          rank: Number(data.rank),
          daihyoSyohinCode: data.daihyo_syohin_code,
          daihyoSyohinName: data.daihyo_syohin_name,
          thumbnail: data.thumbnail,
          genkaTanka: Number(data.genka_tanka),
          baikaTanka: Number(data.baika_tanka),
          itemNum: Number(data.item_num),
          voucherNum: Number(data.voucher_num),
          salesAmount: Number(data.sales_amount),
          itemNumA: Number(data.item_num_a),
          voucherNumA: Number(data.voucher_num_a),
          salesAmountA: Number(data.sales_amount_a),
          reviewNum: Number(data.review_num),
          reviewPointAverage: Number(data.review_point_average),
          bigCategory: data.big_category,
          midCategory: data.mid_category,
          sireCode: data.sire_code,
          sireName: data.sire_name,

          detailUrl: data.detail_url,
          analyzeUrl: data.analyze_url,
          displayOrder: Number(data.rank),
        });
      }
      self.calcTotalValue();
    },

    calcTotalValue: function () {
      const self = this;
      self.salesRankingList.forEach(function (row) {
        self.totalItemNum += row.itemNum;
        self.totalVoucherNum += row.voucherNum;
        self.totalSalesAmount += row.salesAmount;
        self.totalItemNumA += row.itemNumA;
        self.totalVoucherNumA += row.voucherNumA;
        self.totalSalesAmountA += row.salesAmountA;
      });
    },

    /**
     * 一覧 並び順変更
     * @param fieldName
     */
    switchSort: function (fieldName) {
      // 現在のマークを削除
      if (this.sortMarks[this.sortField] != undefined) {
        this.sortMarks[this.sortField].cssClass = "sortable sortFree";
      }

      if (this.sortField == fieldName) {
        // 降順 -> 昇順
        if (this.sortReverse == true) {
          this.sortReverse = false;

        // デフォルトに戻る
        } else {
          this.sortField = "displayOrder";
          this.sortReverse = false;
        }
      } else {
        this.sortField = fieldName;
        this.sortReverse = true; // 降順が先
      }

      // 新しいマークを表示
      if (this.sortMarks[this.sortField] != undefined) {
        this.sortMarks[this.sortField].cssClass = this.sortReverse
          ? "sortable sortDesc"
          : "sortable sortAsc";
      }
    },

    /**
     * 日付選択
     */
    /// 7日前 ～ 前日, 14日前 ～ 8日前
    selectOneWeek: function () {
      this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7));
      this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

      this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -14));
      this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -8));

      this.moveDays = 7; // 初期値
    },
    /// 30日前 ～ 前日, 60日前 ～ 31日前 ※日数を各月同じにするために、30日固定とする
    selectOneMonth: function () {
      this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -30));
      this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

      this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -60));
      this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -31));

      this.moveDays = 30; // 初期値
    },
    /// 1年前 ～ 前日, 2年前 ～ １年前 ※こちらも365日固定
    selectOneYear: function () {
      this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -365));
      this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

      this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -730));
      this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -366));

      this.moveDays = 30; // 初期値
    },
    /// 日付範囲移動
    moveDayRange: function (direction, days) {
      if (!days) {
        days = direction == "backward" ? -this.moveDays : this.moveDays;
      }

      this.dateBStart = $.Plusnao.Date.getDateString(
        $.Plusnao.Date.getAddDate(new Date(this.dateBStart), days)
      );
      this.dateBEnd = $.Plusnao.Date.getDateString(
        $.Plusnao.Date.getAddDate(new Date(this.dateBEnd), days)
      );
      this.dateAStart = $.Plusnao.Date.getDateString(
        $.Plusnao.Date.getAddDate(new Date(this.dateAStart), days)
      );
      this.dateAEnd = $.Plusnao.Date.getDateString(
        $.Plusnao.Date.getAddDate(new Date(this.dateAEnd), days)
      );
    },

    /// 同月比較
    selectCompareSameMonth: function (month) {
      const dateMonth = month - 1; // Dateで利用するので最初から1引いておく。

      const now = new Date();
      let targetMonthStart, targetMonthEnd, compareMonthStart, compareMonthEnd;

      // まだ過ぎていなければ 去年の月が基準
      if (now.getMonth() <= dateMonth) {
        targetMonthStart = new Date(now.getFullYear() - 1, dateMonth, 1);
      // 過ぎていれば今年の月が基準
      } else {
        targetMonthStart = new Date(now.getFullYear(), dateMonth, 1);
      }
      targetMonthEnd = new Date(
        targetMonthStart.getFullYear(),
        targetMonthStart.getMonth() + 1,
        0
      );

      compareMonthStart = new Date(
        targetMonthStart.getFullYear() - 1,
        targetMonthStart.getMonth(),
        1
      );
      compareMonthEnd = new Date(
        compareMonthStart.getFullYear(),
        compareMonthStart.getMonth() + 1,
        0
      );

      this.dateBStart = $.Plusnao.Date.getDateString(targetMonthStart);
      this.dateBEnd = $.Plusnao.Date.getDateString(targetMonthEnd);

      this.dateAStart = $.Plusnao.Date.getDateString(compareMonthStart);
      this.dateAEnd = $.Plusnao.Date.getDateString(compareMonthEnd);

      this.moveDays = 365;
    },
  },
});

const reviewItem = {
  template: "#review-item",
  props: ["item"],
};

/**
 * レビュー一覧情報表示ポップアップ
 */
const vmProductReviewList = new Vue({
  el: "#modalProductReviewList",
  data: {
    caption: "",
    message: "",
    messageClass: "info",
    url: null,
    list: [],

    nowLoading: true,
    /* ソートする時は利用。今はPHPから取得したままでよい
    sortField: 'id',
    sortReverse: false,
    */
  },
  mounted: function () {
    const self = this;
    self.url = $(self.$el).data("url");

    // イベント登録
    $(self.$el).on("show.bs.modal", function (e) {
      self.resetDialog();
      self.nowLoading = true;

      const daihyoSyohinCode = e.relatedTarget.daihyoSyohinCode;
      if (!daihyoSyohinCode) {
        self.message = "レビュー情報を取得できませんでした。";
        self.messageClass = "alert alert-danger";
        return;
      }

      self.caption = "[" + daihyoSyohinCode + "]" + " レビュー一覧";

      $.ajax({
        type: "GET",
        url: self.url,
        dataType: "json",
        data: { daihyo_syohin_code: daihyoSyohinCode },
      })
        .done(function (result) {
          if (result && result.length > 0) {
            for (let i in result) {
              const data = result[i];
              self.list.push({
                id: data.id,
                reviewType: data.review_type,
                productName: data.product_mame,
                reviewUrl: data.review_url,
                point: data.point,
                postDatetime: data.post_datetime,
                title: data.title,
                review: data.review,
                flag: data.flag,
                orderNumber: data.order_number,
                daihyoSyohinCode: data.daihyo_syohin_code,
                orderDatetime: data.order_datetime,
              });
            }
          } else {
            self.message = "レビュー情報が取得できませんでした。";
            self.messageClass = "alert alert-danger";
          }
        })
        .fail(function () {
          self.message = "レビュー情報が取得できませんでした。";
          self.messageClass = "alert alert-danger";
        })
        .always(function () {
          self.nowLoading = false;
        });
    });
  },
  components: {
    "review-item": reviewItem,
  },
  methods: {
    open: function (daihyoSyohinCode, callbackSuccess) {
      this.callbackSuccess = callbackSuccess;

      self.nowLoading = true;
      $(this.$el).modal("show", { daihyoSyohinCode: daihyoSyohinCode });
    },

    resetDialog: function () {
      this.$data.message = "";
      this.$data.messageClass = "";

      this.list = [];
    },
  },
});
