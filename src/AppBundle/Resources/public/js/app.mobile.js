/**
 * 商品ロケーション管理 共通JS
 * Vue 1.x OR Vue 2.x (ready or mounted)
 */
var vmGlobalMessage = null;
$(function() {
  Vue.config.debug = true;

  // 全体メッセージ(global)
  vmGlobalMessage = new Vue({
    el: '#globalMessage',
    data: {
        message: ''
      , messageCssClass: 'alert-warning'
      , loadingImageUrl: null
    },
    ready: function() {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    },
    mounted: function() {
      // console.log('ver 2.x : mounted called');
      this.$nextTick(function() {
        this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
      });
    },

    methods: {
      setMessage: function(message, cssClass, autoHide) {
        cssClass = cssClass || 'alert-info';
        autoHide = autoHide || false;

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
      clearFlashMessage: function() {
        $(this.$el).find('#flashMessage').hide();
      },
      closeWindow: function() {
        window.close();
      }
    }
  });

  // フッター
  var vmHooter = new Vue({
    el: '#footer',
    data: {},
    methods: {
      scrollTop: function() {
        $(SCROLL_ELEMENT).animate({
          scrollTop: 0
        }, 200);
      }
      , scrollBottom: function() {
        $(SCROLL_ELEMENT).animate({
          scrollTop: $(document).height()
        }, 200);
      }
    }

  });

  // ヘッダ
  var vmHeader = new Vue({
    el: '#mobileHeader',
    data: {
      logoutUrl: null
    },
    ready: function() {
      // console.log('ver 1.x : ready called');
      this.init(this);
    },
    mounted: function() {
      // console.log('ver 2.x : mounted called');
      this.$nextTick(function() {
        this.init(this);
      });
    },

    methods: {

      // 初期処理： ready or mounted から実行。(ver 1, 2 両用のため)
      init: function(self) {
        self.logoutUrl = $(self.$el).data('logoutUrl');
      }

      , logout: function() {
        if (! confirm('ログアウトします。よろしいですか？')) {
          return;
        }

        window.location.href = this.logoutUrl;
      }
    }
  });

  /**
   * ピッキング残件数
   */
  var vmPickingListRemainNumber = new Vue({
    el: '#pickingListRemainNumber',
    data: {
      remainNumber : null
      , remainNumberStyle : null
    },
    // Vue 1.x 対応
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');
      self.getPickingListRemainNumberInterval(this);
    },
    // Vue 2.x 対応
    mounted: function() {
      var self = this;
      self.url = $(self.$el).data('url');
      self.getPickingListRemainNumberInterval(this);
    },
    methods: {
      /**
       * ピッキング残件数取得処理(初期表示と再取得設定)
       */
      getPickingListRemainNumberInterval: function(self) {
        // 初期表示
        getPickingListRemainNumber(self);
        // 30秒に一度再取得
        setInterval(() => {
          getPickingListRemainNumber(self);
        }, 30000)
      }
    }
  });
  /**
   * ピッキング残件数取得処理(Ajax)
   */
  function getPickingListRemainNumber(self) {
    var circleStyle = "border-radius: 50%; text-align: center;";
    $.ajax({
      type: "GET"
      , url: self.url
      , dataType: "json"
      , data: null
    })
    .done(function(result) {
      if (result.status == 'ng') {
        self.remainNumber = '?';
        self.remainNumberStyle =  circleStyle + ' background-color:#FFDEAD;';
      } else if (result.remain_number != 0) {
        self.remainNumber = result.remain_number;
        self.remainNumberStyle =  (result.current_warehouse_remain_number == 0  ? circleStyle + ' background-color:#6699FF;' : circleStyle + ' background-color:#f00;');
      }
    })
    .fail(function(stat) {
      self.remainNumber = '?';
      self.remainNumberStyle =  circleStyle + ' background-color:#FFDEAD;';
      console.log(stat);
      console.log('ピッキング残件数が取得できませんでした。');
    });
  }
});

