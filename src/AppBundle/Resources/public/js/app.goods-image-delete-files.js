/**
 * 管理画面 商品画像アテンション画像管理 JS
 */


// データ更新 モーダル
Vue.component('delete-confirm-modal', {
    template: '#templateModalDeleteConfirm'
  , delimiters: ['(%', '%)']
  , props: [
      'state' // { show: true|false }
    , 'list'
  ]
  , data: function() {
    return {
        deleteUrl: null
      , messageState: {}
      , caption: '商品画像　削除'
    };
  }
  , computed: {
  }
  , watch : {
  }
  , mounted: function() {
    this.$nextTick(function (){
      var self = this;
      var modal = $(self.$el);

      self.messageState = new PartsGlobalMessageState();

      // イベント登録
      self.$watch('state.show', function(newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });

      // -- open前
      modal.on('show.bs.modal', function(e) {
        self.messageState.clear();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
        self.messageState.setMessage('削除を実行します、よろしいですか？　', 'alert-danger');
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.hideModal(); // 外部から閉じられた時の手当
        }
      })
    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
    }

    , save: function() {
      var self = this;

      self.messageState.clear();
      this.state.show = false;
      self.$emit('delete-selected-files'); // 親コンポーネントに委譲
    }
  }
});



//一覧画面 一覧表
const imageFilesList = new Vue({
  el: '#imageFilesList',
  data: {
    list: [] // データ
    , totalItemNum: 0 // データ総件数
    , pageItemNumList: [100]
    , searchParameter: null
    , searchParams: {}
    , searchConditions: {
      daihyoSyohinCode: ''
    }
    , storageSearchParams: null
    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , show: false
    }
    , folderId: null
    , allChecked: false
    , url: null
    , initialized: false
  }
  , mounted: function() {
    var self = this;
    this.$nextTick(function () {
      self.folderId = $(self.$el).data('folderId');
      self.url = $(self.$el).data('url');
      self.deleteUrl = $(self.$el).data('deleteUrl');

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
  , computed: {

    canDelete: function() { // 更新対象があれば更新可能
      var self = this;
      if (self.list.length == 0) {
        return false;
      }
      for (var i = 0; i < self.list.length; i++) {
        if (self.list[i].checked) {
          return true;
        }
      }
      return false;
    }
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
          folderId: self.folderId
        , page: pageInfo.page
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
            cssClass = '';
            if (item.ExistsOnDb) {
              cssClass = 'info';
            }
            var row = {
              checked: false
              , FolderId: item.FolderId
              , FolderName: item.FolderName
              , FolderPath: item.FolderPath
              , FileId: item.FileId
              , FileName: item.FileName
              , FileUrl: item.FileUrl
              , FilePath: item.FilePath
              , FileAccessDate: item.FileAccessDate
              , TimeStamp: item.TimeStamp
              , ExistsOnDb: item.ExistsOnDb
              , cssClass : cssClass
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
    /**
     * 更新確認モーダル表示
     */
    , showFormModal: function () {
      this.modalState.show = true;
    }
    /**
     * 削除実行
     */
    , deleteSelectedFiles: function () {
      var self = this;

      self.messageState.clear();
      self.nowLoading = true;

      var data = [];
      for (var i = 0; i < self.list.length; i++) {
        if (self.list[i].checked) {
          data.push(self.list[i].FileId);
        }
      }

      $.ajax({
          type: "POST"
        , url: self.deleteUrl
        , dataType: "json"
        , data: { fileId: data }
      })
      .done(function(result) {
        if (result.status == 'ok') {
          self.showPage({
              page: self.searchParams.page
            , pageItemNum: self.searchParams.pageItemNum
          });
          self.messageState.setMessage(result.message, 'alert-success');
        } else {
          var message = result.message.length > 0 ? result.message : '削除できませんでした。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
      . always(function() {
        self.nowLoading = false;
      });
    }
    /**
     * 全てチェック
     */
    , allCheckClicked: function () {
      for (var i = 0; i < this.list.length; i ++) {
        this.list[i].checked = this.allChecked;
      }
    }
  }
});