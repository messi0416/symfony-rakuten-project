/**
 * 管理画面 商品レビュー一覧 JS
 */

// 全体メッセージ
var vmGlobalMessage = new Vue({
    el: '#globalMessage'
  , delimiters: ['(%', '%)']
  , data: {
      message: ''
    , messageCssClass: ''
    , loadingImageUrl: null
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    });
  }
  , methods: {
    setMessage: function(message, cssClass) {
      cssClass = cssClass || 'alert alert-info';

      this.message = message;
      this.setCssClass(cssClass);
    }
    , setCssClass: function(cssClass) {
      this.messageCssClass = cssClass;
    }
    , clear: function() {
      this.message = '';
      this.messageCssClass = '';
    }
    , closeWindow: function() {
      window.close();
    }
  }
});

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentReviewListItem = {
    template: '#templateReviewListTableRow'
  , props: [
        'item'
      , 'detailUrlBase'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
  }
  , methods: {
  }
};

// 一覧画面 一覧表
var vmEnglishDataListTable = new Vue({
  el: '#reviewListTable'
  , delimiters: ['(%', '%)']
  , data: {
    list: [] // データ
    , totalItemNum: 0 // データ総件数
    , pageItemNumList: [ 20, 50, 100 ]
    , url: null
    , initialized: false
    , searchParameter: null // SearchParameter はURLとパラメータの変換などを管理する共通クラス
    , searchParams: {} // searchParams は SearchParameter と連動し、実際の検索条件を管理する
    , conditions: {} // フォームと連動する、入力中の検索条件。「検索」ボタン押下で searchParams へ反映。（例えばページング時に入力しかけた検索条件を使わないため）
    , moveDays: 7
    , allAverage: 0
  }
  , components: {
    'result-item': vmComponentReviewListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      let self = this;
      // URL文字列取得
      self.url = $(self.$el).data('url');
      self.detailUrlBase = $(self.$el).data('detailUrlBase');
      
      // 絞込条件取得
      var searchParameter = this.getInitSearchParameter();
      var qp = $.Plusnao.QueryString.parse();
        
      // クエリパラメータから
      if (Object.keys(qp).length > 0) {
        searchParameter.setValuesWithAlias(qp);
      }
      self.searchParams = searchParameter.getParams();
      self.searchParameter = searchParameter;
      
      // 検索条件の初期化
      if (!self.searchParams.importDateFrom && !self.searchParams.importDateTo) {
        // クエリパラメータが指定されない場合、レビュー日に初期値を設定する。
        let fromDateStr = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(),-7),false,false);
        let toDateStr = $.Plusnao.Date.getDateString(new Date(),false,false);
        self.searchParams.importDateFrom = fromDateStr;
        self.searchParams.importDateTo = toDateStr;
      }
      if (!self.searchParams.moveDays) {
        self.searchParams.moveDays = 7;
      }

      // クエリパラメータを画面入力検索条件に反映
      Object.assign(self.conditions, self.searchParams);
      self.conditions.neMallId = self.searchParams.neMallId ? self.searchParams.neMallId : '';

      var dateOptions = {
        language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      };
      $('#importDateFrom').datepicker(dateOptions)
        .on({
          changeDate: function () {
            self.conditions.importDateFrom = $(this).val();
          },
          clearDate: function () {
            self.conditions.importDateFrom = null;
          },
        });
      $('#importDateTo').datepicker(dateOptions)
      .on({
        changeDate: function () {
          self.conditions.importDateTo = $(this).val();
        },
        clearDate: function () {
          self.conditions.importDateTo = null;
        },
      });

      
      this.initialized = true;
      this.showPage(null);
    });
  }

  , computed: {
  }

  , methods: {

    /**
   　* 指定ページ表示。
      * @param pageInfo 表示ページの情報
      */
    showPage: function(pageInfo) {
      let self = this;
      // 初期化が済んでいない場合にはreturn
      if (!this.initialized) {
        return;
      }
      // パラメータがない場合は現在ページをリロード
      if (! pageInfo) {
        pageInfo = {
            page: self.searchParams.page
          , pageItemNum: self.searchParams.pageItemNum
        };
      }

      this.initialized = false;

      // パラメータ構築
      var page = pageInfo.page;

      // データ読み込み処理
      var data = {
          page: page
        , limit: pageInfo.pageItemNum
        , conditions: {
            date_from             : self.searchParams.importDateFrom
            , date_to             : self.searchParams.importDateTo
            , score_from          : self.searchParams.scoreFrom
            , score_to            : self.searchParams.scoreTo
            , daihyo_syohin_code  : self.searchParams.daihyoSyohinCode
            , daihyo_syohin_name  : self.searchParams.daihyoSyohinName
            , ne_mall_id          : self.searchParams.neMallId
        }
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
            self.allAverage = 0;
            for (var i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                id : item.id
                , reviewDate : item.review_date.substr(0, 10)
                , score : buildScore(item.score)
                , scoreAverage : item.score_average != null ? item.score_average + '(' + item.review_num + ')' : ''
                , imageUrl : item.image_url
                , daihyoSyohinCode : item.daihyo_syohin_code
                , skuCode : item.sku_code
                , daihyoSyohinName : item.daihyo_syohin_name
                , title: item.title
                , body: item.body
                , postingSite: item.posting_site
              };

              self.list.push(row);
            }

            // ページ情報更新（子コンポーネントで変更できないため親で更新するが、タイミングは読み込み成功してのここ）
            self.totalItemNum = Number(result.count);
            self.searchParams.page = page;
            self.searchParams.pageItemNum = pageInfo.pageItemNum;
            if (result.allAverage != null) {
              self.allAverage = "平均 " + result.allAverage;
            }

            self.searchParameter.setValues(self.searchParams);
            // URL 更新
            var queryString = self.searchParameter.generateQueryString();
            var url = window.location.pathname + (queryString.length > 0 ? ('?' + queryString) : '');
            window.history.pushState(null, null, url);

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
          self.initialized = true;
        });

    }

    , showFirstPage: function() {
      let self = this;
      var pageInfo = {
          page: 1
        , pageItemNum: this.searchParams.pageItemNum
      };
      self.showPage(pageInfo);
    }

    /**
    　* 新規検索実行
    　* 検索フォームに入力されている値を、検索パラメータにコピーして検索を実行する。
      */
    , search: function() {
      vmGlobalMessage.clear(); // グローバルメッセージの表示を消す
      let self = this;
      let pageItemNum = self.searchParams.pageItemNum; // 新規検索しても、1ページの表示件数は維持（負荷対策）
      searchParameter = this.getInitSearchParameter(); // 新規インスタンス
      self.searchParams = searchParameter.getParams();
      self.searchParameter = searchParameter;
      Object.assign(self.searchParams, self.conditions);
      self.searchParams.pageItemNum = pageItemNum;
      this.showFirstPage();
    }

    /**
     * 初期検索条件
     */
    , getInitSearchParameter: function() {
      var searchParameter = new $.Plusnao.SearchParameter;
      searchParameter.addParam('page', 'integer', 'p', 1);
      searchParameter.addParam('pageItemNum', 'integer', 'pn', 20);
      searchParameter.addParam('importDateFrom', 'string', 'df');
      searchParameter.addParam('importDateTo', 'string', 'dt');
      searchParameter.addParam('moveDays', 'string', 'md');
      searchParameter.addParam('scoreFrom', 'string', 'sf');
      searchParameter.addParam('scoreTo', 'string', 'st');
      searchParameter.addParam('daihyoSyohinCode', 'string', 'code');
      searchParameter.addParam('daihyoSyohinName', 'string', 'name');
      searchParameter.addParam('neMallId', 'string', 'shop');

      return searchParameter;
    }

    /**
     * 日付選択
     */
    // 7日前 ～ 前日, 14日前 ～ 8日前
    ,selectOneWeek: function() {
      this.moveDays = 7; // 初期値
      // 入力中の検索条件を更新
      this.conditions.importDateFrom = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7));
      this.conditions.importDateTo = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());
      this.conditions.moveDays = this.moveDays;
      // 検索条件の表示を更新
      $('#importDateFrom').val(this.conditions.importDateFrom);
      $('#importDateTo').val(this.conditions.importDateTo);
      $('#moveDays').val(this.conditions.moveDays);

    }
    // 30日前 ～ 前日, 60日前 ～ 31日前 ※日数を各月同じにするために、30日固定とする
    ,selectOneMonth: function() {
      this.moveDays = 30; // 初期値
      // 入力中の検索条件を更新
      this.conditions.importDateFrom = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -30));
      this.conditions.importDateTo = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());
      this.conditions.moveDays = this.moveDays;
      // 検索条件の表示を更新
      $('#importDateFrom').val(this.conditions.importDateFrom);
      $('#importDateTo').val(this.conditions.importDateTo);
      $('#moveDays').val(this.conditions.moveDays);

    }
    // 日付範囲移動
    , moveDayRange: function(direction, days) {
      if (!days) {
        days = direction == 'backward' ? -(this.conditions.moveDays) : this.conditions.moveDays;
      }

      if(this.conditions.importDateFrom){ // 値が設定されている時だけ移動する。NaN-NaN-NaNを出さない
        // 入力中の検索条件を更新
        this.conditions.importDateFrom = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date($('#importDateFrom').val()), days));
        // 検索条件の表示を更新
        $('#importDateFrom').val(this.conditions.importDateFrom);
      }
      if(this.conditions.importDateTo){ // 値が設定されている時だけ移動する。NaN-NaN-NaNを出さない
        // 入力中の検索条件を更新
        this.conditions.importDateTo = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date($('#importDateTo').val()), days));
        // 検索条件の表示を更新
        $('#importDateTo').val(this.conditions.importDateTo);
      }
    }

    // 同月比較
    , selectCompareSameMonth: function(month) {

      var dateMonth = month - 1; // Dateで利用するので最初から1引いておく。

      var now = new Date();
      var targetMonthStart, targetMonthEnd, compareMonthStart, compareMonthEnd;

      // まだ過ぎていなければ 去年の月が基準
      if (now.getMonth() <= dateMonth) {
        targetMonthStart = new Date(now.getFullYear() - 1, dateMonth, 1);
      // 過ぎていれば今年の月が基準
      } else {
        targetMonthStart = new Date(now.getFullYear(), dateMonth, 1);
      }
      targetMonthEnd = new Date(targetMonthStart.getFullYear(), targetMonthStart.getMonth() + 1, 0);

      compareMonthStart = new Date(targetMonthStart.getFullYear() - 1, targetMonthStart.getMonth(), 1);
      compareMonthEnd = new Date(compareMonthStart.getFullYear(), compareMonthStart.getMonth() + 1, 0);

      $('#importDateFrom').val($.Plusnao.Date.getDateString(targetMonthStart));
      $('#importDateTo').val($.Plusnao.Date.getDateString(targetMonthEnd));

      this.moveDays = 365;
    }

  }

});

// レビューデータアップロードモーダル
var vmReviewUploadCsvModal = new Vue({
  el: '#modalReviewCsvUpload'
  , data: {
    uploadUrl: null
    , registerUrl: null
    , result: null
    , caption: 'レビューデータ取込 CSVアップロード'
    , message: ''
    , messageClass: ''
    , notices: []
    , noticeClass: 'alert alert-warning'
    , noticeHidden: true
    , deleteUrl: null
    , verifyUrl: null
    , nowLoading: false
    , showConfirm: true
    , showRegister: false
    , showFile : true
    , yahooSitesLastRegistrDateList: null
    , fileName : null
    , reviewSiteName : null
    , reviewSiteId : null
  }
  , mounted: function() {
  }
  , methods: {
    onConfirm: function() {
      var self = this;

      this.resetDialog();

      self.uploadUrl = $(self.$el).data('uploadUrl');
      var $input = $(self.$el).find('input[type="file"]');
      var files = $input.get(0).files;
      if (!files.length) {
        this.notices = ['アップロードするファイルが選択されていません。'];
        this.noticeHidden = false;
        return;
      }

      var file = files[0];
      if (!file.name.match(/\.csv$/)) {
        this.notices = ['ファイルの拡張子が .csv ではありません。'];
        this.noticeHidden = false;
        return;
      }

      var formData = new FormData();
      formData.append($input.attr('name'), file);

      this.nowLoading = true;

      $.ajax({
        type: 'POST',
        timeout: 30000,
        url: self.uploadUrl,
        dataType: 'json',
        processData: false,
        contentType: false,
        data: formData
      }).done(function(result, textStatus, jqXHR) {

        if (result.status == 'ok') {
          self.$data.message = result.message;
          self.$data.messageClass = 'alert alert-success';
          self.fileName = result.fileName;
          self.reviewSiteName = result.reviewSiteName;
          self.reviewSiteId = result.reviewSiteId;
          self.yahooSitesLastRegistrDateList = null;
          self.showConfirm = false;
          self.showFile = false;
          self.showRegister = true;
        } else {
          self.$data.message = result.message;
          self.$data.messageClass = 'alert alert-danger';
          if (result.yahooSitesLastRegistrDateList != null) {
            self.yahooSitesLastRegistrDateList = [];
            for (var i = 0; i < result.yahooSitesLastRegistrDateList.length; i++) {
              var item = result.yahooSitesLastRegistrDateList[i];
              var lastDate = item.siteName + ':' + item.lastReviewDate;
              self.yahooSitesLastRegistrDateList.push(lastDate);
            }
          }
          self.showConfirm = true;
          self.showRegister = false;
        }
      }).fail(function(jqXHR, textStatus, errorThrown) {
        if (jqXHR.responseText) {
          self.$data.message = '処理を実行できませんでした。';
          self.$data.messageClass = 'alert alert-danger';
        }
      }).always(function() {
        self.noticeHidden = true;
        self.nowLoading = false;
      });
    },
    resetDialog: function() {
      this.$data.message = '';
      this.$data.messageClass = '';
      this.notices = [];
      this.noticeHidden = true;
      this.showConfirm = true;
      this.showFile = true;
      this.showRegister = false;
      this.nowLoading = false;
      this.yahooSitesLastRegistrDateList = null;
    },
    onRegister: function() {
      var self = this;
      self.registerUrl = $(self.$el).data('registerUrl');
      self.showConfirm = false;
      self.showRegister = false;
      self.showFile = false;
      var data = {
        fileName: self.fileName
        , reviewSiteName: self.reviewSiteName
        , reviewSiteId: self.reviewSiteId
      };
      $.ajax({
        type: 'POST',
        url: self.registerUrl,
        dataType: 'json',
        data: data
      }).done(function(result) {
        if (result.status == 'ok') {
          self.$data.message = result.message;
          self.$data.messageClass = 'alert alert-success';
        } else {
          self.$data.message = result.message;
          self.$data.messageClass = 'alert alert-danger';
        }
      }).fail(function(stat) {
        self.$data.message = '処理を実行できませんでした。';
        self.$data.messageClass = 'alert alert-danger';
      }).always(function() {
      });
    },
    reset: function() {
      var temp = $("#uploadFileSpan").html();
      $("#uploadFileSpan").html(temp);
      this.resetDialog();
    },
  }
});
function buildScore(score) {
  var resultScore = '';
  for (var i = 1; i <= 5; i++) {
    if (i <= score) {
      resultScore = resultScore + '★';
    } else {
      resultScore = resultScore + '☆';
    }
  }
  return resultScore;
}

