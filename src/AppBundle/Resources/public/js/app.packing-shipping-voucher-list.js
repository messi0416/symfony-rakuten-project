/**
 * 出荷伝票リスト JS
 */

const voucherList = new Vue({
  el: '#voucherList',
  data: {
    startUrl: null,
    item: {},
    voucherId: '',
    messageState: {}
  },
  ready: function () {
    this.item = ITEM;
    this.addDataToShippingVoucher();
    this.addDataToPackingList();
    this.startUrl = $(this.$el).data('startUrl');
    this.voucherId = this.item.shippingVoucher.id;
    this.messageState = new PartsGlobalMessageState();
  },
  methods: {
    addDataToShippingVoucher: function() {
      let statusName = '';
      switch (this.item.shippingVoucher.status) {
        case 1:
          statusName = '梱包開始';
          break;
        case 2:
          statusName = '梱包中';
          break;
        case 3:
          statusName = '梱包終了';
          break;
        default:
          break;
      }
      this.item.shippingVoucher.statusName = statusName;
    },
    addDataToPackingList: function() {
      let list = [];
      this.item.packingList.forEach(value => {
        switch (value.status) {
          case '0':
            value.statusName = '';
            break;
          case '1':
            value.statusName = '';
            break;
          case '2':
            if (value.labelReissueFlg === '1') {
              value.statusName = '変更';
              value.changeFlg = true;
              value.css = 'badge badge-change';
            } else {
              value.statusName = 'OK';
              value.css = 'badge badge-ok';
            }
            break;
          case '3':
            value.statusName = '不足';
            value.css = 'badge badge-shortage';
            break;
          case '4':
            value.statusName = '保留';
            value.css = 'badge badge-hold';
            break;
          case '5':
            value.statusName = 'STOP待';
            value.css = 'badge badge-wait-shipping-stop';
            break;
          case '6':
            value.statusName = 'STOP';
            value.css = 'badge badge-shipping-stop';
            break;
          default:
            break;
        }
        list.push(value);
      });
      this.item.packingList = list;
    },
    startPacking: function() {
      this.messageState.clear();
      const self = this;
      $.ajax({
        type: "POST",
        url: self.startUrl,
        dataType: "json",
        data: {
          id: self.voucherId
        },
      })
      .done(function(result) {
        if (result.status === 'ok') {
          if (self.item.packingList.length === 0) {
             self.item.shippingVoucher.status = result.updateInfo.status;
             self.item.shippingVoucher.packingAccountName = result.updateInfo.packingAccount;
             self.addDataToShippingVoucher();
             self.messageState.setMessage('梱包を開始しました。', 'alert alert-success');
          } else {
            location.href = self.item.packingList[0].detailUrl;
          }
        } else {
          const message = result.message ? result.message : '梱包開始処理でエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function() {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
    }
  }
});