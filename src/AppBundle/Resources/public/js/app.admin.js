/**
 * 管理画面用 JS
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

  // ユーザ一覧テーブル
  var vmAccountTable = new Vue({
    el: '#accountListTable',
    data: {
      selfUrl: null
      , deleteAccountUrl: null
      , sortKey: ''
      , sortOrder: 'ASC'
      , displayStatus: 'active'
    },
    ready: function() {
      const currentUrl = new URL(location.href);
      if (currentUrl.searchParams.has('sortKey')) {
        this.sortKey = currentUrl.searchParams.get('sortKey');
      }
      if (currentUrl.searchParams.has('sortOrder')) {
        this.sortOrder = currentUrl.searchParams.get('sortOrder');
      }
      if (currentUrl.searchParams.has('displayStatus')) {
        this.displayStatus = currentUrl.searchParams.get('displayStatus');
      }

      this.deleteAccountUrl = $(this.$el).data('deleteAccountUrl');
    },
    methods: {
      openForm: function(id) {
        if ($.Plusnao.Repository.vmModalAccountForm) {
          $.Plusnao.Repository.vmModalAccountForm.open(id, function(){
            window.location.reload();
          });
        } else {
          throw new Error('編集フォームの読み込みができませんでした。');
        }
      },

      // ステータス表示切替(クライアントサイドで行なうのではなく、取得元から絞り込む)
      changeDisplayOnStatus: function(){
        const self = this;
        const currentUrl = new URL(location.href);
        if (currentUrl.searchParams.has('sortKey')) {
           self.sortKey = currentUrl.searchParams.get('sortKey');
        }
        if (currentUrl.searchParams.has('sortOrder')) {
           self.sortOrder = currentUrl.searchParams.get('sortOrder');
        }
        var displayStatus;
        if (currentUrl.searchParams.has('displayStatus')) {
          displayStatus = currentUrl.searchParams.get('displayStatus');
        }
        // 状態反転
        self.displayStatus = self.displayStatus === 'all' ? 'active' : 'all';;

        currentUrl.searchParams.set('sortKey', self.sortKey);
        currentUrl.searchParams.set('sortOrder', self.sortOrder);
        currentUrl.searchParams.set('displayStatus', self.displayStatus);
        location.href = currentUrl;
      },

      /**
       * 表をソートする。
       */
      sortTable: function(target) {
        const self = this;
        const currentUrl = new URL(location.href);
        if (currentUrl.searchParams.has('sortKey')) {
           self.sortKey = currentUrl.searchParams.get('sortKey');
        }
        if (currentUrl.searchParams.has('sortOrder')) {
           self.sortOrder = currentUrl.searchParams.get('sortOrder');
        }
        if (currentUrl.searchParams.has('displayStatus')) {
          self.displayStatus = currentUrl.searchParams.get('displayStatus');
        } 
        if (self.sortKey === target) {
          self.sortOrder = (self.sortOrder === 'ASC') ? 'DESC' : 'ASC';
        } else {
          self.sortKey = target;
          self.sortOrder = 'ASC'
        }
        
        currentUrl.searchParams.set('sortKey', self.sortKey);
        currentUrl.searchParams.set('sortOrder', self.sortOrder);
        currentUrl.searchParams.set('displayStatus', self.displayStatus);
        location.href = currentUrl;
      },
      
      /**
       * ソートアイコンCSSクラス
       */
      getSortMarkCssClass: function(key) {
        const self = this;
        if (self.sortKey != key) {
          return '';
        }
        return self.sortOrder == 'ASC' ? 'sortAsc' : 'sortDesc';
      },

      deleteAccount: function(id) {
        var self = this;

        if (!confirm('指定したアカウントを削除してよろしいですか？')) {
          return;
        }

        $.ajax({
            type: "GET"
          , url: self.deleteAccountUrl
          , dataType: "json"
          , data: {
            "id": id
          }
        })
          .done(function(result) {
            if (result.errors.length == 0) {

              alert('アカウントを削除しました。[' + result.data.username + ']');
              window.location.reload();

            } else {
              var message = '失敗しました。' + result.errors[0];
              alert(message);
            }
          })
          .fail(function(stat) {
            console.log(stat);
            vmGlobalMessage.setMessage('処理に失敗しました。', 'alert alert-danger');
            $('.modal-footer button.btn-primary', self.$el).hide();
          }).always(function() {
            // self.nowLoading = false;
          });

      }
    }
  });



});
