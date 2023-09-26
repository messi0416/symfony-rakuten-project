$(function() {

// 倉庫切り替え処理
  var vmChangeCurrentWarehouseModal = new Vue({
    el: '#modalChangeCurrentWarehouse',
    data: {
        warehouseListUrl: null
      , changeCurrentWarehouseUrl: null

      , warehouses: []
      , warehouseChangeTo: null
      , currentWarehouseId: null
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

        self.warehouseListUrl = $(self.$el).data('warehouseListUrl');
        self.changeCurrentWarehouseUrl = $(self.$el).data('changeCurrentWarehouseUrl');
        self.currentWarehouseId = $(self.$el).data('currentWarehouseId');

        // モーダル open イベント登録
        // -- open前
        var modal = $(self.$el);
        modal.on('show.bs.modal', function(e) {
          // 倉庫一覧取得
          $.ajax({
              type: "GET"
            , url: self.warehouseListUrl
            , dataType: "json"
            , data: {}
          })
            .done(function(result) {

              if (result.status == 'ok') {

                self.warehouses = [];
                for (var i = 0; i < result.warehouses.length; i++) {
                  var warehouse = result.warehouses[i];
                  if (warehouse.id == self.currentWarehouseId) { // 現在の選択倉庫を除外
                    continue;
                  }

                  self.warehouses.push(warehouse);
                  if (! self.warehouseChangeTo) { // プルダウン初期選択値（先頭）
                    self.warehouseChangeTo = warehouse.id;
                  }
                }

                console.log(self.warehouses);

              } else {
                var message = result.message.length > 0 ? result.message : '倉庫一覧が取得できませんでした。';
                alert(message);
                modal.modal().hide();
              }
            })
            .fail(function(stat) {
              console.log(stat);
              var message = 'エラー：倉庫一覧が取得できませんでした。';
              modal.modal().hide();
            })
            . always(function() {
            });

        });
      }

      , changeWarehouseSubmit: function() {
        var self = this;

        if (!self.warehouseChangeTo) {
          alert('切り替え先の倉庫が選択されていません。');
          return;
        }

        // 倉庫一覧取得
        $.ajax({
          type: "POST"
          , url: self.changeCurrentWarehouseUrl
          , dataType: "json"
          , data: {
            change_to: self.warehouseChangeTo
          }
        })
          .done(function(result) {

            var message;
            if (result.status == 'ok') {

              message = result.message ? result.message : '倉庫を切り替えました。';
              alert(message);
              window.location.reload();

            } else {
              message = result.message.length > 0 ? result.message : '倉庫の切り替えができませんでした。';
              alert(message);
            }
          })
          .fail(function(stat) {
            console.log(stat);
            var message = 'エラー：倉庫一覧が取得できませんでした。';
            alert(message);
          })
          . always(function() {
          });

      }
    }
  });


});
