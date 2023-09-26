/**
 * ログイン面用 JS
 */
$(function() {
  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#messageArea',
    data: {
        message: ''
      , messageCssClass: ''
      , loadingImageUrl: null
      , messageDisp :false
    },
    ready: function() {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    },
    methods: {
      setMessage: function(message, cssClass, autoHide) {
        cssClass = cssClass || 'alert alert-info';
        if (autoHide === undefined) {
          autoHide = true;
        }

        this.message = message;
        this.setCssClass(cssClass);
        this.messageDisp = true;

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
  var vmLogin = new Vue({
    el: '#loginForm',
    data: {
      userCd: ''
      , username: ''
      , url: null
    },
    ready: function(){
      self = this;
      if (USER_LIST_DATA) {
        for (let i = 0; i < USER_LIST_DATA.length; i++) {
          let item = USER_LIST_DATA[i];
          if (item.username == LAST_USER_NAME) {
            self.userCd = item.user_cd;
            return;
          }
        }
      }
    },
    methods: {
      getUsername: function () {
        var self = this;
        self.url = $(self.$el).data('url');
        $.ajax({
          type: "GET"
          , url: self.url
          , dataType: "json"
          , data: { "userCd": self.userCd }
        })
        .done(function(result) {
          if (result) {
            self.username = result.userName;
          } else {
            self.username = '';
          }
        })
        .fail(function(stat) {
          var message = '処理を実行できませんでした。';
          vmGlobalMessage.setMessage(message, 'alert alert-danger',true);
        })
      }
    }
  });
});
