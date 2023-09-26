const vmComponentImportabilityItem = {
  template: "#templateImportabilityListRow",
  props: ["index", "row"],
  data() {
    return this.row;
  },
  methods: {
    changeStatus(event) {
      this.status =
        this.status === event.target.value ? "0" : event.target.value;
      this.$emit("update", "status", this.daihyoSyohinCode, this.status);
    },
    changeNote(event) {
      if (event.value === this.note) {
        return;
      }
      this.$emit("update", "note", this.daihyoSyohinCode, this.note);
    },
    showEnlargedImage(index) {
      this.$refs.lightbox[0].showImage(index);
    },
  },
};

Vue.use(VueLazyload, {
  preLoad: 1.3,
  error: "https://dummyimage.com/130x120/ccc/999.png&text=Not+Found",
  loading: "https://dummyimage.com/130x120/dcdcdc/999.png&text=Now loading",
  attempt: 1,
});

const LightBox = window.Lightbox.default;
Vue.component("light-box", LightBox);

const setInventoryConstant = new Vue({
  el: "#importabilityList",
  data: {
    searchUrl: null,
    updateUrl: null,
    thumbnailUrl: null,
    messageState: {},
    statusList: STATUS_LIST,
    remainStatusList: REMAIN_STATUS_LIST,
    conditions: {
      daihyoSyohinCode: "",
      category: "",
      filterRemainStatusKeys: ["WAITED"],
      status: "",
      updateUserName: "",
      settingDateFrom: null,
      settingDateTo: null,
    },
    dispConditions: {}, // 現在表示している検索結果の条件
    list: [],
    maxImgDisplayPerColum: 8, // 1列の最大表示数

    paginationObj: {
      initPageItemNum: 20, // 1ページに表示する件数
      page: 1, // 現在ページ数
      itemNum: 0, // データ件数
    },
  },
  mounted() {
    const self = this;
    this.searchUrl = $(this.$el).data("searchUrl");
    this.updateUrl = $(this.$el).data("updateUrl");
    this.thumbnailUrl = $(this.$el).data("thumbnailUrl");

    this.messageState = new PartsGlobalMessageState();
    this.dispConditions = { ...this.conditions };

    $("#settingDateFrom", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate() {
          self.conditions.settingDateFrom = $(this).val();
        },
        clearDate() {
          self.conditions.settingDateFrom = null;
        },
      });
    $("#settingDateTo", this.$el)
      .datepicker({
        language: "ja",
        format: "yyyy-mm-dd",
        autoclose: true,
      })
      .on({
        changeDate() {
          self.conditions.settingDateTo = $(this).val();
        },
        clearDate() {
          self.conditions.settingDateTo = null;
        },
      });

    $("#filterRemainStatusKeys", this.$el).on(
      "changed.bs.select",
      self.remainStatusSelectChanged
    );
  },

  components: {
    "result-item": vmComponentImportabilityItem, // 一覧テーブル
  },

  computed: {},

  methods: {
    remainStatusSelectChanged() {
      this.filterRemainStatusKeys = $("#filterRemainStatusKeys").val();
    },

    search: function (reset = false) {
      $.Vendor.WaitingDialog.show("loading ...");

      const self = this;
      self.messageState.clear();
      self.list = [];

      const dateList = [
        self.conditions.settingDateFrom,
        self.conditions.settingDateTo,
      ];
      const regex = /^[0-9]{4}\-(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/;
      for (i = 0; i < dateList.length; i++) {
        if (dateList[i]) {
          if (!regex.test(dateList[i])) {
            self.messageState.setMessage(
              `日付の入力が正しくありません [${dateList[i]}]`,
              "alert alert-danger"
            );
            $.Vendor.WaitingDialog.hide();
            return;
          }
        }
      }

      if (reset) {
        self.paginationObj.page = 1;
      }
      self.conditions.includeShipmentWaited = self.conditions
        .includeShipmentWaited
        ? 1
        : 0;
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          conditions: self.conditions,
          paginationObj: self.paginationObj,
        },
      })
        .done((result) => {
          if (result.status === "ok") {
            self.paginationObj.itemNum = result.count;
            if (result.count === 0) {
              self.messageState.setMessage(
                "該当する商品はありません",
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
        .fail(() => {
          self.messageState.setMessage(
            "エラーが発生しました",
            "alert alert-danger"
          );
        })
        .always(() => {
          self.dispConditions = { ...self.conditions };

          $.Vendor.WaitingDialog.hide();
        });
    },

    changePage(pageInfo) {
      this.paginationObj.page = pageInfo.page;
      this.conditions = { ...this.dispConditions };
      this.search();
    },

    formatList() {
      const self = this;
      self.list = self.list.map((item) => {
        const allField = [
          item.field1,
          item.field2,
          item.field3,
          item.field4,
          item.field5,
        ];
        const displayFields = allField.filter((field) => field);
        item.category = displayFields.join(" / ");

        item.thumbList = item.thumbList.map((thumb) => {
          return self.thumbnailUrl.replace("/dir/file", thumb);
        });

        // ライブラリvue-image-lightbox用
        item.images = [];
        for (let i = 0; i < item.srcList.length; i++) {
          item.images.push({
            thumb: item.thumbList[i],
            src: item.srcList[i],
            caption: `キャプチャ${i + 1}`,
          });
        }
        return item;
      });
    },

    update(target, code, value) {
      const self = this;
      self.messageState.clear();

      const conditions = {
        daihyoSyohinCode: code,
        value: value,
      };

      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: {
          target: target,
          conditions: conditions,
        },
      })
        .done((result) => {
          if (result.status !== "ok") {
            alert(message);
            const message = result.message
              ? result.message
              : "更新処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(() => {
          alert("エラーが発生しました");
          self.messageState.setMessage(
            "エラーが発生しました",
            "alert alert-danger"
          );
        })
        .always(() => {
          self.dispConditions = { ...self.conditions };

          $.Vendor.WaitingDialog.hide();
        });
    },
  },
});
