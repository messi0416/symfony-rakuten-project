var mainProductsInfo = new Vue({
  el: '#mainProductsInfo',
  data: {
    infoUrl: null,
    searchUrl: null,
    list: [],
    columns: [],
    limit: 20,
    conditions: {
      daihyoSyohinCode: "",
    },
    dispConditions: {}, // 現在表示している検索結果の条件
    paginationObj: {
      initPageItemNum: 20, // 1ページに表示する件数
      page: 1, // 現在ページ数
      itemNum: 0, // データ件数
    },
    messageState: new PartsGlobalMessageState()
  },
  mounted: function() {
    this.$nextTick(function () {
      if(MAIN_PRODUCTS_INFO){
        this.columns = Object.keys(MAIN_PRODUCTS_INFO[0]);
        for(let i in MAIN_PRODUCTS_INFO){
          let row = MAIN_PRODUCTS_INFO[i];
          this.list.push(row);
        }
      }
    });

    const self = this;
    this.infoUrl = $(this.$el).data("infoUrl");
    this.searchUrl = $(this.$el).data("searchUrl");

    this.paginationObj.itemNum = $(this.$el).data("count");
    this.messageState = new PartsGlobalMessageState();
    this.dispConditions = { ...this.conditions };

    // パラメータが有る場合、その条件で検索
    if (location.href.indexOf("?") === -1) {
      return;
    }
    const params = new URL(document.location).searchParams;
    for (let key in self.conditions) {
      let value = params.get(key);
      if (!value) {
        continue;
      }
      self.conditions[key] = value;
    }
    self.paginationObj.page = Number(params.get("page"));
    self.search();
  },
  computed: {
    paginationInfo: function () {
      return {
        ...this.paginationObj,
      };
    },
  },
  methods: {
    changePage: function(page){
      this.paginationObj.page = page;
      this.search();
    },
    search: function (reset = false) {
      $.Vendor.WaitingDialog.show("loading ...");

      const self = this;
      self.messageState.clear();
      self.list = [];

      if (reset) {
        self.paginationObj.page = 1;
      }
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          conditions: self.conditions,
          paginationObj: self.paginationObj,
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
    changePage: function (pageInfo) {
      this.paginationObj.page = pageInfo.page;
      this.conditions = { ...this.dispConditions };
      this.search();
    },
    createParameter() {
      const params = [];
      for (let [key, value] of Object.entries(this.dispConditions)) {
        if (value === "" || value === null) {
          continue;
        }
        params.push(key + "=" + value);
      }
      params.push("page=" + this.paginationObj.page);
      if (params.length > 0) {
        history.pushState({}, "", this.infoUrl + "?" + params.join("&"));
      }
    },
    scrollTop: function () {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    },
  }
});