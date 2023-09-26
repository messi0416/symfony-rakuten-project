/**
 * 注残一覧 JS
 */

$(function() {

  var jobRequestData = {
    jobKey: null
  };

  Vue.config.debug = true;

  // ヘッダメニュー
  var vmHeaderMenu = new Vue({
      el: '#headerMenu'
    , data: {
      reassessmentAllUrl : null
      , reassessmentIndividuallyUrl : null
      , initialized : false
      , lastUpdated : null
    }
    , mounted: function() {
      this.$nextTick(function () {
        let self = this;
        
        // URL文字列取得
        self.reassessmentAllUrl = $(self.$el).data('reassessmentAllUrl');
        self.reassessmentIndividuallyUrl = $(self.$el).data('reassessmentIndividuallyUrl');
        self.lastUpdated = LAST_UPDATED;
        
        self.initialized = true; // 二度押し防止
      });
    }
    , computed: {
      displayLastUpdated: function() {
        return this.lastUpdated ? this.lastUpdated : '--'; // Date型の場合PHP側で文字列変換する。
      }
    }
    , methods: {
      openCsvDownloadModal: function (e) {
        vmCsvDownloadModal.open();
        e.preventDefault();
      }
      , reassessmentAll: function() {
        var self = this;
        if (! self.initialized) {
          return;
        }
        self.initialized = false;
        if (confirm($(this.$el).data('message-confirm-all-agent') + "\n\n" + $(this.$el).data('message-confirm'))) {
          let data = {
            isAjax : 1
          }
          $.Vendor.WaitingDialog.show("loading ...");
          $.ajax({
              url: this.reassessmentAllUrl
            , data: data
            , type: "POST"
          }).done(function (result) {
            if (result.status == 'ok') {
              self.lastUpdated = result.lastUpdated;
              alert($(self.$el).data('message-complete') + "\n\n" + $(self.$el).data('message-reload-page'));
              vmVendorOrderListTable.showPage();
            } else {
              alert('ERROR: failed to update unallocated.' + result.message);
            }
          }).fail(function (stmt) {
            alert('ERROR: failed to update unallocated.');
          }).always(function() {
            $.Vendor.WaitingDialog.hide();
            self.initialized = true;
          });
        } else {
          self.initialized = true;
        }
      }
      , reassessmentIndividually: function() {
        var self = this;
        if (! self.initialized) {
          return;
        }
        self.initialized = false;
        if (confirm($(this.$el).data('message-confirm-individual-agent') + "\n\n" + $(this.$el).data('message-confirm'))) {
          let data = {
            isAjax : 1
          }
          $.Vendor.WaitingDialog.show("loading ...");
          $.ajax({
              url: this.reassessmentIndividuallyUrl
            , data: data
            , type: "POST"
          }).done(function (result) {
            if (result.status == 'ok') {
              self.lastUpdated = result.lastUpdated;
              alert($(self.$el).data('message-complete') + "\n\n" + $(self.$el).data('message-reload-page'));
              vmVendorOrderListTable.showPage();
            } else {
              alert('ERROR: failed to update unallocated.' + result.message);
            }
          }).fail(function (stmt) {
            alert('ERROR: failed to update unallocated.');
          }).always(function() {
            $.Vendor.WaitingDialog.hide();
            self.initialized = true;
          });
        } else {
          self.initialized = true;
        }
      }
    }
  });

  // 一覧テーブル 行コンポーネント
  var vmComponentOrderListItem = {
    template: "#result-item",
    props: [
        'item'
      , 'qualityLevelList'
      , 'shippingTypeList'
    ],
    data: function() { // 編集項目は独自保持
      return {
        inEdit: false
        , nowLoading: false
        , hostMain: null
        , editOrderNum: null
        , shippingNumber: this.item.shippingNumber
        , receiveOrderNumber: this.item.receiveOrderNumber
        , warehousingNumber: this.item.warehousingNumber
      };
    },
    watch: { // itemが変わったらdataは初期化。ページングの際、コンポーネントが使いまわされるため
      item: function(newItem, oldItem) {
        this.inEdit = false;
        this.newLoading = false;
        this.hostMain = false;
        this.shippingNumber = newItem.shippingNumber;
        this.receiveOrderNumber = newItem.receiveOrderNumber;
        this.warehousingNumber = newItem.warehousingNumber;
        
      }
    },
    computed: {
      displayOrderDate: function() {
        return this.item.orderDate ? $.Plusnao.Date.getDateString(this.item.orderDate) : '';
      }
      , isUnallocated: function() {
        return this.item.unallocatedFlg == '1';
      }
      , isOrdered: function() {
        return this.item.remainStatus == 'ORDERED';
      }
      , isArrived: function() {
        return this.item.remainStatus == 'ARRIVED';
      }
      , isWaited: function() {
        return this.item.remainStatus == 'WAITED';
      }
      , isShipped: function() {
        return this.item.remainStatus == 'SHIPPED';
      }
      , isShortage: function() {
        return this.item.remainStatus == 'SHORTAGE';
      }
      , displaySkuCode: function() {
        return this.item.syohinCode;
      }

      /// 行 背景CSSクラス
      , rowCss: function() {
        switch (true) {
          case this.isOrdered: return 'info';
          case this.isArrived: return 'warning'; // そんな色はない？
          case this.isWaited: return 'warning';
          case this.isShipped: return 'success';
          case this.isShortage: return 'danger';
        }
      }

      , editSpecButtonCss: function () {
        var css = 'btn-default';
        if (!this.item.product) {
          return;
        }

        // 材質・DESCRIPTION未設定
        if (this.item.isUnsetMaterialDescription) {
          css = 'btn-warning';
        }

        // 重量・サイズ未設定
        if (this.item.isUnsetWeightSize) {
          css = 'btn-danger';
        }

        return css;
      }

      , editSpecUrl: function() {
        return vmVendorOrderListTable.editSpecUrl + '?code=' + this.item.syohinCode;
      }

      , stockedCss: function () {
        return this.item.stockedFlag ? null : 'nonStockedBorder';
      }

      , displayRemainOrderedDate: function() {
        return this.item.remainOrderedDate ? $.Plusnao.Date.getDateString(this.item.remainOrderedDate) : '';
      }
      , displayRemainArrivedDate: function() {
        return this.item.remainArrivedDate ? $.Plusnao.Date.getDateString(this.item.remainArrivedDate) : '';
      }
      , displayRemainWaitingDate: function() {
        return this.item.remainWaitingDate ? $.Plusnao.Date.getDateString(this.item.remainWaitingDate) : '';
      }
      , displayRemainShippingDate: function() {
        return this.item.remainShippingDate ? $.Plusnao.Date.getDateString(this.item.remainShippingDate) : '';
      }
      , displayRemainStockOutDate: function() {
        return this.item.remainStockOutDate ? $.Plusnao.Date.getDateString(this.item.remainStockOutDate) : '';
      }
      /** レビュー平均を★で表示。小数点以下切り捨て */
      , displayReviewScoreStar: function() {
        var resultScore = '';
        // レビューなしの場合は ----- とでもするか
        if (this.item.product.review_point_num === 0) {
          return "-----";
        }
        for (var i = 1; i <= 5; i++) {
          if (i <= this.item.product.review_point_ave) {
            resultScore = resultScore + '★';
          } else {
            resultScore = resultScore + '☆';
          }
        }
        return resultScore;
      }
      /** レビューサマリを表示。「平均(件数)」 */
      , displayReviewScoreSummary: function() {
        // レビューなしなら空
        if (this.item.product.review_point_num > 0) {
          const ave = Math.floor(this.item.product.review_point_ave * Math.pow(10, 2)) / Math.pow(10, 2); // 小数点以下2桁に切り捨て
          return ave + "(" + this.item.product.review_point_num + ")";
        }
        return null;
      }

      , isVendorCommentNew: function() {
        var isNew = false;
        if (
             this.item.vendorComment
          && (
               (! this.item.vendorCommentProcessed)
            || this.item.vendorCommentUpdated > this.item.vendorCommentProcessed
          )
        ) {
          isNew = true;
        }
        return isNew;
      }
      , shippingTypeString: function() {
        return this.shippingTypeList[this.item.shippingType];
      }
      , shippingTypeLabelCssClass: function() {
        if (this.item.shippingType == '1') {
          return 'shippingTypeAir btn-info';
        } else {
          return 'shippingTypeContainer btn-warning';
        }
      }
    }
    , mounted: function() {
      this.$nextTick(function () {
        var self = this;
        self.hostMain = HOST_MAIN;
      });
    },
    methods: {

      /**
       * 注残ステータス更新
       */
      changeStatus: function(status, flag) {
        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            voucher_id : self.item.voucherId
          , status: status
          , flag: flag ? 1 : 0
        };

        $.ajax({
            type: "POST"
          , url: vmVendorOrderListTable.updateStatusUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              var item = result.result;

              self.item.remainStatus = item.remain_status;
              self.item.remainOrderedDate = (item.remain_ordered_date ? new Date(item.remain_ordered_date.replace(/-/g, "/")) : null); // replace for firefox, IE
              self.item.remainArrivedDate = (item.remain_arrived_date ? new Date(item.remain_arrived_date.replace(/-/g, "/")) : null); // replace for firefox, IE
              self.item.remainWaitingDate = (item.remain_waiting_date ? new Date(item.remain_waiting_date.replace(/-/g, "/")) : null); // replace for firefox, IE
              self.item.remainShippingDate = (item.remain_shipping_date ? new Date(item.remain_shipping_date.replace(/-/g, "/")) : null); // replace for firefox, IE
              self.item.remainStockOutDate = (item.remain_stockout_date ? new Date(item.remain_stockout_date.replace(/-/g, "/")) : null); // replace for firefox, IE

              self.item.remainOrderedPerson = item.remain_ordered_person;
              self.item.remainArrivedPerson = item.remain_arrived_person;
              self.item.remainWaitingPerson = item.remain_waiting_person;
              self.item.remainShippingPerson = item.remain_shipping_person;
              self.item.remainStockOutPerson = item.remain_stockout_person;

            } else {
              alert('ERROR: failed to update status.');
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });
      }

      /**
       * 発送伝票番号 更新処理
       */
      , updateShippingNumber: function() {
        var self = this;

        // テキストボックス未入力でフォーカスを外すと元の値がnullのときは空文字と認識される
        if (self.item.shippingNumber === null && self.shippingNumber === '') {
          return;
        }
        if (self.item.shippingNumber === self.shippingNumber) {
          return;
        }

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            voucher_id: self.item.voucherId
          , shipping_number: self.shippingNumber // self.item ではない。
        };

        $.ajax({
            type: "POST"
          , url: vmVendorOrderListTable.updateShippingNumberUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              var item = result.result;

              self.item.shippingNumber = item.shipping_number;

            } else {
              alert('ERROR: failed to update shipping number.');
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
            // テキストボックスの値を更新（失敗時は復元）
            self.shippingNumber = self.item.shippingNumber;
          });
      }

      /**
       * 受注番号 更新処理
       */
      , updateReceiveOrderNumber: function() {
        var self = this;

        // テキストボックス未入力でフォーカスを外すと元の値がnullのときは空文字と認識される
        if (self.item.receiveOrderNumber === null && self.receiveOrderNumber === '') {
          return;
        }
        if (self.item.receiveOrderNumber === self.receiveOrderNumber) {
          return;
        }

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            voucher_id: self.item.voucherId
          , receive_order_number: self.receiveOrderNumber // self.item ではない。
        };

        $.ajax({
            type: "POST"
          , url: vmVendorOrderListTable.updateReceiveOrderNumberUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              var item = result.result;

              self.item.receiveOrderNumber = item.receive_order_number;

            } else {
              alert('ERROR: failed to update receive order number.');
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
            // テキストボックスの値を更新（失敗時は復元）
            self.receiveOrderNumber = self.item.receiveOrderNumber;
          });
      }

      /**
       * 入庫番号 更新処理
       */
      , updateWarehousingNumber: function() {
        var self = this;

        // テキストボックス未入力でフォーカスを外すと元の値がnullのときは空文字と認識される
        if (self.item.warehousingNumber === null && self.warehousingNumber === '') {
          return;
        }
        if (self.item.warehousingNumber === self.warehousingNumber) {
          return;
        }
        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            voucher_id: self.item.voucherId
          , warehousing_number: self.warehousingNumber // self.item ではない。
        };

        $.ajax({
          type: "POST"
          , url: vmVendorOrderListTable.updateWarehousingNumberUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              var item = result.result;

              self.item.warehousingNumber = item.warehousing_number;

            } else {
              alert('ERROR: failed to update warehousing number.');
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
            // テキストボックスの値を更新（失敗時は復元）
            self.warehousingNumber = self.item.warehousingNumber;
          });
      }


      /**
       * ボタン表示 CSSクラス
       */
      , buttonClass: function(action) {
        var cssClass = 'btn-default';
        switch (action) {
          case 'unallocated':
            if (this.isUnallocated) {
              cssClass = 'btn-danger';
            }
            break;
          case 'ordered':
            if (this.isOrdered) {
              cssClass = 'btn-primary';
            }
            break;
          case 'arrived':
            if (this.isArrived) {
              cssClass = 'btn-warning';
            }
            break;
          case 'waited':
            if (this.isWaited) {
              cssClass = 'btn-warning';
            }
            break;
          case 'shipped':
            if (this.isShipped) {
              cssClass = 'btn-success';
            }
            break;
          case 'shortage':
            if (this.isShortage) {
              cssClass = 'btn-danger';
            }
            break;
        }

        return cssClass;
      }

      /**
       * ボタン 非活性
       */
      , buttonDisabled: function(action) {
        var disabled = null;
        switch (action) {
          case 'unallocated':
            if (this.isUnallocated || this.isOrdered || this.isWaited || this.isArrived || this.isShipped || this.isShortage) {
              disabled = 'disabled';
            }
            break;
          case 'ordered':
            if (this.isWaited || this.isArrived || this.isShipped || this.isShortage) {
              disabled = 'disabled';
            }
            break;
          case 'arrived':
            if (this.isWaited || this.isShipped || this.isShortage) {
              disabled = 'disabled';
            }
            break;
          case 'waited':
            if (this.isShipped || this.isShortage) {
              disabled = 'disabled';
            }
            break;
          case 'shipped':
            if (this.isShortage) {
              disabled = 'disabled';
            }
            break;
          case 'shortage':
            /* 利便性のためにいらないとのこと
            if (this.isArrived || this.isShipped) {
              disabled = 'disabled';
            }
            */
            break;
        }

        return disabled;
      }

      /**
       * ボタン 押下時フラグ
       */
      , buttonUpdateFlag: function(action) {
        var flag = true;
        switch (action) {
          // case 'unallocated':
          //   if (this.isUnallocated) {
          //     flag = false;
          //   }
          //   break;
          case 'ordered':
            if (this.isOrdered) {
              flag = false;
            }
            break;
          case 'arrived':
            if (this.isArrived) {
              flag = false;
            }
            break;
          case 'waited':
            if (this.isWaited) {
              flag = false;
            }
            break;
          case 'shipped':
            if (this.isShipped) {
              flag = false;
            }
            break;
          case 'shortage':
            if (this.isShortage) {
              flag = false;
            }
            break;
        }

        return flag;
      }

      /**
       * 連絡事項 編集ポップアップ
       */
      , openEditVendorCommentModal: function () {
        vmEditVendorCommentModal.open(this.item);
      }

      /**
       * 連絡事項 書き戻し
       */
      , updateVendorComment: function(item) {
        this.item.vendorComment = item.vendor_comment ? item.vendor_comment.substring(0, 50) : null;
        this.item.vendorCommentUpdated = item.vendor_comment_updated ? new Date(item.vendor_comment_updated.replace(/-/g, "/")) : null; // replace for firefox, IE

        // 結局こっちも必要。うーん。
        this.vendorComment = this.item.vendorComment;
        this.vendorCommentUpdated = this.item.vendorCommentUpdated;
      }

      /**
       * 発注数編集 切替
       */
      , changeEditOrderNum: function(flag) {
        if (flag == 'off') {
          this.inEdit = false;
          this.editOrderNum = this.item.orderNum; // リセット
        } else {
          this.inEdit = true;
          this.editOrderNum = this.item.orderNum;
        }
      }

      /**
       * 発注数編集 更新実行
       */
      , submitEditOrderNum: function() {
        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            voucher_id: self.item.voucherId
          , order_num: self.editOrderNum // self.itemではない。
        };

        $.ajax({
            type: "POST"
          , url: vmVendorOrderListTable.updateOrderNumUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              var item = result.result;

              self.item.orderNum = item.order_num;
              self.item.remainNum = item.remain_num;

              // 表示値を更新
              self.orderNum = self.item.orderNum;
              self.remainNum = self.item.remainNum;
              self.editOrderNum = self.item.orderNum;

              self.changeEditOrderNum('off');

            } else {
              if (result.message) {
                alert(result.message);
              } else {
                alert('ERROR: failed to update order amount.');
              }
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });
      }

      /**
       * 伝票操作モーダル open
       */
      , openManipulateModal: function() {
        vmManipulateOrderModal.open(this.item);
      }

      /**
       * 発送種別変更
       */
      , openChangeShippingTypeModal: function () {
        vmChangeShippingTypeModal.open(this.item);
      }
    }
  };

  // 注残一覧画面 メイン
  var vmVendorOrderListTable = new Vue({
    el: '#vendorOrderListTable',
    data: {
      // データ
        pageData: [] 
      , totalItemNum: 0 // ページングのデータ総件数
      , products: {}
      , initialized: true // 検索実行中は false
      , searchSummary: { // 検索結果サマリ
          record_count: 0 // データ総件数
        , remain_sum: 0 // 注残合計
        , remain_cost_sum: 0 // 注残金額JPY合計
        , sku_count: 0 // SKU種類数
        , untreated_count : null // 未処理レコード数
        , ordered_count : null // 発注済レコード数
        , arrived_count : null // 入荷済レコード数
        , waited_count : null // 出荷待レコード数
        , shipped_count : null // 出荷済レコード数
        , shortage_count : null // 不足レコード
        , empty_sku_count : null // SKUが紐づかないレコード数
        , empty_shipping_number_count : null // 発注伝票番号がないレコード数
        , incomplete_sku_count : null // SKUがデータ不足のレコード数
      }
      
      , pageItemNumList: [ 20, 50, 100, 200 ]
      , searchParameter: null // SearchParameter はURLとパラメータの変換などを管理する共通クラス
      , searchParams: {}      // searchParams は SearchParameter と連動し、実際の検索条件を管理する
      , conditions: {} // フォームと連動する、入力中の検索条件。「検索」ボタン押下で searchParams へ反映。（例えばページング時に入力しかけた検索条件を使わないため）
      , isSearched: false // conditions の値が検索済みで、searchParams と一致していれば true。変更されると再検索までfalseとなる

      // 絞込プルダウン用配列
      , remainStatusList: []
      , alertList: []
      , bulkList: []

      // 一覧表示ラベル切り替え用配列
      , shippingTypeList: []

      // 処理URL
      , baseUrl: null // （基本URL）
      , searchUrl: null
      , searchLabelUrl: null
      , updateStatusUrl : null
      , updateShippingNumberUrl : null
      , updateReceiveOrderNumberUrl : null
      , updateWarehousingNumberUrl : null
      , updateOrderNumUrl: null
      , labelPdfListUrl: null
      , editSpecUrl: null

      // 品質チェック
      , qualityLevelList: {}
      
      // 一括処理
      , bulkTrigger : ""
    }
    , components: {
      'result-item': vmComponentOrderListItem // 一覧テーブル
    }
    , mounted: function() {
      this.$nextTick(function () {
        let self = this;
        
        // URL文字列取得
        self.baseUrl = $(self.$el).data('url');
        self.searchUrl = $(self.$el).data('searchUrl');
        self.searchLabelUrl = $(self.$el).data('searchLabelUrl');
        self.updateStatusUrl = $(self.$el).data('updateStatusUrl');
        self.updateShippingNumberUrl = $(self.$el).data('updateShippingNumberUrl');
        self.updateReceiveOrderNumberUrl = $(self.$el).data('updateReceiveOrderNumberUrl');
        self.updateWarehousingNumberUrl = $(self.$el).data('updateWarehousingNumberUrl');
        self.updateOrderNumUrl = $(self.$el).data('updateOrderNumUrl');
        self.labelPdfListUrl = $(self.$el).data('labelPdfListUrl');
        self.editSpecUrl = $(self.$el).data('editSpecUrl');
  
        self.remainStatusList = remainStatusListData;
        self.alertList = alertListData;
        self.bulkList = bulkListData;
        
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
        if (!self.searchParams.page) {
          self.searchParams.page = 1;
        }
        if (!self.searchParams.pageItemNum) {
          self.searchParams.pageItemNum = 100;
        }
        // クエリパラメータの値コピー、プルダウンは値がなければ初期化
        Object.assign(self.conditions, self.searchParams);
        // null合体演算子はEclipseでエラー表示になったので三項演算子
        self.conditions.status = self.searchParams.status ? self.searchParams.status : '';
        self.conditions.vendorComment = self.searchParams.vendorComment ? self.searchParams.vendorComment : '';
        self.conditions.unallocated = self.searchParams.unallocated ? self.searchParams.unallocated : '';
        self.conditions.shippingType = self.searchParams.shippingType ? self.searchParams.shippingType : '';
        self.conditions.alert = self.searchParams.alert ? self.searchParams.alert : '';
        
        var dateOptions = {
          language: 'ja'
          , format: 'yyyy-mm-dd'
          , autoclose: true
        };
        $('#conditionOrderDateFrom').datepicker(dateOptions)
          .on({
            changeDate: function () {
              self.conditions.orderDateFrom = $(this).val();
              self.isSearched = false;
            },
            clearDate: function () {
              self.conditions.orderDateFrom = null;
              self.isSearched = false;
            },
          });
        $('#conditionOrderDateTo').datepicker(dateOptions)
          .on({
            changeDate: function () {
              self.conditions.orderDateTo = $(this).val();
              self.isSearched = false;
            },
            clearDate: function () {
              self.conditions.orderDateTo = null;
              self.isSearched = false;
            },
          });
        
        // tooltip 有効化: th
        // $(self.$el).find('th span').tooltip();
        $(self.$el).find('a[data-toggle="tooltip"]').tooltip();
  
        // tooltip 有効化: エラーメッセージ
        $(self.$el).find('form .form-control').tooltip({
          html: true
          , template: '<div class="tooltip error" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
        });
  
        self.shippingTypeList = SHIPPING_TYPE_LIST;
        
        this.showPage(null, true);
      }); 
    }
    , computed: {
      isBulkSelected: function() {
        return this.bulkTrigger !== '';
      }
      , sortMarks: function() {

        var fields = [
            'voucherNumber'
          , 'syohinCode'
          , 'orderNum'
          , 'remainNum'
          , 'unallocatedFlg'
          , 'scheduledDate'
          , 'orderDate'
          , 'remainOrderedDate'
          , 'remainArrivedDate'
          , 'remainWaitingDate'
          , 'remainShippingDate'
          , 'remainStockOutDate'
          , 'shippingNumber'
          , 'supportColName'
          , 'supportRowName'
          , 'cost'
          , 'category'
        ];

        var ret = {};
        for (var i = 0; i < fields.length; i++) {
          ret[fields[i]] = this.getSortMarkCssClass(fields[i]);
        }

        return ret;
      }
      
      , displayRemainSum : function() {
        return $.Plusnao.String.numberFormat(this.searchSummary.remain_sum);
      }
      , displayRemainCostSum : function() {
        return $.Plusnao.String.numberFormat(this.searchSummary.remain_cost_sum);
      }
      , displaySkuCount : function() {
        return $.Plusnao.String.numberFormat(this.searchSummary.sku_count);
      }
    },
    methods: {
      
      /**
      　* 指定ページ表示。
       * 現在処理中の検索条件（フォーム入力値ではなく、内部保持値）で検索を行い、指定ページを表示する。
       * 他の様々な処理（新規検索、ページング、ソート、更新の後処理など）から呼ばれる。
       * @param pageInfo 表示ページの情報
       * @param isNewSearch　新規検索かどうか　conditionの値をsearchParamsと反映した後の、新規検索であれば、成功時はisSearched = true とする
       */
      showPage: function(pageInfo, isNewSearch = false) {
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
        $.Vendor.WaitingDialog.show("loading ...");      
        
        // パラメータ構築
        let page = pageInfo.page;
        
        // データ読み込み処理
        let data = {
            page: page
          , limit: pageInfo.pageItemNum
          , search: {
              syohin_code: self.searchParams.syohinCode
            , order_date_from: self.searchParams.orderDateFrom
            , order_date_to: self.searchParams.orderDateTo
            , voucher_number: self.searchParams.voucherNumber
            , status: self.searchParams.status
            , vendor_comment: self.searchParams.vendorComment
            , unallocated: self.searchParams.unallocated
            , shipping_type: self.searchParams.shippingType
            , shipping_number: self.searchParams.shippingNumber
            , receive_order_number: self.searchParams.receiveOrderNumber
            , warehousing_number: self.searchParams.warehousingNumber
            , shipping_operation_number: self.searchParams.shippingOperationNumber
            , person: self.searchParams.person
            , alert: self.searchParams.alert
          }
          , sortKey: self.searchParams.sortField
          , sortOrder: self.searchParams.sortOrder
        };
  
        $.ajax({
            type: "GET"
          , url: self.searchUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {  
            if (result.status == 'ok') {

              self.products = result.products;
              self.pageData = [];
              
              // 取得したデータをオブジェクトに変換、追加情報付与
              for (let i = 0; i < result.list.length; i++) {
                let item = result.list[i];
                let row = self.convertJsonToObject(item);
                row.product = self.products[row.syohinCode];
                if (row.product == null) {
                  row.isUnsetMaterialDescription = true;
                  row.isUnsetWeightSize = true;
                } else {
                  // 材質・DESCRIPTION未設定
                  row.isUnsetMaterialDescription = (row.product.hint_ja == null || row.product.hint_ja == '') || (row.product.hint_cn == null || row.product.hint_cn == '')
                    || (row.product.description_en == null || row.product.description_en == '') || (row.product.description_cn == null || row.product.description_cn == '');
                  // 重量・サイズ未設定
                  row.isUnsetWeightSize = Number(row.product.weight) <= 0
                    || !Number(row.product.depth) || !Number(row.product.width) || !Number(row.product.height);
                }
                self.pageData.push(row);
              }
  
              // ページ情報更新
              self.totalItemNum = result.count;
              self.searchSummary = result.summary
              self.searchParams.page = page;
              self.searchParams.pageItemNum = pageInfo.pageItemNum;
              self.searchParameter.setValues(self.searchParams);
              
              // URL 更新
              var queryString = self.searchParameter.generateQueryString();
              var url = window.location.pathname + (queryString.length > 0 ? ('?' + queryString) : '');
              window.history.pushState(null, null, url);
              if (isNewSearch) {
                self.isSearched = true;
              }
            } else {
              self.message = 'エラーが発生しました [' + result.message + ']';
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(function(stat) {
              self.message = 'エラーが発生しました';
              self.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $.Vendor.WaitingDialog.hide();
            self.initialized = true;
          });
        }
        /**
         * 現在保持している検索条件で1ページ目を表示する
         * @param isNewSearch 新規検索であれば true
         */
      , showFirstPage: function(isNewSearch = false) {
        var self = this;
        var pageInfo = {
            page: 1
          , pageItemNum: this.searchParams.pageItemNum
        };
        self.showPage(pageInfo, isNewSearch);
      }
      /**
      　* 新規検索実行
      　* 検索フォームに入力されている値を、検索パラメータにコピーして検索を実行する。
       */
      , search: function() {
        let self = this;
        let pageItemNum = self.searchParams.pageItemNum; // 新規検索しても、1ページの表示件数は維持（負荷対策）
        searchParameter = this.getInitSearchParameter(); // 新規インスタンス
        self.searchParams = searchParameter.getParams();
        self.searchParameter = searchParameter;
        Object.assign(self.searchParams, self.conditions);
        self.searchParams.pageItemNum = pageItemNum;
        this.showFirstPage(true);
      }
      /**
       * 検索フォームで検索条件を変更した場合にfalseとする
       * ※この方式だと、条件変更→戻す　とした場合も、再検索するまで false になったままなので、conditionの値とsearchParamsの値が一致しているかを
       * 随時比較したほうが良いが、うまく動かなかったのでひとまずこれ
       */
      , changeSearchedFlgFalse: function() {
        this.isSearched = false;
      }
      
      /**
       * 初期検索条件
       */
      , getInitSearchParameter: function() {
        var searchParameter = new $.Plusnao.SearchParameter;
        searchParameter.addParam('page', 'integer', 'p', 1);
        searchParameter.addParam('pageItemNum', 'integer', 'pn', 100);
        searchParameter.addParam('syohinCode', 'string', 'sc');        
        searchParameter.addParam('orderDateFrom', 'string', 'odf');
        searchParameter.addParam('orderDateTo', 'string', 'odt');                
        searchParameter.addParam('voucherNumber', 'integer', 'v');
        searchParameter.addParam('status', 'string', 's');
        searchParameter.addParam('vendorComment', 'string', 'c');
        searchParameter.addParam('unallocated', 'integer', 'ua');
        searchParameter.addParam('shippingType', 'integer', 'st');
        searchParameter.addParam('shippingNumber', 'string', 'sn');
        searchParameter.addParam('receiveOrderNumber', 'string', 'rn');
        searchParameter.addParam('warehousingNumber', 'string', 'wn');
        searchParameter.addParam('shippingOperationNumber', 'string', 'on');
        searchParameter.addParam('person', 'string', 'ps');
        searchParameter.addParam('alert', 'string', 'a');
        searchParameter.addParam('sortField', 'string', 'sf');
        searchParameter.addParam('sortOrder', 'integer', 'so');
        return searchParameter;
      }

      /**
       * Ajax取得配列を行オブジェクトに変換
       */
      , convertJsonToObject: function(item) {

        return {
            voucherId: item.id // 仕入先特定文字列の「id」と紛らわしいため置き換え
          , voucherNumber : item.voucher_number
          , shippingType : item.shipping_type
          , lineNumber : Number(item.line_number)
          , syohinCode : item.syohin_code
          , unallocatedFlg : item.unallocated_flg
          , daihyoSyohinCode: item.daihyo_syohin_code
          , orderNum : Number(item.order_num) || 0
          , remainNum : Number(item.remain_num) || 0
          , scheduledDate : (item.scheduled_date ? new Date(item.scheduled_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , comment : item.comment
          , sireCode : item.sire_code
          , agentCode : item.agent_code
          , orderDate : (item.order_date ? new Date(item.order_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , remainStatus : item.remain_status
          , remainOrderedDate : (item.remain_ordered_date ? new Date(item.remain_ordered_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , remainArrivedDate : (item.remain_arrived_date ? new Date(item.remain_arrived_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , remainWaitingDate : (item.remain_waiting_date ? new Date(item.remain_waiting_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , remainShippingDate : (item.remain_shipping_date ? new Date(item.remain_shipping_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , remainStockOutDate : (item.remain_stockout_date ? new Date(item.remain_stockout_date.replace(/-/g, "/")) : null) // replace for firefox, IE

          , remainOrderedPerson   : item.remain_ordered_person
          , remainArrivedPerson   : item.remain_arrived_person
          , remainWaitingPerson   : item.remain_waiting_person
          , remainShippingPerson  : item.remain_shipping_person
          , remainStockOutPerson  : item.remain_stockout_person

          , shippingNumber : item.shipping_number
          , shippingOperationNumber : item.shipping_operation_number
          , receiveOrderNumber : item.receive_order_number
          , warehousingNumber : item.warehousing_number
          , colName : item.colname
          , rowName : item.rowname
          , supportColName : item.support_colname
          , supportRowName : item.support_rowname
          , cost : Number(item.cost) || 0
          , vendorComment: item.vendor_comment
          , vendorCommentUpdated: (item.vendor_comment_updated ? new Date(item.vendor_comment_updated.replace(/-/g, "/")) : null) // replace for firefox, IE
          , vendorCommentProcessed: (item.vendor_comment_processed ? new Date(item.vendor_comment_processed.replace(/-/g, "/")) : null) // replace for firefox, IE

          , category : item.category ? item.category.split('/').join("\n") : ''
          , imageListPageUrl: item.image_list_page_url
          , imageUrl : item.image_url
          , detailUrl: item.mignonlindo_url
          , orderComment: item.order_comment
          , addresses: item.addresses
          , stockedFlag: item.stocked_flag == 1

          , qualityLevel: Number(item.quality_level)
        }
      }
      /**
       * ソートアイコンCSSクラス
       */
      , getSortMarkCssClass: function(field) {
        return (field == this.searchParams.sortField)
          ? (this.searchParams.sortOrder == 1 ? 'sortAsc' : 'sortDesc' )
          : 'sortFree';
      }

      /**
       * 一覧 並び順変更
       * @param fieldName
       */
      , switchSort: function(fieldName) {
        if (this.searchParams.sortField == fieldName) {
          // 降順 -> 昇順
          if (this.searchParams.sortOrder == -1) {
            this.searchParams.sortOrder = 1;

            // デフォルトに戻る
          } else {
            this.searchParams.sortField = null;
            this.searchParams.sortOrder = 1;
          }

        } else {
          this.searchParams.sortField = fieldName;
          this.searchParams.sortOrder = -1; // 降順が先
        }
        this.showFirstPage();
      }
      , checkDataRequire: function() {
        var result = {'isShippingNumberEmpty': false, 'isSkuEmpty': false};
        if (this.searchSummary.empty_shipping_number_count > 0) {
          result.isShippingNumberEmpty = true;
        }
        if (this.searchSummary.empty_sku_count > 0
            || this.searchSummary.incomplete_sku_count > 0) {
          result.isSkuEmpty = true;
        }
        return result;
      }
      , checkDataStatus: function() {
        var result = {'ordered': 0, 'arrived': 0, 'waited': 0, 'shipped': 0};
        result.ordered = this.searchSummary.ordered_count;
        result.arrived = this.searchSummary.arrived_count;
        result.waited = this.searchSummary.waited_count;
        result.shipped = this.searchSummary.shipped_count;        
        return result;
      }
      
      /**
       * 一括更新
       */
      , triggerExport: function(e) {
        if (this.bulkTrigger == 'EXPORT_SHIPPING') {
          var checkData = this.checkDataRequire();
          window.vmDialogShippingNumberEmpty.open(checkData);
        } else {
          var checkData = this.checkDataStatus();
          checkCount = 0;
          if (this.bulkTrigger == 'STATUS_UPDATE_ORDERED') {
            checkCount = checkData.ordered + checkData.arrived + checkData.waited + checkData.shipped;
          }
          if (this.bulkTrigger == 'STATUS_UPDATE_ARRIVED') {
            checkCount = checkData.arrived + checkData.waited + checkData.shipped;
          }
          if (this.bulkTrigger == 'STATUS_UPDATE_WAITED') {
            checkCount = checkData.waited + checkData.shipped;
          }
          if (this.bulkTrigger == 'STATUS_UPDATE_SHIPPING') {
            checkCount = checkData.shipped;
          }
          
          if(checkCount < 1){
            window.vmDialogConfirmExport.open(this.bulkTrigger);
          } else {
            window.vmDialogErrorUpdate.open();
          }
        }
      }
      , openLabelPdfDownloadModal: function (e) {
        let self = this;
        // 初期化が済んでいない場合にはreturn
        if (!this.initialized) {
          return;
        }
        
        this.initialized = false;
        $.Vendor.WaitingDialog.show("loading ...");
        // モーダルを二重に開くと、片方閉じたときに modal-open が消されて、残っているほうがスクロール出来なくなるので対応
        $('.modal').on('hidden.bs.modal', function () {
          if ($('.modal').is(':visible')) {
            $('body').addClass('modal-open');
          }
        });

        // データ読み込み処理
        const data = {
          search: {
              syohin_code: self.searchParams.syohinCode
            , order_date_from: self.searchParams.orderDateFrom
            , order_date_to: self.searchParams.orderDateTo
            , voucher_number: self.searchParams.voucherNumber
            , status: self.searchParams.status
            , vendor_comment: self.searchParams.vendorComment
            , unallocated: self.searchParams.unallocated
            , shipping_type: self.searchParams.shippingType
            , shipping_number: self.searchParams.shippingNumber
            , receive_order_number: self.searchParams.receiveOrderNumber
            , warehousing_number: self.searchParams.warehousingNumber
            , shipping_operation_number: self.searchParams.shippingOperationNumber
            , person: self.searchParams.person
            , alert: self.searchParams.alert
          }
          , sortKey: self.searchParams.sortField
          , sortOrder: self.searchParams.sortOrder
        };
  
        $.ajax({
            type: "GET"
          , url: self.searchLabelUrl
          , dataType: "json"
          , data
        })
          .done(function(result) {  
            if (result.status == 'ok') {
              // 引き渡すデータを作成
              let data = [];
              for (let i = 0; i < result.list.length; i++) {
                let item = result.list[i];
                data.push({
                    category: item.category
                  , syohinCode: item.syohin_code
                  , orderNum: Number(item.order_num) || 0
                  , remainNum: Number(item.remain_num) || 0
                  , colName: item.colname
                  , rowName: item.rowname
                  , supportColName: item.support_colname
                  , supportRowName: item.support_rowname
                });
              }
      
              window.vmLabelPrintList.open(data);

            } else {
              self.message = 'エラーが発生しました [' + result.message + ']';
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(function(stat) {
              self.message = 'エラーが発生しました';
              self.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $.Vendor.WaitingDialog.hide();
            self.initialized = true;
          });
        e.preventDefault();
      }
      // 一括処理のボタン非活性を選出
      , buttonBulkDisabled: function() {
        var disabled = null;

        if (!this.isBulkSelected || !this.isSearched) {
          disabled = 'disabled';
        } else if (this.totalItemNum == 0) {
          disabled = 'disabled';
        }

        return disabled;
      }
      , showModalSetting: function(e) {
        e.preventDefault();
        vmSettingRateModal.open();
      }

      /**
       * 発送方法変更 書き戻し
       * 発送方法変更ダイアログより呼ばれる。サーバデータはダイアログの処理で更新されるが、画面表示をここで更新する。
       */
      , updateShippingType: function(voucherId, newShippingType) {
        let self = this;
        let targetItem = self.pageData.find(item => item.voucherId == voucherId);
        targetItem.shippingType = newShippingType;
      }
      /**
       * 備考 書き戻し
       * 備考変更ダイアログより呼ばれる。サーバデータはダイアログの処理で更新されるが、画面表示をここで更新する。
       */
      , updateVendorComment: function(voucherId, newVendorComment) {
        let self = this;
        let targetItem = self.pageData.find(item => item.voucherId == voucherId);
        targetItem.vendorComment = newVendorComment;
      }
      /** 検索条件クリア */
      , clearSearchConditions: function() {
        let self = this;
        self.conditions = {};
        self.conditions.status = '';
        self.conditions.vendorComment = '';
        self.conditions.unallocated = '';
        self.conditions.shippingType = '';
        self.conditions.alert = '';
      }
    }
  });

  // モーダル
  var vmEditVendorCommentModal = new Vue({
    el: '#modalEditVendorComment',
    data: {
        caption: 'information'
      , message: ''
      , messageClass: 'alert-info'
      , noticeHidden: true
      , nowLoading: false

      , getUrl: null
      , updateUrl: null

      , item: {}
      , vendorComment: null
      , originalVendorComment: null
    }
    , mounted: function() {
      this.$nextTick(function () {
        let self = this;
        self.getUrl = $(self.$el).data('get-url');
        self.updateUrl = $(self.$el).data('update-url');
  
        // イベント登録
        $(self.$el).on('show.bs.modal', function(e) {
          self.nowLoading = true;

          $.ajax({
              type: "GET"
            , url: self.getUrl
            , dataType: "json"
            , data: {
                voucher_id: self.item.voucherId
            }
          })
            .done(function(result) {
              if (result.status == 'ok') {
                if (result.result.vendor_comment) {
                  self.vendorComment = result.result.vendor_comment.replace(/\\n/g, "\n");
                } else {
                  self.vendorComment = null;
                }
                self.originalVendorComment = self.vendorComment;
  
              } else {
                self.message = 'no information.';
                self.messageClass = 'alert alert-warning';
              }
  
              $('.modal-footer button.btn-primary', self.$el).show();
            })
            .fail(function(stat) {
              self.message = 'error';
              self.messageClass = 'alert alert-danger';
            })
            . always(function() {
              self.nowLoading = false;
            });
        });
      });
    },

    methods: {
      onSubmit: function() {
        var self = this;
        self.nowLoading = true;

        // Ajaxでキュー追加
        let data = {
            voucher_id: self.item.voucherId
          , vendor_comment: self.vendorComment
        };
        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {

              // 一覧の連絡事項へ書き戻し
              vmVendorOrderListTable.updateVendorComment(self.item.voucherId, result.result.vendor_comment);
              $(self.$el).modal('hide');

            } else {
              self.message = 'ERROR: failed to update comment.';
              self.messageClass = 'alert-danger';
              self.vendorComment = self.originalVendorComment;
            }
          })
          .fail(function(stat) {
            self.message = 'ERROR: failed to update.';
            self.messageClass = 'alert-danger';
            self.vendorComment = self.originalVendorComment;
          })
          .always(function() {
            self.nowLoading = false;
          });
      },

      open: function(item) {
        let self = this;
        self.nowLoading = true;
        self.item = item;

        this.vendorComment = null;
        this.originalVendorComment = null;
        $(this.$el).modal('show');
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  /**
   * CSVダウンロード
   */
  var vmCsvDownloadModal = new Vue({
    el: "#modalCsvDownload",
    data: {
        caption: ''
      , message: ''
      , messageClass: 'info'

      , searchConditions: {
          orderDateFrom: null
        , orderDateTo: null
      }
      , nowLoading: true
      , remainStatusList: null
    }
    , methods: {
      open: function() {
        const self = this;

        // 検索条件の受注日だけ親フォームからコピー
        $(self.$el).find('#csvDownloadDateFrom input').val(vmVendorOrderListTable.searchParams.orderDateFrom);
        $(self.$el).find('#csvDownloadDateTo input').val(vmVendorOrderListTable.searchParams.orderDateTo);
        
        self.nowLoading = false;
        self.remainStatusList = remainStatusListData;
        $(this.$el).modal('show');
      }

      , resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';

        this.list = [];
      }

      , onSubmit: function() {
        return true;
      }

      , clearForm: function() {
        var self = this;

        $(self.$el).find('#voucherNumber').val('');
        $(self.$el).find('#syohinCode').val('');
        $(self.$el).find('#shippingNumber').val('');
        $(self.$el).find('#receiveOrderNumber').val('');
        $(self.$el).find('#status').val('');

        // 検索フォーム 値引き継ぎ
        $(self.$el).find('#csvDownloadDateFrom input').val('');
        $(self.$el).find('#csvDownloadDateTo input').val('');
      }


    }
  });

  var vmSettingRateModal = new Vue({
    el: "#modalSettingRate",
    data: {
      message: '',
      nowLoading: true,
      rateUsd: '',
      urlGetRate: '',
      urlUpdateRate: '',
      messageClass: ''
    }
    , mounted: function() {
      this.$nextTick(function () {
        this.urlGetRate = $(this.$el).data('urlGetRate');
        this.urlUpdateRate = $(this.$el).data('urlUpdateRate');
      });
    },
    computed: {

    },
    methods: {
      open: function() {
        var self = this;
        self.nowLoading = true;
        if (self.rateUsd != '') {
          self.nowLoading = false;
        } else {
          $.ajax({
              type: "GET"
            , url: self.urlGetRate
            , dataType: "json"
            , data: {}
          }).done(function(result) {
            if (result.status == 'ok') {
              self.rateUsd = result.result.EXCHANGE_RATE_USD;
              self.nowLoading = false;
            }
          });
          self.resetDialog();
        }
        $(self.$el).modal('show');
      }
      , resetDialog: function() {
        this.message = '';
        this.messageClass = '';
      }
      , updateRateSetting: function(e) {
        e.preventDefault();
        var self = this;
        if (self.rateUsd == '') {
          self.message = 'Error';
          self.messageClass = 'warning';
          setTimeout(function() {
            self.resetDialog();
          }, 2000);
        } else {
          self.nowLoading = true;
          $.ajax({
              type: "POST"
            , url: self.urlUpdateRate
            , dataType: "json"
            , data: {rateUsd: self.rateUsd}
          }).done(function(result) {
            if (result.status == 'ok') {
              self.nowLoading = false;
              self.message = $(self.$el).data('message-update-success');
              self.messageClass = 'success';
              setTimeout(function() {
                $(self.$el).modal('hide');
                self.resetDialog();
              }, 2000);
            } else {
              self.messageClass = 'warning';
              self.message = $(self.$el).data('message-update-failure');
            }
          });
        }
      }
    }
  });


  /**
   * 伝票操作モーダル
   */
  var vmManipulateOrderModal = new Vue({
    el: "#modalManipulateOrder",
    data: {
        caption: ''
      , message: ''
      , messageClass: 'info'
      , nowLoading: true
      , showSubmitSplit: false

      , urlSubmitSplit : null
      , messageConfirmSplit: null
      , messageCompleteSplit : null

      , item: {}
      , newOrderNum: null
      , newOrderNumOption: {
          min: 0
        , max: null
      }
    }
    , mounted: function() {
      this.$nextTick(function () {
        var self = this;

        self.urlSubmitSplit = $(this.$el).data('urlSubmitSplit');
        self.messageConfirmSplit = $(this.$el).data('messageConfirmSplit');
        self.messageCompleteSplit = $(this.$el).data('messageCompleteSplit');
  
        // イベント登録
        $(self.$el).on('show.bs.modal', function(e) {
          self.nowLoading = false;
        });
      });
    },
    computed: {
      isShowSubmit: function() {
        return this.showSubmitSplit && this.newOrderNum > 0 && (this.item.orderNum - this.newOrderNum) > 0
      }
      , leftOrderNum: function() {
        return this.item.orderNum - this.newOrderNum;
      }
    },
    methods: {
      open: function(item) {
        var self = this;
        self.nowLoading = true;
        var max = item.remainNum == item.orderNum ? item.remainNum - 1 : item.remainNum;
        if (max < 0) { max = 0 } // これはイレギュラー
        self.newOrderNumOption.max = max;

        self.resetDialog();

        self.item = item;
        self.newOrderNum = 0;
        self.showSubmitSplit = true;

        $(self.$el).modal('show');
      }

      , resetDialog: function() {
        this.message = '';
        this.messageClass = '';

        this.item = {};
        this.newOrderNum = 0;
        this.showSubmitSplit = false;
      }

      , onSubmit: function() {

        var self = this;

        if (!confirm(self.messageConfirmSplit)) {
          return;
        }

        self.showSubmitSplit = false;

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
            parent_id: self.item.voucherId
          , move_num: self.newOrderNum
        };

        $.ajax({
            type: "POST"
          , url: self.urlSubmitSplit
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              vmVendorOrderListTable.showPage();
            } else {
              alert('ERROR: failed to update status. Please search again.');
              self.showSubmitSplit = true;
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
            self.showSubmitSplit = true;
          })
          .always(function() {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });

        return true;
      }
    }
  });

  /** 輸出書類出力 */
  window.vmDialogShippingNumberEmpty = new Vue({
    el: '#modalEmptyShipping'
    , data: {
      message: ''
      , showShippingNumberEmpty: false
      , showSkuEmpty: false
      , dataCheck: null
      , messageSku: ''
      , messageShippingNumber: ''
      , state: {jobKey: null}
      , dummyKey: null

      , timerPast: null
      , timerLoad: null

      , status: null
      , started: null
      , past: null
      , finished: null
      , info: {}
      , nowLoading: false
      , showFooter: true
      , showTitle: false
      , xhr: null
      , pushedToQueue: false
      , messagePushedToQueue: ''
    }
    , mounted: function() {
      this.$nextTick(function () {
        var self = this;
        self.messageSku = '<p>' + $(self.$el).data('message-sku-spec-empty') + '</p><p>' + $(self.$el).data('message-confirm-print') + '</p>';
        self.messageShippingNumber = '<p>' + $(self.$el).data('message-empty-shipping-number') + '</p><p>' + $(self.$el).data('message-confirm-print') + '</p>';
  
  
        $(self.$el).data('message-confirm-change');
  
        self.dummyKey = $(this.$el).data('dummyKey');
        self.checkUrlBase = $(this.$el).data('checkUrlStatusJob');
  
        $(self.$el).on('hide.bs.modal', function (e) {
          self.showFooter = true;
          self.pushedToQueue = false;
          self.messagePushedToQueue = '';
          self.showTitle = false;
          self.nowLoading = false;
          if(self.xhr) {
            self.xhr.abort();
          }
        });
        $(self.$el).on('show.bs.modal', function (e) {
          self.showFooter = true;
          self.pushedToQueue = false;
          self.messagePushedToQueue = '';
          self.showTitle = false;
          self.nowLoading = false;
        });
      });
    }
    , methods: {

      open: function(data) {
        this.showShippingNumberEmpty = false;
        this.showSkuEmpty = false;
        if(data.isShippingNumberEmpty) {
          this.message = this.messageShippingNumber;
          this.showShippingNumberEmpty = true;
        } else if(data.isSkuEmpty) {
          this.message = this.messageSku;
          this.showSkuEmpty = true;
        } else {
          this.message = '';
          this.showSkuEmpty = false;
          this.showShippingNumberEmpty =false;
        }
        if (this.message != '') {
          this.dataCheck = data;
          $(this.$el).modal('show');
        } else {
          $(this.$el).modal('show');
          this.message = '';
          this.showSkuEmpty = false;
          this.onSubmitExport();
        }
      },
      onSubmitShippingNumberEmpty: function(e) {
        this.showShippingNumberEmpty = false;
        if (this.dataCheck.isSkuEmpty) {
          this.message = this.messageSku;
          this.showSkuEmpty = true;
        } else {
          this.onSubmitExport();
        }
      },
      onSubmitSkuEmpty: function(e) {
        this.onSubmitExport();
      },
      onSubmitExport: function(e) {
        let self = this;
        let searchParams = vmVendorOrderListTable.searchParams;
        // 送信データ作成
        let data = {
            bulkTrigger: self.bulkTrigger
          , search: {
              syohin_code: searchParams.syohinCode
            , order_date_from: searchParams.orderDateFrom
            , order_date_to: searchParams.orderDateTo
            , voucher_number: searchParams.voucherNumber
            , status: searchParams.status
            , vendor_comment: searchParams.vendorComment
            , unallocated: searchParams.unallocated
            , shipping_type: searchParams.shippingType
            , shipping_number: searchParams.shippingNumber
            , receive_order_number: searchParams.receiveOrderNumber
            , warehousing_number: searchParams.warehousingNumber
            , shipping_operation_number: searchParams.shippingOperationNumber
            , person: searchParams.person
            , alert: searchParams.alert
            , is_empty_shipping_number: 1
            , sortBy: searchParams.sortField
            , direction: searchParams.sortOrder
          }
        };
        
        self.showFooter = false;
        self.showTitle = false;
        self.pushedToQueue = true;
        self.messagePushedToQueue = $(self.$el).data('message-pushed-to-queue');
        var url = $(self.$el).data('orderListExportExcel');
        self.xhr = $.ajax({
          type: "POST"
          , url: url
          , dataType: "json"
          , data: data
          , beforeSend: function() {
            self.nowLoading = false;
            self.message = '';
            self.showTitle = false;
          }
        })
        .done(function (result) {
          self.nowLoading = false;
          self.showTitle = false;
          if (result.status == 'ok') {
          } else {
            self.messagePushedToQueue = 'Pushed to queue is faild.';
          }
          setTimeout(function() {
            $(self.$el).modal('hide');
          }, 2000);
        })
        .fail(function (stat) {
          // alert('ERROR: failed to update');
        })
        .always(function () {

        });
        // $(this.$el).modal('hide');
      }
    }
  });

  /** 一括更新　こういう名前なのに輸出書類出力は行わないらしい  */
  window.vmDialogConfirmExport = new Vue({
    el: '#modalConfirmExport'
    , data: {
        message: null
      , checkData: null
      , bulkTrigger: null
      , urlSubmitBulkUpdate: null
      
    }
    , mounted: function() {
      this.$nextTick(function () {
        var self = this;
        self.message = '';
        self.bulkTrigger = '';
        self.urlOrderBulkUpdate = $(this.$el).data('urlOrderBulkUpdate');
  
        // イベント登録
        $(self.$el).on('show.bs.modal', function(e) {
          if(self.bulkTrigger == 'STATUS_UPDATE_ORDERED'){
            self.message = '<p>' + $(self.$el).data('message-confirm-update-ordered') + '</p>';
          } else if(self.bulkTrigger == 'STATUS_UPDATE_ARRIVED'){
            self.message = '<p>' + $(self.$el).data('message-confirm-update-arrived') + '</p>';
          } else if(self.bulkTrigger == 'STATUS_UPDATE_WAITED'){
            self.message = '<p>' + $(self.$el).data('message-confirm-update-waited') + '</p>';
          } else if(self.bulkTrigger == 'STATUS_UPDATE_SHIPPING'){
            self.message = '<p>' + $(self.$el).data('message-confirm-update-shipped') + '</p>';
          }
        });
      });
    }
    , methods: {
      open: function(bulkTrigger) {
        var self = this;
        self.bulkTrigger = bulkTrigger;
        $(this.$el).modal('show');
      },
      onSubmitConfirmExport: function() {
        let self = this;
        let searchParams = vmVendorOrderListTable.searchParams;
        // 送信データ作成
        let data = {
            bulkTrigger: self.bulkTrigger
          , search: {
              syohin_code: searchParams.syohinCode
            , order_date_from: searchParams.orderDateFrom
            , order_date_to: searchParams.orderDateTo
            , voucher_number: searchParams.voucherNumber
            , status: searchParams.status
            , vendor_comment: searchParams.vendorComment
            , unallocated: searchParams.unallocated
            , shipping_type: searchParams.shippingType
            , shipping_number: searchParams.shippingNumber
            , receive_order_number: searchParams.receiveOrderNumber
            , warehousing_number: searchParams.warehousingNumber
            , shipping_operation_number: searchParams.shippingOperationNumber
            , person: searchParams.person
            , alert: searchParams.alert
            , is_empty_shipping_number: 1
            , sortBy: searchParams.sortField
            , direction: searchParams.sortOrder
          }
          , isAjax: 1
        };
        
        $.ajax({
          type: "POST"
          , url: self.urlOrderBulkUpdate
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              vmVendorOrderListTable.showPage();
            } else {
              alert('ERROR: failed to update status. Please search again.');
            }
          })
          .fail(function(stat) {
            alert('ERROR: failed to update');
          })
          .always(function() {
            // Hide loading
            $(self.$el).modal('hide');
            $.Vendor.WaitingDialog.hide();
          });
      }
    }
  });

  window.vmDialogErrorUpdate = new Vue({
    el: '#modalErrorUpdate'
    , data: {
      message: null,
    }
    , mounted: function() {
      this.$nextTick(function () {
        var self = this;
        self.message = $(self.$el).data('data-message-error-update');
  
        // イベント登録
        $(self.$el).on('show.bs.modal', function(e) {
          self.message = '<p>' + $(self.$el).data('message-error-update') + '</p>';
        });
      });
    }
    , methods: {
      open: function() {
        $(this.$el).modal('show');
      },
      close: function() {
        $(this.$el).modal('hide');
      }
    }
  });

  /** 発送種別変更 */
  var vmChangeShippingTypeModal = new Vue({
    el: '#modalChangeShippingType',
    data: {
        caption: 'information'
      , message: ''
      , messageClass: 'alert-info'
      , noticeHidden: true
      , nowLoading: false

      , updateUrl: null
      , confirmMessage: null
      , stringAir: null
      , stringContainer: null

      , item: {}
    }
    , mounted: function() {
        this.$nextTick(function () {
          let self = this;
          self.updateUrl = $(self.$el).data('update-url');
          self.confirmMessage = $(self.$el).data('message-confirm-change');
          self.stringAir = $(self.$el).data('message-string-air');
          self.stringContainer = $(self.$el).data('message-string-container');
          
          // イベント登録
          $(self.$el).on('show.bs.modal', function(e) {
            self.nowLoading = false;
          });
      });
    },

    methods: {
      onSubmit: function() {
        var self = this;
        self.nowLoading = true;

        // Ajaxでキュー追加
        var data = {
            voucher_id: self.item.voucherId
          , current_shipping_type: self.item.shippingType
        };
        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {

              // 一覧の発送種別への書き戻し
              vmVendorOrderListTable.updateShippingType(self.item.voucherId, result.result.shipping_type);
              $(self.$el).modal('hide');

            } else {
              self.message = 'ERROR: failed to update shipping type.';
              self.messageClass = 'alert-danger';
            }
          })
          .fail(function(stat) {
            self.message = 'ERROR: failed to update.';
            self.messageClass = 'alert-danger';
          })
          .always(function() {
            self.nowLoading = false;
          });
      },

      open: function(item) {
        let self = this;
        self.nowLoading = true;
        self.item = item;
        $updateTypeString = self.item.shippingType == 1 ? self.stringContainer : self.stringAir;
        self.messageClass = 'alert-success';
        self.message = self.confirmMessage.replace('%shippingTypeString%', $updateTypeString);
        $(this.$el).modal('show');
      }
    }
  });
});