/**
 *
 */
$(function() {

  // アカウント編集フォーム モーダル
  var vmModalAccountForm = new Vue({
    el: '#modalAccountForm',
    data: {
      caption: 'ユーザ編集'
      , message: null
      , messageClass: null
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true

      , findUrl: null
      , updateUrl: null
      , nowLoading: true

      , id: null
      , username: null
      , user_cd: null
      , password: null
      , email: null
      , ne_account: null
      , ne_password : null
      , is_active: null
      , is_locked: null
      , buyer_order: null

      , role_message: ''

      , role_customer_manager: null
      , role_delivery_manager : null
      , role_system_manager : null
      , role_system_user : null
      , role_score_browsing : null
      , role_sales_product_account: null
      , role_sales_product_default_display: null
      , role_product_management_browsing: null
      , role_product_management_updating: null

      , callbackSuccess: null
    },
    ready: function() {
      var self = this;

      self.findUrl = $(this.$el).data('findUrl');
      self.updateUrl = $(this.$el).data('updateUrl');
      self.role_message = '';

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        var id = e.relatedTarget.id;
        if (!id) {
          self.message = 'ユーザ情報を取得できませんでした。';
          self.messageClass = 'alert alert-danger';
          $('.modal-footer button.btn-primary', self.$el).hide();
          return;
        }

        if (id === 'new') {

          self.message = '情報を入力し、保存ボタンをクリックして下さい。';
          self.messageClass = 'alert alert-info';
          $('.modal-footer button.btn-primary', self.$el).show();

          self.id = 'new';
          self.is_active = -1;
          self.is_locked = 0;

          self.role_customer_manager = 0;
          self.role_delivery_manager = 0;
          self.role_system_manager = 0;
          self.role_system_user = 0;
          self.role_score_browsing = 0;
          self.role_sales_product_account = 0;
          self.role_sales_product_default_display = 0;
          self.role_product_management_browsing = 0;
          self.role_product_management_updating = 0;

          self.nowLoading = false;

        } else {
          $.ajax({
            type: "GET"
            , url: self.findUrl
            , dataType: "json"
            , data: {
              "id": id
            }
          })
            .done(function(result) {

              if (result.valid) {

                self.id          = result.id;
                self.username    = result.username;
                self.user_cd     = result.user_cd;
                self.password    = null; // パスワードはクリア
                self.email       = result.email;
                self.ne_account  = result.ne_account;
                self.ne_password = result.ne_password;
                self.is_active   = result.is_active;
                self.is_locked   = result.is_locked;
                self.role_customer_manager = result.role_customer_manager;
                self.role_delivery_manager = result.role_delivery_manager;
                self.role_system_manager = result.role_system_manager;
                self.role_system_user = result.role_system_user;
                self.role_score_browsing = result.role_score_browsing;
                self.role_sales_product_account = result.role_sales_product_account;
                self.role_sales_product_default_display = result.role_sales_product_default_display;
                self.role_product_management_browsing = result.role_product_management_browsing;
                self.role_product_management_updating = result.role_product_management_updating;
                self.buyer_order = result.buyer_order;
                self.message = '情報を編集し、保存ボタンをクリックして下さい。';
                self.messageClass = 'alert alert-info';
                $('.modal-footer button.btn-primary', self.$el).show();

              } else {
                self.message = 'ユーザ情報を取得できませんでした。';
                self.messageClass = 'alert alert-danger';
                $('.modal-footer button.btn-primary', self.$el).hide();
              }
            })
            .fail(function(stat) {
              console.log(stat);
              self.message = 'ユーザ情報を取得できませんでした。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();
            }).always(function() {
            self.nowLoading = false;
          });
        }

      });
    },

    methods: {
      open: function(id, callbackSuccess) {
        this.callbackSuccess = callbackSuccess;

        self.nowLoading = true;
        $(this.$el).modal('show', { id: id });
      },

      onSubmit: function() {
        var self = this;

        if (! self.validatePassword()) {
          return;
        }

        self.nowLoading = true;
        self.noticeHidden = true;
        var data = {
          id          : self.id
          , username    : self.username
          , user_cd     : self.user_cd
          , password    : self.password
          , email       : self.email
          , ne_account  : self.ne_account
          , ne_password : self.ne_password
          , is_active   : self.is_active
          , is_locked   : self.is_locked
          , role_customer_manager : self.role_customer_manager
          , role_delivery_manager : self.role_delivery_manager
          , role_system_manager   : self.role_system_manager
          , role_system_user      : self.role_system_user
          , role_score_browsing   : self.role_score_browsing
          , role_sales_product_account : self.role_sales_product_account
          , role_sales_product_default_display : self.role_sales_product_default_display
          , role_product_management_browsing : self.role_product_management_browsing
          , role_product_management_updating : self.role_product_management_updating
          , buyer_order : self.buyer_order
        };

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        }).done(function(result) {

          if (result.errors.length > 0) {
            self.message = 'ユーザ情報を更新できませんでした。';
            self.messageClass = 'alert alert-danger';
            self.notices = result.errors;
            self.noticeHidden = false;
            return;
          }

          // 新規の場合は新たに新規行を追加
          if (result.is_new) {
            self.message = 'ユーザ情報を登録しました。';
            self.messageClass = 'alert alert-success';
          } else {
            self.message = 'ユーザ情報を更新しました。';
            self.messageClass = 'alert alert-success';
          }

          var data = result.data;
          // 更新データ
          self.id          = data.id;
          self.username    = data.username;
          self.user_cd     = data.user_cd;
          self.email       = data.email;
          self.is_active   = data.is_active;
          self.is_locked   = data.is_locked;
          self.role_customer_manager = data.role_customer_manager;
          self.role_delivery_manager = data.role_delivery_manager;
          self.role_system_manager = data.role_system_manager;
          self.role_system_user = data.role_system_user;
          self.role_score_browsing = data.role_score_browsing;
          self.role_sales_product_account = data.role_sales_product_account;
          self.role_sales_product_default_display = data.role_sales_product_default_display;
          self.role_product_management_browsing = data.role_product_management_browsing;
          self.role_product_management_updating = data.role_product_management_updating;
          self.buyer_order = data.buyer_order;
          self.ne_account  = data.ne_account;
          self.ne_password = data.ne_password;
          self.password    = null; // パスワードはクリア

          // 更新できたら、ページをリロードする（一覧表示更新のための簡略実装）
          $(self.$el).on('hidden.bs.modal', function(e) {
            if (self.callbackSuccess) {
              self.callbackSuccess();
            }
          });

        }).fail(function() {
          self.message = 'ユーザ情報を更新できませんでした。';
          self.messageClass = 'alert alert-danger';

        }).always(function() {
          self.nowLoading = false;

        });

      },

      /**
       * パスワードのバリデーションチェック
       * 半角英数を含む8文字以上
       * 入力される文字や文字数に制限はない
       */
      validatePassword() {
        // パスワードの更新でなければチェックしない
        if (this.id !== 'new') {
          if (this.password === '' || this.password === null) {
            this.message = '情報を編集し、保存ボタンをクリックして下さい。';
            this.messageClass = 'alert alert-info';
            return true;
          }
        }

        const containsNumberRegex = /\d/;
        if (! containsNumberRegex.test(this.password)) {
          this.message = 'パスワードに半角数字が含まれていません。';
          this.messageClass = 'alert alert-warning';
          return false;
        }

        const containsHalfWidthRegex = /[a-zA-Z]/;
        if (! containsHalfWidthRegex.test(this.password)) {
          this.message = 'パスワードに半角英字が含まれていません。';
          this.messageClass = 'alert alert-warning';
          return false;
        }

        const passwordMinLength = 8;
        if (this.password.length < passwordMinLength) {
          this.message = '文字数が8文字未満です。';
          this.messageClass = 'alert alert-warning';
          return false;
        }

        this.message = '情報を編集し、保存ボタンをクリックして下さい。';
        this.messageClass = 'alert alert-info';
        return true;
      },

      resetDialog: function() {
        this.nowLoading = true;

        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.id         = null;
        this.username   = null;
        this.user_cd    = null;
        this.password   = null;
        this.email      = null;
        this.ne_account = null;
        this.ne_password = null;
        this.is_active  = null;
        this.is_locked  = null;
        this.role_customer_manager = null;
        this.role_delivery_manager = null;
        this.role_system_manager = null;
        this.role_system_user = null;
        this.role_score_browsing = null;
        this.role_sales_product_account = null;
        this.role_sales_product_default_display = null;
        this.role_product_management_browsing = null;
        this.role_product_management_updating = null;
        this.buyer_order = null;
      },
      roleMouseover: function (e) {
        let self = this;

        switch (e) {
          case 'cm':
            self.role_message = 'ネクストエンジンのCSV出力と在庫出力が可能になります。';
            break;
          case 'dm':
            self.role_message = '各種CSV出力と仕入・注残一覧で良品・欠品の入力確定が可能になります。';
            break;
          case 'sm':
            self.role_message = '各種CSV出力とキューのロック解除・一時停止・停止キャンセルが可能になります。';
            break;
          case 'su':
            self.role_message = 'キューのロック解除・一時停止・停止キャンセルが可能になります。';
            break;
          case 'sb':
            self.role_message = '箱詰めスコアの閲覧が可能になります。';
            break;
          case 'spa':
            self.role_message = '商品の売上に寄与することが可能になります。';
            break;
          case 'spd':
            self.role_message = '担当者別売上一覧で実績が標準で表示されます。';
            break;
          case 'pmb':
            self.role_message = '商品情報の閲覧が可能になります。';
            break;
          case 'pmu':
            self.role_message = '商品情報の編集が可能になります。';
            break;
          default:
            self.role_message = '';
            break;
        }
      }
    }
  });

  // グローバルレポジトリへ格納
  $.Plusnao.Repository.vmModalAccountForm = vmModalAccountForm;
});
