/**
 * 発送方法変更一覧用 JS
 */
// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentDeliveryChangeShippingMethodListItem = {
  template: '#templateDeliveryChangeShippingMethodListTableRow'
  , props: [
    'item'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
    //displayOrderDate: function() {
    //  return this.item.orderDate ? $.Plusnao.Date.getDateString(this.item.orderDate) : '';
    //}
  }
  , methods: {

    deleteVoucher: function() {
      this.$emit('delete-voucher', this.item);
    }

    , openVoucherWindow: function() {
      $.Plusnao.NextEngine.openVoucherWindow(this.item.voucherNumber, 'logistics-list');
      if (! this.isVoucherOpened) {
        this.$emit('voucher-opened', this.item.voucherNumber);
      }
      return false;
    }
  }
};



var vmDeliveryChangeShippingMethodList = new Vue({
    el: '#deliveryChangeShippingMethodList'
  , data: {
      messageState: new PartsGlobalMessageState()

    , url: null
    , urlAddVoucherConfirm: null
    , urlAddVoucher: null
    , urlDeleteVoucher: null
    , urlVoucherListUpdate: null
    , urlDownloadCsv: null

    , availableShippingMethods: []

    , list: []
    , shippingMethods: [] // loadData時に更新
    , activeTab: ''
    , csvDateTime: null // loadData時に更新

    , inputVoucherNumber: ''

    , currentVoucher: null

  }
  , components: {
    'result-item': vmComponentDeliveryChangeShippingMethodListItem // 一覧テーブル
  }

  , computed: {
    pageData: function() {
      var self = this;

      // タブ振り分け
      var list = self.list.slice();
      return list.filter(function(item, index) {
        if (self.activeTab == '') { // すべて
          return true;
        }

        return item.currentReceiveOrderDeliveryId == self.activeTab;
      });
    }

    , csvDownloadUrl: function() {
      var url = null;
      if (this.activeTab.length > 0) {
        var now = $.Plusnao.Date.getDateString(this.csvDateTime, true);
        url = this.urlDownloadCsv + '?method=' + this.activeTab + '&now=' + (now.replace(/[: -]/g, ''));
      }
      return url;
    }

    , csvDownloadFilename: function() {
      var filename = null;
      if (this.activeTab.length > 0) {
        var now = $.Plusnao.Date.getDateString(this.csvDateTime, true);

        var shippingMethod = this.activeTab;
        if (this.availableShippingMethods) {
          for (var i = 0; i < this.availableShippingMethods.length; i++) {
            if (this.availableShippingMethods[i].code == this.activeTab) {
              shippingMethod = this.availableShippingMethods[i].name;
              break;
            }
          }
        }

        filename = '宛名_' + shippingMethod + '_' + (now.replace(/[: -]/g, '')) + '.csv';
      }
      return filename;
    }
  }

  , mounted: function() {
    this.$nextTick(function () {

      this.url = $(this.$el).data('urlGetList');
      this.urlAddVoucherConfirm = $(this.$el).data('urlAddVoucherConfirm');
      this.urlAddVoucher = $(this.$el).data('urlAddVoucher');
      this.urlDeleteVoucher = $(this.$el).data('urlDeleteVoucher');
      this.urlVoucherListUpdate = $(this.$el).data('urlVoucherListUpdate');
      this.urlDownloadCsv = $(this.$el).data('urlDownloadCsv');

      this.availableShippingMethods = $(this.$el).data('availableShippingMethods');

      var dateOptions = {
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      };

      $('#importDateFrom').datepicker(dateOptions);
      $('#importDateTo').datepicker(dateOptions);

      this.loadData();
    });
  }
  , methods: {

    loadData: function() {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('loading ...');

      // データ読み込み処理
      var data = {
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
            self.shippingMethods = [];

            for (var i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              //var row = {
              //    neSyohinSyohinCode : item.ne_syohin_syohin_code
              //  , stock          : Number(item.stock)
              //  , orderRemainNum : Number(item.order_remain_num)
              //  , orderNum       : Number(item.order_num)
              //  , assignedNum    : Number(item.assigned_num)
              //  , unassignedNum  : Number(item.unassigned_num)
              //  , voucherNum     : Number(item.voucher_num)
              //  , shortage       : Number(item.shortage)
              //  , lastOrdered    : (item.last_ordered ? new Date(item.last_ordered.replace(/-/g, "/")) : null) // replace for firefox, IE
              //};
              //
              //self.list.push(row);

              self.list.push(item);

              // shippingMethods 更新
              var exists = false;
              for (var j = 0; j < self.shippingMethods.length; j++) {
                if (self.shippingMethods[j].code == item.currentReceiveOrderDeliveryId) {
                  exists = true;
                  break;
                }
              }
              if (!exists) {
                self.shippingMethods.push({
                    code: item.currentReceiveOrderDeliveryId
                  , name: item.currentShippingMethod
                });
              }
            }

            self.shippingMethods.sort(function(a, b) {
              return Number(a.code) - Number(b.code);
            });

            self.csvDateTime = new Date();

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });
    }

    , showFirstPage: function() {
      var pageInfo = {
          page: 1
        , pageItemNum: this.pageItemNum
      };
      this.showPage(pageInfo);
    }

    , getTabActiveCss: function(code) {
      return code == this.activeTab ? 'active' : null;
    }

    , activateTab: function(code) {
      this.activeTab = code;
    }

    , addVoucherConfirm: function() {
      var self = this;

      if (!self.inputVoucherNumber || self.inputVoucherNumber.length == 0) {
        return;
      }

      var data = {
        voucherNumber: self.inputVoucherNumber
      };

      $.ajax({
          type: "POST"
        , url: self.urlAddVoucherConfirm
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.currentVoucher = result.voucher;

            // 簡易実装。
            if (confirm(result.message)) {
              return self.addVoucher();
            } else {
              self.messageState.setMessage('処理を中止しました。', 'alert alert-info');
            }

          } else {
            var message = result.message.length > 0 ? result.message : '伝票の確認に失敗しました。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
        });

    }


    , addVoucher: function() {
      var self = this;

      if (!self.currentVoucher) {
        alert('伝票が指定されていません。処理を中断しました。');
        return;
      }

      var data = {
        voucherNumber: self.currentVoucher.voucherNumber
      };

      $.ajax({
          type: "POST"
        , url: self.urlAddVoucher
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.currentVoucher = null;
            self.inputVoucherNumber = '';

            self.messageState.setMessage(result.message, 'alert alert-success');
            return self.loadData();

          } else {
            var message = result.message.length > 0 ? result.message : '伝票の追加に失敗しました。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
        });
    }

    , deleteVoucher: function(item) {
      var self = this;

      if (!confirm("この伝票を発送方法変更対象から削除しますか？ \n\n（削除しても、NextEngine上で変更された発送方法は戻りません）")) {
        return;
      }
      var data = {
        voucherNumber: item.voucherNumber
      };

      $.ajax({
          type: "POST"
        , url: self.urlDeleteVoucher
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert alert-success');
            return self.loadData();

          } else {
            var message = result.message.length > 0 ? result.message : '伝票の削除に失敗しました。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
        });

    }

    /// NextEngine受注明細差分更新＆発送方法変更反映 （キュー追加処理のみ）
    , updateVoucherList: function() {
      var self = this;

      if (!confirm("NextEngine受注明細差分更新を行い、発送方法の変更を反映します。よろしいですか？")) {
        return;
      }

      var data = {};

      $.ajax({
          type: "POST"
        , url: self.urlVoucherListUpdate
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            self.messageState.setMessage(result.message, 'alert alert-success');


          } else {
            var message = result.message.length > 0 ? result.message : 'データの更新に失敗しました。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
        });

    }


  }
});
