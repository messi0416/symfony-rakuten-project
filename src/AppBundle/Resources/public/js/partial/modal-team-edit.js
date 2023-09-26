/**
 * 管理画面　設定 チーム編集(モーダル)
 */
var teamEdit = new Vue({
  el: '#teamEditModal',
  data: {
    team : {},
    messageClass: null,
    message: null,
    saveUrl: null,
    callbackSuccess: null,
    errorMessage: null,
  },
  mounted: function () {
    const self = this;
    self.saveUrl = $(this.$el).data('saveUrl');
  },
  methods: {
    // 初期設定
    setInit: function() {
      this.messageClass = null;
      this.message = null;
      this.errorMessage = null;
    },
    // モーダルオープン時処理
    open: function(team, callbackSuccess) {
      this.setInit();
      this.team = team;
      this.callbackSuccess = callbackSuccess;
      $(this.$el).modal('show');
    },
    // チームの保存ボタン押下時処理
    save: function() {
      this.setInit();
      const self = this;
      $.ajax({
        type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: self.team
      }).done(function(result) {
        if (result.error) {
          self.message = 'ユーザ情報を更新できませんでした。';
          self.messageClass = 'alert alert-danger';
          self.errorMessage = result.error;
          return;
        }

        if (result.is_new) {
          // 新規登録の場合
          self.message = 'ユーザ情報を登録しました。';
          self.messageClass = 'alert alert-success';
        } else {
          // 更新の場合
          self.message = 'ユーザ情報を更新しました。';
          self.messageClass = 'alert alert-success';
        }

        // 更新できたら、ページをリロードする（一覧表示更新のための簡略実装）
        $(self.$el).on('hidden.bs.modal', function(e) {
          if (self.callbackSuccess) {
            self.callbackSuccess();
          }
        });
      }).fail(function () {
        self.message = 'ユーザ情報を更新できませんでした。';
        self.messageClass = 'alert alert-danger';
      });
    },
  },
});

$.Plusnao.Repository.teamEdit = teamEdit;