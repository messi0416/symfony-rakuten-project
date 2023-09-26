/**
 * セット商品ピッキングリスト画面 JS
 */

/**
 * 一覧ブロック 行コンポーネント
 */
Vue.component('result-item', {
  template: "#result-item",
  props: [
      'item'
    , 'listStates'
  ],
  data: function() {
    return {
        itemIndex: this.item.itemIndex
      , neSyohinSyohinCode : this.item.neSyohinSyohinCode
      , locationId : this.item.locationId
      , locationCode : this.item.locationCode
      , position : this.item.position
      , stock : this.item.stock
      , moveNum : this.item.moveNum
      , status : this.item.status
      , pictDirectory : this.item.pictDirectory
      , pictFilename : this.item.pictFilename
      , imageUrl : this.item.imageUrl
      , linkUrl : this.item.linkUrl
      , created : this.item.created
      , updated : this.item.updated

      , errorMessage: this.item.errorMessage
    };
  },
  computed: {
    isActiveItem: function() {
      return this.listStates.currentDetailItemIndex === this.itemIndex
    }
    , hasError: function() {
      return this.item.errorMessage != "";
    }
    , rowCss: function() {
      var css = "";
      if (this.isActiveItem) {
        css = "list-group-item-info";
      } else if (this.hasError) {
        css = "list-group-item-danger";
      } else if (this.item.status == 1) {
        css = "list-group-item-success";
      } else if (this.item.status == 3) {
        css = "list-group-item-warning";
      }
      return css;
    }
    , statusCss: function() {
      var css = "";
      if (this.hasError) {
        css = "label-danger";
      } else if (this.item.status == 1) {
        css = "label-success";
      } else if (this.item.status == 3) {
        css = "label-warning";
      }
      return css;
    }
    , statusWord: function() {
      var word = "";
      if (this.hasError) {
        word = "エラー";
      } else if (this.item.status == 1) {
        word = "OK";
      } else if (this.item.status == 3) {
        word = "PASS";
      }
      return word;
    }
  },
  ready: function() {
  },
  methods: {
    openDetailModal: function() {
      this.$emit('open-detail', this.item);
    }
  }
});

/**
 * 詳細モーダルコンポーネント
 */
Vue.component('detail-modal', {
  template: "#detail-modal",
  props: [
      'item'
    , 'locationData'
    , 'nowLoading'
    , 'listStates'
    , 'info' // 双方向バインド用
  ],
  data: function() {
    return {
        itemIndex: this.item.itemIndex
      , neSyohinSyohinCode : this.item.neSyohinSyohinCode
      , currentLocation : this.item.currentLocation
      , moveNum : this.item.moveNum
      , status : this.item.status
      , imageUrl : this.item.imageUrl
      , linkUrl : this.item.linkUrl
      , created : this.item.created
      , updated : this.item.updated

      , errorMessage: this.item.errorMessage
    };
  },
  computed: {
    isStatusOk: function() {
      return this.item.status == 1;
    }
    , hasError: function() {
      return this.item.errorMessage != "";
    }
    , statusCss: function() {
      var css = "";
      if (this.hasError) {
        css = "label-danger";
      } else if (this.item.status == 1) {
        css = "label-success";
      } else if (this.item.status == 3) {
        css = "label-warning";
      }
      return css;
    }
    , statusWord: function() {
      var word = "";
      if (this.hasError) {
        word = "エラー";
      } else if (this.item.status == 1) {
        word = "OK";
      } else if (this.item.status == 3) {
        word = "PASS";
      }
      return word;
    }
  },

  methods: {
    /// 次へ
    movePrev: function() {
      this.$emit('move-prev');
    }
    /// 前へ
    , moveNext: function() {
      this.$emit('move-next');
    }
    /// OK / PASS
    , submit: function(button) {
      switch (button) {
        case 'ok':
          this.$emit('submit-ok');
          break;
        case 'pass':
          this.$emit('submit-pass');
          break;
      }
    }
  }
});


/**
 * 一覧ブロック
 */
var vmSetProductPickingList = new Vue({
    el: "#setProductPickingList"
  , data: {
      pickingList: []

    , dataUrl: null
    , locationUrl: null
    , refreshUrl: null
    , submitUrl: null

    // 詳細モーダルコンポーネントと双方向バインド
    , detailInfo: {
    }

    // ページ送り設定
    , pageItemNum: 20 // 設定値: 1ページ表示件数
    // , pageListMaxLength: 6 // 設定値: ページリンク 表示最大件数
    , pageItemNumList: [ 2, 20, 50, 100 ]
    , page: 1 // 現在のページ

    , messageState: {}

    // フィルター
    , filterProcessed: 'all'

    , currentListIndex: 0 // 一覧でのインデックス
    , currentLocationData: {} // 選択されているピッキング対象商品のロケーション一覧情報
    , currentLocationLoading: true

    // 各itemで共有する状態オブジェクト
    , listStates: {
        currentDetailItemIndex: null
      , inProcess: false
      , inProcessItemIndex: null
      , processMessage: ""
      , processMessageCss: "alert-info"
    }

  }
  , mounted: function() {
    const self = this;

    this.$nextTick(function() {
      self.dataUrl = $(this.$el).data('dataUrl');
      self.locationUrl = $(this.$el).data('locationUrl');
      self.refreshUrl = $(this.$el).data('refreshUrl');
      self.submitUrl = $(this.$el).data('submitUrl');

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

      currentDetailItem : function() {
        if (this.listStates.currentDetailItemIndex >= 0 && this.pickingList[this.listStates.currentDetailItemIndex]) {
          return this.pickingList[this.listStates.currentDetailItemIndex];
        }
        return {};
    }
    , itemCount: function() {
      return this.pickingList.length;
    }

    , filteredItemCount: function() {
      return this.listData.length;
    }
    ///// ソート・フィルター済みリスト
    //// ※ページング処理のため、Vue.js v-for の filterBy, orderBy が利用できない。
    , listData: function() {
      var self = this;
      var list;
      list = self.pickingList.slice(); // 破壊防止

      // 絞込
      list = list.filter(function(item, i) {
        var result = true;

        // 絞込 処理状態
        if (self.filterProcessed == 'notProcessed') {
          result = result && item.status == 0;
        } else if (self.filterProcessed == 'processed') {
          result = result && item.status != 0;
        }

        return result;
      });

      return list;
    }

    , pageData: function() {
      var startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }

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
          if (result.status == 'ok') {

            self.pickingList = [];

            var i, row;
            for (i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              row = {
                  itemIndex: i // IDとして利用
                , id: item.id
                , neSyohinSyohinCode : item.ne_syohin_syohin_code
                , currentLocation : item.current_location
                , moveNum : Number(item.move_num) || 0
                , status : Number(item.status) || 0
                //, pictDirectory : item.pict_directory
                //, pictFilename : item.pict_filename
                , imageUrl : item.image_url
                , linkUrl : item.link_url
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
          self.messageState.setMessage('エラー：更新に失敗しました。', 'alert-danger');
          deferred.reject();
        })
        .always(function() {
        });

      return deferred.promise();
    }

    /// ロケーション一覧取得
    , loadLocation: function() {

      const self = this;
      const deferred = new $.Deferred();

      if (!self.currentDetailItem) {
        return;
      }

      self.currentLocationLoading = true;
      self.currentLocationData = {};

      $.ajax({
          type: "GET"
        , url: self.locationUrl
        , dataType: "json"
        , data: {
          code: self.currentDetailItem.neSyohinSyohinCode
        }
      })
        .done(function(result) {
          if (result.status == 'ok') {
            self.currentLocationData = result.data;

            deferred.resolve();

          } else {
            self.messageState.setMessage(result.message, 'alert-danger');
            deferred.reject();
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラー：更新に失敗しました。', 'alert-danger');
          deferred.reject();
        })
        .always(function() {
          self.currentLocationLoading = false;
        });

      return deferred.promise();
    }

    /// ロケーション更新
    , refreshLocation: function() {
      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('ロケーション情報取得中 ...');

      $.ajax({
          type: "POST"
        , url: self.refreshUrl
        , dataType: "json"
        , data: {}
      })
        .done(function(result) {
          if (result.status == 'ok') {

            self.messageState.setMessage(result.message, 'alert-success');
            self.loadData().then(function(){
              $.Vendor.WaitingDialog.hide();
            });

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


    // ==============================
    // OK / PASS処理
    // ==============================
    , submitOk: function() {
      return this.submit('ok');
    }
    , submitPass: function() {
      return this.submit('pass');
    }
    , submit: function(button) {
      var self = this;

      if (! self.currentDetailItem.neSyohinSyohinCode) {
        alert('商品データが取得できませんでした。');
        return;
      }

      // 処理中確認
      if (self.listStates.inProcess) {
        alert("現在、データ更新処理中です。完了するまで次の処理はできません。");
        return;
      }

      // 更新状態 セット
      self.listStates.inProcess = true;
      self.listStates.inProcessItemIndex = self.listStates.currentDetailItemIndex;
      self.listStates.processMessage = "現在、データ更新処理を実行中です。";
      self.listStates.processMessageCss = "alert-warning";

      // 処理実行
      var data = {
          id: self.currentDetailItem.id
        , syohin_code: self.currentDetailItem.neSyohinSyohinCode
        , move_num: self.currentDetailItem.moveNum
        , button: button
        , data_hash: self.currentLocationData.dataHash
      };
      var processedItem = self.pickingList[self.listStates.inProcessItemIndex];

      $.ajax({
          type: "POST"
        , url: self.submitUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            processedItem.status = result.picking_status;
            processedItem.errorMessage = "";

            self.listStates.processMessage = "";
            self.listStates.processMessageCss = "alert-info";

            // status の変更により、listData が移動している可能性があるため、再度listIndexを取り直す。
            var currentItem = self.pickingList[self.listStates.currentDetailItemIndex];
            if (currentItem) {
              var i = self.listData.indexOf(currentItem);
              if (i == -1) {
                return;
              }

              if (i != self.currentListIndex) {
                self.moveDetail(i, true);
              }
            }

          } else {
            processedItem.errorMessage = result.message;
            self.listStates.processMessage = "エラー：更新できませんでした。";
            self.listStates.processMessageCss = "alert-danger";
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.listStates.processMessage = "エラー：更新に失敗しました。";
          self.listStates.processMessageCss = "alert-danger";

          processedItem.errorMessage = "通信エラー";
        })
        .always(function() {
          self.listStates.inProcessItemIndex = null;
          self.listStates.inProcess = false;
        });

      // 特に結果を待たずに、次へ進む。
      self.moveDetailNext();
    }


    // ==============================
    // 詳細モーダル
    // ※注意： 詳細に関する index は常に、listData に対してのみ操作する。（itemIndexとは別もの。絞込・ソート等に対応するため）
    // ==============================
    /**
     * 詳細モーダル
     */
    , openDetail: function(item) {

      var self = this;
      var i = this.listData.indexOf(item);

      if (i == -1) {
        alert("データが見つかりませんでした。");
        return;
      }

      self.moveDetail(i, true);
      $('#modalPickingDetail', this.$el).modal().show();
    }

    /**
     * 詳細モーダル移動
     */
    , moveDetail: function(index, noFade) {
      var self = this;

      var moveProcess = function() {
        self.currentListIndex = index;
        self.listStates.currentDetailItemIndex = self.listData[self.currentListIndex].itemIndex;
        self.moveToPageForIndex(self.currentListIndex);

        self.loadLocation();
      };

      if (noFade) {
        moveProcess();
      } else {
        var $modalBody = $('#modalPickingDetail .modal-body');
        $modalBody.fadeTo(200, 0.2, function() {
          moveProcess();
          $modalBody.fadeTo(100, 1);
        });
      }
    }
    , moveDetailPrev: function() {
      if (this.currentListIndex > 0) {
        this.moveDetail(this.currentListIndex - 1);
      } else {
        alert('すでに先頭か、あるいはデータがありません。');
      }
    }
    , moveDetailNext: function() {
      if (this.currentListIndex < (this.listData.length - 1)) {
        this.moveDetail(this.currentListIndex + 1);
      } else {
        alert('すでに最後か、あるいはデータがありません。');
      }
    }

    // -------------------------
    // ページ操作
    // -------------------------

    , showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    /**
     * インデックスに対応するページ番号取得
     */
    , calculatePageForIndex: function(index) {
      return Math.ceil((index + 1) / this.pageItemNum);
    }
    /**
     * インデックスに対応するページを表示
     */
    , moveToPageForIndex: function(index) {
      var pageNum = this.calculatePageForIndex(index);
      if (pageNum !== this.page) {
        this.showPage({
            page: pageNum
          , pageItemNum: this.pageItemNum
        });
      }
    }


  }

});
