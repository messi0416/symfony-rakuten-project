// 一覧画面 一覧テーブル 行コンポーネント
const templateDeliveryShippingVoucherAutoGenerateHistoryListItem = {
  template: '#templateDeliveryShippingVoucherAutoGenerateHistoryListTableRow',
  props: [
    'item',
  ],
  methods: {
    // 再実行
    retry: function() {
      const self = this;
      $.ajax({
        type: "POST",
        url: self.item.retryUrl,
        dataType: "json"
      }).done(function(result) {
        self.$emit('show-retry-result', 'done', result.status, result.message);
      }).fail(function(stat) {
        self.$emit('show-retry-result', 'fail');
      });
    },
    // 伝票番号コピー
    copy: function() {
      const voucherNumberText = this.item.voucherNumbers.join('\n')
      navigator.clipboard.writeText(voucherNumberText);
    }
  }
};

const deliveryShippingVoucherAutoGenerateHistory = new Vue({
  el: '#deliveryShippingVoucherAutoGenerateHistory',
  data: {
    searchUrl: null, // 検索URL
    retryUrl: null, // 変更URL
    messageState: {}, // エラーメッセージ
    conditions: {
      warehouseId: null,
      status: null
    },
    displayedConditions: {},
    list: [],

    paginationObj: {
      initPageItemNum: 20, // 1ページに表示する件数
      initPageItemNumList: [ 20, 50, 100 ], // 1ページに表示する件数のリスト
      page: 1, // 現在ページ数
    }
  },
  components: {
    'result-item': templateDeliveryShippingVoucherAutoGenerateHistoryListItem // 一覧テーブル
  },
  mounted: function() {
    const self = this;
    // URL取得
    self.searchUrl = $(self.$el).data('searchUrl');
    self.retryUrl = $(self.$el).data('retryUrl');

    self.messageState = new PartsGlobalMessageState();
    self.search();
  },
  computed: {
    // 表示するリスト
    pageData: function () {
      const startPage = (this.paginationObj.page - 1) * this.paginationObj.initPageItemNum;
      return this.list.slice(startPage, startPage + this.paginationObj.initPageItemNum);
    },
    // コンポーネントに渡すオブジェクト
    paginationInfo: function() {
      return {
        ...this.paginationObj,
        itemNum: this.list.length
      };
    }
  },
  methods: {
    // 検索
    search: function() {
      const self = this;
      self.messageState.clear();
      self.list = [];
      self.paginationObj.page = 1;
      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          conditions: self.conditions
        },
      }).done(function(result) {
        if (result.status == 'ok') {
          self.list = result.list;
          self.displayedConditions = {...self.conditions};
        } else {
          const message = result.message ? result.message : '検索でエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      }).fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      });
    },
    // ページ送りコンポーネントの変更があった場合の処理
    changePage: function (pageInfo) {
      this.paginationObj.page = pageInfo.page;
      this.paginationObj.initPageItemNum = pageInfo.pageItemNum;
    },
    // 再実行の結果を表示
    showRetryResult: function(ajaxResult, status = '', message = '') {
      const self = this;
      self.messageState.clear();
      if (ajaxResult === 'done') {
        if (status === 'ok') {
          self.conditions = {...self.displayedConditions}
          self.search();
          self.messageState.setMessage('再登録しました。', 'alert alert-success');
        } else {
          const displayMessage = message ? message : '再実行でエラーが発生しました。';
          self.messageState.setMessage(displayMessage, 'alert alert-danger');
        }
      } else {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      }
    }
  }
});