/**
 * ロケーション詳細画面用 JS
 */
$(function () {
  // 一覧テーブル 行コンポーネント
  Vue.component("result-item", {
    template: "#result-item",
    props: ["item", "hasLocationData"],
    data: function () {
      return {
        neSyohinSyohinCode: this.item.neSyohinSyohinCode,
        stock: this.item.stock,
        rfidStock: this.item.rfidStock,
        diff: this.item.diff,
        image: this.item.image,
        isChecked: this.item.isChecked,
      };
    },
    computed: {
      rowCss: function () {
        return this.item.isChecked ? "success" : "";
      },
      productDetailUrl: function () {
        return (
          vmRfidLocationEditor.productDetailUrlBase +
          this.item.neSyohinSyohinCode
        );
      },
      displayDiff: function () {
        const displayDiff = {
          value: this.diff.toLocaleString(),
          css: "",
        };
        if (this.diff > 0) {
          displayDiff.value = "+" + displayDiff.value;
          displayDiff.css = "text-primary";
        }
        if (this.diff < 0) {
          displayDiff.css = "text-danger";
        }
        return displayDiff;
      },
    },
    mounted: function () {},
    methods: {
      toggleCheck: function () {
        this.item.isChecked = !this.item.isChecked;
      },
    },
  });

  /**
   * 商品ブロック
   */
  const vmRfidLocationEditor = new Vue({
    el: "#rfidLocationEditor",
    data: {
      readingId: null,
      locationCode: null,

      location: {},
      warehouse: {},
      hasLocationData: false,
      products: [],
      productsNum: 0,
      productsDetail: [],

      searchUrl: null,
      locationDetailUrl: null,
      updateUrl: null,
      productDetailUrlBase: null,

      hasError: false,
      errorMessage: "",
    },
    mounted: function () {
      this.searchUrl = $(this.$el).data("searchUrl");
      this.locationDetailUrl = $(this.$el).data("locationDetailUrl");
      this.updateUrl = $(this.$el).data("updateUrl");
      this.productDetailUrlBase = $(this.$el)
        .data("productDetailUrl")
        .replace(/DUMMY/, "");

      // URLパラメータチェック
      const params = new URL(document.location).searchParams;
      this.readingId = params.get("readingId");
      this.locationCode = params.get("locationCode");
      if (this.readingId === null) {
        this.setError("読取IDが指定されていません");
      } else {
        this.search();
      }
    },
    computed: {
      checkedProductsNum: function () {
        return this.products.reduce(function (result, item) {
          return result + (item.isChecked ? 1 : 0);
        }, 0);
      },
      checkedStockTotal: function () {
        return this.products.reduce(function (result, item) {
          return result + (item.isChecked ? item.stock : 0);
        }, 0);
      },
      checkedRfidStockTotal: function () {
        return this.products.reduce(function (result, item) {
          return result + (item.isChecked ? item.rfidStock : 0);
        }, 0);
      },
      locationFormStr: function () {
        return this.hasLocationData ? "変更" : "指定";
      },
    },
    methods: {
      search: function () {
        const self = this;

        // 初期化
        self.locationDetailUrl = $(this.$el).data("locationDetailUrl");
        self.location = {};
        self.warehouse = {};
        self.hasLocationData = false;
        self.products = [];
        self.productsNum = 0;
        self.productsDetail = [];

        // URLパラメータ追加
        let url = new URL(location.href);
        let params = new URLSearchParams(url.search);
        params.set('readingId', self.readingId);
        if (self.locationCode) {
          params.set('locationCode', self.locationCode);
        } else {
          params.delete('locationCode');
        }
        // パラメータをURLにセット
        url.search = params.toString();
        history.pushState({}, '', url.toString());

        // 検索処理
        $.Vendor.WaitingDialog.show("検索中 ...");

        self.clearError();

        $.ajax({
          type: "POST",
          url: self.searchUrl,
          dataType: "json",
          data: {
            readingId: self.readingId,
            locationCode: self.locationCode,
          },
        })
          .done(function (result) {
            if (result.status == "ok") {
              if (result.locationData?.location) {
                self.location = result.locationData.location;
                self.warehouse = result.locationData.warehouse;

                self.locationDetailUrl = self.locationDetailUrl.replace(
                  /DUMMY/,
                  self.location.id
                );
                self.hasLocationData = true;
              }
              self.products = result.products.map((item) => {
                return {
                  neSyohinSyohinCode: item.neSyohinSyohinCode.trim(),
                  stock: Number(item.stock),
                  rfidStock: Number(item.rfidStock),
                  diff: Number(item.diff),
                  image: item.image,
                  isChecked: false,
                };
              });
              self.productsNum = self.products.length;
              self.productsDetail = result.productsDetail;
            } else {
              self.setError(result.message);
            }
          })
          .fail(function (stat) {
            console.log(stat);
            self.setError("エラー：検索に失敗しました。");
          })
          .always(function () {
            $.Vendor.WaitingDialog.hide();
          });
      },

      updateStocks: function () {
        $.Vendor.WaitingDialog.show("更新中 ...");
        const self = this;

        const targets = self.products.filter((product) => {
          return product.isChecked === true && product.diff !== 0;
        });

        const targetsLength = targets.length;
        let i = 1;
        targets.forEach((target) => {
          const sku = target.neSyohinSyohinCode;
          const rfidStock = target.rfidStock;

          let isUpdated = false;
          const locations = self.productsDetail[sku].locations.map(
            (location) => {
              if (location.locationId === self.location.id) {
                location.stock = rfidStock;
                location.hasError = false;
                
                isUpdated = true;
              }
              delete location.created;
              delete location.updated;
              return location;
            }
          );
          // 既存が在庫0なら追加
          if (!isUpdated) {
            locations.push({
              neSyohinSyohinCode: sku,
              warehouseId: self.warehouse.id,
              locationId: self.location.id,
              locationCode: self.location.locationCode,
              stock: rfidStock,
              position: Math.max(...locations.map(location => location.position)) + 1,
              hasError: false,
            });
          }
          const data_hash = self.productsDetail[sku].dataHash;
          let comment = "RFID読取結果との数量差異";
          comment += target.diff > 0 ? "（増加）" : "（減少）";

          self.updateUrl = $(this.$el).data("updateUrl").replace(/DUMMY/, sku);

          $.ajax({
            type: "POST",
            url: self.updateUrl,
            dataType: "json",
            data: {
              locations,
              data_hash,
              comment,
            },
          })
            .done(function (result) {
              if (result.status == "ok") {
                window.location.href = result.redirect; // リダイレクト
              } else {
                self.setError(result.message, "alert-danger");
                $.Vendor.WaitingDialog.hide();
                return;
              }
            })
            .fail(function (stat) {
              console.log(stat);
              self.setError("エラー：更新に失敗しました。", "alert-danger");
              $.Vendor.WaitingDialog.hide();
              return;
            })
            .always(function () {
              if (i >= targetsLength) {
                $.Vendor.WaitingDialog.hide();
                // ロケーション詳細画面へリダイレクト
                window.location.href = self.locationDetailUrl;
              }
              i++;
            });
        });
      },

      /// 全て選択
      checkAll: function () {
        for (let i = 0; i < this.products.length; i++) {
          this.products[i].isChecked = true;
        }
      },

      /// 全て選択解除
      unCheckAll: function () {
        for (let i = 0; i < this.products.length; i++) {
          this.products[i].isChecked = false;
        }
      },

      reloadPage: function () {
        location.reload();
      },

      /// エラーセット
      setError: function (message) {
        this.errorMessage = message;
        this.hasError = message.length != 0;
      },

      /// エラーリセット
      clearError: function () {
        this.errorMessage = "";
        this.hasError = false;
      },
    },
  });
});
