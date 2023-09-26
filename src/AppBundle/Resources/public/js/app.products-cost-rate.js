/**
 * 商品別原価率一覧 JS
 */
$(function() {

  Vue.config.debug = true;

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#globalMessage',
    data: {
        message: ''
      , messageCssClass: ''
      , loadingImageUrl: null
    },
    ready: function() {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    },
    methods: {
      setMessage: function(message, cssClass, autoHide) {
        cssClass = cssClass || 'alert alert-info';
        autoHide = autoHide || true;

        this.message = message;
        this.setCssClass(cssClass);

        if (autoHide) {
          setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
        }
      },
      setCssClass: function(cssClass) {
        this.messageCssClass = cssClass;
      },
      clear: function() {
        this.message = '';
        this.messageCssClass = '';
      },
      closeWindow: function() {
        window.close();
      }
    }
  });

  // 機能ブロック
  var vmFunctionBlock = new Vue({
    el: '#functionBlock',
    data: {
        threshold: null
      , shakeBorder: null
      , changeAmountUp: null
      , changeAmountDown: null
      , changeAmountAdditional: null
    },
    methods: {
      /// 再計算
      updateRates: function() {
        vmUpdateRatesConfirmModal.open();
      },

      /// 揺さぶり
      unsettleRates: function() {
        vmUnsettleRatesConfirmModal.open();
      },

      /// 更新確認
      openUpdateCostRateProcessConfirm: function() {
        vmUpdateCostRateProcessModal.open();
        return false;
      },

      /// リセット確認
      openResetConfirmDialog: function() {
        vmResetCostRateListConfirmModal.open();
        return false;
      }
    }
  });


  var vmProductCostRateListTable = new Vue({
    el: '#productCostRateListTable',
    data: {
      updateCostRateUrl: null
    },
    ready: function() {
      var self = this;
      self.updateCostRateUrl = $(self.$el).data('url');
    },
    methods: {
      /// 手入力による原価率更新
      updateCostRate: function(daihyoSyohinCode, $event) {
        var self = this;
        var target = $event.target;
        var originalValue = $(target).data('originalValue');

        // DB更新
        var data = {
            daihyo_syohin_code: daihyoSyohinCode
          , cost_rate_after: $(target).val()
        };

        $.ajax({
            type: "POST"
          , url: self.updateCostRateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.valid) {
              $(target).data('originalValue', result.cost_rate);
            } else {
              alert('原価率の更新に失敗しました。');
              $(target).val(originalValue);
            }
          })
          .fail(function(stat) {
            alert('原価率の更新でエラーが発生しました。');
            $(target).val(originalValue);
          })
          . always(function() {
          });
      }
    }
  });


  // 原価率更新 モーダル
  var vmUpdateCostRateProcessModal = new Vue({
    el: '#modalUpdateCostRateProcess',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert-info'
      , notices: []
      , noticeClass: 'alert-warning'
      , noticeHidden: true
      , queueUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {

        var message = '商品別原価率の更新処理を行います。\n\n';
        message += 'よろしいですか？';

        self.message = message;
        self.messageClass = 'alert-warning';

        $('.modal-footer button.btn-primary', self.$el).show();
      });
    },

    methods: {
      open: function() {
        self.nowLoading = true;
        $(this.$el).modal('show');
      },

      onSubmit: function() {
        var self = this;

        $('.modal-footer button.btn-primary', self.$el).hide();

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });

          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 確認モーダル
  var message;

  // 再計算処理 確認モーダル
  message = '一覧表の原価率の増減を再計算します。';
  message += "\nよろしいですか？";
  var vmUpdateRatesConfirmModal = $.Plusnao.Vue.createCommonModalViewModel('#modalUpdateRatesConfirm', '原価率一覧 再計算', message, function() {
    $('.modal-footer button.btn-primary', self.$el).hide();
    window.location.href = this.url
                            + '?threshold=' + vmFunctionBlock.threshold
                            + '&shake_border='+ vmFunctionBlock.shakeBorder
                            + '&change_amount_up='+ vmFunctionBlock.changeAmountUp
                            + '&change_amount_down='+ vmFunctionBlock.changeAmountDown
                            + '&change_amount_additional='+ vmFunctionBlock.changeAmountAdditional;
  });

  // 揺さぶり 確認モーダル
  message = '一覧表の原価率に揺さぶり計算を加えます。';
  message += "\nよろしいですか？";
  var vmUnsettleRatesConfirmModal = $.Plusnao.Vue.createCommonModalViewModel('#modalUnsettleRatesConfirm', '原価率一覧 揺さぶり処理', message, function() {
    $('.modal-footer button.btn-primary', self.$el).hide();
    window.location.href = this.url
                            + '?threshold=' + vmFunctionBlock.threshold
                            + '&shake_border='+ vmFunctionBlock.shakeBorder
                            + '&change_amount_up='+ vmFunctionBlock.changeAmountUp
                            + '&change_amount_down='+ vmFunctionBlock.changeAmountDown
                            + '&change_amount_additional='+ vmFunctionBlock.changeAmountAdditional;
  });

  // 原価率リセット 確認モーダル
  message = '一覧表の原価率を初期値にリセットします。（実際の商品データは更新されません。）';
  message += "\nよろしいですか？";
  var vmResetCostRateListConfirmModal = $.Plusnao.Vue.createCommonModalViewModel('#modalResetCostRateListConfirm', '原価率一覧 リセット', message);




});
