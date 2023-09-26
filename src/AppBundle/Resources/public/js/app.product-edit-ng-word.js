const setInventoryConstant = new Vue({
  el: "#product_ng_word_list",
  data: {
    indexUrl: null,
    searchUrl: null,
    createUrl: null,
    updateUrl: null,
    deleteUrl: null,
    messageState: {},
    list: [],
    list_first_half: [],
    content: null,
    keyword: null,
    sortKey: null,
    sortVal: null,
    paginationObj: {
      initPageItemNum: 100, // 1ページに表示する件数
      page: 1, // 現在ページ数
      itemNum: 0, // データ件数
    },
  },

  mounted: function () {
    const self = this;
    self.indexUrl = $(self.$el).data("indexUrl");
    self.searchUrl = $(self.$el).data("searchUrl");
    self.updateUrl = $(self.$el).data('updateUrl');
    self.createUrl = $(self.$el).data('createUrl');
    self.deleteUrl = $(self.$el).data('deleteUrl');
    

    this.messageState = new PartsGlobalMessageState();

    $("#content", this.$el).on(
      "changed.bs.select",
      self.contentChanged
    );

    const params = new URL(document.location).searchParams;
    self.keyword = params.get("keyword");
    self.sortKey = params.get("sortKey");
    self.sortVal = params.get("sortVal");
    self.paginationObj.page = Number(params.get("page"));
    self.paginationObj.page = self.paginationObj.page > 0 ? self.paginationObj.page : 1;
    self.search();
  },

  computed: {
    list_half_count () {
      return  Math.trunc((this.list.length + 1) / 2);
    },
    base_num () {
      return (this.paginationObj.page - 1) * this.paginationObj.initPageItemNum
    },
  },

  methods: {
    contentChanged: function () {
      this.keyword = $("#keyword").val();
    },

    create: function () {
      const self = this;

      self.messageState.clear();
      if(self.content == "") {
        self.messageState.setMessage("NGワードを入力してください。", "alert alert-danger")
      }
      $.Vendor.WaitingDialog.show("loading ...");

      
      $.ajax({
        type: "POST",
        url: self.createUrl,
        dataType: "json",
        data: {
          content: self.content
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.messageState.setMessage(
              "NGワードを登録しました。",
              "alert alert-success"
            );
            self.sortKey = null
            self.sortVal = null
            self.keyword = null
            self.search(true)
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

    edit: function(item_index) {
      const self = this;
      self.list = self.list.map((item, index) => {
        if(index == item_index) {
          return {
            ...item,
            edit: true,
          }
        } else {
          return item
        }
      })
    },

    update: function(item_index) {
      const self = this;

      $.Vendor.WaitingDialog.show("loading ...");

      self.messageState.clear();
      const target_item = this.list[item_index]

      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: {
          id: target_item.id,
          content: target_item.content,
        },
      })
      .done(function (result) {
        if (result.status === "ok") {
          self.list = self.list.map((item, index) => {
            if(index == item_index) {
              return {
                ...item,
                edit: false,
              }
            } else {
              return item
            }
          })
          self.messageState.setMessage(
            "NGワードを更新しました。",
            "alert alert-success"
          );
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

    remove: function(item_index) {
      const self = this;

      $.Vendor.WaitingDialog.show("loading ...");

      self.messageState.clear();
      const target_item = this.list[item_index]

      $.ajax({
        type: "POST",
        url: self.deleteUrl,
        dataType: "json",
        data: {
          id: target_item.id,
        },
      })
      .done(function (result) {
        if (result.status === "ok") {
          self.messageState.setMessage(
            "NGワードを削除しました。",
            "alert alert-success"
          );
          self.search()
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

    search: function (reset = false) {
      const self = this;

      $.Vendor.WaitingDialog.show("loading ...");

      self.messageState.clear();
      self.list = [];

      if (reset) {
        self.paginationObj.page = 1;
      }
      console.log('self.searchUrl', self.searchUrl);
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          keyword: self.keyword,
          paginationObj: self.paginationObj,
          sortKey: self.sortKey,
          sortVal: self.sortVal,
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
            console.log('result', result);
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
          self.createParameter();
          $.Vendor.WaitingDialog.hide();
        });
    },
    createParameter() {
      const params = [];
      if(this.keyword) params.push("keyword=" + this.keyword);
      if(this.sortKey) params.push("sortKey=" + this.sortKey);
      if(this.sortVal) params.push("sortVal=" + this.sortVal);
      params.push("page=" + this.paginationObj.page);
      if (params.length > 0) {
        history.pushState({}, "", this.indexUrl + "?" + params.join("&"));
      }
    },
    sortBy(key) {
      switch(this.sortVal) {
        case 'DESC':
          this.sortVal = 'ASC';
          break;
        case 'ASC':
          this.sortVal = null;
          break;
        case null:
          this.sortVal = 'DESC';
          break;
      }
      this.sortKey = key;
      // 複数件検索結果がある場合のみ、データ再取得
      // 1件以下の時は、次回検索ボタン押下の時のソート予約のみ
      if (this.paginationObj.itemNum > 1) {
        this.search();
      }
    },
    addSortArrow: function (key) {
      return {
        asc: this.sortVal == 'ASC',
        desc: this.sortVal == 'DESC',
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
