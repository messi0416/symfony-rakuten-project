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
var vmComponentVoucherNumberListItem = {
    template: '#templateVoucherNumberListTableRow'
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
var vmVoucherNumberListTable = new Vue({
  el: '#voucherNumberListTable'
  , delimiters: ['(%', '%)']
  , data: {
    list: [] // データ
    , url: null
    , initialized: false
    , searchParameter: null // SearchParameter はURLとパラメータの変換などを管理する共通クラス
    , searchParams: {} // searchParams は SearchParameter と連動し、実際の検索条件を管理する
    , conditions: {} // フォームと連動する、入力中の検索条件。「検索」ボタン押下で searchParams へ反映。（例えばページング時に入力しかけた検索条件を使わないため）
  }
  , components: {
    'result-item': vmComponentVoucherNumberListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      let self = this;
      // URL文字列取得
      self.url = $(self.$el).data('url');
      
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
      if (!self.searchParams.salesDateFrom) {
        // クエリパラメータが指定されない場合、受注日に初期値を設定する。
        let fromDateStr = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddMonth(new Date(),-6),false,false);
        self.searchParams.salesDateFrom = fromDateStr;
      }

      // クエリパラメータを画面入力検索条件に反映
      self.conditions = {...self.searchParams};

      var dateOptions = {
        language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      };
      $('#salesDateFrom').datepicker(dateOptions)
        .on({
          changeDate: function () {
            self.conditions.salesDateFrom = $(this).val();
          },
          clearDate: function () {
            self.conditions.salesDateFrom = null;
          },
        });
      $('#salesDateTo').datepicker(dateOptions)
      .on({
        changeDate: function () {
          self.conditions.salesDateTo = $(this).val();
        },
        clearDate: function () {
          self.conditions.salesDateTo = null;
        },
      });
      this.initialized = true;
    });
  }

  , computed: {
  }

  , methods: {

    /**
   　 * 指定ページ表示。
     */
    showPage: function() {
      let self = this;
      // 初期化が済んでいない場合にはreturn
      if (!this.initialized) {
        return;
      }

      this.initialized = false;
      $.Vendor.WaitingDialog.show("loading ...");

      // データ読み込み処理
      var data = {
          condition: {
            salesDateFrom       : self.searchParams.salesDateFrom
            , salesDateTo       : self.searchParams.salesDateTo
            , customerName      : self.searchParams.customerName.replace(/　/g, " ")
            , customerNameWithoutSpace: self.searchParams.customerNameWithoutSpace.replace(/[　 ]/g, "")
            , customerNameKana  : self.searchParams.customerNameKana.replace(/　/g, " ")
            , tel               : self.searchParams.tel.replace(/-/g, "")
            , postCode          : self.searchParams.postCode.replace(/-/g, "")
            , address           : self.searchParams.address
            , email             : self.searchParams.email
            , voucherNumber     : self.searchParams.voucherNumber
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
                voucherNumber : item.voucher_number
                , salesDate : item.sales_date.substr(0, 10)
                , shippingDate : (item.shipping_date != null && item.shipping_date != '0000-00-00 00:00:00') ? item.shipping_date.substr(0, 10) : ''
                , status : item.sales_status
                , cancelStatus : item.cancel_status
                , shopName : item.shop_name
                , customerName : item.customer_name
                , customerTel : item.customer_tel
                , customerAddress : item.customer_address
                , neUrl : "https://main.next-engine.com/Userjyuchu/jyuchuInp?kensaku_denpyo_no=" + item.voucher_number + "&jyuchu_meisai_order=jyuchu_meisai_gyo"
                , detailCount : item.detail_count
              }

              self.list.push(row);
            }
            if (result.message != null && result.message.length > 0) {
              vmGlobalMessage.setMessage(result.message, 'alert alert-warning');
            }

          } else {
            let message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-warning');
          }
          
          self.searchParameter.setValues(self.searchParams);
          // URL 更新
          var queryString = self.searchParameter.generateQueryString();
          var url = window.location.pathname + (queryString.length > 0 ? ('?' + queryString) : '');
          window.history.pushState(null, null, url);
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');
        })
        . always(function() {
          self.initialized = true;
          $.Vendor.WaitingDialog.hide();
        });

    }

    /**
     * 新規検索実行
     * 検索フォームに入力されている値を、検索パラメータにコピーして検索を実行する。
     */
    , search: function() {
      vmGlobalMessage.clear(); // グローバルメッセージの表示を消す
      let self = this;
      self.list = [];
      const searchParameter = this.getInitSearchParameter(); // 新規インスタンス
      self.searchParams = searchParameter.getParams();
      self.searchParameter = searchParameter;
      Object.assign(self.searchParams, self.conditions);
      this.showPage();
    }

    /**
     * 初期検索条件
     */
    , getInitSearchParameter: function() {
      var searchParameter = new $.Plusnao.SearchParameter;
      searchParameter.addParam('salesDateFrom', 'string', 'sf');
      searchParameter.addParam('salesDateTo', 'string', 'st');
      searchParameter.addParam('customerName', 'string', 'name');
      searchParameter.addParam('customerNameWithoutSpace', 'string', 'namens');
      searchParameter.addParam('customerNameKana', 'string', 'kana');
      searchParameter.addParam('tel', 'string', 'tel');
      searchParameter.addParam('postCode', 'string', 'zip');
      searchParameter.addParam('address', 'string', 'address');
      searchParameter.addParam('email', 'string', 'email');   
      searchParameter.addParam('voucherNumber', 'string', 'voucherNumber');      
      return searchParameter;
    }

  }

});
