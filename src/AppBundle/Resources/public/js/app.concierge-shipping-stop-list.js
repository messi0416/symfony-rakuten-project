/**
 * 出荷STOP JS
 */

/** メイン画面 */
const conciergeShippingStopList = new Vue({
  el: '#conciergeShippingStopList',
  data:{
    // 検索用
    conditions: {
      voucherNumber: "",
      orderNumber: "",
      neMallId: "",
    },
    displayedConditions: {},
    list: [],
    searchUrl: null,
    stopUrl: null,
    messageState: {}, // エラーメッセージ
  },
  mounted: function() {
    // URL取得
    this.searchUrl = $(this.$el).data('searchUrl');
    this.stopUrl = $(this.$el).data('stopUrl');

    this.messageState = new PartsGlobalMessageState();
  },
  methods: {
    // 検索
    search: function(reset = true) {
      const self = this;
      self.list = {};

      // reset=falseの場合、メッセージはクリアせず、検索中の条件で再検索
      if (reset) {
        self.messageState.clear();
      }
      if (!reset) {
        self.conditions = {...self.displayedConditions};
      }

      // 入力チェック
      if (self.conditions.voucherNumber === '' && self.conditions.orderNumber === '') {
        self.messageState.setMessage('伝票番号か受注番号のいずれかは必ず入力してください。', 'alert alert-danger');
        return;
      }
      const voucherNumber = Number(self.conditions.voucherNumber);
      if (Number.isNaN(voucherNumber)) {
        self.messageState.setMessage(`伝票番号は数値を入力してください。 「${self.conditions.voucherNumber}」`, 'alert alert-danger');
        return;
      }

      $.ajax({
        type: "POST",
        url: self.searchUrl,
        dataType: "json",
        data: {
          conditions: self.conditions
        },
      })
      .done(function(result) {
        if (result.status === 'ok') {
          self.list = result.list;
          self.list.map(item =>{
            item.totalAmount = Number(item.totalAmount).toLocaleString();
            return item;
          })
          // 検索結果になっている条件として保存。（次回の「検索」押下までv-modelの影響を受けない。）
          self.displayedConditions = {...self.conditions};
        } else {
          const message = result.message ? result.message : '検索でエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function() {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
    },
    // 出荷STOP
    stop: function(id) {
      const self = this;
      $.ajax({
        type: "POST",
        url: self.stopUrl,
        dataType: "json",
        data: {
          id: id
        },
      })
      .done(function(result) {
        if (result.status === 'ok') {
          self.messageState.setMessage('出荷STOP申請しました。', 'alert alert-success');
          self.search(false);
        } else {
          const message = result.message ? result.message : '出荷STOPでエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function() {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
      window.scrollTo({top: 0, behavior: 'smooth'});
    }
  }
});