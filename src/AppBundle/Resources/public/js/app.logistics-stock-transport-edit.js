/**
 * ロジ 移動伝票編集
 */
// モジュールロード試験
// => 2018/03/20現在
// => このjsだけ scriptタグに type="module" をつけることで一応は可能。
// => ただしFireFoxでは about:config の enableModuleScript を Trueにする必要がある。（つまりはまだ実験的機能）
// => 今からBabelなりを利用するのも無駄の様なので、正式対応まではimport/exportは忘れる
//
//import Person from '/bundles/app/js/modules/test_module.js';
//(function(){
//  const person = new Person('name', 20);
//  person.talk();
//})();

ELEMENT.locale(ELEMENT.lang.ja);


// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentLogisticsStockTransportListItem = {
    template: '#templateLogisticsStockTransportListTableRow'
  , props: [
      'transport'
    , 'item'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
      displayShortage: function() {

      return this.item.amount - this.item.picked;
    }
    , displayShortageCss: function() {
      let css = '';
      if (this.displayShortage > 0) {
        css = 'text-danger bold';
      } else if (this.displayShortage < 0) {
        css = 'text-info';
      }
      return css;
    }
    , displayCreated: function() {
      return this.item.created ? $.Plusnao.Date.getDateString(this.item.created, true) : '';
    }
    , displayUpdated: function() {
      return this.item.updated ? $.Plusnao.Date.getDateString(this.item.updated, true) : '';
    }

    , isFinished: function() {
      return this.transport.isFinished();
    }
  }
  , mounted: function() {
    this.$nextTick(function() {
      //this.userListUrlBase = $(this.$el).data('userListUrlBase');
      //this.plusnaoLoginUrlBase = $(this.$el).data('plusnaoLoginUrlBase');
    });
  }
  , methods: {
      remove: function() {
      this.$emit('remove-item', this.item);
    }
  }
};

// 詳細一覧
const vmComponentLogisticsStockTransportList = {
    template: '#deliveryStockTransportDetailList'
  , props: [
      'transport'
    , 'details'
  ]
  , data: function() {
    return {
    };
  }
  , components: {
    'listItem': vmComponentLogisticsStockTransportListItem // 一覧行
  }

  , mounted: function() {
    this.$nextTick(function () {

    });
  }

  , computed: {
  }
  , methods: {

    removeDetail: function(item) {
      this.$emit('remove-detail', item);
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------
  }
};


/**
 * 倉庫在庫一覧モーダル
 */
Vue.component('modal-product-warehouse-stock-list', {
    template: '#templateModalProductWarehouseStockList'
  , delimiters: ['(%', '%)']
  , props: [
      'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
    , 'transport'
    , 'warehouseList'
  ]
  , data: function() {
    return {
        findProductWarehouseStockListUrl: null
      , daihyoSyohinCode: null
      , remainNum: 0
      , stockList: []
    };
  }
  , computed: {
    caption: function() {
      let caption = '倉庫在庫一覧';
      if (this.warehouseName.length > 0) {
        caption = caption + " ( " + this.warehouseName + " )";
      }
      return caption;
    }

    , warehouseName: function() {
      let name = '';
      if (this.transport && this.transport.departureWarehouseId && this.warehouseList) {
        for (let i = 0; i < this.warehouseList.length; i++) {
          if (this.warehouseList[i].id == this.transport.departureWarehouseId) {
            name = this.warehouseList[i].name;
          }
        }
      }
      return name;
    }

  }

  , watch : {
  }

  , mounted: function() {
    this.$nextTick(function (){
      const self = this;
      const modal = $(self.$el);

      self.findProductWarehouseStockListUrl = $(self.$el).data('findProductWarehouseStockListUrl');

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
        self.reset();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.state.show = false; // 外部から閉じられた時のステータス手当
        }
      })
    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
    }

    , reset: function() {
      this.daihyoSyohinCode = null;
      this.remainNum = null;
      this.stockList = [];
    }

    , search: function() {
      const self = this;

      // データ読み込み処理
      const data = {
          daihyoSyohinCode: self.daihyoSyohinCode
        , warehouseId: self.transport.departureWarehouseId
      };

      $.ajax({
          type: "GET"
        , url: self.findProductWarehouseStockListUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status === 'ok') {
            self.stockList = [];
            for (let i = 0; i < result.data.length; i++) {
              let row = result.data[i];
              row.stock = Number(row.stock);
              row.moveNum = row.stock > self.remainNum ? row.stock - self.remainNum : 0;
              self.stockList.push(row);
            }

            if (self.stockList.length === 0) {
              alert('商品が見つからないか、倉庫に在庫がありません。');
            }

          } else {
            let message = result.message.length > 0 ? result.message : 'データが取得できませんでした。';
            alert(message);
            // self.productListState.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          console.log(stat);
          alert('エラーが発生しました。');
          // self.productListState.setMessage('エラーが発生しました。', 'alert-danger');
        })
        . always(function() {
        });
    }

    , submitList: function() {
      if (this.stockList.length === 0) {
        alert('追加するSKUがありません。');
        return;
      }
      let total = 0;
      for (let i = 0; i < this.stockList.length; i++) {
        total += this.stockList[i].moveNum;
      }
      if (total <= 0) {
        alert('移動するSKUがありません。');
      }

      this.$emit('submit-list', this.stockList);

      this.hideModal();
    }

  }
});


/**
 * CSV読込モーダル
 */
Vue.component('modal-load-csv', {
    template: '#templateModalLoadCsv'
  , delimiters: ['(%', '%)']
  , props: [
      'state' // { show: true|false }
    , 'transport'
  ]
  , data: function() {
    return {
        stockList: []
      , message: ""
      , messageCss: "alert-info"
    };
  }
  , computed: {
    caption: function() {
      return 'CSV読み込み';
    }
  }

  , mounted: function() {
    this.$nextTick(function (){
      const self = this;
      const modal = $(self.$el);

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
        self.reset();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.state.show = false; // 外部から閉じられた時のステータス手当
        }
      })
    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
    }

    , reset: function() {
      this.stockList = [];
      this.message = "";
      this.messageCss = "alert-info";
      $('#loadCsvInput', this.$el).val(null);
    }

    , loadCsv: function($event) {

      const self = this;
      const input = $('#loadCsvInput', self.$el);

      input.parse({
        config: {
          header: true,
          complete: function(results, file) {

            let num = 0;
            if (results) {
              for (let i = 0; i < results.data.length; i++) {
                let row = results.data[i];
                if (row.ne_syohin_syohin_code && row.move_num) {
                  self.stockList.push({
                      neSyohinSyohinCode: row.ne_syohin_syohin_code
                    , moveNum: Number(row.move_num)
                  });

                  num += 1;
                }
              }

              self.message = num + "商品をCSVファイルから読み込みました。";
              self.messageCss = "alert-success";
            }
          }
        },
        before: function(file, inputElem)
        {
          if (!file || !file.name || ! file.name.match(/.*\.csv/)) {
            return {
              action: "abort",
              reason: "ファイルの拡張子が.csvではありません。"
            };
          }

          self.reset();
        },
        error: function(err, file, inputElem, reason)
        {
          self.message = reason;
          self.messageCss = "alert-danger";
        },
        complete: function()
        {
        }
      });
    }

    , submitList: function() {
      if (this.stockList.length === 0) {
        alert('追加するSKUがありません。');
        return;
      }
      let total = 0;
      for (let i = 0; i < this.stockList.length; i++) {
        total += this.stockList[i].moveNum;
      }
      if (total <= 0) {
        alert('移動するSKUがありません。');
      }

      this.$emit('submit-list', this.stockList);

      this.hideModal();
    }

  }
});


// メイン
const vmComponentLogisticsStockTransportEdit = new Vue({
    el: '#logisticsStockTransportEdit'
  , delimiters: ['(%', '%)']
  , data: {
    messageState: {}
    , updateUrl: null
    , completeTransportUrl: null
    , findSkuUrl: null
    , findProductWarehouseStockListUrl: null
    , createPickingListUrl: null
    , deleteUrl: null
    , listUrl: null
    , downloadShoplistLabelCsvUrl: null

    , statusList: []
    , transportCodeList: []
    , warehouseList: []

    , transport: {
      displayCreated: function () {
      }
      , displayUpdated: function () {
      }
    }
    , details: []

    , addDetailInputSyohinCode: ""
    , choiceProductModalState: {
      show: false
    }
    , addDetailMessageState: {}

    , loadCsvModalState: {
      show: false
    }

    , completeModalVisible: false
    , completeModalForm: {
      locationCode: ""
    }

    , modalProductWarehouseStockListModalState: {
      show: false
    }

    , labelPrintModalState: {
        show: false
      , showRealShopButton: false
      , initialList: []
    }
  }

  , components: {
    'detailsTable': vmComponentLogisticsStockTransportList // 一覧テーブル
  }

  , mounted: function() {
    this.$nextTick(function () {
      const self = this;

      // メッセージオブジェクト
      self.messageState = new PartsGlobalMessageState();
      self.addDetailMessageState = new PartsGlobalMessageState();
      self.updateUrl = $(self.$el).data('updateUrl');
      self.completeTransportUrl = $(self.$el).data('completeTransportUrl');
      self.findSkuUrl = $(self.$el).data('findSkuUrl');
      self.createPickingListUrl = $(self.$el).data('createPickingListUrl');
      self.deleteUrl = $(this.$el).data('deleteUrl');
      self.listUrl = $(this.$el).data('listUrl');
      self.downloadShoplistLabelCsvUrl = $(this.$el).data('downloadShoplistLabelCsvUrl');


      let i, item;
      if (TRANSPORT) {
        const transport = TRANSPORT;

        // 日付変換
        $.each(['date', 'departureDate', 'estimatedDate', 'arrivalDate', 'created', 'updated'], function(i, key) {
          transport[key] = transport[key] ? new Date(transport[key].replace(/-/g, "/")) : null; // replace for firefox, IE
        });

        transport.displayCreated = function() {
          return this.created ? $.Plusnao.Date.getDateString(this.created) : '';
        };
        transport.displayUpdated = function() {
          return this.updated ? $.Plusnao.Date.getDateString(this.updated) : '';
        };
        transport.toScalarObject = function() {
          const result = Object.assign({}, self.transport);

          $.each(['date', 'departureDate', 'estimatedDate', 'arrivalDate', 'created', 'updated'], function(i, key) {
            result[key] = result[key] ? $.Plusnao.Date.getDateString(result[key]) : null;
          });
          return result;
        };
        transport.isFinished = function() {
          return Number(this.status) === 40 || Number(this.status) === 90;
        };

        self.transport = transport;

      } else {
        self.messageState.setMessage('データがありません。', 'alert-info');
      }
      if (DETAILS) {
        for (i = 0; i < DETAILS.length; i++) {
          item = DETAILS[i];
          self.details.push(item);
        }
      }

      self.statusList = STATUS_LIST;
      self.transportCodeList = TRANSPORT_CODE_LIST;
      self.warehouseList = WAREHOUSE_LIST;
    });
  }

  , computed: {
    displayTransportCreated: function() {
      return this.transport.displayCreated();
    }
    , displayTransportUpdated: function() {
      return this.transport.displayUpdated();
    }

    , isFinished: function() {
      return this.transport && this.transport.isFinished && this.transport.isFinished();
    }
    , isAllAssigned: function () {
      let result = true;
      for(let item of this.details){
        if(Number(item.picked) !== 0) {
          result = Boolean("");
        }
      }
      return result;
    }
  }

  , methods: {

    update: function() {
      const self = this;

      if (this.transport.id) {
        if (!confirm('変更を保存しますか？')) {
          return;
        }
      }

      // Show loading
      $.Vendor.WaitingDialog.show();

      self.updateProcess().done(function(result) {

        if (result.status === 'ok') {
          self.messageState.setMessage(result.message, 'alert alert-success');
          if (result.redirect) {
            window.location.href = result.redirect;
          }

        } else {
          self.messageState.setMessage(result.message, 'alert alert-danger');

        }

      }).fail(function() {
        self.messageState.setMessage('移動伝票の更新時にエラーが発生しました。', 'alert alert-danger');

      }).always(function() {
        // Show loading
        $.Vendor.WaitingDialog.hide();

      });
    }

    , updateProcess: function() {
      const self = this;
      const data = {
          transport: self.transport.toScalarObject()
        , details: self.details ? self.details : []
      };

      return $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      });
    }


    , removeDetail: function(detail) {

      let message = '';
      if (detail.picked > 0) {
        message = 'すでに在庫が' + detail.picked + '個割り当てられていますが、商品を削除してよろしいですか？';
      } else if (this.transport.status != '0') {
        message = '状態が「未処理」ではない伝票です。商品を削除してよろしいですか？'
      }
      if (message) {
        if (!confirm(message)) {
          return;
        }
      }

      const index = this.details.indexOf(detail);
      if (index !== -1) {
        this.details.splice(index, 1);
      }
    }

    , addDetailByInput: function() {
      const self = this;

      self.addDetailMessageState.clear();
      if (!self.addDetailInputSyohinCode || self.addDetailInputSyohinCode.length === 0) {
        return;
      }

      // 検索
      const data = {
        code: self.addDetailInputSyohinCode
      };

      $.ajax({
          type: "GET"
        , url: self.findSkuUrl
        , dataType: "json"
        , data: data
      }).done(function(result) {

        if (result.status === 'ok' && result.data) {
          const item = {
            neSyohinSyohinCode: result.data.neSyohinSyohinCode
          };
          self.addDetail(item);

        } else {
          self.addDetailMessageState.setMessage('商品が取得できませんでした', 'alert-warning p6v mb0');
        }

      }).fail(function() {
        self.addDetailMessageState.setMessage('商品取得エラー', 'alert-danger p6v mb0');

      }).always(function() {
      });
    }

    , addDetail: function(item) {
      if (item && item.neSyohinSyohinCode) {
        const self = this;

        self.addDetailMessageState.clear();

        for (let i = 0; i < this.details.length; i++) {
          if (item.neSyohinSyohinCode === this.details[i].neSyohinSyohinCode) {
            self.addDetailMessageState.setMessage('この商品はすでに含まれています', 'alert-warning p6v mb0');
            return;
          }
        }
        const row = {
            transportId: this.transport.id
          , neSyohinSyohinCode: item.neSyohinSyohinCode
          , amount: 0
          , picked: 0
          , shortage: 0
          , created: new Date()
          , updated: new Date()
        };

        this.details.push(row);
      }
    }

    , addDetailsList: function(list) {
      const self = this;
      let message = "";
      self.addDetailMessageState.clear();

      for (let index = 0; index < list.length; index++) {
        let item = list[index];
        if (item && item.neSyohinSyohinCode) {

          if (item.moveNum <= 0) {
            continue;
          }

          let error = false;
          for (let i = 0; i < this.details.length; i++) {
            if (item.neSyohinSyohinCode === this.details[i].neSyohinSyohinCode) {
              message += "重複スキップ: " + item.neSyohinSyohinCode + "\n";
              error = true;
              break;
            }
          }
          if (error) {
            continue;
          }

          let row = {
              transportId: this.transport.id
            , neSyohinSyohinCode: item.neSyohinSyohinCode
            , amount: item.moveNum
            , picked: 0
            , shortage: 0
            , created: new Date()
            , updated: new Date()
          };

          this.details.push(row);
        }
      }

      if (message.length > 0) {
        alert(message);
      }
    }

    , showChoiceProductModal: function(event) {
      this.choiceProductModalState.show = true;
    }

    , showProductWarehouseStockListModal: function(event) {
      this.modalProductWarehouseStockListModalState.show = true;
    }

    , showLoadCsvModal: function(event) {
      this.loadCsvModalState.show = true;
    }

    , showLabelModal: function() {

      let list = [];
      for (let i = 0; i < this.details.length; i++) {
        const detail = this.details[i];
        if (detail.picked > 0) {
          list.push({
              neSyohinSyohinCode: detail.neSyohinSyohinCode
            , num: detail.picked
          });
        }
      }
      this.labelPrintModalState.initialList = list;
      this.labelPrintModalState.show = true;
    }

    , completeTransport: function() {
      const self = this;

      // モーダルは閉じる
      self.completeModalVisible = false;

      // Show loading
      $.Vendor.WaitingDialog.show();

      // まずデータセーブ
      const deferred = self.updateProcess();

      deferred.then(
          function(result) {
            const d = $.Deferred();

            if (result.status === 'ok') {

              const data = {
                location_code: self.completeModalForm.locationCode
              };
              $.ajax({
                  type: "POST"
                , url: self.completeTransportUrl
                , dataType: "json"
                , data: data
              })
                .done(function(result) {
                  d.resolve(result);
                })
                .fail(function(stat) {
                  d.reject(stat);
                })
              ;

            } else {
              d.reject(result.message);
            }

            return d.promise();
          }
        )
        .then(
          function(result) {
            const d = $.Deferred();

            if (result.status === 'ok') {
              self.messageState.setMessage(result.message, 'alert alert-success');
              d.resolve();
              if (result.redirect) {
                window.location.href = result.redirect;
              }

            } else {
              d.reject(result.message);
            }

            return d.promise();
          }
        )
        .fail(function(result) {
          let message = '移動伝票の完了処理でエラーが発生しました。';

          console.log(typeof result);

          if (typeof result === 'string') {
            message = result;
          } else if (typeof result === 'object' && result.message) {
            message = result.message;
          }
          self.messageState.setMessage(message, 'alert alert-danger');
        })
        .always(function() {
            $.Vendor.WaitingDialog.hide();
        });

    }

    , createPickingList: function(id) {
      const self = this;

      if (!confirm('ピッキングリストを作成します。すでにある場合には削除して再作成されます。よろしいですか？')) {
        return;
      }

      const data = {
        id: id
      };

      $.ajax({
          type: "POST"
        , url: self.createPickingListUrl
        , dataType: "json"
        , data: data
      }).done(function(result) {

        if (result.status === 'ok') {
          self.messageState.setMessage(result.message, 'alert alert-success');
          window.location.reload();
        } else {
          self.messageState.setMessage(result.message, 'alert alert-danger');
        }

      }).fail(function() {
        self.messageState.setMessage('ピッキングリストの作成ができませんでした。', 'alert alert-danger');

      }).always(function() {
      });

    }

    , deleteItem: function(id) {
      const self = this;

      if (!confirm('この伝票を削除してよろしいですか？')) {
        return;
      }

      const data = {
        id: id
      };

      $.ajax({
          type: "POST"
        , url: self.deleteUrl
        , dataType: "json"
        , data: data
      }).done(function(result) {

        if (result.status === 'ok') {
          self.messageState.setMessage(result.message, 'alert alert-success');

          window.location.href = self.listUrl;

        } else {
          self.messageState.setMessage(result.message, 'alert alert-danger');
        }

      }).fail(function() {
        self.messageState.setMessage('移動伝票の削除に失敗しました。', 'alert alert-danger');

      }).always(function() {
      });
    }

    , downloadCsvTemplate: function() {
      let csv = [];

      csv.push(['ne_syohin_syohin_code', 'move_num'].join(","));

      csv = csv.join("\n") + "\n";
      const data = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
      const element = document.createElement('a');
      element.href = data;
      element.setAttribute('download', 'stock_transport_template.csv');
      this.$el.appendChild(element);
      element.click();
      this.$el.removeChild(element);
    }

    , downloadCsv: function() {
      const self = this;
      let csv = [];

      csv.push([
        'ne_syohin_syohin_code',
        'move_num',
        'picked',
        'shortage'
      ].join(","));

      const transport = self.transport;
      const details = self.details ? self.details : [];

      console.log(details);
      const fileName = 'stock_transport_' + (transport ? transport.id : 'new') + '.csv';

      for (let i = 0; i < self.details.length; i++) {
        let detail = self.details[i];
        csv.push([
          detail.neSyohinSyohinCode,
          detail.amount,
          detail.picked,
          detail.shortage
        ].join(","));
      }

      csv = csv.join("\n") + "\n";
      const data = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
      const element = document.createElement('a');
      element.href = data;
      element.setAttribute('download', fileName);
      this.$el.appendChild(element);
      element.click();
      this.$el.removeChild(element);
    }
    
    , getShoplistLabelCsvDownloadUrl: function(id) {
      return this.downloadShoplistLabelCsvUrl + "?id=" + id+ "&type=labelFromTransport"
    }

  }

});


