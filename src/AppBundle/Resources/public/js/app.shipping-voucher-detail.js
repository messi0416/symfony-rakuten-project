/**
 * 伝票詳細画面 JS
 */

const shippingVoucherDetail = new Vue({
  el: '#shippingVoucherDetail',
  data: {
    okUrl: null,
    holdUrl: null,
    shortageUrl: null,
    changeDeliveryUrl: null,
    completeUrl: null,
    weightSizeEditUrl: null,
    changeDeliveryCompleteUrl: null,
    item: ITEM,
    deliveryMethodList: DELIVERY_METHOD_LIST,
    inquiryNumber: null, // 現在のお問い合わせ番号
    selectDeliveryMethod: "", // 配送方法
    selectSortageInfo: {
      skucode: null,
      requiredAmount: null,
      assignNum: null,
      updated: null,
    }, // 商品数量不足モーダルで使うもの
    messageState: new PartsGlobalMessageState(), // メインのエラーメッセージ
    messageStateShortageModal: new PartsGlobalMessageState(), // 商品数量不足モーダルのエラーメッセージ
    messageStateChangeDeliveryMethodModalModal: new PartsGlobalMessageState(), // 配送方法変更モーダルのエラーメッセージ
    completeButtonName: "完了して次へ",
    stopButtonName: "STOPして次へ",
    nextButtonName: "次へ"
  },
  ready: function () {
    this.okUrl = $(this.$el).data('ok');
    this.holdUrl = $(this.$el).data('hold');
    this.shortageUrl = $(this.$el).data('shortage');
    this.changeDeliveryUrl = $(this.$el).data('changeDelivery');
    this.completeUrl = $(this.$el).data('complete');
    this.weightSizeEditUrl = $(this.$el).data('weightSizeEdit');
    this.changeDeliveryCompleteUrl = $(this.$el).data('changeDeliveryAndComplete');

    this.completeButtonName = this.item.packing.isLast ? "全件梱包完了" : "完了して次へ";
    this.stopButtonName = this.item.packing.isLast ? "STOPして全件梱包完了" : "STOPして次へ";
    this.nextButtonName = this.item.packing.isLast ? "伝票リストへ" : "次へ";
  },
  computed: {
  },
  methods: {
    /**
     * SKU別重量・サイズ 編集画面を別タブで開く
     */
    openWeightSizeEdit: function(code) {
      const url = this.weightSizeEditUrl + "?code=" + code;
      window.open(url);
    },
    openProductDetail: function(url) {
      window.open(url);
    },
    /**
     * OKボタン押下時の処理
     */
    onClickOk: function(id) {
      const self = this;
      self.messageState.clear();
      if (!self.item.packing.isUpdatable) {
        self.messageState.setMessage('梱包開始していないか、梱包完了しているため変更できません。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
        return;
      }
      const shippingVoucherDetail = self.item.shippingVoucherDetail.find(detail => detail.id === id);

      // 不足もしくは保留のステータスの場合、取り消してOKに変更するか確認する
      if (shippingVoucherDetail.isShortage) {
        if (!confirm('不足状態を取消して、保留に変更してよろしいですか？')) {
          return;
        }
      }

      const form = {
          id : shippingVoucherDetail.id,
          isOk : !shippingVoucherDetail.isOk,
          updated: shippingVoucherDetail.updated,
      };
      self.loading(id, 'open');
      $.ajax({
        type: "POST",
        url: self.okUrl,
        dataType: "json",
        data: {
          form: form
        }
      }).done(function(result) {
        if (result.status == 'ok') {
          const index = self.item.shippingVoucherDetail.findIndex(detail => detail.id === id);
          self.item.shippingVoucherDetail[index].isOk = result.isOk;
          self.item.shippingVoucherDetail[index].isHold = result.isHold;
          self.item.shippingVoucherDetail[index].isShortage = result.isShortage;
          self.item.shippingVoucherDetail[index].updated = result.updated;
        } else {
          self.messageState.setMessage('[' + shippingVoucherDetail.skucode + '] ' + result.message, 'alert-danger');
          $("html,body").animate({scrollTop:0},600);
        }
      }).fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
      }).always(function() {
        self.loading(id, 'hide');
      });
    },
    /**
     * 保留ボタン押下時の処理
     */
    onClickHold: function(id) {
      const self = this;
      self.messageState.clear();
      if (!self.item.packing.isUpdatable) {
        self.messageState.setMessage('梱包開始していないか、梱包完了しているため変更できません。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
        return;
      }
      const shippingVoucherDetail = self.item.shippingVoucherDetail.find(detail => detail.id === id);

      // 不足のステータスの場合、取り消して保留に変更するか確認する
      if (shippingVoucherDetail.isShortage) {
        if (!confirm('不足状態を取消して、保留に変更してよろしいですか？')) {
          return;
        }
      }

      const form = {
          id : shippingVoucherDetail.id,
          isHold : !shippingVoucherDetail.isHold,
          updated: shippingVoucherDetail.updated,
      };
      self.loading(id, 'open');
      $.ajax({
        type: "POST",
        url: self.holdUrl,
        dataType: "json",
        data: {
          form: form
        }
      }).done(function(result) {
        if (result.status == 'ok') {
          const index = self.item.shippingVoucherDetail.findIndex(detail => detail.id === id);
          self.item.shippingVoucherDetail[index].isOk = result.isOk;
          self.item.shippingVoucherDetail[index].isHold = result.isHold;
          self.item.shippingVoucherDetail[index].isShortage = result.isShortage;
          self.item.shippingVoucherDetail[index].updated = result.updated;
        } else {
          self.messageState.setMessage('[' + shippingVoucherDetail.skucode + '] ' + result.message, 'alert-danger');
          $("html,body").animate({scrollTop:0},600);
        }
      }).fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
      }).always(function() {
        self.loading(id, 'hide');
      });

    },

    /**
     * 数量不足モーダルを開いたときの処理
     */
    openModalShortage: function(id) {
      const self = this;
      self.messageStateShortageModal.clear();
      this.inquiryNumber = null;
      const target = self.item.shippingVoucherDetail.find(detail => detail.id === id);
      self.selectSortageInfo = {
        id: target.id,
        requiredAmount: target.requiredAmount,
        assignNum: target.isShortage ? target.assignNum : null,
        updated: target.updated,
      };
    },
    /**
     * 数量不足モーダルで登録ボタンを押下時の処理
     */
    onClickShortageRegister: function() {
      const self = this;
      self.messageStateShortageModal.clear();
      if (!self.item.packing.isUpdatable) {
        self.messageStateShortageModal.setMessage('梱包開始していないか、梱包完了しているため変更できません。', 'alert-danger');
        return;
      }
      if (!self.selectSortageInfo.assignNum) {
        self.messageStateShortageModal.setMessage('見つかった数を入力してください。', 'alert-danger');
        return;
      }
      const form = {
          packingId: self.item.packing.id,
          detailId: self.selectSortageInfo.id,
          assignNum : self.selectSortageInfo.assignNum,
          updated: self.selectSortageInfo.updated,
          inquiryNumber : self.inquiryNumber,
          packingUpdated: self.item.packing.updated,
      };
      $.ajax({
        type: "POST",
        url: self.shortageUrl,
        dataType: "json",
        data: {
          form: form
        }
      }).done(function(result) {
        if (result.status == 'ok') {
          const index = self.item.shippingVoucherDetail.findIndex(detail => detail.id === self.selectSortageInfo.id);
          self.item.shippingVoucherDetail[index].isOk = result.isOk;
          self.item.shippingVoucherDetail[index].isShortage = result.isShortage;
          self.item.shippingVoucherDetail[index].isHold = result.isHold;
          self.item.shippingVoucherDetail[index].assignNum = self.selectSortageInfo.assignNum;
          self.item.shippingVoucherDetail[index].updated = result.updated;
          self.item.packing.updated = result.packingUpdated;
          $('#shortageModal').modal('hide');
        } else {
          self.messageStateShortageModal.setMessage(result.message, 'alert-danger');
        }
      }).fail(function(stat) {
        self.messageStateShortageModal.setMessage('エラーが発生しました。', 'alert-danger');
      });
    },
    /**
     * 配送方法変更モーダルを開いたときの処理
     * 入力値初期化
     */
    openModalChangeDeliveryMethod: function() {
      this.messageStateChangeDeliveryMethodModalModal.clear();
      this.inquiryNumber = null;
      this.selectDeliveryMethod = "";
    },
    /**
     * 配送方法変更モーダルで登録ボタンを押下時の処理
     */
    onClickChangeDeliveryMethodRegister: function() {
      const self = this;
      self.messageStateChangeDeliveryMethodModalModal.clear();
      if (!self.item.packing.isChangableDelivery) {
        self.messageStateChangeDeliveryMethodModalModal.setMessage('梱包開始していないため変更できません。', 'alert-danger');
        return;
      }
      if (!self.selectDeliveryMethod) {
        self.messageStateChangeDeliveryMethodModalModal.setMessage('新しい発送方法を選択してください。', 'alert-danger');
        return;
      }
      const form = {
          packingId: self.item.packing.id,
          deliveryMethodId : self.selectDeliveryMethod,
          updated: self.item.packing.updated,
          inquiryNumber : self.inquiryNumber,
      };
      $.ajax({
        type: "POST",
        url: self.changeDeliveryUrl,
        dataType: "json",
        data: {
          form: form
        }
      }).done(function(result) {
        if (result.status == 'ok') {
          // 選択した配送方法を取得する
          const deliveryName = self.deliveryMethodList.find(deliveryMethod => deliveryMethod.id === self.selectDeliveryMethod).name;

          // 変更後の値を設定する
          self.item.packing.labelReissueFlg = true;
          self.item.packing.deliveryName = deliveryName;
          self.item.packing.updated = result.updated;

          $('#changeDeliveryMethodModal').modal('hide');
        } else {
          self.messageStateChangeDeliveryMethodModalModal.setMessage(result.message, 'alert-danger');
        }
      }).fail(function(stat) {
        self.messageStateChangeDeliveryMethodModalModal.setMessage('エラーが発生しました。', 'alert-danger');
      });
    },
    /**
     * 配送方法変更モーダルで「登録して次へ」ボタンを押下時の処理
     */
    onClickChangeDeliveryMethodRegisterAndComplete: function() {
      const self = this;
      self.messageStateChangeDeliveryMethodModalModal.clear();
      if (!self.item.packing.isUpdatable) {
        self.messageStateChangeDeliveryMethodModalModal.setMessage('梱包開始していないか、梱包完了しているため変更できません。', 'alert-danger');
        return;
      }
      if (!self.selectDeliveryMethod) {
        self.messageStateChangeDeliveryMethodModalModal.setMessage('新しい発送方法を選択してください。', 'alert-danger');
        return;
      }
      const hasStatusList = self.item.shippingVoucherDetail.map(shippingVoucherDetail => {
        return (shippingVoucherDetail.isHold || shippingVoucherDetail.isShortage || shippingVoucherDetail.isOk);
      });
      // SKUでボタンが押されていない場合確認する
      if (self.item.packing.totalAmount > 1 && !hasStatusList.every(statusFlg => statusFlg)) {
        if (!confirm('未処理の商品が残っています。全てOKに変更してもよろしいですか？')) {
          return;
        }
      }
      const form = {
          packingId: self.item.packing.id,
          deliveryMethodId : self.selectDeliveryMethod,
          updated: self.item.packing.updated,
          inquiryNumber : self.inquiryNumber,
          isLast: self.item.packing.isLast,
          nextVoucherNumber: self.item.packing.nextVoucherNumber,
          warehouseId: self.item.packing.warehouseId,
          pickingListDate: self.item.packing.pickingListDate,
          pickingListNumber: self.item.packing.pickingListNumber,
      };
      $.ajax({
        type: "POST",
        url: self.changeDeliveryCompleteUrl,
        dataType: "json",
        data: {
          form: form
        }
      }).done(function(result) {
        if (result.status == 'ok') {
          location.href = result.redirect;
        } else {
          self.messageStateChangeDeliveryMethodModalModal.setMessage(result.message, 'alert-danger');
        }
      }).fail(function(stat) {
        self.messageStateChangeDeliveryMethodModalModal.setMessage('エラーが発生しました。', 'alert-danger');
      });
    },
    /**
     * 完了して次へ押下時の処理
     */
    onClickComplete: function(type='') {
      const self = this;
      self.messageState.clear();

      if (type !== 'next' && !self.item.packing.isUpdatable) {
        self.messageState.setMessage('梱包開始していないか、梱包完了しているため変更できません。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
        return;
      }

      let stopFlg = false; // 出荷STOPにするか否か
      let inquiryNumber = null; // お問い合わせ番号
      let onlyNextFlg = false; // (更新処理は行わず)次の画面に遷移するのみか否か

      switch (type) {
        case '':
          const hasStatusList = self.item.shippingVoucherDetail.map(shippingVoucherDetail => {
            return (shippingVoucherDetail.isHold || shippingVoucherDetail.isShortage || shippingVoucherDetail.isOk);
          });
          // SKUでボタンが押されていない場合確認する
          if (self.item.packing.totalAmount > 1 && !hasStatusList.every(statusFlg => statusFlg)) {
            if (!confirm('未処理の商品が残っています。全てOKに変更してもよろしいですか？')) {
              return;
            }
          }
          break;
        case 'stop':
          stopFlg = true;
          inquiryNumber = self.inquiryNumber
          break;
        case 'next':
          onlyNextFlg = true;
          break;
      }

      const form = {
          packingId: self.item.packing.id,
          updated: self.item.packing.updated,
          isLast: self.item.packing.isLast,
          nextVoucherNumber: self.item.packing.nextVoucherNumber,
          warehouseId: self.item.packing.warehouseId,
          pickingListDate: self.item.packing.pickingListDate,
          pickingListNumber: self.item.packing.pickingListNumber,
          stopFlg: stopFlg,
          inquiryNumber: inquiryNumber,
          onlyNextFlg: onlyNextFlg,
          nextPickingListNumber: null,
          packingGroupId: null,
      };
      this.completeProcess(form);
    },
    /**
     * 次のセットへ押下時の処理
     */
    onClickNextSet: function() {
      const self = this;
      self.messageState.clear();
      if (!self.item.packing.isUpdatable) {
        self.messageState.setMessage('梱包開始していないか、梱包完了しているため変更できません。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
        return;
      }
      const hasStatusList = self.item.shippingVoucherDetail.map(shippingVoucherDetail => {
        return (shippingVoucherDetail.isHold || shippingVoucherDetail.isShortage || shippingVoucherDetail.isOk);
      });
      // SKUでボタンが押されていない場合確認する
      if (self.item.packing.totalAmount > 1 && !hasStatusList.every(statusFlg => statusFlg)) {
        if (!confirm('未処理の商品が残っています。全てOKに変更してもよろしいですか？')) {
          return;
        }
      }

      const form = {
          packingId: self.item.packing.id,
          updated: self.item.packing.updated,
          isLast: self.item.packing.isLast,
          nextVoucherNumber: self.item.packing.nextVoucherNumber,
          warehouseId: self.item.packing.warehouseId,
          pickingListDate: self.item.packing.pickingListDate,
          pickingListNumber: self.item.packing.pickingListNumber,
          stopFlg: false,
          inquiryNumber: null,
          onlyNextFlg: false,
          nextPickingListNumber: self.item.packing.nextPickingListNumber,
          packingGroupId: self.item.packing.packingGroupId,
      };
      this.completeProcess(form);
    },
    /**
     * 完了処理
     */
    completeProcess: function (form) {
      const self = this;
      $.ajax({
        type: "POST",
        url: self.completeUrl,
        dataType: "json",
        data: {
          form: form
        }
      }).done(function(result) {
        if (result.status == 'ok') {
          location.href = result.redirect;
        } else {
          self.messageState.setMessage(result.message, 'alert-danger');
          $("html,body").animate({scrollTop:0},600);
        }
      }).fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert-danger');
        $("html,body").animate({scrollTop:0},600);
      });
    },

    loading: function(id, test) {
      if (test === 'open') {
        $('#loading' + id).css('display', 'block');
      } else if (test === 'hide') {
        $('#loading' + id).css('display', 'none');
      }

    },
  },
});