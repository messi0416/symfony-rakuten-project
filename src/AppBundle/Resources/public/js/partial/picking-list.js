/**
 * ピッキングリスト コンポーネント用JS
 * Vue.js >= 2.0.0
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
      , pickedNum: this.item.pickedNum
      , shortage : this.item.shortage
      , status : this.item.status
      , newLocationCode: this.item.newLocationCode
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
      return this.item.errorMessage !== "";
    }
    , isStatusOk: function() {
      return this.item.status === 1;
    }
    , isStatusIncorrect: function() {
      return this.item.status === 2;
    }
    , isStatusPass: function() {
      return this.item.status === 3;

    }
    , isUnlocated: function() {
      return (
           (this.isStatusOk || this.isStatusIncorrect)
        && this.item.pickedNum > 0
        && (this.item.newLocationCode.length === 0)
      )
      ;
    }


    , rowCss: function() {
      let css = "";
      if (this.isActiveItem) {
        css = [ "list-group-item-info" ];
      } else if (this.hasError) {
        css = [ "list-group-item-danger" ];
      } else if (this.isStatusOk) {
        css = [ "list-group-item-success" ];
        if (! this.isUnlocated) {
          css.push( "gray" );
        }
      } else if (this.isStatusIncorrect) {
        css = [ "list-group-item-warning" ];
        if (! this.isUnlocated) {
          css.push( "gray" );
        }
      } else if (this.isStatusPass) {
        css = "list-group-item-danger";
      }
      return css;
    }
    , statusCss: function() {
      let css = "";
      if (this.hasError) {
        css = "label-danger";
      } else if (this.isUnlocated) {
        css = "label-success";
      } else if (this.isStatusOk) {
        css = "label-default";
      } else if (this.isStatusIncorrect) {
        css = "label-warning";
      } else if (this.isStatusPass) {
        css = "label-danger";
      }
      return css;
    }
    , statusWord: function() {
      let word = "";
      if (this.hasError) {
        word = "エラー";
      } else if (this.isUnlocated) {
        word = "ロケ未作成"
      } else if (this.isStatusOk) {
        word = "OK";
      } else if (this.isStatusIncorrect) {
        word = "不足";
      } else if (this.isStatusPass) {
        word = "パス";
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
 *
 * @typedef {Object} DetailModal
 * @property {Array} locationData
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
      , pickedNum: this.item.pickedNum
      , shortage : this.item.shortage
      , status : this.item.status
      , imageUrl : this.item.imageUrl
      , linkUrl : this.item.linkUrl
      , created : this.item.created
      , updated : this.item.updated

      , errorMessage: this.item.errorMessage
    };
  },
  computed: {
    hasError: function() {
      return this.item.errorMessage !== "";
    }

    , isStatusOk: function() {
      return this.item.status === 1;
    }
    , isStatusIncorrect: function() {
      return this.item.status === 2;
    }
    , isStatusPass: function() {
      return this.item.status === 3;

    }
    , isUnlocated: function() {
      return (
        (this.isStatusOk || this.isStatusIncorrect)
        && this.pickedNum > 0
        && (this.item.newLocationCode.length === 0)
      );
    }

    , statusCss: function() {
      let css = "";
      if (this.hasError) {
        css = "label-danger";
      } else if (this.isUnlocated) {
        css = "label-success";
      } else if (this.isStatusOk) {
        css = "label-default";
      } else if (this.isStatusIncorrect) {
        css = "label-warning";
      } else if (this.isStatusPass) {
        css = "label-danger";
      }
      return css;
    }
    , statusWord: function() {
      let word = "";
      if (this.hasError) {
        word = "エラー";
      } else if (this.isUnlocated) {
        word = "ロケ未作成"
      } else if (this.isStatusOk) {
        word = "OK";
      } else if (this.isStatusIncorrect) {
        word = "不足";
      } else if (this.isStatusPass) {
        word = "パス";
      }
      return word;
    }
    , emphasizeCss: function() {
      return this.item.shortage > 1 ? 'label-danger' : 'label-default';
    }
    , totalStock: function() {
      let total = 0;
      // console.log(this.locationData);

      /** @type {DetailModal} this */

      if (this.locationData && this.locationData.locations) {
        for (let i = 0; i < this.locationData.locations.length; i++) {
          let location = this.locationData.locations[i];
          total += location.stock;
        }
      }
      return total;
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

    /// 編集モーダルopen
    , openEditStockModal: function(location) {

      let i = this.locationData.locations.indexOf(location);
      if (i === -1) {
        console.log('not found!!');
        return;
      }

      this.$emit('close-detail'); // こちらのモーダルは閉じる
      this.$emit('open-edit-modal', this.locationData.locations[i]);
    }
  }
});

/**
 * 在庫編集＆一部OK モーダルコンポーネント
 */
Vue.component('detail-edit-stock-modal', {
  template: "#detail-edit-stock-modal"
  , props: [
      'item'
    , 'location'
  ]
  , data: function() {
    return {
        updateUrl: null
      , message: ""
      , messageCss: "alert-info"

      , newStock: 0
    };
  }
  , computed: {
    displayPickingNum: function() {
      return (this.newStock >= this.location.pickingNum) ? this.location.pickingNum : this.newStock;
    }
  }

  , watch: {
    location: function() {
      this.newStock = this.location.stock;
    }
  }

  , mounted: function() {
    const self = this;
    this.$nextTick(function() {
      self.updateUrl = $(this.$el).data('updateUrl');

      const modal = $(self.$el);

      modal.on('show.bs.modal', function() {
      });

      // -- close後
      modal.on('hidden.bs.modal', function() {
      });
    });
  }
  , methods: {
    setMessage: function(message, css) {
      this.message = message;
      this.messageCss = css;
    }

    , clearMessage: function() {
      this.message = "";
      this.messageCss = "alert-info";
    }

    , closeModal: function() {
      const self = this;

      self.clearMessage();

      // 編集は閉じる
      self.$emit('close-edit-stock-modal');
      // 詳細を開く
      self.$emit('open-detail-modal', self.item);
    }

    /// 保存処理
    , processUpdateStock: function() {
      const self = this;

      const type = Object.prototype.toString.call(self.newStock).slice(8, -1);

      if (type !== 'Number' || self.newStock.length === 0) {
        self.setMessage('現在の実在庫数が入力されていません。', 'alert-danger');
        return;
      }

      const data = {
          mode: 'w' // 倉庫在庫ピッキング
        , neSyohinSyohinCode: self.location.neSyohinSyohinCode
        , locationId: self.location.locationId
        , newStock: self.newStock

        , pickingNum: self.location.pickingNum
      };

      // Show loading
      $.Vendor.WaitingDialog.show('ロケーション在庫修正中 ...');

      return $.ajax({
          type: "POST"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      });

    }


    /// 編集のみ保存実行
    , submitEdit: function() {
      const self = this;

      self.processUpdateStock()

        .done(function(result) {

          let message;
          if (result.status === 'ok') {
            // self.newLocationCode = ''; // 入力リセット
            self.setMessage(result.message, 'alert-success');
            // self.$emit('load-data');

            // エラー
          } else {
            message = result.message && result.message.length > 0 ? result.message : '保存処理に失敗しました。';
            self.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
          $.Vendor.WaitingDialog.hide();
        });

    }

    /// 編集&一部OK 実行
    , submitOk: function() {

      const self = this;

      self.processUpdateStock()

        .done(function(result) {

          let message;
          if (result.status === 'ok') {

            self.$emit('submit-ok-partial', self.location);
            self.closeModal(); // いずれにせよ詳細に戻る（メッセージもそこに表示される）

            // エラー
          } else {
            message = result.message && result.message.length > 0 ? result.message : '保存処理に失敗しました。';
            self.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
          $.Vendor.WaitingDialog.hide();
        });

    }
  }
});



/**
 * ロケーション作成 モーダルコンポーネント
 */
Vue.component('detail-create-location-modal', {
    template: "#detail-create-location-modal"
  , props: [
    'list'
    , 'notProcessedCount'
  ]
  , data: function() {
    return {
        createUrl: null
      , newLocationCode: ""
      , message: ""
      , messageCss: "alert-info"

      , confirm: false
    };
  }
  , computed: {
    submitWord: function() {
      return this.confirm ? '追加する' : '作成する';
    }
  }

  , mounted: function() {
    const self = this;
    this.$nextTick(function() {
      self.createUrl = $(this.$el).data('createUrl');

      const modal = $(self.$el);

      modal.on('show.bs.modal', function() {
        self.setMessage('現在のピッキング商品でロケーションを作成しますか？', 'alert-info');
        self.confirm = false;
      });

      // -- close後
      modal.on('hidden.bs.modal', function() {
        self.setMessage('', 'alert-info');
        self.confirm = false;
      });
    });
  }
  , methods: {
    setMessage: function(message, css) {
      this.message = message;
      this.messageCss = css;
    }

    , submit: function() {
      const self = this;

      if (!self.newLocationCode.length) {
        self.setMessage('ロケーションコードを入力してください。', 'alert-danger');
        return;
      }

      const data = {
          newLocationCode: self.newLocationCode
        , confirm: self.confirm ? '1' : '0'
      };

      // Show loading
      $.Vendor.WaitingDialog.show('ロケーション情報作成中 ...');

      $.ajax({
          type: "POST"
        , url: self.createUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          let message;
          if (result.status === 'ok') {
            // self.newLocationCode = ''; // 入力リセット
            self.setMessage(result.message, 'alert-success');
            self.$emit('load-data');

            // 確認表示
          } else if (result.status === 'confirm') {

            self.setMessage(result.message, 'alert-warning');
            self.confirm = true;

            // エラー
          } else {
            message = result.message && result.message.length > 0 ? result.message : 'ロケーション作成に失敗しました。';
            self.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
          $.Vendor.WaitingDialog.hide();
        });

    }
  }
});


Vue.component('parts-picking-list', {
    template: '#partsPickingList'
  , delimiters: ['(%', '%)']

  , props: [
      'messageState'
    , 'pickingList'

    , 'locationUrl'
    , 'submitUrl'
    , 'applyTransportDetailUrl'

  ]

  , data: function() {
      return {
        // 詳細モーダルコンポーネントと双方向バインド
          detailInfo: {}

        // ページ送り設定
        , pageItemNum: 20 // 設定値: 1ページ表示件数
        // , pageListMaxLength: 6 // 設定値: ページリンク 表示最大件数
        , pageItemNumList: [2, 20, 50, 100]
        , page: 1 // 現在のページ

        // フィルター
        , filterProcessed: 'all'

        , currentListIndex: 0 // 一覧でのインデックス
        , currentLocationData: {} // 選択されているピッキング対象商品のロケーション一覧情報
        , currentLocationLoading: true

        // 在庫編集＆一部OK対象ロケーション
        , editTargetLocation: {}

        // 各itemで共有する状態オブジェクト
        , listStates: {
            currentDetailItemIndex: null
          , inProcess: false
          , inProcessItemIndex: null
          , processMessage: ""
          , processMessageCss: "alert-info"
        }
      };
  }

  , mounted: function() {
    const self = this;

    this.$nextTick(function() {
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
      const self = this;
      let list = self.pickingList.slice(); // 破壊防止

      // 絞込
      list = list.filter(function(item, i) {
        let result = true;

        // 絞込 処理状態
        if (self.filterProcessed === 'notProcessed') {
          result = result && item.status === 0;
        } else if (self.filterProcessed === 'processed') {
          result = result && item.status !== 0;
        }

        return result;
      });

      return list;
    }

    , pageData: function() {
      let startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }

    , unlocatedListData: function() {
      const self = this;
      let list = self.pickingList.slice(); // 破壊防止

      // 絞込
      list = list.filter(function(item, i) {
        return (
             (item.status === 1 || item.status === 2)
          && item.pickedNum > 0
          && (item.newLocationCode.length === 0)
        );
      });

      return list;
    }

    , notProcessedCount: function() {
      return this.pickingList.reduce(function(result, item) {
        return result + (item.status === 0 ? 1 : 0);
      }, 0);
    }

    , unlocatedCount: function() {
      return this.unlocatedListData.length;
    }

    , isTypeWarehouse: function() {
      return this.pickingList[0] && this.pickingList[0].type === 'warehouse';
    }
    , isTypeFba: function() {
      return this.pickingList[0] && this.pickingList[0].type === 'fba';
    }
  }
  , methods: {
    loadData: function() {
      this.$emit('load-data');
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
          if (result.status === 'ok') {
            self.currentLocationData = result.data;

            /// ロケーションからのピッキング数算出（表示用）
            let pickingRemain = self.currentDetailItem.shortage;
            for (let i = 0; i < self.currentLocationData.locations.length; i++) {
              let location = self.currentLocationData.locations[i];
              let locationRemain = location.stock;
              let pickingNum = pickingRemain;
              if (location.stock <= pickingRemain) {
                pickingNum = location.stock;
              } else {
                pickingNum = pickingRemain;
              }

              location.pickingNum = pickingNum;
              location.locationRemain = pickingNum === 0 ? '-' : location.stock - pickingNum;

              pickingRemain = pickingRemain - pickingNum;
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
          self.currentLocationLoading = false;
        });

      return deferred.promise();
    }

    /// ロケーション更新
    , refreshLocation: function(doReload) {
      this.$emit('refresh-location', doReload);
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
    , submitIncorrect: function() {
      return this.submit('incorrect');
    }

    , submit: function(button) {
      const self = this;

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
      const data = {
        id: self.currentDetailItem.id
        , syohin_code: self.currentDetailItem.neSyohinSyohinCode
        , move_num: self.currentDetailItem.shortage
        , button: button
        , data_hash: self.currentLocationData.dataHash
      };
      const processedItem = self.pickingList[self.listStates.inProcessItemIndex];

      $.ajax({
          type: "POST"
        , url: self.submitUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status === 'ok') {

            processedItem.pickedNum = result.picked_num;
            processedItem.shortage = result.shortage;
            processedItem.status = result.picking_status;

            processedItem.errorMessage = "";

            self.listStates.processMessage = "";
            self.listStates.processMessageCss = "alert-info";

            // status の変更により、listData が移動している可能性があるため、再度listIndexを取り直す。
            const currentItem = self.pickingList[self.listStates.currentDetailItemIndex];
            if (currentItem) {
              let i = self.listData.indexOf(currentItem);
              if (i === -1) {
                return;
              }

              if (i !== self.currentListIndex) {
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

    /// 一部OK
    , submitOkPartial: function(location) {
      const self = this;

      // データ再読み込み後に実行
      self.loadLocation()

        .done(function() {

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
          const data = {
            id: self.currentDetailItem.id
            , syohin_code: self.currentDetailItem.neSyohinSyohinCode
            , move_num: self.currentDetailItem.shortage
            , button: 'ok'
            , data_hash: self.currentLocationData.dataHash
            , location_id: location.locationId
          };
          const processedItem = self.pickingList[self.listStates.inProcessItemIndex];

          $.ajax({
            type: "POST"
            , url: self.submitUrl
            , dataType: "json"
            , data: data
          })
            .done(function(result) {
              if (result.status === 'ok') {

                processedItem.pickedNum = result.picked_num;
                processedItem.shortage = result.shortage;
                processedItem.status = result.picking_status;
                processedItem.errorMessage = "";

                self.listStates.processMessage = "";
                self.listStates.processMessageCss = "alert-info";

                // status の変更により、listData が移動している可能性があるため、再度listIndexを取り直す。
                const currentItem = self.pickingList[self.listStates.currentDetailItemIndex];
                if (currentItem) {
                  let i = self.listData.indexOf(currentItem);
                  if (i === -1) {
                    return;
                  }

                  if (i !== self.currentListIndex) {
                    self.moveDetail(i, true);
                  } else {
                    self.loadLocation();
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
        })
        .fail(function() {

        });

    }


    // ==============================
    // 詳細モーダル
    // ※注意： 詳細に関する index は常に、listData に対してのみ操作する。（itemIndexとは別もの。絞込・ソート等に対応するため）
    // ==============================
    /**
     * 詳細モーダル
     */
    , openDetail: function(item) {

      const self = this;
      const i = self.listData.indexOf(item);

      if (i === -1) {
        alert("データが見つかりませんでした。");
        return;
      }

      self.moveDetail(i, true);
      $('#modalPickingDetail', this.$el).modal('show')
    }

    , closeDetail: function() {
      const self = this;
      $('#modalPickingDetail', this.$el).modal('hide');
    }

    /**
     * 詳細モーダル移動
     */
    , moveDetail: function(index, noFade) {
      const self = this;

      const moveProcess = function() {
        self.currentListIndex = index;
        self.listStates.currentDetailItemIndex = self.listData[self.currentListIndex].itemIndex;
        self.moveToPageForIndex(self.currentListIndex);

        self.loadLocation();
      };

      if (noFade) {
        moveProcess();
      } else {
        const $modalBody = $('#modalPickingDetail .modal-body');
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

    /// 在庫編集＆一部OK モーダル表示
    , openEditStockModal: function(location) {
      this.editTargetLocation = location;

      $('#modalPickingDetailEditStock', this.$el).modal('show');
    }

    , closeEditStock: function() {
      $('#modalPickingDetailEditStock', this.$el).modal('hide');
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
      const pageNum = this.calculatePageForIndex(index);
      if (pageNum !== this.page) {
        this.showPage({
          page: pageNum
          , pageItemNum: this.pageItemNum
        });
      }
    }


    // ==============================
    // ロケーション作成モーダル
    // ==============================
    /**
     * ロケーション作成モーダル
     */
    , openLocationCreate: function() {
      $('#modalPickingDetailCreateLocation', this.$el).modal().show();
    }


    // ==============================
    // 移動伝票へ反映処理
    // ==============================
    , applyTransportDetail: function() {
      const self = this;

      const data = {};

      // Show loading
      $.Vendor.WaitingDialog.show('移動伝票へ反映中 ...');

      $.ajax({
        type: "POST"
        , url: self.applyTransportDetailUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          let message;
          if (result.status === 'ok') {
            // self.newLocationCode = ''; // 入力リセット
            self.messageState.setMessage(result.message, 'alert-success');
            self.$emit('load-data');

            // 確認表示
          } else if (result.status === 'confirm') {

            self.messageState.setMessage(result.message, 'alert-warning');
            self.confirm = true;

            // エラー
          } else {
            message = result.message && result.message.length > 0 ? result.message : '移動伝票への反映に失敗しました。';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
          $.Vendor.WaitingDialog.hide();
        });

    }


  }

})
;
