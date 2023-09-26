/**
 * 配送ラベル再発行伝票一覧 JS
 */

/** メイン画面 */
const shippingInquiryNumberReissueList = new Vue({
  el: '#shippingInquiryNumberReissueList',
  data:{
    // 検索用
    conditions: {
      warehouseId: WAREHOUSE_ID,
      packingGroupName: "",
      packingGroupComment: "",
      voucherNumber: "",
      onlyNotCsvDownload: true
    },
    displayedConditions: SAVED_CONDITIONS, // 検索中の条件を格納。初期表示時は空文字。
    activeMethodId: "",
    deliveryMethodList: [],
    resultList: {},
    list: {},
    voucherNumberText: "",
    searchUrl: null,
    downloadUrl: null,
    messageState: {}, // エラーメッセージ
    savedMessage: SAVED_MESSAGE // 検索結果以外のエラーメッセージ、無ければ空文字
  },
  mounted: function() {
    // URL取得
    this.searchUrl = $(this.$el).data('searchUrl');
    this.downloadUrl = $(this.$el).data('downloadUrl');

    this.messageState = new PartsGlobalMessageState();

    // 初期表示時に、デフォルトで検索。検索中なら、その条件で再検索。
    if (this.displayedConditions !== '') {
      this.conditions = {...this.displayedConditions};
    }
    this.search();
  },
  methods: {
    selectTab: function(id) {
      this.activeMethodId = id;
    },
    // 発送方法毎に、IDに応じて「method35」のようなキー情報を追加する。
    addKeyToDeliveryMethod: function() {
      this.deliveryMethodList.map(function(method) {
        method.key = 'method' + method.id;
        return method;
      })
    },
    // 配送ラベル再発行伝票の全データを、配送方法毎に整形し、必要データを追加する。
    arrangeList: function() {
      const self = this;
      self.deliveryMethodList.forEach(method => {
        // 発送方法IDに応じて「method35」のようにしたものを、キーとして定義。
        self.list[method.key] = {};

        const data = [];
        // ステータス未発行分
        const unissuedData = {
          'ids': [],
          'packingIds': [],
        }
        const voucherNumbers = [];
        self.resultList.forEach(item => {
          if (Number(item.deliveryMethodId) === method.id) {
            // 表示用のステータス名と、そのCSS設定用classを追加。
            // また、ステータス未発行のID、梱包IDのデータを抽出。
            switch (item.status) {
              case '0':
                item.statusName = '';
                unissuedData.ids.push(item.id);
                unissuedData.packingIds.push(item.shippingVoucherPackingId);
                break;
              case '1':
                item.statusName = 'DL済み'
                item.css = 'badge badge-done'
                break;
              case '9':
                item.statusName = '削除'
                item.css = 'badge badge-delete'
                break;
              default:
                break;
            }
            data.push(item);
            voucherNumbers.push(item.voucherNumber);
          }
        })
        self.list[method.key].deliveryMethodId = method.id;
        self.list[method.key].data = data;
        self.list[method.key].unissuedData = unissuedData;
        self.list[method.key].voucherNumbers = voucherNumbers;
        // テキストエリア表示用。
        self.list[method.key].voucherNumberText = voucherNumbers.join('\n');
      });
    },
    // クリップボードにコピー
    writeToClipboard: function(text) {
      navigator.clipboard.writeText(text);
    },
    // 検索
    search: function() {
      const self = this;
      self.deliveryMethodList = [];
      self.list = {};
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
        if (result.status == 'ok') {
          self.deliveryMethodList = result.deliveryMethodList;
          self.activeMethodId = result.deliveryMethodList[0].id;
          self.resultList = result.list;
          self.addKeyToDeliveryMethod();
          self.arrangeList();
          // 検索結果になっている条件として保存。（次回の「検索」押下までv-modelの影響を受けない。）
          self.displayedConditions = {...self.conditions};
          // 検索以外のエラーメッセージがある場合、検索結果は表示し、そのエラーも表示する。
          if (self.savedMessage !== '') {
            self.messageState.setMessage(self.savedMessage, 'alert alert-danger');
            self.savedMessage = '';
          }
        } else {
          // 検索以外のエラーメッセージ>検索のエラーメッセージ>検索の予期せぬエラーの優先度で画面表示。
          let message = '';
          if (self.savedMessage !== '') {
            message = self.savedMessage;
            self.savedMessage = '';
          } else if (result.message !== '') {
            message = result.message;
          } else {
            message = '検索でエラーが発生しました';
          }
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function() {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
    },
    // CSVダウンロード
    download: function(key) {
      // aタグ生成(HTML buttonタグなので)
      let alink = document.createElement('a');

      // href属性追加
      let url = `${this.downloadUrl}`;
      url += `?unissuedIds=${this.list[key].unissuedData.ids}`;
      url += `&unissuedPackingIds=${this.list[key].unissuedData.packingIds}`;
      url += `&voucherNumbers=${this.list[key].voucherNumbers}`;
      // 現在フォームの内容ではなく、検索結果になっている条件を送信。
      url += `&conditions=${JSON.stringify(this.displayedConditions)}`;
      url += `&activeMethodId=${this.activeMethodId}`;
      alink.href = url;

      // 実行
      alink.click();
    }
  }
});