/**
 * 商品管理画面用 JS
 */
$(function() {

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

  // フォーム
  var vmForm = new Vue({
    el: "#deleteExcludedProductForm",
    data: {
      backUrl: null
    },
    ready: function() {
      this.backUrl = $(this.$el).data('backUrl');
      console.log(this.backUrl);
    },
    methods: {
      backToList: function() {
        location.href = this.backUrl;
      }
    }
  });

  // 一覧画面 一覧表
  var vmDeleteExcludedProductsList = new Vue({
    el: '#deleteExcludedProductsList',
    data: {},
    methods: {
      openDeleteConfirmModal: function (url, syohinCode) {
        vmDeleteConfirmModal.open(url, syohinCode);
      }
    }
  });

  // 一覧画面 削除確認モーダル
  var vmDeleteConfirmModal = new Vue({
    el: '#modalDeleteExcludedProductsDeleteConfirm',
    data: {
        caption: '削除確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , actionUrl: null
    },
    ready: function() {
      var self = this;

      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        var url = e.relatedTarget.url;
        var syohinCode = e.relatedTarget.syohinCode;
        if (!url) {
          self.message = '削除対象が特定できませんでした。';
          self.messageClass = 'alert alert-danger';
          return;
        }

        self.actionUrl = url;

        self.message = '[' + syohinCode + ']' + 'を削除してよろしいですか？';
        self.messageClass = 'alert alert-warning';
      });
    },

    methods: {
      onSubmit: function() {
        if (!this.actionUrl) {
          self.message = '削除対象が特定できませんでした。';
          self.messageClass = 'alert alert-danger';

        }

        location.href = this.actionUrl;
      },

      open: function(url, syohinCode) {
        $(this.$el).modal('show', { 'url' : url, 'syohinCode': syohinCode });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeClass = 'alert alert-warning';
        this.actionUrl = null;
      }
    }
  });

});
