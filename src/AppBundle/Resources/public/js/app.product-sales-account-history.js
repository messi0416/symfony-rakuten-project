/**
 * 管理画面 商品売上担当者更新履歴画 JS
 */

const history = new Vue({
  el: '#history',
  data: {
    searchUrl: null,
    listUrl: null,
    searchItem: {
      updatedFrom: $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7), false, false),
      updatedTo: $.Plusnao.Date.getDateString(new Date(), false, false),
      code: null,
      userId :null,
    },
    users: [],
    list: [],
    messageState : {},

    paginationObj: {
      initPageItemNum: 20, // 1ページに表示する件数
      initPageItemNumList: [ 20, 50, 100 ], // 1ページに表示する件数のリスト
      page: 1, // 現在ページ数
    }
  },
  mounted: function() {
    const self = this;
    // URL取得
    this.searchUrl = $(this.$el).data('searchUrl');
    this.listUrl = $(this.$el).data('listUrl');

    // 検索用データ取得
    this.users = USERS_DATA;

    // datepicker設定
    $('.datepicker').datepicker({
      language: 'ja',
      format : 'yyyy-mm-dd',
      autoclose: true
    }).on('change',function (e) {
      self.$set(self.searchItem, e.target.name, e.target.value);
    });

    this.messageState = new PartsGlobalMessageState();

    // 検索
    const code = (new URL(document.location)).searchParams.get('code');
    if (code != undefined) {
      self.searchItem.code = code;
    }
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
          form: self.searchItem
        },
      }).done(function(result) {
        if (result.status == 'ok') {
          self.list = result.list;
          self.list.forEach(item => {
            item.products.map(product => {
              product.href = self.listUrl + "?code=" + product.code;
              return product;
            });
          });
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
    }
  }
});