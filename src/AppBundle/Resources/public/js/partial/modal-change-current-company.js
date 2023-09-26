$(function() {

// 会社切り替え処理
  var vmChangeCurrentCompanyModal = new Vue({
    el: '#modalChangeCurrentCompany',
    data: {
        companyListUrl: null
      , changeCurrentCompanyUrl: null

      , companys: []
      , companyChangeTo: null
      , currentCompanyId: null
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

        self.companyListUrl = $(self.$el).data('companyListUrl');
        self.changeCurrentCompanyUrl = $(self.$el).data('changeCurrentCompanyUrl');
        self.currentCompanyId = $(self.$el).data('currentCompanyId');

        // モーダル open イベント登録
        // -- open前
        var modal = $(self.$el);
        modal.on('show.bs.modal', function(e) {
          // 会社一覧取得
          $.ajax({
              type: "GET"
            , url: self.companyListUrl
            , dataType: "json"
            , data: {}
          })
            .done(function(result) {

              if (result.status == 'ok') {

                self.companys = [];
                for (var i = 0; i < result.companys.length; i++) {
                  var company = result.companys[i];
                  if (company.id == self.currentCompanyId) { // 現在の選択会社を除外
                    continue;
                  }

                  self.companys.push(company);
                  if (! self.companyChangeTo) { // プルダウン初期選択値（先頭）
                    self.companyChangeTo = company.id;
                  }
                }

                console.log(self.companys);

              } else {
                var message = result.message.length > 0 ? result.message : '会社一覧が取得できませんでした。';
                alert(message);
                modal.modal().hide();
              }
            })
            .fail(function(stat) {
              console.log(stat);
              var message = 'エラー：会社一覧が取得できませんでした。';
              modal.modal().hide();
            })
            . always(function() {
            });

        });
      }

      , changeCompanySubmit: function() {
        var self = this;

        if (!self.companyChangeTo) {
          alert('切り替え先の会社が選択されていません。');
          return;
        }

        // 会社一覧取得
        $.ajax({
          type: "POST"
          , url: self.changeCurrentCompanyUrl
          , dataType: "json"
          , data: {
            change_to: self.companyChangeTo
          }
        })
          .done(function(result) {

            var message;
            if (result.status == 'ok') {

              message = result.message ? result.message : '会社を切り替えました。';
              alert(message);
              window.location.reload();

            } else {
              message = result.message.length > 0 ? result.message : '会社の切り替えができませんでした。';
              alert(message);
            }
          })
          .fail(function(stat) {
            console.log(stat);
            var message = 'エラー：会社一覧が取得できませんでした。';
            alert(message);
          })
          . always(function() {
          });

      }
    }
  });


});
