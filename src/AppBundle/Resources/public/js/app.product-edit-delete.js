const productDelete = new Vue({
  el: "#productDelete",
  data: {
    daihyoSyohinCode: null,
    productDeleteInfo: {},
    product: {},
    searchUrl: null,
    executeUrl: null,
    messageState: {},
  },
  mounted: function () {
    const self = this;
    // URL取得
    self.searchUrl = $(self.$el).data("searchUrl");
    self.executeUrl = $(self.$el).data("executeUrl");

    self.messageState = new PartsGlobalMessageState();

    // codeパラメータが有る場合、そのコードで検索
    const code = new URL(document.location).searchParams.get("code");
    if (code != undefined) {
      self.daihyoSyohinCode = code;
      self.search();
    }
  },

  computed: {
    isDeletable: function() {
      return this.productDeleteInfo.canDelete;
    }, 
    errorMessageStock: function() {
      return this.productDeleteInfo.stock ? "総在庫が存在するため削除できません。在庫・移動伝票を確認してください。" : "-";
    },
    errorMessageRemain: function() {
      return this.productDeleteInfo.remain ? "注残が存在するため削除できません" : "-";
    },
    errorMessageSales: function() {
      return this.productDeleteInfo.sales ? "受注履歴が存在するため削除できません　（発送済みやキャンセルも履歴に含みます）" : "-";      
    },
    errorMessageSetProduct: function() {
      if (!this.productDeleteInfo.setProduct
          || (Array.isArray(this.productDeleteInfo.setProduct) && this.productDeleteInfo.setProduct.length === 0)) {
        return '-';
      }
      return "以下のセット商品に含まれているため、削除できません。<br/>[" + this.productDeleteInfo.setProduct.join(',') + "]"; 
    },
    errorMessageSalesProductAccount: function() {
      return this.productDeleteInfo.productSalesAccount ? "商品売上担当者が存在します。削除してください　（適用期間外でも、有効な状態では商品を削除できません）" : "-";   
    },
    errorMessageDeleteExcluded: function() {
      return this.productDeleteInfo.deleteExcluded ? "削除除外商品に登録されているため削除できません" : "-";   
    },    
    warningMessageOtoriyose: function() {
      return this.productDeleteInfo.otoriyose ? "おとりよせで販売中です。削除は可能ですが、先に販売停止しないと、入れ違いで受注が入る可能性があります。" : "-";   
    },
  },

  methods: {
    search: function () {
      $.Vendor.WaitingDialog.show("loading ...");
      const self = this;
      self.messageState.clear();
      self.productDeleteInfo = {};
      self.product = {};
      $.ajax({
        type: "GET",
        url: self.searchUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode,
        },
      })
        .done(function (result) {
          if (result.status == "ok") {
            window.history.pushState(null, null, window.location.pathname + "?code=" + encodeURIComponent(self.daihyoSyohinCode));
            if (!result.productDeleteInfo.product) {
              self.messageState.setMessage(
                "商品データが取得できませんでした",
                "alert alert-warning"
              );
              return;
            }
            self.productDeleteInfo = result.productDeleteInfo;
            self.product = self.productDeleteInfo.product;
            self.daihyoSyohinCode = self.product.daihyoSyohinCode;
            self.product.genkaTnk = self.product.genkaTnk ? self.product.genkaTnk.toLocaleString() : '';
            self.product.baikaTnk = self.product.baikaTnk ? self.product.baikaTnk.toLocaleString() : '';
          } else {
            const message = result.message ? result.message : "検索処理でエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました", "alert alert-danger");
        })
        .always(function() {
          $.Vendor.WaitingDialog.hide();
        });
    },
    
    executeDelete: function() {
      if (!confirm('この商品を削除してよろしいですか？')) {
        return;
      }
      
      $.Vendor.WaitingDialog.show("Deleting ...");
      const self = this;
      $.ajax({
        type: "POST",
        url: self.executeUrl,
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode,
        },
      })
        .done(function (result) {
          if (result.status == "ok") {
            // 削除できたことが確認できれば、商品情報は削除
            self.productDeleteInfo = {};
            self.product = {};

            // 結果を整形
            let snapshotMsg = "\n\n[スナップショットレコード数]\n";
            for (let key in result.snapshot) {
              if (result.snapshot.hasOwnProperty(key)) {
                snapshotMsg += key + ': ' + result.snapshot[key] + "\n";
              }
            }
            let deleteMsg = "\n\n[削除レコード数]\n";
            for (let key in result.delete) {
              if (result.delete.hasOwnProperty(key)) {
                deleteMsg += key + ': ' + result.delete[key] + "\n";
              }
            }
          
            self.messageState.setMessage(
              "削除しました。" + snapshotMsg + deleteMsg,
              "alert alert-success multiLine"
            );
          } else {
            const message = result.message ? result.message : "エラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました", "alert alert-danger");
        })
        .always(function() {
          $.Vendor.WaitingDialog.hide();
        });
    }
  },
});
