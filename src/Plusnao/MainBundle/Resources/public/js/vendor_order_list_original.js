/**
 * 注残一覧 JS
 */

$(function() {

  var jobRequestData = {
    jobKey: null
  };

  /**
   * 「注残一覧」拠点ごとの未引当フラグ更新
   */
  var vmReassessIndividuallyAgent = new Vue({
      el: "#reassessIndividuallyAgent"
    , data: {
      url: null
    }
    , ready: function() {
      this.url = $(this.$el).data('url');
    }
    , methods: {
      reassessment: function() {
        if (confirm($(this.$el).data('message-confirm-individual-agent') + "\n\n" + $(this.$el).data('message-confirm'))) {
          var url = this.url;
          window.location.href = url;
        }
      }
    }
  });

  /**
   * 「注残一覧」すべての未引当フラグ更新
   */
  var vmReassessAllAgent = new Vue({
    el: "#reassessAllAgent"
    , methods: {
      reassessment: function() {
        var self = this;
        if (confirm($(this.$el).data('message-confirm-all-agent') + "\n\n" + $(this.$el).data('message-confirm'))) {
          //ボタンを操作不可にする
          $(this.$el).find('button[id="reassessAll"]').html("<i class='fa fa-spin fa-spinner'></i> " + $(this.$el).data('message-updating'));
          $(this.$el).find('button[id="reassessAll"]').prop('disabled',true);
          $('button[id="reassessOnly"]').prop('disabled',true);
          $.ajax({
              url: vmVendorOrderListTable.updateUnallocatedUrl
            , type: "POST"
          }).done(function () {
            alert($(self.$el).data('message-complete') + "\n\n" + $(self.$el).data('message-reload-page'));
            location.reload(true);
          }).fail(function (stmt) {
            alert('ERROR: failed to update unallocated.');
          })
        }
      }
    }
  });
  Vue.config.debug = true;

  // 検索フォーム
  var vmSearchForm = new Vue({
      el: '#searchForm'
    , data: {
    }
    , methods: {
      clearSearchConditions: function() {
        $(this.$el).find('input[name="search[syohin_code]"]').val('');
        $(this.$el).find('input[name="search[order_date_from]"]').val('');
        $(this.$el).find('input[name="search[order_date_to]"]').val('');
      }

      , getSearchConditions: function() {
        return {
            syohinCode:    $(this.$el).find('input[name="search[syohin_code]"]').val()
          , orderDateFrom: $(this.$el).find('input[name="search[order_date_from]"]').val()
          , orderDateTo:   $(this.$el).find('input[name="search[order_date_to]"]').val()
        };
      }

      , openCsvUploadModal: function (e) {
        vmCsvUploadModal.open();
        e.preventDefault();
      }
      , openCsvDownloadModal: function (e) {
        vmCsvDownloadModal.open(this.getSearchConditions());
        e.preventDefault();
      }

    }
  });

  // 一覧テーブル 行コンポーネント
  Vue.component('result-item', {
    template: "#result-item",
    props: [
        'item'
      , 'qualityLevelList'
      , 'shippingTypeList'
    ],
    data: function() {
      return {
          voucherId: this.item.voucherId
        , voucherNumber : this.item.voucherNumber
        , shippingType : this.item.shippingType
        , lineNumber : this.item.lineNumber
        , syohinCode : this.item.syohinCode
        , orderNum : this.item.orderNum
        , remainNum : this.item.remainNum
        , scheduledDate : this.item.scheduledDate
        , comment : this.item.comment
        , sireCode : this.item.sireCode
        , agentCode : this.item.agentCode
        , orderDate : this.item.orderDate
        , remainStatus : this.item.remainStatus
        , unallocatedFlg : this.item.unallocatedFlg
        , remainOrderedDate : this.item.remainOrderedDate
        , remainArrivedDate : this.item.remainArrivedDate
        , remainWaitingDate : this.item.remainWaitingDate
        , remainShippingDate : this.item.remainShippingDate
        , remainStockOutDate : this.item.remainStockOutDate

        , remainOrderedPerson : this.item.remainOrderedPerson
        , remainArrivedPerson : this.item.remainArrivedPerson
        , remainWaitingPerson : this.item.remainWaitingPerson
        , remainShippingPerson : this.item.remainShippingPerson
        , remainStockOutPerson : this.item.remainStockOutPerson

        , shippingNumber : this.item.shippingNumber
        , receiveOrderNumber : this.item.receiveOrderNumber
        , warehousingNumber : this.item.warehousingNumber
        , shippingOperationNumber: this.item.shippingOperationNumber
        , colName : this.item.colName
        , rowName : this.item.rowName
        , supportColName : this.item.supportColName
        , supportRowName : this.item.supportRowName
        , cost : this.item.cost
        , vendorComment : this.item.vendorComment
        , vendorCommentUpdated : this.item.vendorCommentUpdated
        , vendorCommentProcessed : this.item.vendorCommentProcessed

        , category : this.item.category
        , imageListPageUrl: this.item.imageListPageUrl
        , imageUrl: this.item.imageUrl
        , detailUrl: this.item.detailUrl
        , orderComment: this.item.orderComment
        , addresses: this.item.addresses
        , stockedFlag: this.item.stockedFlag

        , qualityLevel: this.item.qualityLevel

        , product: this.item.product

        // instance value
        , inEdit: false
        , nowLoading: false
        , editOrderNum: this.item.orderNum

        , hostMain: null
      };
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
        return this.stockedFlag ? null : 'nonStockedBorder';
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

      , qualityLevelCss: function() {
        return this.getQualityLevelCss(this.item.qualityLevel);
      }
      , qualityLevelIcon: function() {
        return this.getQualityLevelIcon(this.item.qualityLevel);
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
    },
    ready: function() {
      this.hostMain = HOST_MAIN;
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
        vmEditVendorCommentModal.open(this);
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
       * 発送種別 書き戻し
       */
      , updateShippingType: function(item) {
        this.item.shippingType = item.shipping_type;
        this.shippingType = item.shipping_type;
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
      //
      // /**
      //  * 商品諸元編集モーダル open
      //  */
      // , openEditProductSpecModal: function () {
      //     var getUrl = window.location;
      //     var plusnaoBaseUrl = getUrl.protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1];
      //   //window.open(this.item.detailUrl,'_blank'); //this.item.syohinCode
      //   //window.open('https://d3-s.dev.plusnao.co.jp/app_test.php/goods/weight_size/edit?code='+this.item.syohinCode,'_blank'); //this.item.syohinCode
      //   window.open(plusnaoBaseUrl+'/goods/weight_size/edit?code='+this.item.syohinCode,'_blank'); //this.item.syohinCode
      //   //window.open(plusnaoBaseUrl+'/location/product/sku/'+this.item.daihyoSyohinCode,'_blank'); //this.item.syohinCode
      //   //window.open(baseUrl+this.item.syohinCode,'_blank'); //this.item.syohinCode
      //   console.log('Product Code: '+this.item.baseUrl);
      //   console.log('Product Code: '+this.baseUrl);
      //   //vmEditProductSpecModal.open(this.item); //wasim
      // }

      /**
       * 品質CSS取得
       */
      , getQualityLevelCss: function(level) {
        var css = null;
        switch (level) {
          case this.qualityLevelList.none: // 未設定
            css = 'btn-default';
            break;
          case this.qualityLevelList.ng: // 不可
            css = 'btn-danger';
            break;
          case this.qualityLevelList.ok: // 可
            css = 'btn-warning';
            break;
          case this.qualityLevelList.good: // 良
            css = 'btn-success';
            break;
        }
        return css;
      }

      /**
       * 品質アイコン取得
       */
      , getQualityLevelIcon: function(level) {
        var css = null;
        switch (level) {
          case this.qualityLevelList.none: // 未設定
            css = 'fa-minus';
            break;
          case this.qualityLevelList.ng: // 不可
            css = 'fa-thumbs-o-down';
            break;
          case this.qualityLevelList.ok: // 可
            css = 'fa-circle-o';
            break;
          case this.qualityLevelList.good: // 良
            css = 'fa-thumbs-o-up';
            break;
        }
        return css;
      }

      /**
       *
       */
      , updateQualityLevel: function(level) {
        this.$emit('update-quality-level', this.item, level);
      }
      /**
       * 発送種別変更
       */
      , openChangeShippingTypeModal: function () {
        vmChangeShippingTypeModal.open(this);
      }
    }
  });

  // 注残一覧画面 メイン
  var vmVendorOrderListTable = new Vue({
    el: '#vendorOrderListTable',
    data: {
      // データ
        list: []
      , products: {}

      // ページ送り設定
      , pageItemNum: 100 // 設定値: 1ページ表示件数
      , pageListMaxLength: 8 // 設定値: ページリンク 表示最大件数
      , page: 1 // 現在のページ

      // 絞込プルダウン用配列
      , voucherNumberList: []
      , voucherNumberDateList: {}
      , remainStatusList: []
      , alertList: []
      , bulkList: []

      // 一覧表示ラベル切り替え用配列
      , shippingTypeList: []

      // 並び順指定
      , sortField: null
      , sortOrder: -1

      // 絞込
      , filterVoucherNumber: ""
      , filterStatus: ""
      , filterVendorComment: ""
      , filterUnallocated: ""
      , filterShippingType: ""
      , filterShippingNumber: ""
      , filterReceiveOrderNumber: ""
      , filterWarehousingNumber: ""
      , filterShippingOperationNumber: ""
      , filterPerson: ""
      , filterAlert:""
      
      // 一括更新
      , bulkTrigger : ""
      , updateVoucherNumber: ""
      , updateStatus: ""
      , updateUnallocated: ""
      , updateVendorComment: ""
      , updateShippingNumber: ""
      , updateReceiveOrderNumber: ""
      , updateWarehousingNumber: ""
      , updateShippingOperationNumber: ""
      , updatePerson: ""
      , updateAlert: ""
      , updateShippingType: ""

      , filterHashKeys: {
          p : 'page'
        , pn: 'pageItemNum'
        , v : 'filterVoucherNumber'
        , s : 'filterStatus'
        , c : 'filterVendorComment'
        , ua: 'filterUnallocated'
        , st: 'filterShippingType'
        , sn: 'filterShippingNumber'
        , rn: 'filterReceiveOrderNumber'
        , wn: 'filterWarehousingNumber'
        , on: 'filterShippingOperationNumber'
        , ps: 'filterPerson'
        , a: 'filterAlert'
        , sf: 'sortField'
        , so: 'sortOrder'
      }

      // 処理URL
      , baseUrl: null // （基本URL）
      , updateStatusUrl : null
      , updateShippingNumberUrl : null
      , updateReceiveOrderNumberUrl : null
      , updateWarehousingNumberUrl : null
      , updateOrderNumUrl: null
      , updateQualityLevelUrl : null
      , labelPdfListUrl: null
      , editSpecUrl: null
      , updateUnallocatedUrl: null

      // 品質チェック
      , qualityLevelList: {}
      , shippingTypeList: {}
    },
    ready: function () {
      var self = this;

      // URL文字列取得
      self.baseUrl = $(self.$el).data('url');
      self.updateStatusUrl = $(self.$el).data('updateStatusUrl');
      self.updateShippingNumberUrl = $(self.$el).data('updateShippingNumberUrl');
      self.updateReceiveOrderNumberUrl = $(self.$el).data('updateReceiveOrderNumberUrl');
      self.updateWarehousingNumberUrl = $(self.$el).data('updateWarehousingNumberUrl');
      self.updateOrderNumUrl = $(self.$el).data('updateOrderNumUrl');
      self.updateQualityLevelUrl = $(self.$el).data('updateQualityLevelUrl');
      self.labelPdfListUrl = $(self.$el).data('labelPdfListUrl');
      self.editSpecUrl = $(self.$el).data('editSpecUrl');
      self.updateUnallocatedUrl = $(self.$el).data('updateUnallocatedUrl');

      self.remainStatusList = remainStatusListData;
      self.alertList = alertListData;
      self.bulkList = bulkListData;
      self.products = productListData;

      // ソート処理のため、ここで数値変換をしておく。 （※現在はfilterByを利用しているため、ソートには利用していない）
      var list = [];
      var voucherNumberList = [];
      var voucherNumberDateList = {};
      var i;
      for (i in vendorOrderListData) {
        var item = vendorOrderListData[i];
        var row = self.convertJsonToObject(item);

        // 商品諸元情報 格納
        row.product = self.products[row.syohinCode];

        if(row.product == null){
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
        list.push(row);

        if (voucherNumberList.indexOf(row.voucherNumber) === -1) {
          voucherNumberList.push(row.voucherNumber);
          voucherNumberDateList[row.voucherNumber] = row.orderDate ? '(' + $.Plusnao.Date.getDateString(row.orderDate) + ')' : '';
        }
      }

      self.$set('list', list);
      self.$set('voucherNumberList', voucherNumberList.sort());
      self.$set('voucherNumberDateList', voucherNumberDateList);

      // tooltip 有効化: th
      // $(self.$el).find('th span').tooltip();
      $(self.$el).find('a[data-toggle="tooltip"]').tooltip();

      // tooltip 有効化: エラーメッセージ
      $(self.$el).find('form .form-control').tooltip({
        html: true
        , template: '<div class="tooltip error" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
      });

      // URLハッシュから絞込キーを取得
      self.parseHashFilterConditions();

      // 絞込条件の変更を$watch
      self.$watch('page', self.updateHashFilterConditions);
      self.$watch('pageItemNum', self.updateHashFilterConditions);
      self.$watch('sortField', self.updateHashFilterConditions);
      self.$watch('sortOrder', self.updateHashFilterConditions);
      self.$watch('filterVoucherNumber', self.updateHashFilterConditions);
      self.$watch('filterStatus', self.updateHashFilterConditions);
      self.$watch('filterVendorComment', self.updateHashFilterConditions);
      self.$watch('filterUnallocated', self.updateHashFilterConditions);
      self.$watch('filterShippingType', self.updateHashFilterConditions);
      self.$watch('filterShippingNumber', self.updateHashFilterConditions);
      self.$watch('filterReceiveOrderNumber', self.updateHashFilterConditions);
      self.$watch('filterWarehousingNumber', self.updateHashFilterConditions);
      self.$watch('filterShippingOperationNumber', self.updateHashFilterConditions);
      self.$watch('filterPerson', self.updateHashFilterConditions);
      self.$watch('filterAlert', self.updateHashFilterConditions);

      self.qualityLevelList = QUALITY_LEVEL_LIST;
      self.shippingTypeList = SHIPPING_TYPE_LIST;

    },
    computed: {

        itemCount: function() {
        return this.list.length;
      }

      , isBulkSelected: function() {
        return this.bulkTrigger !== '';
      }

      , filteredItemCount: function() {
        return this.listData.length;
      }

      ///// ソート・フィルター済みリスト
      //// ※ページング処理のため、Vue.js v-for の filterBy, orderBy が利用できない。
      , listData: function() {
        var self = this;
        var list = self.list.slice(); // 破壊防止
        // ソート
        if (self.sortField) {
          list.sort(function(a, b) {
            var cmpA, cmpB;
            // 文字列
              if (self.sortField == 'shippingNumber') {
              cmpA = a[self.sortField] === null ? "" : a[self.sortField];
              cmpB = b[self.sortField] === null ? "" : b[self.sortField];
            // その他（数値、Date）
            } else {
              cmpA = a[self.sortField];
              cmpB = b[self.sortField];
            }

            if (cmpA > cmpB) { return 1 * self.sortOrder; }
            if (cmpA < cmpB) { return -1 * self.sortOrder; }
            return 0;
          });
        // 初期ソート 伝票番号、商品コード、明細行
        } else {
          list.sort(function(a, b) {
            // 伝票番号
            if (a.voucherNumber > b.voucherNumber) { return 1 }
            if (a.voucherNumber < b.voucherNumber) { return -1 }

            // 商品コード
            if (a.syohinCode > b.syohinCode) { return 1 }
            if (a.syohinCode < b.syohinCode) { return -1 }

            // 明細行
            if (a.lineNumber > b.lineNumber) { return 1 }
            if (a.lineNumber < b.lineNumber) { return -1 }

            return 0;
          });
        }

        list = list.filter(function(item, i) {
          var result = true;

          // 絞込: 伝票番号
          if (self.filterVoucherNumber.length) {
            result = result && item.voucherNumber == self.filterVoucherNumber;
          }

          // 絞込: 注残ステータス
          if (self.filterStatus.length) {
            result = result && item.remainStatus == self.filterStatus;
          }

          // 絞込: 連絡事項
          if (self.filterVendorComment.length && self.filterVendorComment == "1") {
            result = result && (item.vendorComment !== null && item.vendorComment.length > 0);
          }

          // 絞込: 未引当
          if (self.filterUnallocated.length) {
            result = result && item.unallocatedFlg == self.filterUnallocated;
          }

          // 絞込: 発送種別
          if (self.filterShippingType.length) {
            result = result && item.shippingType == self.filterShippingType;
          }

          // 絞込：発送伝票番号
          if (self.filterShippingNumber.length) {
            result = result && item.shippingNumber && (item.shippingNumber.indexOf(self.filterShippingNumber) != -1);
          }

          // 絞込：受注番号
          if (self.filterReceiveOrderNumber.length) {
            result = result && item.receiveOrderNumber && (item.receiveOrderNumber.indexOf(self.filterReceiveOrderNumber) != -1);
          }

          // 絞込：入庫番号
          if (self.filterWarehousingNumber.length) {
            result = result && item.warehousingNumber && (item.warehousingNumber.indexOf(self.filterWarehousingNumber) != -1);
          }

          // 絞込：出庫番号
          if (self.filterShippingOperationNumber.length) {
            result = result && item.shippingOperationNumber && (item.shippingOperationNumber.indexOf(self.filterShippingOperationNumber) != -1);
          }

          // 絞込：担当者
          if (self.filterPerson.length) {
            result = result && (
                  (item.remainOrderedPerson && (item.remainOrderedPerson.indexOf(self.filterPerson) != -1))
               || (item.remainArrivedPerson && (item.remainArrivedPerson.indexOf(self.filterPerson) != -1))
               || (item.remainWaitingPerson && (item.remainWaitingPerson.indexOf(self.filterPerson) != -1))
               || (item.remainShippingPerson && (item.remainShippingPerson.indexOf(self.filterPerson) != -1))
               || (item.remainStockOutPerson && (item.remainStockOutPerson.indexOf(self.filterPerson) != -1))
            );
          }

          // 絞込: 警告・注意あり
          if (self.filterAlert.length) {
            if (self.filterAlert == "ALL") {
              result = result && (!item.stockedFlag || item.isUnsetWeightSize || item.isUnsetMaterialDescription);
            } else if (self.filterAlert == "REGULAR_NONE") {
              result = result && !item.stockedFlag;
            } else if (self.filterAlert == "UNSET_WEIGHT_SIZE") {
              result = result && item.isUnsetWeightSize;
            } else if (self.filterAlert == "UNSET_MATERIAL_DESCRIPTION") {
              result = result && item.isUnsetMaterialDescription;
            }
          }

          return result;
        });

        return list;
      }

      , pageData: function() {
        var startPage = (this.page - 1) * this.pageItemNum;
        return this.listData.slice(startPage, startPage + this.pageItemNum);
      },

      isStartPage: function(){
        return (this.page == 1);
      },

      isEndPage: function(){
        return (this.page == this.pageNum);
      },

      /// 最大ページ数 （現在のフィルタ条件を考慮）
      pageNum: function() {
        return Math.ceil(this.listData.length / this.pageItemNum);
      },

      pageList: function() {
        var pages = [];
        var i;
        if (this.pageNum <= this.pageListMaxLength) {
          for (i = 1; i <= this.pageNum; i++) {
            pages.push(i);
          }
        } else {

          var listHalf = Math.floor(this.pageListMaxLength / 2);
          if (!listHalf) {
            listHalf = 1;
          }
          var listQuarter = Math.floor(this.pageListMaxLength / 4);
          if (!listQuarter) {
            listQuarter = 1;
          }

          var isSkipForward = this.page <= (this.pageNum - listHalf); // 大きい方をスキップ
          var isSkipBackward = this.page >= listHalf; // 小さい方をスキップ

          var showNum = this.pageListMaxLength - 2  // start & end
            - (isSkipForward ? 1 : 0) // 「...」
            - (isSkipBackward ? 1 : 0); // 「...」

          var prePageNum = Math.floor((showNum -1) / 2);
          var postPageNum = (showNum - 1) - prePageNum;
          var start = isSkipBackward ? this.page - prePageNum : 2;
          var end = isSkipForward ? this.page + postPageNum : (this.pageNum - 1);

          if (this.page - prePageNum < 2) {
            end += (2 - (this.page - prePageNum));
          }
          if (this.page + postPageNum > (this.pageNum - 1)) {
            start -= (this.page + postPageNum - (this.pageNum - 1));
          }

          pages.push(1); // 先頭ページ
          if (isSkipBackward) {
            pages.push('…')
          }
          for (i = start; i <= end; i++) {
            pages.push(i);
          }
          if (isSkipForward) {
            pages.push('…')
          }
          pages.push(this.pageNum); // 最終ページ
        }

        return pages;
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

      // 集計値: SKU
      , displayFilteredSkuCount: function() {
        var keys = [];
        var ret = this.listData.reduce(function(result, item) {
          if (keys.indexOf(item.syohinCode) == -1) {
            keys.push(item.syohinCode);
            result++;
          }
          return result;
        }, 0);

        return $.Plusnao.String.numberFormat(ret);
      }

      // 集計値: 注残数
      , displayFilteredRemainCount: function() {
        var ret = this.listData.reduce(function(result, item) {
          result += item.remainNum;
          return result;
        }, 0);

        return $.Plusnao.String.numberFormat(ret);
      }

      // 集計値: 原価(日本円)
      , displayFilteredCostTotal: function() {
        var ret = this.listData.reduce(function(result, item) {
          result += (item.cost * item.remainNum);
          return result;
        }, 0);

        return $.Plusnao.String.numberFormat(ret);
      }

    },
    methods: {

      /**
       * Ajax取得配列を行オブジェクトに変換
       */
      convertJsonToObject: function(item) {

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
        };
      }

      /**
       * 件数取得
       */
      , getNumInfo: function() {
        var self = this;

        var result = {
            total: 0
          , targetTotal: 0
          , targetDown: 0
          , targetUp: 0
          // , targetKeep: 0 これは変更なしということで不要
        };

        for (var i = 0; i < self.list.length; i++) {
          var item = self.list[i];
          result.total++;
          if (self.isDiscountTarget(item)) {
            result.targetTotal++;
            if (item.priceDiff > 0) {
              result.targetUp++;
            } else if (item.priceDiff < 0) {
              result.targetDown++;
            }
          }
        }

        return result;
      },

      /**
       * ソートアイコンCSSクラス
       */
      getSortMarkCssClass: function(field) {
        return (field == this.sortField)
          ? (this.sortOrder == 1 ? 'sortAsc' : 'sortDesc' )
          : 'sortFree';
      },

      /**
       * 一覧 並び順変更
       * @param fieldName
       */
      switchSort: function(fieldName) {
        if (this.sortField == fieldName) {
          // 降順 -> 昇順
          if (this.sortOrder == -1) {
            this.sortOrder = 1;

            // デフォルトに戻る
          } else {
            this.sortField = null;
            this.sortOrder = 1;
          }

        } else {
          this.sortField = fieldName;
          this.sortOrder = -1; // 降順が先
        }
      }

      /**
       * ページ送り
       */
      , showPrev: function(event) {
        event.preventDefault();
        if (! this.isStartPage) {
          this.page--;
        }
      }

      , showNext: function(event) {
        event.preventDefault();
        if (! this.isEndPage) {
          this.page++;
        }
      }

      , showPage: function(page, event) {
        // aタグなどでは$eventを渡してリンク挙動を抑制
        if (event) {
          event.preventDefault();
        }
        if (page >= 1 && page <= this.pageNum) {
          this.page = page;
        }
      }

      /**
       * ページ判定
       */
      , isPage: function(num) {
        return (this.page === parseInt(num));
      }
      , checkDataRequire: function() {
        var result = {'isShippingNumberEmpty': false, 'isSkuEmpty': false};
        var dataCheck = this.listData;
        for (var i = 0; i < dataCheck.length; i++) {
          var itemList = dataCheck[i];
          if (itemList.shippingNumber == '' || itemList.shippingNumber == null) {
            result.isShippingNumberEmpty = true;
          }

          if (itemList.product == null) {
            result.isSkuEmpty = true;
          } else if (Number(itemList.product.weight) <= 0) {
            result.isSkuEmpty = true;
          } else if (
               !Number(itemList.product.depth)
            || !Number(itemList.product.width)
            || !Number(itemList.product.height)
            || !Number(itemList.product.height)
            || (itemList.product.description_en == null || itemList.product.description_en == '' )
            || (itemList.product.description_cn == null || itemList.product.description_cn == '' )
            || (itemList.product.hint_ja == null || itemList.product.hint_ja == '' )
            || (itemList.product.hint_cn == null || itemList.product.hint_cn == '' )
          ) {
            result.isSkuEmpty = true;
          }
        }
        return result;
      }
      , checkDataStatus: function() {
        var result = {'orderd': 0, 'arrived': 0, 'waited': 0, 'shipped': 0};
        var dataCheck = this.listData;
        for (var i = 0; i < dataCheck.length; i++) {
          var itemList = dataCheck[i];
          
          if(itemList.remainStatus == 'ORDERED'){
            result.orderd++;
          }
          if(itemList.remainStatus == 'ARRIVED'){
            result.arrived++;
          }
          if(itemList.remainStatus == 'WAITED'){
            result.waited++;
          }
          if(itemList.remainStatus == 'SHIPPED'){
            result.shipped++;
          }
        }
        return result;
      }
      , triggerExport: function(e) {
        if (this.bulkTrigger == 'EXPORT_SHIPPING') {
          var checkData = this.checkDataRequire();
          window.vmDialogShippingNumberEmpty.open(checkData);
        } else {
          var checkData = this.checkDataStatus();
          console.log(checkData);
          checkCount = 0;
          if (this.bulkTrigger == 'STATUS_UPDATE_ORDERED') {
            checkCount = checkData.orderd + checkData.arrived + checkData.waited + checkData.shipped;
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

        // 引き渡すデータを作成
        var data = [];
        for (var i = 0; i < this.listData.length; i++) {
          data.push({
              category: this.listData[i].category
            , syohinCode: this.listData[i].syohinCode
            , orderNum: this.listData[i].orderNum
            , remainNum: this.listData[i].remainNum
            , colName: this.listData[i].colName
            , rowName: this.listData[i].rowName
            , supportColName: this.listData[i].supportColName
            , supportRowName: this.listData[i].supportRowName
            // TODO displayColName, displayRowName 追加

            , checked: true
          });
        }

        window.vmLabelPrintList.open(data);
        e.preventDefault();
      }

      /// データ差し替え ※伝票明細分割処理で元明細を更新するための処理
      , replaceItem: function(item) {
        var result = null;
        for (var i = 0; i < this.list.length; i++) {
          if (this.list[i].voucherId == item.voucherId) {
            this.list.$set(i, item);
            result = this.list[i];
            break;
          }
        }
        return result;
      }
      , addItem: function(item) {
        this.list.push(item);
        return this.list[(this.list.length - 1)];
      }
      , removeItem: function(item) {
        this.list.$remove(item);
      }

      /// URLハッシュから絞込条件、ソート条件を取得
      , parseHashFilterConditions: function() {
        var self = this;

        var hash = window.location.hash;
        if (!hash) {
          return;
        }

        var matches = hash ? hash.match(/#!([^#]+)$/) : [];
        var filterKeys = self.filterHashKeys;
        if (matches && matches.length > 0) {
          var filters = matches[1].split(/&/).map(function(ele) {
            return ele.split(/=/);
          });

          var i, key;
          for (key in filterKeys) {
            var propertyName = filterKeys[key];
            var val = null;
            for (i in filters) {
              if (filters[i] && filters[i].length && filters[i][0] === key) {
                val = filters[i][1];
                if (propertyName === 'page' || propertyName === 'pageItemNum' || propertyName === 'sortOrder') {
                  val = Number(val);
                }
                if (propertyName === 'filterPerson') {
                  val = decodeURI(val);
                }

                if (self[propertyName] !== val) {
                  self.$set(propertyName, val);
                }
                break;
              }
            }
            
            if (propertyName.startsWith('filter')) {
              updateName = propertyName.replace('filter','update');
              self.$set(updateName, val);
            }
            
            // マッチしなかった場合、初期値
            if (val === null) {
              switch (propertyName) {
                case 'page':
                  val = 1;
                  break;
                case 'pageItemNum':
                  val = 100;
                  break;
                case 'sortOrder':
                  val = -1;
                  break;
                case 'sortField':
                  val = null;
                  break;
                default:
                  val = "";
              }
              self.$set(propertyName, val);
            }
          }
        }
      }
      
      // 一括処理のボタン非活性を選出
      , buttonBulkDisabled: function() {
        var disabled = null;

        if (!this.isBulkSelected) {
          disabled = 'disabled';
        } else if (this.listData.length < 1) {
          disabled = 'disabled';
        }

        return disabled;
      }
      , showModalSetting: function(e) {
        e.preventDefault();
        vmSettingRateModal.open();
      }

      /// URLハッシュのｐ絞込条件、ソート条件を更新
      , updateHashFilterConditions: function() {
        var self = this;

        var filterKeys = self.filterHashKeys;
        var hashValues = [];
        var key, propertyName;
        for (key in filterKeys) {
          propertyName = filterKeys[key];

          var val = self[propertyName];
          if (propertyName === 'page' || propertyName === 'pageItemNum' || propertyName === 'sortOrder') {
            val = val.toString();
          }
          
          if (propertyName.startsWith('filter')) {
            updateName = propertyName.replace('filter','update');
            self.$set(updateName, val);
          }

          if (val !== null && val.length > 0) {
            hashValues.push(key + "=" + val);
          }
        }

        if (hashValues.length > 0) {
          window.location.hash = "#!" + hashValues.join('&');
        }
      }

      /// 品質チェック 更新
      , updateQualityLevel: function(item, level) {
        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('updating ...');

        var data = {
            voucher_id: item.voucherId
          , level: level
        };

        $.ajax({
            type: "POST"
          , url: self.updateQualityLevelUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.status == 'ok') {

              // 同じ商品を全更新
              if (result.result.daihyoSyohinCode) {
                var level = result.result.qualityLevel ? Number(result.result.qualityLevel) : self.qualityLevelList.none;
                for (var i = 0; i < self.list.length; i++) {
                  var item = self.list[i];
                  if (item.daihyoSyohinCode == result.result.daihyoSyohinCode) {
                    item.qualityLevel = level;
                  }
                }
              }

              self.message = '品質設定を更新しました。';
              self.messageClass = 'alert-success';

            } else {
              alert('ERROR: failed to update quality level.');
            }
          })
          .fail(function (stat) {
            alert('ERROR: failed to update');
          })
          .always(function () {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });

        return true;
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

      , targetOrder: null
      , vendorComment: null
      , originalVendorComment: null
    },
    ready: function() {
      var self = this;
      self.getUrl = $(self.$el).data('get-url');
      self.updateUrl = $(self.$el).data('update-url');

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();
        self.nowLoading = true;

        self.targetOrder = e.relatedTarget.order;

        $.ajax({
            type: "GET"
          , url: self.getUrl
          , dataType: "json"
          , data: {
              voucher_id: self.targetOrder.voucherId
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
    },

    methods: {
      onSubmit: function() {
        var self = this;
        self.nowLoading = true;

        // Ajaxでキュー追加
        var data = {
            voucher_id: self.targetOrder.voucherId
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
              var item = result.result;

              // 一覧の連絡事項へ書き戻し
              self.targetOrder.updateVendorComment(item);
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

      open: function(order) {
        $(this.$el).modal('show', { order: order });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.nowLoading = false;

        this.targetOrder = null;
        this.vendorComment = null;

        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });




  /**
   * CSVアップロード
   */
  var vmCsvUploadModal = new Vue({
    el: "#modalCsvUpload",
    data: {
      caption: 'ピッキングリスト取込 CSVアップロード'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , postUrl: null
      , deleteUrl: null
      , verifyUrl: null

      , nowLoading: false
    },
    ready: function() {
      var self = this;
      self.postUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        $('.modal-footer button.btn-primary', self.$el).show();
        $('.modal-footer button.btn-warning', self.$el).show();
      });
    },

    methods: {
      open: function(vmSearch) {

        self.nowLoading = true;
        $(this.$el).modal('show');
      },

      onSubmit: function() {
        var self = this;

        var $input = $(self.$el).find('input[type="file"]');
        var files = $input.get(0).files;
        if (!files.length) {
          this.notices = [$(self.$el).data('message-nothing-select')];
          this.noticeHidden = false;
          return;
        }

        var file = files[0];
        if (!file.name.match(/\.csv$/)) {
          this.notices = [$(self.$el).data('message-other-extension')];
          this.noticeHidden = false;
          return;
        }

        var formData = new FormData();
        var status = "ng";
        formData.append($input.attr('name'), file);

        this.resetDialog();

        this.nowLoading = true;
        this.caption = "アップロード中 ...";

        $.ajax({
          type: 'POST',
          timeout: 30000,
          url: self.postUrl,
          dataType: 'json',
          processData: false,
          contentType: false,
          data: formData
        }).done(function(result, textStatus, jqXHR) {

          if (result.status == 'ok') {
            status = 'ok';
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

          } else {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-danger';
          }

          return false;
        }).fail(function(jqXHR, textStatus, errorThrown) {
          if (jqXHR.responseText) {
            self.$data.message = jqXHR.responseText;
            self.$data.messageClass = 'alert alert-danger';
          }
          return false;
        }).always(function() {
          if(status == 'ok'){
            location.reload(true);
          } else {
            $('.modal-footer button.btn-primary', self.$el).show();
            $('.modal-footer button.btn-warning', self.$el).show();
            self.noticeHidden = true;
            self.nowLoading = false;
            self.caption = "ピッキングリスト取込 CSVアップロード";
          }
        });
      },

      resetDialog: function() {
        this.caption = "ピッキングリスト取込 CSVアップロード";
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
        $('.modal-footer button.btn-warning', self.$el).hide();

        this.nowLoading = false;
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
    },
    ready: function() {
      var self = this;

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        // 検索フォーム 値引き継ぎ
        $(self.$el).find('#csvDownloadDateFrom input').val(self.searchConditions.orderDateFrom);
        $(self.$el).find('#csvDownloadDateTo input').val(self.searchConditions.orderDateTo);

        self.nowLoading = false;
      });
    },

    methods: {
      open: function(searchConditions) {

        this.searchConditions.orderDateFrom = searchConditions.orderDateFrom;
        this.searchConditions.orderDateTo = searchConditions.orderDateTo;

        this.nowLoading = true;
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
    },
    ready: function() {
      this.urlGetRate = $(this.$el).data('urlGetRate');
      this.urlUpdateRate = $(this.$el).data('urlUpdateRate');
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
    },
    ready: function() {
      var self = this;

      self.urlSubmitSplit = $(this.$el).data('urlSubmitSplit');
      self.messageConfirmSplit = $(this.$el).data('messageConfirmSplit');
      self.messageCompleteSplit = $(this.$el).data('messageCompleteSplit');

      var option = {
          min: 0
        , initval: 0
        , step: 1
        , decimals: 0
        , boostat: 5
        , maxboostedstep: 10
      };
      $('#splitOrderNum', self.$el).TouchSpin(option);

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.nowLoading = false;
      });
    },
    computed: {
      isShowSubmit: function() {
        return this.showSubmitSplit && this.newOrderNum > 0;
      }
      , leftOrderNum: function() {
        return this.item.orderNum - this.newOrderNum;
      }
    },
    methods: {
      open: function(item) {
        var self = this;
        self.nowLoading = true;

        // 注残数 = 発注数の場合は1つ残す
        var max = item.remainNum == item.orderNum ? item.remainNum - 1 : item.remainNum;
        if (max < 0) { max = 0 } // これはイレギュラー

        $('#splitOrderNum', self.$el).trigger('touchspin.updatesettings', { max: max });

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

              var parentItem = result.result.parentOrder;
              var newItem = result.result.newOrder;

              // オブジェクト差し替え・追加
              parentItem = vmVendorOrderListTable.replaceItem(vmVendorOrderListTable.convertJsonToObject(parentItem));
              vmVendorOrderListTable.addItem(vmVendorOrderListTable.convertJsonToObject(newItem));

              self.message = self.messageCompleteSplit;
              self.messageClass = 'alert-success';

              self.item = parentItem;
              self.newOrderNum = 0;

            } else {
              alert('ERROR: failed to update status.');
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


  /**
   * 諸元編集モーダル
   */
  var vmEditProductSpecModal = new Vue({
    el: "#modalEditProductSpec",
    data: {
      caption: ''
      , message: ''
      , messageClass: 'info'
      , nowLoading: true

      , urlSubmitUpdate: null

      , item: {}
      , product: {}
      , originalProduct: {}
    },
    ready: function () {
      var self = this;

      self.urlSubmitUpdate = $(this.$el).data('urlSubmitUpdate');

      // イベント登録
      $(self.$el).on('show.bs.modal', function (e) {
        self.nowLoading = false;
      });

    },
    computed: {},
    methods: {
      open: function (item) {
        var self = this;
        self.nowLoading = true;

        self.resetDialog();

        self.item = item;
        self.product = item.product;
        self.originalProduct = {
            weight: self.product.weight
          , depth: self.product.depth
          , width: self.product.width
          , height: self.product.height
          // , weight_check_need_flg: self.product.weight_check_need_flg
        };

        $(self.$el).modal('show');
      }

      , resetDialog: function () {
        this.message = '';
        this.messageClass = '';

        this.item = {};
        this.product = {};
        this.originalProduct = {};
      }

      , onSubmit: function () {

        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('Wait a moment ...');

        var data = {
          product: self.product
        };

        $.ajax({
          type: "POST"
          , url: self.urlSubmitUpdate
          , dataType: "json"
          , data: data
        })
          .done(function (result) {

            if (result.status == 'ok') {

              self.message = '商品情報を更新しました。'; // 呼び元がないため翻訳なし
              self.messageClass = 'alert-success';

            } else {
              alert('ERROR: failed to update status.');

              self.product.weight = self.originalProduct.weight;
              self.product.depth = self.originalProduct.depth;
              self.product.width = self.originalProduct.width;
              self.product.height = self.originalProduct.height;
              // self.product.weight_check_need_flg = self.originalProduct.weight_check_need_flg;
            }
          })
          .fail(function (stat) {
            alert('ERROR: failed to update');
          })
          .always(function () {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });

        return true;
      }
    }
  });

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
    , ready: function() {
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
        var self = this;
        self.showFooter = false;
        self.showTitle = false;
        self.pushedToQueue = true;
        self.messagePushedToQueue = $(self.$el).data('message-pushed-to-queue');
        $('#is-empty-shipping-number').val('1');
        var url = $(self.$el).data('orderListExportExcel');
        self.xhr = $.ajax({
          type: "POST"
          , url: url
          , dataType: "json"
          , data: $('#frm-bulk-data').serializeArray()
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

  window.vmDialogConfirmExport = new Vue({
    el: '#modalConfirmExport'
    , data: {
      message: null,
      checkData: null,
      bulkTrigger: null
    }
    , ready: function() {
      var self = this;
      self.message = '';
      self.bulkTrigger = '';

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

    }
    , methods: {
      open: function(bulkTrigger) {
        var self = this;
        self.bulkTrigger = bulkTrigger;
        $(this.$el).modal('show');
      },
      onSubmitConfirmExport: function() {
        $(this.$el).modal('hide');
        $('#frm-bulk-data').submit();
      }
    }
  });

  window.vmDialogErrorUpdate = new Vue({
    el: '#modalErrorUpdate'
    , data: {
      message: null,
    }
    , ready: function() {
      var self = this;
      self.message = $(self.$el).data('data-message-error-update');

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.message = '<p>' + $(self.$el).data('message-error-update') + '</p>';
        console.log($(self.$el).data());
        console.log(self.message);
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
});

// モーダル
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

    , targetOrder: null
  },
  ready: function() {
    var self = this;
    self.updateUrl = $(self.$el).data('update-url');
    self.confirmMessage = $(self.$el).data('message-confirm-change');
    self.stringAir = $(self.$el).data('message-string-air');
    self.stringContainer = $(self.$el).data('message-string-container');

    // イベント登録
    $(self.$el).on('show.bs.modal', function(e) {
      self.resetDialog();
      self.targetOrder = e.relatedTarget.order;
      $updateTypeString = self.targetOrder.shippingType == 1 ? self.stringContainer : self.stringAir;
      self.messageClass = 'alert-success';
      self.message = self.confirmMessage.replace('%shippingTypeString%', $updateTypeString);
    });

    // 開始時はアクセスしない。サーバ側が変更済みの場合は更新リクエストを送られても何もしないだけ
  },

  methods: {
    onSubmit: function() {
      var self = this;
      self.nowLoading = true;

      // Ajaxでキュー追加
      var data = {
          voucher_id: self.targetOrder.voucherId
        , current_shipping_type: self.targetOrder.shippingType
      };
      $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {
            var item = result.result;

            // 一覧の発送種別への書き戻し
            self.targetOrder.updateShippingType(item);
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

    open: function(order) {
      $(this.$el).modal('show', { order: order });
    },

    resetDialog: function() {
      this.$data.message = '';
      this.$data.messageClass = '';
      this.nowLoading = false;

      this.targetOrder = null;
    }
  }
});


