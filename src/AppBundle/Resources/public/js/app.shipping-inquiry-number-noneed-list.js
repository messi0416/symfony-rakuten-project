/**
 * 不使用お問い合わせ番号一覧 JS
 */

/** メイン画面 */
const shippingInquiryNumberNoneedList = new Vue({
  el: '#shippingInquiryNumberNoneedList',
  data:{
    // 検索用
    conditions: {
      warehouseId: WAREHOUSE_ID,
      packingGroupName: "",
      packingGroupComment: "",
      voucherNumber: "",
      onlyIncompleteInput: true
    },
    displayedConditions: {},
    activeMethodId: "",
    activeMethodName: "",
    deliveryMethodList: [],
    resultList: {},
    inquiryNumberText: "",
    searchUrl: null,
    completeUrl: null,
    messageState: {}, // エラーメッセージ
  },
  mounted: function() {
    // URL取得
    this.searchUrl = $(this.$el).data('searchUrl');
    this.completeUrl = $(this.$el).data('completeUrl');

    this.messageState = new PartsGlobalMessageState();

    // 初期表示時、デフォルト条件で検索。
    this.search();
  },
  computed: {
    displayList: function() {
      const self = this;
      const list = {};

      const data = [];
      const unregisteredIds = [];
      const inquiryNumbers = [];
      self.resultList.forEach(item => {
        if (Number(item.deliveryMethodId) === self.activeMethodId) {
          // 表示用のステータス名と、そのCSS設定用classを追加。
          // また、ステータス未登録のIDを抽出。
          switch (item.status) {
            case '0':
              item.statusName = '';
              unregisteredIds.push(item.id)
              break;
            case '1':
              item.statusName = '入力完了'
              item.css = 'badge badge-done'
              break;
            default:
              break;
          }
          data.push(item);
          inquiryNumbers.push(item.inquiryNumber)
        }
      })
      list.data = data;
      list.unregisteredIds = unregisteredIds;
      // テキストエリア表示用。
      list.inquiryNumberText = inquiryNumbers.join('\n');

      return list;
    },
  },
  methods: {
    selectTab: function(id, name) {
      this.activeMethodId = id;
      this.activeMethodName = name;
    },
    // クリップボードにコピー
    writeToClipboard: function() {
      navigator.clipboard.writeText(this.displayList.inquiryNumberText);
    },
    // 検索
    search: function() {
      const self = this;
      self.deliveryMethodList = [];
      self.resultList = {};
      self.activeMethodId = "";
      self.activeMethodName = "";
      self.messageState.clear();
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
          self.deliveryMethodList = result.deliveryMethodList;
          self.activeMethodId = result.deliveryMethodList[0].id;
          self.activeMethodName = result.deliveryMethodList[0].name;
          self.resultList = result.list;
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
    // 入力完了
    complete: function() {
      const self = this;
      const unregisteredIds = self.displayList.unregisteredIds;
      if (unregisteredIds.length === 0) {
        const message = '未登録のお問い合わせ番号がありません';
        self.messageState.setMessage(message, 'alert alert-danger');
        return;
      }
      $.ajax({
        type: "POST",
        url: self.completeUrl,
        dataType: "json",
        data: {
          unregisteredIds: unregisteredIds
        },
      })
      .done(function(result) {
        if (result.status === 'ok') {
          unregisteredIds.forEach(id => {
            const index = self.resultList.findIndex(item => {
              return item.id === id;
            });
            self.resultList[index].status = '1';
          })
        } else {
          const message = result.message ? result.message : '入力完了でエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function() {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
    }
  }
});