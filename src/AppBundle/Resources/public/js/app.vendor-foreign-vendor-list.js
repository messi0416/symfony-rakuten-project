/**
 * 仕入先管理画面用 JS
 */

// 全体メッセージ
var vmGlobalMessage = new Vue({
    el: '#globalMessage'
  , delimiters: ['(%', '%)']
  , data: {
      message: ''
    , messageCssClass: ''
    , loadingImageUrl: null
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    });
  }
  , methods: {
    setMessage: function(message, cssClass, autoHide) {
      cssClass = cssClass || 'alert alert-info';
      autoHide = autoHide || true;

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
      }
    }
    , setCssClass: function(cssClass) {
      this.messageCssClass = cssClass;
    }
    , clear: function() {
      this.message = '';
      this.messageCssClass = '';
    }
    , closeWindow: function() {
      window.close();
    }
  }
});

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentForeignVendorItem = {
    template: '#templateVendorForeignVendorListTableRow'
  , props: [
      'item'
  ]
  , data: function() {

    console.log('component data');
    console.log(this.item);

    return {
        id                    : this.item.id
      , code                  : this.item.code
      , url                   : this.item.url
      , name                  : this.item.name
      , sireCode              : this.item.sireCode
      , registrationAvailable : this.item.registrationAvailable ? true : false
      , targetFlag            : this.item.targetFlag ? true : false
      , created               : this.item.created
      , updated               : this.item.updated
    };
  }
  , computed: {
    displayRegistrationAvailable: function() {
      return this.item.registrationAvailable ? '○' : '-';
    }

    , displayCreated: function() {
      return this.created ? $.Plusnao.Date.getDateString(this.created) : '';
    }
  }
  , methods: {
    // TODO 実装
    edit: function() {
      console.log("編集");

      console.log(this.id);
      console.log(this.code);

      this.$emit('edit-item', this.item);
    }

    , remove: function() {
      console.log("削除");
      this.$emit('remove-item', this.item);
    }
  }
};

// 登録フォーム
var vmComponentModalForeignVendor = {
  template: '#templateModalVendorForeignForm'
  , props: [
      'item'
    , 'show'
  ]
  , data: function() {
    return {
        id                    : this.item.id
      , code                  : this.item.code
      , url                   : this.item.url
      , name                  : this.item.name
      , sireCode              : this.item.sireCode
      , registrationAvailable : this.item.registrationAvailable ? true : false
      , targetFlag            : this.item.targetFlag ? true : false
      , created               : this.item.created
      , updated               : this.item.updated

      , caption: '編集フォーム'
      , messageClass: 'alert-success'
      , message: null

      , noticeHidden: true
      , noticeClass: 'alert-info'
      , notices: []
    };
  }
  , watch : {
    show: function() {
      var modal = $(this.$el);
      if (this.show && modal.is(':hidden')) {
        modal.modal('show');
      } else if (!this.show && !modal.is(':hidden')) {
        modal.modal('hide');
      }
    }
    , item: function() {
      console.log('item changed');
      console.log(this.item);
    }
  }

  , mounted: function() {
    this.$nextTick(function (){
      var self = this;
      // イベント登録
      $(self.$el).on('shown.bs.modal', function(e) {
      });
      $(self.$el).on('hidden.bs.modal', function(e) {
      })
    });
  }
  , methods: {
    hideForm: function() {
      this.$emit('hide-form');
    }
    , save: function() {
      this.$emit('update-item', this.item);
    }
  }
};


// 一覧画面 一覧表
var vmVendorForeignVendorListTable = new Vue({
    el: '#vendorForeignVendorListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    , pageItemNum: 2 // 20
    , pageItemNumList: [ 1, 2, 3, 5, 10 ]
    , page: 1

    , url: null
    , currentItem: {}
    , editFormShown: false
  }
  , components: {
      'result-item': vmComponentForeignVendorItem // 一覧テーブル
    , 'modal-form': vmComponentModalForeignVendor // 登録フォーム
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');

      var pageInfo = {
          page: this.page
        , pageItemNum: this.pageItemNum
      };
      this.showPage(pageInfo);
    });
  }

  , computed: {
  }
  , methods: {
    showPage: function(pageInfo) {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('loading ...');

      var page = pageInfo.page;

      console.log(page);

      // データ読み込み処理
      var data = {
          page: page
        , limit: pageInfo.pageItemNum
      };

      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          console.log(result);

          if (result.status == 'ok') {

            self.list = [];
            var i;

            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  'id'                    : item.id
                , 'code'                  : item.code
                , 'url'                   : item.url
                , 'name'                  : item.name
                , 'sireCode'              : item.sire_code
                , 'registrationAvailable' : item.registration_available
                , 'target_flag'           : item.target_flag
                , 'created'               : (item.created ? new Date(item.created.replace(/-/g, "/")) : null) // replace for firefox, IE
                , 'updated'               : (item.updated ? new Date(item.updated.replace(/-/g, "/")) : null) // replace for firefox, IE
              };

              self.list.push(row);

            }

            self.totalItemNum = Number(result.count);
            self.page = page;

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------
    /// 新規登録フォームを開く
    , addItem: function() {
      this.currentItem = {};
      this.editFormShown = true;
      console.log('add handler');
    }

    /// 編集フォームを開く
    , editItem: function(item) {
      this.currentItem = item;
      this.editFormShown = true;
      console.log('edit handler');
    }

    /// 編集フォームを閉じる
    , hideForm: function() {
      this.editFormShown = false;
      // console.log('hide form handler handler');
    }

    // TODO 実装
    /// 編集結果保存
    , updateItem: function(item) {
      console.log('update handler');
    }

    // TODO 実装
    /// 削除
    , removeItem: function(item) {
      console.log('remove handler');
    }

  }

});

