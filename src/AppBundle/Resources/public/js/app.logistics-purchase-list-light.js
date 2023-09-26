/**
 * 管理画面 ロジ用 JS
 * 簡易版入荷入力画面
 */
$(function () {
});


// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentLogisticsPurchaseListItem = {
  template: '#templateLogisticsPurchaseListTableRow'
  , props: [
    'item'
    , 'index'
  ]
  , data: function () {

    return {};
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
    displayDeliveryDate: function () {
      return this.item.deliveryDate ? $.Plusnao.Date.getDateString(this.item.deliveryDate) : '';
    }
    , displayQuantityPrice: function () {
      return $.Plusnao.String.numberFormat(this.item.quantityPrice);
    }
  }
  , methods: {}
};


// 一覧画面 一覧表
var vmRealShopProductStockListTable = new Vue({
  el: '#purchaseList'
  , delimiters: ['(%', '%)']
  , data: {
    list: [] // データ

    , pageItemNum: 50
    , pageItemNumList: [20, 50, 100]
    , page: 1

    , totalItemNum: 0

    , listUrl: null
    , queueJobListUrl: null
    , submitUrl: null
    , locationPrefix: null
    , warehouseId: null

    , downloadUrlBase: null

    , submitMode: 'regular'

    , vendorList: []
    , agentList: []
    , warehouseList: []

    , orders: {
      orderNumber: null
      , sireName: null
      , deliveryDate: null
      , syohinCode: null
      , remainNum: null
    }

    , searchConditions: {
      orderNumber: null
      , syohinCode: null
      , remainNum: 1
      , shippingNumber: null
      , sireCode: null
      , agentCode: null
      , agentStatus: null
      , shippingType: null
      , comment: null

      , shippingNumberMode: 'complete'
    }
    , defaultSearchConditions: {
      orderNumber: null
      , syohinCode: null
      , remainNum: 1
      , shippingNumber: null
      , sireCode: null
      , agentCode: null
      , agentStatus: null
      , shippingType: null
      , comment: null
      , shippingNumberMode: 'complete'
    }

    , messageState: {}
    , runningProcesses: null

  }
  , components: {
    'result-item': vmComponentLogisticsPurchaseListItem // 一覧テーブル
  }
  , mounted: function () {
    this.$nextTick(function () {
      this.listUrl = $(this.$el).data('listUrl');
      this.submitUrl = $(this.$el).data('submitUrl');
      this.downloadUrlBase = $(this.$el).data('downloadUrlBase');
      this.queueJobListUrl = $(this.$el).data('queueJobListUrl');

      // 倉庫初期値セット
      this.warehouseId = $(this.$el).data('currentWarehouseId');

      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      this.loadListData();
    });
  }

  , computed: {

    // sort, filter済みデータ
    listData: function () {
      var self = this;
      return self.list.slice();
    }

    , pageData: function () {
      return this.listData;
    }

    , sortField: function () {
      var keys = Object.keys(this.orders);
      for (var i = 0; i < keys.length; i++) {
        var key = keys[i];
        if (this.orders[key] !== null) {
          return key;
        }
      }
      return null;
    }
    , sortDirection: function () {
      var sortField = this.sortField;
      if (sortField) {
        switch (this.orders[sortField]) {
          case 1:
            return 'ASC';
            break;
          case -1:
            return 'DESC';
            break;
        }
      }

      return null;
    }

    , submitButtonCss: function () {
      var css;
      switch (this.submitMode) {
        case 'regular':
          css = 'btn-warning';
          break;
        case 'shortage':
          css = 'btn-danger';
          break;
        default:
          css = 'btn-default';
          break;
      }
      return css;
    }
    , submitButtonDisabled: function () {
      return !(this.submitMode == 'regular' || this.submitMode == 'shortage');
    }

    , downloadUrl: function () {
      if (!this.downloadUrlBase) {
        return '#';
      }

      var url = this.downloadUrlBase;
      var params = [];
      if (this.sortField !== null) {
        params.push("o=" + this.sortField);
      }
      if (this.sortDirection !== null) {
        params.push("od=" + this.sortDirection);
      }

      for (var i in Object.keys(this.searchConditions)) {
        var k = Object.keys(this.searchConditions)[i];
        var v = this.searchConditions[k];

        if (v !== null) {
          params.push("search[" + k + "]=" + encodeURIComponent(v));
        }
      }

      return this.downloadUrlBase + "?" + params.join('&');
    }

  }
  , methods: {

    loadListData: function () {
      var self = this;

      // データ読み込み処理
      var data = {
        page: self.page
        , limit: self.pageItemNum
        , o: self.sortField
        , od: self.sortDirection
        , search: self.searchConditions
      };

      // Show loading
      $.Vendor.WaitingDialog.show('Loading', {
        backdrop: false
      });

      $.ajax({
        type: "GET"
        , url: self.listUrl
        , dataType: "json"
        , data: data
      })
        .done(function (result) {

          if (result.status == 'ok') {

            var i;

            self.list = [];
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                id: item['id']
                , orderNumber: item['発注伝票番号']
                , lineNumber: item['明細行']
                , sireName: item['仕入先名']
                , deliveryDate: (item['予定納期'] ? new Date(item['予定納期'].replace(/-/g, "/")) : null) // replace for firefox, IE
                , syohinCode: item['商品コード']
                , orderNum: Number(item['発注数'])
                , regularNum: Number(item['regular'])
                , defectiveNum: Number(item['defective'])
                , shortageNum: Number(item['shortage'])
                , remainNum: Number(item['注残計'])
                , comment: item['備考']
                , quantityPrice: Number(item['quantity_price'])
                , agentName: item['依頼先名']
                , agentStatus: item['依頼先状態']
                , supportColName: item['support_colname']
                , supportRowName: item['support_rowname']
                , vendorComment: item['vendor_comment']
                , shippingTypeString: (item['shipping_type'] == '1' ? 'エア' : 'コンテナ')
                , shippingNumber: item['shipping_number']
              };

              self.list.push(row);
            }

            self.totalItemNum = result.pageInfo.totalCount;

            self.vendorList = result.vendorList || [];
            self.agentList = result.agentList || [];
            self.warehouseList = result.warehouseList || [];

            self.messageState.clear();

            self.$nextTick(function () {
              FixedMidashi.create(); // ヘッダ固定テーブル 再計算
            });


          } else {
            var message = result.message.length > 0 ? result.message : 'データを取得できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        .always(function () {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });

    }

    , showPage: function (pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;

      this.loadListData();
    }

    /// 直接 loadListData() よりはワンクッション置いてみる
    , search: function () {
      this.page = 1;
      return this.loadListData();
    }

    /// 絞込クリア
    , resetSearchConditions: function () {
      var fields = Object.keys(this.defaultSearchConditions);
      for (var i = 0; i < fields.length; i++) {
        var key = fields[i];
        this.searchConditions[key] = this.defaultSearchConditions[key];
      }

      this.search();
    }

    /// 検索フォーム Enterイベント (focus off)
    , leaveWhile: function (event) {
      // changeイベント発火のために一度離れて戻る。 (jQuery.trigger()ではchange発火せず)
      event.target.blur();

      setTimeout(function () {
        event.target.focus();
      }, 500);
    }

    , toggleSearchShippingNumberMode: function () {
      this.searchConditions.shippingNumberMode = (this.searchConditions.shippingNumberMode == 'complete' ? 'partial' : 'complete');

      if (this.searchConditions.shippingNumber && this.searchConditions.shippingNumber.length > 0) {
        this.search();
      }
    }


    /// 入力確定処理
    , submitInput: function () {
      var self = this;

      var actionName;
      switch (self.submitMode) {
        case 'regular':
          actionName = '一括良品';
          break;
        case 'shortage':
          actionName = '一括欠品';
          break;
        default:
          actionName = '';
          break;
      }
      if (!actionName.length) {
        alert('処理内容が選択されていません。');
        return;
      }
      if (!self.listData.length) {
        alert('処理対象の発注レコードがありません。');
        return;
      }


      $.ajax({
        type: "GET"
        , url: self.queueJobListUrl
        , dataType: "json"
      })
        .done(function (result) {
          if (result.message == null) {

            self.runningProcesses = result.runningProcesses;

            // 実行中のキューが存在する時
            if (Object.keys(self.runningProcesses).length > 0) {

            $("#dl").dialog({  // 実行順が前後してしまうためコールバックを使用
              dialogClass: "wkDialogClass"
              , modal: true
              , width: 300
              , height: 200
              , title: "確認画面"
              , buttons: [{
                class: "wkBtnOk"
                , text: "ＯＫ"
                , click: function () {
                  $(this).dialog("close");
                  if (!confirm('この絞込条件で入力確定(' + actionName + ')します。よろしいですか？')) {
                    return;
                  }
                  self.submitProcessing();
                }
              }
                , {
                  class: "wkBtnNg"
                  , text: "キャンセル"
                  , click: function () {
                    $(this).dialog("close");
                  }
                }]
            });
            } else {
              if (!confirm('この絞込条件で入力確定(' + actionName + ')します。よろしいですか？')) {
                return;
              }
              self.submitProcessing();
            }

          } else {
            var message = result.message.length > 0 ? result.message : 'データを取得できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        });

    }
    , submitProcessing: function () {
      var self = this;

      var data = {
        prefix: self.locationPrefix
        , warehouseId: self.warehouseId
        , submitMode: self.submitMode
        , search: self.searchConditions
      };

      // Show loading
      $.Vendor.WaitingDialog.show('入力確定処理 実行中...', {
        backdrop: false
      });

      $.ajax({
        type: "POST"
        , url: self.submitUrl
        , dataType: "json"
        , data: data
      })
        .done(function (result) {

          if (result.status == 'ok') {

            alert(result.message);
            self.search();

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        .always(function () {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });
    }


    , toggleOrder: function (key) {
      if (this.orders[key]) {
        if (this.orders[key] == 1) {
          this.orders[key] = -1;
        } else {
          this.orders[key] = null;
        }
      } else {
        var k;
        for (k in this.orders) {
          this.orders[k] = null;
        }
        this.orders[key] = 1;
      }

      this.loadListData();
    }

    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function (key) {
      if (!this.orders[key]) {
        return '';
      }
      return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------

    , updateItem: function (item, result) {
      //var date = result.date ?  new Date(result.date.replace(/-/g, "/")) : null;
      //item.sunPaymentReminder = date;
    }

  }

});

