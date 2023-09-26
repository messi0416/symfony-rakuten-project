
/**
 * 内訳設定モーダル
 */
var modalSetProductDetails = {
  template: '#templateModalSetProductDetails'
  , props: [
    'state'
  ]
  , data: function() {
    return {
        findUrlBase: null
      , findChoiceUrl: null
      , updateUrl: null

      , list: []
      , freeStock: 0
      , orderNum: 0

      , messageState: new PartsGlobalMessageState()
    };
  }
  , computed: {
      caption: function() {
      return this.state.currentProductCode;
    }

    , findUrl: function() {
      return this.findUrlBase.replace(/__DUMMY__/, this.state.currentProductCode);
    }

    , canEdit: function() {
      return this.freeStock == 0 && this.orderNum == 0;
    }

  }

  , mounted: function() {
    this.$nextTick(function () {
      var self = this;
      var modal = $(self.$el);

      self.findUrlBase = $(self.$el).data('findUrl');
      self.findChoiceUrl = $(self.$el).data('findChoiceUrl');
      self.updateUrl = $(self.$el).data('updateUrl');

      // イベント登録
      self.$watch('state.show', function(newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });

      // -- open前
      modal.on('show.bs.modal', function(e) {
        self.reset();

        // データ取得
        self.loadListData();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.state.show = false; // 外部から閉じられた時のステータス手当
        }
      });

      self.$watch('state.currentProductCode', function() {
        self.reset();

        // データ取得
        self.loadListData();
      })


    });
  }

  , methods: {

    hideModal: function() {
      this.state.show = false;
    }

    , reset: function() {
      this.list = [];
      this.messageState.clear();
    }

    , loadListData: function() {
      var self = this;

      // データ読み込み処理
      var data = {};

      $.ajax({
          type: "GET"
        , url: self.findUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var i;

            self.list = [];
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              self.list.push(item);
            }

            // 空なら空行を1行入れる
            if (self.list.length == 0) {
              self.addRow();
            }

            // フリー在庫数
            self.freeStock = Number(result.freeStock);
            self.orderNum = Number(result.orderNum);

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {

        });
    }

    /// データ保存
    , saveData: function() {
      var self = this;

      self.messageState.clear();

      if (!self.canEdit) {
        return;
      }

      // 簡易入力チェック
      var isValid = true;
      for (var i = 0; i < self.list.length; i++) {
        var item = self.list[i];
        if (!item.num) {
          self.messageState.setMessage('数量が指定されていない行があります。', 'alert-warning');
          isValid = false;
        }
        if (!item.neSyohinSyohinCode || item.neSyohinSyohinCode.length == 0) {
          self.messageState.setMessage('商品が指定されていない行があります。', 'alert-warning');
          isValid = false;
        }
      }
      if (!isValid) {
        return;
      }

      if (!confirm('このデータで保存してよろしいですか？')) {
        return;
      }

      // データ保存処理
      var data = {
          setSyohinCode: self.state.currentProductCode
        , list: self.list
      };

      $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          var message;
          if (result.status == 'ok') {

            message = result.message.length > 0 ? result.message : '内訳を保存しました。';
            self.messageState.setMessage(message, 'alert-success');
            // self.loadListData();
            window.location.reload();

          } else {
            message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {

        });
    }

    /// 1行追加
    , addRow: function() {

      this.list.push({
          neSyohinSyohinCode: null
        , colname: null
        , rowname: null
        , num: 1 // 初期値
      });
    }

    /// 1行削除
    , removeRow: function(index) {
      if (!this.canEdit) {
        return;
      }

      this.list.splice(index, 1);
    }

    /// 1件 詳細商品変更
    , changeChoice: function(index, item) {
      var self = this;

      item.colname = '';
      item.rowname = '';
      if (!item.neSyohinSyohinCode || item.neSyohinSyohinCode.length == 0) {
        return;
      }

      var data = {
        code: item.neSyohinSyohinCode
      };

      $.ajax({
          type: "GET"
        , url: self.findChoiceUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          var message;
          if (result.status == 'ok' && result.data) {

            item.neSyohinSyohinCode = result.data.neSyohinSyohinCode;
            item.colname = result.data.colname;
            item.rowname = result.data.rowname;

          } else {
            message = result.message && result.message.length > 0 ? result.message : '(no data)';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {

        });

    }
  }
};



// 一覧表
var vmSetProductDetail = new Vue({
    el: '#setProductDetail'
  , delimiters: ['(%', '%)']
  , data: {
      modalDetailState: {
        show: false
      , currentProductCode: null
    }

    , messageState: {}
  }
  , components: {
    'modal-detail': modalSetProductDetails // 内訳設定
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.messageState = new PartsGlobalMessageState();
    });
  }

  , computed: {
  }
  , methods: {

    /**
     * 内訳設定モーダル open
     */
      showDetailModal: function(productCode) {
      this.modalDetailState.currentProductCode = productCode;
      this.modalDetailState.show = true;
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------

  }

});




