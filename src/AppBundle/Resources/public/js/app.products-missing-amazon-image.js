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
    },
    methods: {
      submitForm: function() {
        $(this.$el).submit();
      }
    }
  });

  // 一覧画面 一覧表
  var vmList = new Vue({
    el: '#missingAmazonImageListTable',
    data: {},
    methods: {
    }
  });
});
