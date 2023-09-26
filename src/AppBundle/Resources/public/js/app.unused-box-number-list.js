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
      }    // ----------------------------------
      // イベントハンドラ
      // ----------------------------------
      , openNewProductModal: function() {
        this.messageState.clear();
        this.modalNewProductState.show = true;
      }
    }
  });

  //// フォーム
  var vmForm = new Vue({
    el: "#searchForm",
    data: {
       modalNewProductState: {
        show: false
        , eventOnChoiceProduct: 'submit-product'
      }
    },
    ready: function() {
    },
    methods: {
      submitForm: function() {
        $("div").remove(".alert-danger");
        $(this.$el).submit();
      }
    }
  });

// 倉庫切り替え処理
  var vmChangeCurrentWarehouseModal = new Vue({
    el: '#modalChangeWarehouse',
    data: {

      duplicateBoxcodeListUrl: null

      , duplicateBoxcodes: [],
      nowLoading : true
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

        self.duplicateBoxcodeListUrl = $(self.$el).data('duplicateBoxcodeListUrl');

        // モーダル open イベント登録
        // -- open前
        var modal = $(self.$el);
        modal.on('show.bs.modal', function(e) {
          // $.Vendor.WaitingDialog.show('loading ...');
          self.nowLoading = true;
          $.ajax({
            type: "GET"
            , url: self.duplicateBoxcodeListUrl
            , dataType: "json"
            , data: {}
          })
              .done(function(result) {

                if (result.status == 'ok') {
                  self.nowLoading = false;

                  self.duplicateBoxcodes = [];
                  self.duplicateBoxcodes = result.list;
                  
                } else {
                  var message = result.message.length > 0 ? result.message : '箱番号重複一覧が取得できませんでした。';
                  alert(message);
                  modal.modal().hide();
                }
              })
              .fail(function(stat) {
                console.log(stat);
                var message = 'エラー：箱番号重複一覧が取得できませんでした。';
                modal.modal().hide();
              })
              . always(function() {
                // $.Vendor.WaitingDialog.hide();
              });

        });
      }
    }
  });




});
