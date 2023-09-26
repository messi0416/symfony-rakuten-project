/**
 * ロケーション 空き棚一覧画面用 JS
 * Vue 2.x
 */

/**
 * イベントハブ用オブジェクト
 */
var Hub = new Vue();

/**
 * 一覧ブロック 行コンポーネント
 */
Vue.component('result-item', {
  template: "#result-item"
  , props: [
      'item'
    , 'currentWarehouseId'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
    isValidBoxCode: function() {
      // return this.item.boxCode && /^[A-Za-z0-9_]+$/.test(this.item.boxCode);
      return this.item.boxNumber && /^[A-Za-z0-9_]+$/.test(this.item.boxNumber.toString());
    }
    , confirmButtonCss: function() {
      return this.isValidBoxCode ? null : 'disabled';
    }
  }
  , methods: {

    updateConfirm: function() {
      if (!this.isValidBoxCode) {
        return;
      }
      Hub.$emit('update-confirm', this.item);
    }
  }
});

/**
 * 更新確認モーダル コンポーネント
 */
Vue.component('update-confirm-modal', {
  template: "#update-confirm-modal"
  , props: [
  ]
  , data: function() {
    return {
        item: {}
      , confirmInfo: {}

      , confirmUrl: null
      , updateUrl: null

      , messageCss: null
      , message: ''
    };
  }
  , computed: {
    isConfirmed: function() {
      return this.confirmInfo.id
          && this.confirmInfo.id > 0
          && this.confirmInfo.locationCode
          && this.confirmInfo.locationCode.length > 0
          && this.confirmInfo.moveTo
          && this.confirmInfo.moveTo.length > 0
        ;
    }

    , isMoved: function() {
      return this.item.moved;
    }
  }
  , mounted: function() {
    this.$nextTick(function(){
      this.confirmUrl = $(this.$el).data('confirmUrl');
      this.updateUrl = $(this.$el).data('updateUrl');

      Hub.$on('update-confirm', this.open);
    }.bind(this));
  }
  , methods: {
    // 表示初期化
    clear: function() {
      this.message = '';
      this.messageCss = null;
      this.item = {};
      this.confirmInfo = {};
    }

    // 箱移動確認モーダル表示
    , open: function(item) {
      var self = this;

      self.clear();

      self.item= item;

      // 入力補助機能 ロケーション接頭文字追加
      var boxNumber = item.boxNumber.toString();

      var boxCode = item.locationType + boxNumber;
      item.boxCode = boxCode; // わかりやすいように上書きしてしまう

      var data = {
          rackCode  : item.rackCode
        , placeCode : item.placeCode
        , boxCode   : boxCode
      };

      $.Vendor.WaitingDialog.show("データ取得中 ...");
      $.ajax({
          type: "GET"
        , url: self.confirmUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            self.messageCss = 'alert-info';
            self.message = 'このロケーションを移動してよいですか？';
            self.confirmInfo = {
                id            : Number(result.result.id)
              , locationCode  : result.result.location_code
              , moveTo        : result.result.move_to
            };

          } else {
            self.messageCss = 'alert-danger';
            self.message = result.message;

          }
        })
        .fail(function(stat) {
          console.log(stat);

          self.messageCss = 'alert-danger';
          self.message = 'エラー：確認データ取得に失敗しました。';
        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();

          // モーダル OPEN!
          $('#modalUpdateConfirm').modal().show();

        });
    }

    , updateSubmit: function() {
      var self = this;

      if (!this.isConfirmed) {
        alert('移動元・移動先が正しく取得できませんでした。');
        return;
      }

      var data = {
          id     : self.confirmInfo.id
        , moveTo : self.confirmInfo.moveTo
      };

      $.Vendor.WaitingDialog.show("データ更新中 ...");
      $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            self.messageCss = 'alert-success';
            self.message = 'ロケーションを移動しました。';
            self.item.moved = true;

          } else {
            self.messageCss = 'alert-danger';
            self.message = result.message;

          }
        })
        .fail(function(stat) {
          console.log(stat);

          self.messageCss = 'alert-danger';
          self.message = 'エラー：ロケーション移動に失敗しました。';
        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });

    }


  }
});



/**
 * 検索フォーム・一覧テーブル
 */
var vmLocationEmptyRackList = new Vue({
  el: "#locationEmptyRackList"
  , data: {
      currentWarehouseId: null /* 原則、自倉庫以外のロケーションは触れないとする。リンクもひとまずは無効 */

    , list: []
    , listALL: []
    , rackInitialList: [] // 件数表示用。棚の初期リスト

    , filterRackCode: ''
    , filterLocationType: ''

    , url: null
    , confirmUrl: null
    , updateUrl: null
  }
  , mounted: function() {
    var self = this;

    this.$nextTick(function() {
      self.url = $(self.$el).data('url');
      self.confirmUrl = $(self.$el).data('confirmUrl');
      self.updateUrl = $(self.$el).data('updateUrl');

      self.currentWarehouseId = CURRENT_WAREHOUSE_ID;
      self.list = [];
      self.listALL = [];

      // データ取得
      $.Vendor.WaitingDialog.show("データ取得中 ...");
      $.ajax({
          type: "GET"
        , url: self.url
        , dataType: "json"
        , data: {}
      })
        .done(function(result) {
          if (result.status == 'ok') {
            for (var i = 0; i < result.list.length; i++) {
              var row = result.list[i];

              self.list.push({
                　　rackCode    : row.rack_code
                , placeCode   : row.place_code
                , locationType: row.location_type
                , boxNumber : null
                , boxCode   : null
                , moved     : false
              });
            }
            for (var x = 0; x < result.listALL.length; x++) {
              var rowALL = result.listALL[x];

              self.listALL.push({
                  warehouseId   : rowALL.warehouse_id
                , rackInitial   : rowALL.rack_initial
                , rackCode      : rowALL.rack_code
                , placeCode     : rowALL.place_code
                , locationType  : rowALL.location_type
                , boxNumber : null
                , boxCode   : null
                , moved     : false
                , rackCodeHeader   : rowALL.rack_code.charAt(0)
              });
            }
            
            let rackInitials = {}; // 表示対象の棚イニシャルリスト
            ignoreRegExp = '[PSVX]'; // 無視するイニシャルリスト（棚なしで、箱を直接利用しているものなど）
            for (let i = 0; i < self.listALL.length; i++) {
              rackInitial = self.listALL[i].rackInitial;
              if (!rackInitials[rackInitial] 
                  && self.listALL[i].warehouseId == self.currentWarehouseId
                  && !rackInitial.match(ignoreRegExp)) {
                rackInitials[rackInitial] = rackInitial; // 値は使わないのでなんでもいい
              }
            }
            rackInitialList = Object.keys(rackInitials);
            rackInitialList.push('他倉庫');
            self.rackInitialList = rackInitialList;
          } else {
            alert(result.message);
          }
        })
        .fail(function(stat) {
          console.log(stat);
          alert('エラー：データ取得に失敗しました。');
        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });
    });

  }
  , computed: {
      /**
       * 自倉庫の、現在の絞り込みに合致する棚リスト。
       * 一覧表示に利用
       */
      filteredList: function() {
        var self = this;
        var list = self.list.slice(); // 破壊防止

        list = list.filter(function(item) {
          var result = true;
          var reg;

          // 棚番号
          if (self.filterRackCode.length > 0) {
            reg = new RegExp($.Plusnao.String.regexQuote(self.filterRackCode), 'i');
            result = result && reg.test(item.rackCode);
          }
          // ロケーション種別
          if (self.filterLocationType.length > 0) {
            result = result && (item.locationType == self.filterLocationType);
          }
          return result;
        });

        return list;
      }
      /**
       * 全体のデータから、現在の絞り込み条件に合わせたリスト。
       * 件数表示に利用
       */
    , filteredListALL: function() {
        var self = this;
        var listALL = self.listALL.slice(); // 破壊防止

        listALL = listALL.filter(function(item) {
          var resultALL = true;
          var regALL;
          
          // 無視するイニシャルリスト（棚なしで、箱を直接利用しているものなど）
          ignoreRegExp = '[PSVX]';
          if (item.rackInitial.match(ignoreRegExp)) {
            resultALL = false;
          }

          // 棚番号
          if (self.filterRackCode.length > 0) {
            regALL = new RegExp($.Plusnao.String.regexQuote(self.filterRackCode), 'i');
            resultALL = resultALL && regALL.test(item.rackCode);
          }
          // ロケーション種別
          if (self.filterLocationType.length > 0) {
            resultALL = resultALL && (item.locationType == self.filterLocationType);
          }
          return resultALL;
        });
        return listALL;
      }
      
      /**
      　* フィルタ後の結果を集計したオブジェクトを生成
       */
    , filteredListAllCount: function() {
      const self = this;
      const result = {};
      for (let i = 0; i < self.rackInitialList.length; i++) {
        result[self.rackInitialList[i]] = 0;
      }
      for (let i = 0; i < self.filteredListALL.length; i++) {
        rackInitial = self.filteredListALL[i].rackInitial;
        if (rackInitial in result) {
          result[rackInitial]++;
        } else {
          result['他倉庫']++;
        }
      }
      return result;
    }
  }

  , methods: {
      isCurrentWarehouse: function(warehouseId) {
        return this.currentWarehouseId == warehouseId;
    }
  }
});

