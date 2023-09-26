/**
 * 商品管理画面用 JS
 */
$(function() {

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#header',
    data: {
        message: ''
      , messageCssClass: ''
    },
    ready: function() {
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
    el: "#searchForm",
    data: {
    },
    ready: function() {
      $('#dateStart').datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
      });
      $('#dateEnd').datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
      });
    },
    methods: {
      submitForm: function() {
        $(this.$el).submit();
      }
    }
  });
  //
  //// テーブル
  //var vmTable = new Vue({
  //    el: "#notWhiteListTable"
  //  , data: {
  //
  //  }
  //  , computed: {
  //    imageUrl: function() {
  //      return "";
  //    }
  //  }
  //  , methods: {
  //
  //    /// 画像ポップアップモーダル表示
  //    openImageModal: function(result) {
  //      if (vmPopupImage) {
  //        vmPopupImage.open(this.imageUrl);
  //      } else {
  //        throw new Error('エラーが発生しました。');
  //      }
  //    }
  //  }
  //});
  //
  //
  //// 画像ポップアップ
  //var vmPopupImage = new Vue({
  //  el: '#modalPopupPicture',
  //  data: {
  //    caption: ''
  //    , nowLoading: null
  //    , imageUrl: null
  //  },
  //  ready: function() {
  //    var self = this;
  //    self.url = $(self.$el).data('url');
  //
  //    // イベント登録
  //    $(self.$el).on('show.bs.modal', function(e) {
  //      self.nowLoading = false;
  //    });
  //  },
  //
  //  methods: {
  //    open: function(imageUrl) {
  //      this.nowLoading = true;
  //      this.imageUrl = imageUrl;
  //
  //      $(this.$el).modal('show');
  //    },
  //
  //    resetDialog: function() {
  //      $('.modal-footer button.btn-primary', self.$el).hide();
  //    }
  //  }
  //});

});
