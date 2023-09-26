/**
 * SKU別送料設定　保留一覧JS
 */
// データ更新 モーダル
Vue.component('update-confirm-modal', {
    template: '#templateModalUpdateConfirm'
  , delimiters: ['(%', '%)']
  , props: [
      'state' // { show: true|false }
    , 'list'
  ]
  , data: function() {
    return {
        updateUrl: null
      , messageState: {}
      , caption: 'SKU別送料設定　保留情報更新'
//      , messageClass: 'alert alert-success'
//      , message: '送料設定を更新します。よろしいですか？'
    };
  }
  , computed: {
  }
  , watch : {
  }
  , mounted: function() {
    this.$nextTick(function (){
      var self = this;
      var modal = $(self.$el);

      self.messageState = new PartsGlobalMessageState();

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
        self.messageState.clear();
      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
        self.messageState.setMessage('更新を実行します、よろしいですか？　', 'alert-success');
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.hideModal(); // 外部から閉じられた時の手当
        }
      })
    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
    }

    , save: function() {
      var self = this;

      self.messageState.clear();
      this.state.show = false;
      self.$emit('update-pendings'); // 親コンポーネントに委譲
    }
  }
});

/**
 * 管理画面 SKU別送料設定　保留一覧 JS
 */

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentPendingListItem = {
    template: '#templatePendingListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
        locationProductSkuUrlBase: ""
    };
  }
  , computed: {
      locationProductSkuUrl: function() {
      return this.locationProductSkuUrlBase.replace(/__DUMMY__/g, this.item.daihyoSyohinCode);
    }
  }
  , mounted: function() {
    this.$nextTick(function() {
      this.locationProductSkuUrlBase = $(this.$el).data('locationProductSkuUrlBase');
    });
  }
};

// 一覧画面 一覧表
var vmShippingDivisionList = new Vue({
    el: '#shippingPendingList'
  , data: {
      list: [] // データ
    , url: null
    , initialized: false
    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , show: false
    }
    , updateUrl: null
    , nowLoading: false
  }
  , components: {
      'result-item': vmComponentPendingListItem // 一覧テーブル
  }

  , mounted: function() {
    var self = this;
    this.$nextTick(function () {
      self.url = $(self.$el).data('url');
      self.updateUrl = $(self.$el).data('updateUrl');

      // メッセージオブジェクト
      self.messageState = new PartsGlobalMessageState();
      self.list = [];
      self.initialized = true;
      self.showPage();
    });
  }

  , computed: {
      canUpdate: function() { // 更新対象があれば更新可能
        var self = this;
        if (self.list.length == 0) {
          return false;
        }
        for (var i = 0; i < self.list.length; i++) {
          if (self.list[i].reflectStatus != 1) {
            return true;
          }
        }
        return false;
      }
  }
  , methods: {
    // 一覧表示
    showPage: function() {
      // 初期化が済んでいない場合にはreturn
      if (!this.initialized) {
        return;
      }
      this.initialized = false;
      var self = this;
      $.ajax({
        type: "GET"
      , url: self.url
      , dataType: "json"
      })
      .done(function(result) {
        if (result.status == 'ok') {
          self.list = [];
          for (var i = 0; i < result.items.length; i++) {
            self.list.push(self.convertItem(result.items[i]));
          }
          if (result.items.length == 0) {
            self.messageState.setMessage('対象データがありません', 'alert-success');
          }
        } else {
          var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        console.log(stat);
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
      . always(function() {
        self.initialized = true;
      });
    }
    ,
    // 取得データをJS用に変換
    convertItem: function(item) {
       return {
          id                         : item.id
        , daihyoSyohinCode           : item.daihyoSyohinCode
        , daihyoSyohinName           : item.daihyoSyohinName
        , targetCode                 : item.targetNeSyohinSyohinCode ? item.targetNeSyohinSyohinCode : item.axisCode
        , size                       : item.height + ', ' + item.width + ', ' + item.depth
        , weight                     : item.weight
        , prevSdName                 : item.prevSdName
        , prevSdPrice                : item.prevSdPrice + '円'
        , pendingSdName              : item.pendingSdName
        , pendingSdPrice             : item.pendingSdPrice + '円'
        , mpSdName                   : item.mpSdName
        , mpSdPrice                  : item.mpSdPrice + '円'
        , reflectStatus              : item.reflectStatus
      };
    }
    , showFormModal: function () {
      this.modalState.show = true;
    }
    , updatePendings: function () {
      var self = this;

      self.messageState.clear();
      self.nowLoading = true;

      var data = [];
      for (var i = 0; i < self.list.length; i++) {
        data.push({
            'id': self.list[i].id
          , 'reflectStatus': self.list[i].reflectStatus
        })
      }

      $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: JSON.stringify(data)
      })
      .done(function(result) {
        if (result.status == 'ok') {
          self.messageState.setMessage(result.message, 'alert-success');
          self.showPage();
        } else {
          var message = result.message.length > 0 ? result.message : '更新できませんでした。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
      . always(function() {
        self.nowLoading = false;
      });
    }
  }
});
