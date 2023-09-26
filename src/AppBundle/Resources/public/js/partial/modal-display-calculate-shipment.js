$(function() {

// 出荷量表示処理
  var vmDisplayCalculateShipmentModal = new Vue({
    el: '#modalDisplayCalculateShipment',
    data: {
      calculateShipmentListUrl: null
      
      , calculateShipment: []
      , message: ''
      , messageClass: 'alert'
    },
    ready: function() {
      this.init(this);
    },

    methods: {

      // 初期処理
      init: function(self) {

        self.calculateShipmentListUrl = $(self.$el).data('calculateShipmentListUrl');

        // モーダル open イベント登録
        // -- open前
        var modal = $(self.$el);
        modal.on('show.bs.modal', function(e) {
          // 会社一覧取得
          $.ajax({
              type: "GET"
            , url: self.calculateShipmentListUrl
            , dataType: "json"
            , data: {}
          })
            .done(function(result) {

              if (result.status == 'ok') {
                  self.calculateShipment = [];
                for (var i = 0; i < result.calculateShipment.length; i++) {
                  var calculateShipment = result.calculateShipment[i];
                  // 桁区切りなどの表示揃え
                  calculateShipment['order_amount'] = parseFloat(calculateShipment['order_amount']).toLocaleString( undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
                  calculateShipment['stock_move_amount'] = parseFloat(calculateShipment['stock_move_amount']).toLocaleString( undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
                  calculateShipment['total'] = parseFloat(calculateShipment['total']).toLocaleString( undefined, {minimumFractionDigits: 1, maximumFractionDigits: 1});
                  
                  self.calculateShipment.push(calculateShipment);
                }

              } else {
                var message = result.message.length > 0 ? result.message : '出荷量が取得できませんでした。';
                alert(message);
                modal.modal().hide();
              }
            })
            .fail(function(stat) {
              console.log(stat);
              self.message = 'エラー：出荷量が取得できませんでした。';
              self.messageClass = 'alert alert-danger'
              modal.modal().hide();
            })
            . always(function() {
            });

        });
      }
    }
  });


});
