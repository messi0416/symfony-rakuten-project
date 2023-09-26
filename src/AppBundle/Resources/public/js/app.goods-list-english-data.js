/**
 * 管理画面 セット商品一覧 JS
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
    setMessage: function(message, cssClass, autoHide) {
      cssClass = cssClass || 'alert alert-info';
      autoHide = autoHide || true;

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
      }
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
var vmComponentEnglishDataListItem = {
    template: '#templateEnglishDataListTableRow'
  , props: [
        'item'
      , 'detailUrlBase'
  ]
  , data: function() {
    return {
    };
  }
  /* もし、this.item の直接参照がいやなら、下記のwatchでインスタンスのプロパティを更新する必要がある。
  , watch: {
    item: function() {
      this.orderDate          = this.item.orderDate;
      this.paymentMethod      = this.item.paymentMethod;
      this.paymentType        = this.item.paymentType;
      this.purchaseQuantity   = this.item.purchaseQuantity;
      this.sunPaymentReminder = this.item.sunPaymentReminder;
      this.voucherNumber      = this.item.voucherNumber;
    }
  }
  */
  , computed: {
    detailUrl: function() {
      return this.detailUrlBase + '?code=' + this.item.daihyoSyohinCode;
    }
    , displayManualInput: function() {
      return this.item.englishTitle && this.item.englishTitle.length > 0
             ? (this.item.manualInput ? '手入力' : '自動')
             : ''
             ;
    }
  }
  , methods: {
  }
};


// 一覧画面 一覧表
var vmEnglishDataListTable = new Vue({
    el: '#englishDataListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    // , page: 1
    // , pageItemNum: 50
    , pageItemNumList: [ 20, 50, 100 ]

    , url: null

    , initialized: false
    , searchParameter: null
    , searchParams: {}

    , storageSearchParams: null

    , orders: {
      //  syohinCode: null
      //, shopStock: null
      //, freeStock: null
      //, orderNum: null
      //, cost: null
      //, basePrice: null
      //, currentPrice: null
      //, labelType: null
    }

  }
  , components: {
      'result-item': vmComponentEnglishDataListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.detailUrlBase = $(this.$el).data('detailUrlBase');

      var searchParameter = this.getInitSearchParameter();

      this.storageSearchParams = new $.Plusnao.Storage.Local($.Plusnao.Const.StorageKey.GOODS_ENGLISH_DATA_SEARCH_PARAMS);

      // 絞込条件取得
      var qp = $.Plusnao.QueryString.parse();
      // クエリパラメータから
      if (Object.keys(qp).length > 0) {
        searchParameter.setValuesWithAlias(qp);

      // localStorageから取得
      } else {
        var params = this.storageSearchParams.get();
        if (params) {
          searchParameter.setValues(params);

        // 引き継ぎなしの初期状態
        } else {
          searchParameter.setValue('registered', 1); // 「登録済み」がデフォルト

        }
      }

      this.searchParams = searchParameter.getParams();
      this.searchParameter = searchParameter;

      this.initialized = true;
      this.showPage({
          page: this.searchParams.page
        , pageItemNum: this.searchParams.pageItemNum
      });
    });
  }

  , computed: {
  }

  , methods: {

    showPage: function(pageInfo) {

      // 初期化が済んでいない場合にはreturn
      if (!this.initialized) {
        return;
      }

      this.initialized = false;

      var self = this;

      // Show loading
      // $.Vendor.WaitingDialog.show('loading ...');

      var page = pageInfo.page;

      // データ読み込み処理
      var data = {
          page: page
        , limit: pageInfo.pageItemNum
        , conditions: {
              daihyo_syohin_code: self.searchParams.syohinCode
            , registered        : self.searchParams.registered
            , manual_input      : self.searchParams.manualInput
            , check_flg         : self.searchParams.checked
        }
        , orders: {
          //  daihyo_syohin_code: this.orders.syohinCode
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
            for (var i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  daihyoSyohinCode : item.daihyo_syohin_code
                , englishTitle: item.english_title
                , manualInput: item.manual_input != 0
                , checkFlg: item.check_flg != 0
                , imageUrl: item.image_url
              };

              self.list.push(row);
            }

            // ページ情報更新（子コンポーネントで変更できないため親で更新するが、タイミングは読み込み成功してのここ）
            self.totalItemNum = Number(result.count);
            self.searchParams.page = page;
            self.searchParams.pageItemNum = pageInfo.pageItemNum;

            self.searchParameter.setValues(self.searchParams);

            // 検索条件 localStorage保存
            self.storageSearchParams.set(self.searchParams);

            // URL 更新
            var queryString = self.searchParameter.generateQueryString();
            var url = window.location.pathname + (queryString.length > 0 ? ('?' + queryString) : '');
            window.history.pushState(null, null, url)

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          // $.Vendor.WaitingDialog.hide();

          self.initialized = true;
        });

    }

    , showFirstPage: function() {
      var pageInfo = {
          page: 1
        , pageItemNum: this.searchParams.pageItemNum
      };
      this.showPage(pageInfo);
    }

    , toggleOrder: function(key) {
      if (this.orders[key]) {
        if (this.orders[key] == 1) {
          this.orders[key] = -1;
        } else {
          this.orders[key] = null;
        }
      } else {
        var k;
        for (k in this.orders) {
          if (this.orders.hasOwnProperty(k)) {
            this.orders[k] = null;
          }
        }
        this.orders[key] = 1;
      }

      // リロード
      this.showFirstPage();
    }

    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function(key) {
      if (!this.orders[key]) {
        return '';
      }
      return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    }

    /**
     * 初期検索条件
     */
    , getInitSearchParameter: function() {
      var searchParameter = new $.Plusnao.SearchParameter;

      searchParameter.addParam('syohinCode', 'string', 'c');
      searchParameter.addParam('registered', 'integer', 'r');
      searchParameter.addParam('manualInput', 'integer', 'm');
      searchParameter.addParam('checked', 'integer', 'ck');

      searchParameter.addParam('page', 'integer', 'p', 1);
      searchParameter.addParam('pageItemNum', 'integer', 'pn', 50);

      return searchParameter;
    }

    /**
     * 絞込条件削除 (localStorage)
     */
    , clearSearchParams: function() {
      this.searchParameter = this.getInitSearchParameter();
      this.searchParams = this.searchParameter.getParams();
      this.storageSearchParams.set(this.searchParams);
      this.showFirstPage();
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------


  }

});

