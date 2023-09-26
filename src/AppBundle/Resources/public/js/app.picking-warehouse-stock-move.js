/**
 * 倉庫在庫ピッキングリスト画面 JS
 */



// noinspection JSUnusedGlobalSymbols
/**
 * 一覧ブロック
 */
const vmWarehouseStockMovePickingList = new Vue({
    el: "#warehouseStockMovePickingList"
  , data: {
      pickingList: []

    , dataUrl: null
    , refreshUrl: null

    , messageState: {}
  }
  , mounted: function() {
    const self = this;

    this.$nextTick(function() {
      self.dataUrl = $(this.$el).data('dataUrl');
      self.refreshUrl = $(this.$el).data('refreshUrl');

        // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      // Show loading
      $.Vendor.WaitingDialog.show('読み込み中 ...');

      // 初期データ読込
      self.loadData().then(function(){
        $.Vendor.WaitingDialog.hide();
      });
    });
  }
  , computed: {
  }

  , methods: {
    loadData: function() {

      const self = this;
      const deferred = new $.Deferred();

      $.ajax({
          type: "GET"
        , url: self.dataUrl
        , dataType: "json"
        , data: {}
      })
        .done(function(result) {
          if (result.status === 'ok') {

            self.pickingList = [];

            let i, row;
            for (i = 0; i < result.list.length; i++) {

              /**
               * @typedef {Object} Item
               * @property {string} id
               * @property {string} ne_syohin_syohin_code
               * @property {string} current_location
               * @property {string} move_num
               * @property {string} picked_num
               * @property {string} shortage
               * @property {string} status
               * @property {string} new_location_code
               * @property {string} image_url
               * @property {string} link_url
               * @property {string} type
               * @property {string} created
               * @property {string} updated
               * @property {string} label_type
               */
              /** @type {Item} */
              let item = result.list[i];
              row = {
                  itemIndex: i // IDとして利用
                , id: item.id
                , neSyohinSyohinCode : item.ne_syohin_syohin_code
                , currentLocation : item.current_location
                , moveNum : Number(item.move_num) || 0
                , pickedNum : Number(item.picked_num) || 0
                , shortage : Number(item.shortage) || 0
                , status : Number(item.status) || 0
                , newLocationCode: item.new_location_code ? item.new_location_code : ""
                , imageUrl : item.image_url
                , linkUrl : item.link_url
                , type : item.type
                , created : (item.created ? new Date(item.created.replace(/-/g, "/")) : null) // replace for firefox, IE
                , updated : (item.updated ? new Date(item.updated.replace(/-/g, "/")) : null) // replace for firefox, IE

                , labelType: item.label_type

                , errorMessage: ""
              };

              self.pickingList.push(row);
            }

            deferred.resolve();

          } else {
            self.messageState.setMessage(result.message, 'alert-danger');
            deferred.reject();
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラー：データの取得に失敗しました。', 'alert-danger');
          deferred.reject();
        })
        .always(function() {
        });

      return deferred.promise();
    }

    /// ロケーション更新
    , refreshLocation: function(doReload) {
      const self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('ロケーション情報取得中 ...');

      $.ajax({
          type: "POST"
        , url: self.refreshUrl
        , dataType: "json"
        , data: {}
      })
        .done(function(result) {
          if (result.status === 'ok') {

            self.messageState.setMessage(result.message, 'alert-success');
            if (doReload) {
              window.location.reload();

            } else {
              self.loadData().then(function(){
                $.Vendor.WaitingDialog.hide();
              });
            }

          } else {
            self.messageState.setMessage(result.message, 'alert-danger');
            $.Vendor.WaitingDialog.hide();
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラー：更新に失敗しました。', 'alert-danger');
          $.Vendor.WaitingDialog.hide();
        })
        .always(function() {
        });
    }
  }

});
