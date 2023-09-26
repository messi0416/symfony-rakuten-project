/**
 * 商品ロケーション 箱振り画面用 JS
 * for Vue1.x
 */

var vmLocationStoreImportProducts = new Vue({
    el: '#locationStoreImportProduct'
  , data: {

      locationList: []
    , selectedLocationId: null
    , moveTo: ''
    , moveNum: 0
    , dataHash: null
    , neSyohinSyohinCode: null

    , inputMode: null // scan | input
    , inputBarcode: null

    , searchUrlBase: null
    , submitMoveUrl: null

    , locationHistory: []

    , validation: {
        moveTo: null
      , moveNum: null
    }

    , storageLocationHistory: null
    , storageMoveFrom: null
    , storageMoveTo: null

    , storageLastSubmittedCode: null

    // 仕入備考の存在確認
    , hasSireComment: (SIRECOMMENT !== null && SIRECOMMENT.length > 0)
    // 仕入備考の確認チェックボックス（デフォルトOFF）
    , confirmFlg: false

    // レビュー平均点
    , reviewAverage: REVIEW_AVERAGE
    , GOOD_SCORE: 4.2
    , BAD_SCORE: 3.7
  }
  , ready: function() {
    // 仕入備考がなければ確認チェックボックスは最初からtrueになる
    this.confirmFlg = !(this.hasSireComment)

    this.searchUrlBase = $(this.$el).data('searchUrlBase');
    this.submitMoveUrl = $(this.$el).data('submitMoveUrl');

    this.dataHash = $(this.$el).data('dataHash');
    this.neSyohinSyohinCode = $(this.$el).data('neSyohinSyohinCode');
    this.locationList = $(this.$el).data('locationList');

    var i;

    // ロケーション移動元 初期値取得
    this.storageMoveFrom = new $.Plusnao.Storage.Local($.Plusnao.Const.StorageKey.LOCATION_STORE_IMPORT_PRODUCT_MOVE_FROM);
    var defaultMoveFrom = this.storageMoveFrom.get() ? this.storageMoveFrom.get() : '';

    // 初期値
    if (this.locationList.length > 0) {
      if (defaultMoveFrom.length > 0) {
        for (i = 0; i < this.locationList.length; i++) {
          if (this.locationList[i].locationCode === defaultMoveFrom) {
            this.selectedLocationId = this.locationList[i].locationId;
          }
        }
      }
      // 無ければ先頭
      if (!this.selectedLocationId) {
        this.selectedLocationId = this.locationList[0].locationId;
      }
    }

    // 入力モード
    if (window.location.search && window.location.search.match(/m=input/)) {
      this.inputMode = 'input';
    } else {
      this.inputMode = 'scan';
    }

    // ロケーション入力履歴取得
    this.storageLocationHistory = new $.Plusnao.Storage.Local($.Plusnao.Const.StorageKey.LOCATION_STORE_IMPORT_PRODUCT_LOCATION_HISTORY);
    this.locationHistory = this.storageLocationHistory.get();
    if (!this.locationHistory) {
      this.locationHistory = [];
      this.storageLocationHistory.set(this.locationHistory);
    }

    // ロケーション移動先 初期値取得
    this.storageMoveTo = new $.Plusnao.Storage.Local($.Plusnao.Const.StorageKey.LOCATION_STORE_IMPORT_PRODUCT_MOVE_TO);
    var defaultMoveTo = this.storageMoveTo.get() ? this.storageMoveTo.get() : '';

    // 初期値
    if (this.locationHistory.length > 0) {
      if (defaultMoveTo.length > 0) {
        for (i = 0; i < this.locationHistory.length; i++) {
          if (this.locationHistory[i] === defaultMoveTo) {
            this.moveTo = this.locationHistory[i];
          }
        }
      }
    }

    // 最終確定商品
    this.storageLastSubmittedCode = new $.Plusnao.Storage.Local($.Plusnao.Const.StorageKey.LOCATION_STORE_IMPORT_PRODUCT_LAST_SUBMITTED);

  }

  , watch: {
    selectedLocationId: function() {
      var loc = this.selectedLocation;
      if (loc) {
        this.moveNum = loc.stock;
        this.storageMoveFrom.set(loc.locationCode);
      }
    }

    , moveTo: function() {
        this.storageMoveTo.set(this.moveTo);
    }
  }

  , computed: {
      selectedLocation: function() {
        if (!this.selectedLocationId) {
          return null;
        }

        for (var i = 0; i < this.locationList.length; i++) {
          var loc = this.locationList[i];
          if (loc.locationId == this.selectedLocationId) {
            return loc;
          }
        }
    }
    , selectedLocationStock: function() {
      var loc = this.selectedLocation;
      return loc ? loc.stock : null;
    }

    , formCssMoveTo: function() {
      var ret = null;
      if (this.validation.moveTo === false) {
        return 'has-error';
      }
    }
    , formCssMoveNum: function() {
      var ret = null;
      if (this.validation.moveNum === false) {
        return 'has-error';
      }
    }
    , displayReviewRank: function() {
      let message = '';
      let css = '';
      switch (true) {
        case !this.reviewAverage:
          message = '';
          break;
        case this.reviewAverage >= this.GOOD_SCORE:
          message = '良い';
          css = 'bg-info'
          break;
        case this.reviewAverage >= this.BAD_SCORE:
          message = '普通';
          css = 'bg-info'
          break;
        case this.reviewAverage < this.BAD_SCORE:
          message = '悪い';
          css = 'bg-danger'
          break;
      }
      return {
        message: message ? `${message} (★${this.reviewAverage})` : '',
        css,
      }
    }
  }

  , methods: {

    searchByInputBarcode: function() {
      if (!this.inputBarcode || this.inputBarcode.length < 13) {
        return;
      }

      // 未確定で次の検索チェック
      var submitted = this.storageLastSubmittedCode.get() ? this.storageLastSubmittedCode.get() : '';
      if (submitted !== this.neSyohinSyohinCode) {
        if (!confirm("まだこの商品の箱振りがされていません。\n他の商品を検索してよろしいですか？")) {
          return;
        }
      }

      var url = this.searchUrlBase.replace(/__CODE__/g, this.inputBarcode);
      return window.location.href = this.addModeParam(url);
    }

    , changeInputMode: function(mode) {
      switch (mode) {
        case 'scan':
        case 'input':
          this.inputMode = mode;
          break;
        default:
          this.inputMode = 'scan';
          break;
      }
    }

    , addModeParam: function(url) {
      url += (url.indexOf('?') == -1 ? '?' : '&') + 'm=' + this.inputMode;
      return url;
    }

    , submitMove: function(doCompleteLocation) {
      if (!this.validateMove()) {
        return;
      }

      var self = this;

      var location = self.selectedLocation;
      if (!location) {
        return;
      }

      $.Vendor.WaitingDialog.show();

      var data = {
          syohin_code: location.neSyohinSyohinCode
        , move_from: location.locationCode
        , move_to: self.moveTo
        , move_num: self.moveNum
        , data_hash: self.dataHash
      };

      $.ajax({
          type: "POST"
        , url: self.submitMoveUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            if (doCompleteLocation) {
              // ロケーション履歴 全削除
              self.locationHistory = [];
              self.moveTo = '';
              self.storageLocationHistory.set(self.locationHistory);

            } else {
              // ロケーション履歴 追加
              for (var i = 0; i < self.locationHistory.length; i++) {
                var loc = self.locationHistory[i];
                if (loc == self.moveTo) {
                  self.locationHistory.splice(i, 1); // 削除して後で先頭に追加 = 先頭へ移動。
                  break;
                }
              }
              self.locationHistory.unshift(self.moveTo);
              // 履歴は10件まで
              if (self.locationHistory.length > 10) {
                self.locationHistory = self.locationHistory.slice(0, 10);
              }
            }

            self.storageLocationHistory.set(self.locationHistory);

            // 最終確定商品 更新
            self.storageLastSubmittedCode.set(location.neSyohinSyohinCode);

            window.location.href = self.addModeParam(result.redirect); // リダイレクト

          } else {
            self.setError(result.message);
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.setError('エラー：更新に失敗しました。');
        })
        .always(function() {
          $.Vendor.WaitingDialog.hide();
        });

    }

    , validateMove: function() {
      this.validation.moveTo = this.moveTo.length > 0;
      this.validation.moveNum = this.moveNum > 0;

      return this.isValid();
    }

    , isValid: function() {
      var isValid = true;
      for (var k in this.validation) {
        if (this.validation[k] === false) {
          isValid = false;
          break;
        }
      }

      return isValid;
    }

    //, resetValidation: function() {
    //  this.validation.moveTo = null;
    //  this.validation.moveNum = null;
    //}

    , setError: function(message) {
      alert(message);
    }

    , setHistoryLocationToMoveTo: function(location) {
      this.moveTo = location;
      $('#locationHistoryDropDown', this.$el).dropdown('toggle');
    }

    , reload: function() {
      window.location.reload();
    }

  }
});
