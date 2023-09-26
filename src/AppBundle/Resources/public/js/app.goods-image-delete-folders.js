/**
 * 管理画面 商品画像削除 JS
 */


//一覧画面 一覧表
const imageFoldersList = new Vue({
  el: '#imageFoldersList',
  data: {
    list: [] // データ
    , totalItemNum: 0 // データ総件数
    , pageItemNumList: [100]
    , searchParameter: null
    , searchParams: {}
    , storageSearchParams: null
    , messageState: {}
    , url: null
    , initialized: false
  }
  , mounted: function() {
    var self = this;
    this.$nextTick(function () {
      self.url = $(self.$el).data('url');

      let searchParameter = this.getInitSearchParameter();
      this.searchParams = searchParameter.getParams();
      if (this.searchParams.pageItemNum == null) {
        this.searchParams.pageItemNum = 100;
      }
      this.searchParameter = searchParameter;

      // メッセージオブジェクト
      self.messageState = new PartsGlobalMessageState();
      self.list = [];
      self.initialized = true;
      self.showPage({
          page: this.searchParams.page
        , pageItemNum: this.searchParams.pageItemNum
      });
    });
  }
  , methods: {
    // 一覧表示
    showPage: function(pageInfo) {
      // 初期化が済んでいない場合にはreturn
      if (!this.initialized) {
        return;
      }
      this.initialized = false;
      let self = this;
      self.messageState.clear();

      // データ読み込み処理
      let data = {
          page: pageInfo.page
        , limit: pageInfo.pageItemNum
        , conditions: self.searchConditions
      };

      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
      .done(function(result) {
        if (result.status == 'ok') {

          self.list = [];
          for (var i = 0; i < result.list.length; i++) {
            var item = result.list[i];
            var row = {
              FolderId : item.FolderId
              , FolderName : item.FolderName
              , FolderPath : item.FolderPath
              , FileCount : item.FileCount
              , TimeStamp : item.TimeStamp
              , url : item.url
            };
            self.list.push(row);
          }

          self.totalItemNum = Number(result.count);
          self.searchParams.page = pageInfo.page;
          self.searchParams.pageItemNum = pageInfo.pageItemNum;
          self.searchParameter.setValues(self.searchParams);

          if (result.list.length == 0) {
            self.messageState.setMessage('対象データがありません', 'alert-success');
          }
        } else {
          let message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
      . always(function() {
        self.initialized = true;
      });
    }
    , showFirstPage: function() {
      let pageInfo = {
          page: 1
        , pageItemNum: pageItemNum
      };
      this.showPage(pageInfo);
    }
    , search: function() {
      let pageInfo = {
          page: 1
        , pageItemNum: this.searchParams.pageItemNum
      }
      this.showPage(pageInfo);
    }
    /**
     * 初期検索条件
     */
    , getInitSearchParameter: function() {
      var searchParameter = new $.Plusnao.SearchParameter;
      return searchParameter;
    }
  }
});