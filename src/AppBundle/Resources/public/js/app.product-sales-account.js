// 一覧画面 一覧テーブル 行コンポーネント
const templateProductSalesAccountListItem = {
  template: '#templateProductSalesAccountListTableRow',
  props: [
    'item',
    'index'
  ],
  mounted: function () {
  },
  methods: {
    // 削除
    deleteAccount: function () {
      const self = this;
      if (window.confirm('削除しても良いですか？')) {
        productSalesAccount.messageState.clear();
        $.ajax({
          type: "POST",
          url: productSalesAccount.deleteUrl,
          dataType: "json",
          data: {
            id: self.item.id
          },
        })
          .done(function (result) {
            if (result.status == 'ok') {
              productSalesAccount.searchAccount();
              productSalesAccount.messageState.setMessage('削除しました。', 'alert alert-success');
            } else {
              const message = result.message ? result.message : '削除処理でエラーが発生しました';
              productSalesAccount.messageState.setMessage(message, 'alert alert-danger');
            }
          })
          .fail(function () {
            productSalesAccount.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
          })
      }
    },
    // 戻す
    restoreAccount: function () {
      const self = this;
      if (window.confirm('再登録しても良いですか？')) {
        productSalesAccount.messageState.clear();
        $.ajax({
          type: "POST",
          url: productSalesAccount.restoreUrl,
          dataType: "json",
          data: {
            id: self.item.id
          },
        })
          .done(function (result) {
            if (result.status == 'ok') {
              productSalesAccount.searchAccount();
              productSalesAccount.messageState.setMessage('再登録しました。', 'alert alert-success');
            } else {
              const message = result.message ? result.message : '戻す処理でエラーが発生しました';
              productSalesAccount.messageState.setMessage(message, 'alert alert-danger');
            }
          })
          .fail(function () {
            productSalesAccount.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
          })
      }
    }
  }
};

const productSalesAccount = new Vue({
  el: '#productSalesAccount',
  data: {
    typeList: [
      { id: "on", name: "絞込ON" },
      { id: "off", name: "絞込OFF" }
    ],
    selectType: "off",
    searchUrl: null, // 検索URL
    changeUrl: null, // 変更URL
    deleteUrl: null, // 削除URL
    restoreUrl: null, // 削除取消URL
    historyUrl: null, // 商品売上担当者更新履歴URL
    addUrl: null, // 商品売上担当者更新履歴URL
    salesAccountUrl: null, // 商品売上担当者一覧表示URL
    messageState: {}, // エラーメッセージ
    daihyoSyohinCode: null,
    filterItem: {
      applyDateFrom: $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7), false, false),
      applyDateTo: null,
    },
    product: null,
    list: [],
    noFilterList: [],
    modalList: [],
    modalReflectList: [],
    modalMessageState: {}
  },
  components: {
    'result-item': templateProductSalesAccountListItem // 一覧テーブル
  },
  mounted: function () {
    const self = this;
    // URL取得
    self.searchUrl = $(self.$el).data('searchUrl');
    self.changeUrl = $(self.$el).data('changeUrl');
    self.deleteUrl = $(self.$el).data('deleteUrl');
    self.restoreUrl = $(self.$el).data('restoreUrl');
    self.historyUrl = $(self.$el).data('historyUrl');
    self.addUrl = $(self.$el).data('addUrl');
    self.salesAccountUrl = $(self.$el).data('salesAccountUrl');

    self.messageState = new PartsGlobalMessageState();
    self.modalMessageState = new PartsGlobalMessageState();

    // codeパラメータが有る場合、そのコードで検索
    const code = (new URL(document.location)).searchParams.get('code');
    if (code != undefined) {
      self.daihyoSyohinCode = code;
      self.searchAccount();
    }
  },
  methods: {
    filterAccount :function(id) {
      this.messageState.clear();
      if (id === 'on') {
        // 連続でONを押下した時、一度絞込前の状態に戻す
        if (this.selectType === 'on') {
          this.list = [...this.noFilterList];
        }

        // 絞込の開始日が終了日より後になっている場合は表示データ無し
        if (
          this.filterItem.applyDateFrom
          && this.filterItem.applyDateTo
          && this.filterItem.applyDateFrom > this.filterItem.applyDateTo
        ) {
          this.list = [];
          this.messageState.setMessage('絞込期間が不適切です', 'alert alert-danger');
          return;
        }

        // 登録状態で、指定期間内で有効日が1日でも存在するデータに限定
        this.list = this.list.filter(item => {
          return (
            item.status === 1
            &&
            (
              !this.filterItem.applyDateTo ||
              item.applyStartDate <= this.filterItem.applyDateTo
            )
            &&
            (
              !item.applyEndDate ||
              !this.filterItem.applyDateFrom ||
              this.filterItem.applyDateFrom <= item.applyEndDate
            )
          );
        });
      }
      if (id === 'off') {
        this.list = [...this.noFilterList];
      }
      this.selectType = id;
    },
    goToAccountHistory() {
      if (this.product !== null) {
        location.href = this.historyUrl + "?code=" + this.product.daihyoSyohinCode;
      } else {
        location.href = this.historyUrl;
      }
    },
    goToAddAccount(code) {
      location.href = this.addUrl + "?code=" + code;
    },
    // 検索
    searchAccount: function () {
      const self = this;
      self.messageState.clear();
      self.product = null;
      self.list = [];
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.daihyoSyohinCode
        },
      })
        .done(function (result) {
          if (result.status == 'ok') {
            self.product = result.product;
            self.list = result.list;
            self.noFilterList = [...self.list];
            const registeredList = self.list.filter(item => item.status === 1);
            // 削除状態か適用期間内に現在日が含まれていない場合、概算%を0とし按分対象外とする
            const now = $.Plusnao.Date.getDateString(new Date(),false,false);
            const totalWorkAmount = registeredList
              .filter(item => (item.status === 1 && item.applyStartDate <= now && (!item.applyEndDate||item.applyEndDate >= now)))
              .reduce((total, item) => total + parseFloat(item.workAmount), 0);
            self.list
              .map(item => item.approximate = (item.status === 1 && item.applyStartDate <= now && (!item.applyEndDate||item.applyEndDate >= now)) ? (Math.round(parseFloat(item.workAmount) / totalWorkAmount * 100 * 10)) / 10 : 0);
          } else {
            const message = result.message ? result.message : '検索処理でエラーが発生しました';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function () {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
        })
    },
    createFilterDatepicker: function(selector) {
      const self = this;
      $(selector).datepicker({
        language: 'ja',
        format: 'yyyy-mm-dd',
        autoclose: true
      }).on('change', function (e) {
        self.$set(self.filterItem, e.target.name, e.target.value);
      });
    },
    // モーダルを開く
    openChangeAccountInfoModal: function () {
      const self = this;
      this.modalMessageState.clear();
      this.modalList = JSON.parse(JSON.stringify(self.list));
      this.modalReflectList = [];
      $('#changeAccountInfoModal').on('shown.bs.modal', function () {
        // モーダルが開いた後にdatepickerの設定をする
        $(".datepicker").datepicker({
          language: 'ja',
          format: 'yyyy-mm-dd',
          autoclose: true
        }).on('change', function (e) {
          self.$set(self.modalList[e.target.dataset.index], e.target.name, e.target.value);
        });
      });
    },
    // 反映確認ボタン押下
    reflect: function () {
      const self = this;
      this.modalReflectList = JSON.parse(JSON.stringify(self.modalList))
      registeredList = JSON.parse(JSON.stringify(self.modalList.filter(item => item.status === 1)));
      // 削除状態か適用期間内に現在日が含まれていない場合、概算%を0とし按分対象外とする
      const now = $.Plusnao.Date.getDateString(new Date(),false,false);
      const totalWorkAmount = this.modalReflectList
        .filter(item => (item.status === 1 && item.applyStartDate <= now && (!item.applyEndDate||item.applyEndDate >= now)))
        .reduce((total, item) => total + parseFloat(item.workAmount), 0);
      this.modalReflectList
        .map(item => item.approximate = (item.status === 1 && item.applyStartDate <= now && (!item.applyEndDate||item.applyEndDate >= now)) ? (Math.round(parseFloat(item.workAmount) / totalWorkAmount * 100 * 10 )) / 10 : 0);
    },
    // 確定ボタン押下
    onSubmit: function () {
      if (!confirm('反映結果の内容で更新を行ってもよろしいですか？')) {
        return;
      }
      const self = this;
      // ステータスが「登録」で、変更があった行のみ取得
      const requestList = this.modalReflectList.filter(reflectItem => {
        const originalItem = self.list.find(item => item.id === reflectItem.id);
        return originalItem.status === 1 &&
          (originalItem.applyStartDate !== reflectItem.applyStartDate ||
            originalItem.applyEndDate !== reflectItem.applyEndDate ||
            originalItem.detail !== reflectItem.detail ||
            originalItem.workAmount !== reflectItem.workAmount);
      });
      $.ajax({
        type: "POST",
        url: self.changeUrl,
        dataType: "json",
        data: {
          daihyoSyohinCode: self.product.daihyoSyohinCode,
          list: requestList,
        },
      })
        .done(function (result) {
          if (result.status == 'ok') {
            location.href = self.salesAccountUrl + '?code=' + self.product.daihyoSyohinCode;
          } else {
            const message = result.message ? result.message : '変更処理でエラーが発生しました';
            self.modalMessageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function () {
          self.modalMessageState.setMessage('エラーが発生しました。', 'alert alert-danger');
        });
    }
  }
});
