// 初期処理
$(function () {
  // トップページ表示時 タブ選択
  var defaultTarget = '#tabLocationMoveList';
  var hashTabName = document.location.hash;
  var targetNav = null;
  if (! hashTabName) {
    hashTabName = defaultTarget;
  }
  targetNav = $('.nav-tabs a[href=' + hashTabName + ']');
  if (targetNav.size() > 0) {
    targetNav.tab('show');
  }

  // タブ変更時イベント（ハッシュをつけるだけ）
  $("#indexNavTab a[data-toggle=tab]").on("shown.bs.tab", function (e) {
    document.location.hash = $(e.currentTarget).attr('href');
    $('html, body').stop().animate({
      scrollTop: 0
    }, 0);
  });
});

/**
 * ロケーション一眼画面用 JS
 * Vue 1.x
 */
/**
 * 一覧ブロック 行コンポーネント
 */

Vue.component('result-item', {
  template: "#result-item",
  props: [
      'item'
    , 'currentWarehouseId'
  ],
  data: function() {
    return {
      locationDetailUrlBase: ''
    };
  },
  computed: {
    isChecked: function() {
      return this.item.checked;
    }
    , checkedCss: function() {
      return this.isChecked ? 'fa-check-square-o' : 'fa-square-o';
    }
    , locationDetailUrl: function() {
      return this.locationDetailUrlBase.replace(/__DUMMY__/g, this.item.id);
    }
    , isCurrentWarehouse: function() {
      return this.item.warehouseId == this.currentWarehouseId;
    }
  },
  ready: function() {
    this.locationDetailUrlBase = $(this.$el).data('locationDetailUrlBase');
  },
  methods: {
    //openDetailModal: function() {
    //  this.$emit('open-detail', this.item);
    //}

    toggleCheck: function() {
      this.item.checked = !(this.item.checked);
    }
  }
});


/**
 * 検索フォーム・一覧テーブル
 */
var vmLocationList = new Vue({
  el: "#locationList"
  , data: {
      currentWarehouseId: null /* 原則、これ以外のロケーションは触れないとする。リンクもひとまずは無効 */
    , searchWarehouse: null
    , searchKeywordLocation: ''
    , searchLikeMode: 'full'
    , searchStockMin: null
    , searchStockMax: null

    , locations: []
    , warehouses: []

    , sortStock: null
    , sortWarehouse: null
    , sortLocationCode: null

    , checkAll: false

    , locationWarehouses: [] // 同一倉庫チェック用
    , warehouseMoveTo: null  // 移動先倉庫ID
    , mergeTargetId: null // 統合先ロケーションコード
    , mergeTargetMessage: null // 統合チェックエラーメッセージ

    , changeRackMessage: null
    , changeRackMessageCss: null
    , changeRackFrom: null
    , changeRackTo: null
    , changeRackTargetCount: 0
    , changeRackDuplicateCount: 0

    , removeRackCodeMessage: null
    , removeRackCodeMessageCss: null
    , removeRackCodeValidation: null
    , removeRackCodeList: []
    , removeRackCodeDuplicated: []

    , url: null
    , warehouseMoveUrl: null
    , mergeUrl: null
    , validateChangeRackUrl: null
    , changeRackUrl: null
    , validateRemoveRackCodeUrl: null
    , removeRackCodeUrl: null

  }
  , ready: function() {
    this.url = $(this.$el).data('url');
    this.warehouseMoveUrl = $(this.$el).data('warehouseMoveUrl');
    this.mergeUrl = $(this.$el).data('mergeUrl');
    this.validateChangeRackUrl = $(this.$el).data('validateChangeRackUrl');
    this.changeRackUrl = $(this.$el).data('changeRackUrl');
    this.validateRemoveRackCodeUrl = $(this.$el).data('validateRemoveRackCodeUrl');
    this.removeRackCodeUrl = $(this.$el).data('removeRackCodeUrl');

    this.currentWarehouseId = CURRENT_WAREHOUSE_ID;

    if (typeof(SEARCH_PARAMS) != 'undefined') {
      this.searchWarehouse = SEARCH_PARAMS.warehouseId;
      this.searchKeywordLocation = SEARCH_PARAMS.keyword;
      this.searchLikeMode = SEARCH_PARAMS.mode;
      this.searchStockMin = SEARCH_PARAMS.stockMin.length ? Number(SEARCH_PARAMS.stockMin) : null;
      this.searchStockMax = SEARCH_PARAMS.stockMax.length ? Number(SEARCH_PARAMS.stockMax) : null;
    }

    if (typeof(SORT_PARAMS) != 'undefined') {
      this.sortStock = SORT_PARAMS.stock;
      this.sortWarehouse = SORT_PARAMS.warehouse;
      this.sortLocationCode = SORT_PARAMS.locationCode;
    }

    if (typeof(WAREHOUSES) != 'undefined') {
      this.warehouses = WAREHOUSES;
    }

    this.locations = [];
    if (typeof(LOCATIONS) != 'undefined') {
      for (var i = 0; i < LOCATIONS.length; i++) {
        var row = LOCATIONS[i];
        row.checked = false;
        this.locations.push(row);
      }
    }
  }
  , computed: {
    sortCssStock: function() {
      var css = 'hidden';
      switch (this.sortStock) {
        case 'asc':
          css = 'fa-sort-amount-asc';
          break;
        case 'desc':
          css = 'fa-sort-amount-desc';
          break;
      }
      return css;
    }

    , sortCssWarehouse: function() {
      var css = 'hidden';
      switch (this.sortWarehouse) {
        case 'asc':
          css = 'fa-sort-amount-asc';
          break;
        case 'desc':
          css = 'fa-sort-amount-desc';
          break;
      }
      return css;
    }
    , sortCssLocationCode: function() {
      var css = 'hidden';
      switch (this.sortLocationCode) {
        case 'asc':
          css = 'fa-sort-amount-asc';
          break;
        case 'desc':
          css = 'fa-sort-amount-desc';
          break;
      }
      return css;
    }
    , checkAllButtonCss: function() {
      return this.checkAll ? 'fa-check-square-o' : 'fa-square-o';
    }

    , checkedCode: function() {
      return this.locations.slice().filter(function(row) {
        return row.checked;
      });
    }

    , orderedCheckedCode: function() {
      return this.checkedCode.sort(function(a, b) {
        // 倉庫
        if (a.warehouseId < b.warehouseId) {
          return -1;
        } else if (a.warehouseId > b.warehouseId) {
          return 1;
        }

        // ロケーションコード
        if (a.locationCode < b.locationCode) {
          return -1;
        } else if (a.locationCode > b.locationCode) {
          return 1;
        } else {
          return 0;
        }
      });
    }

    , checkedFirstLocation: function() {
      return this.orderedCheckedCode.length > 0 ? this.orderedCheckedCode[0] : {};
    }


    , isValidChangeRackCode: function() {
      var reValidRackCode = /^[A-Z]{1,}[0-9]{0,4}$/;

      if (!this.changeRackFrom || ! reValidRackCode.test(this.changeRackFrom)) {
        return false;
      }

      if (!this.changeRackTo || ! reValidRackCode.test(this.changeRackTo)) {
        return false;
      }

      return true;
    }

    , isValidChangeRack: function() {
      var isValid = true;

      if (this.changeRackTargetCount == 0) {
        isValid = false;
      }
      if (this.changeRackDuplicateCount > 0) {
        isValid = false;
      }

      if (!this.isValidChangeRackCode) {
        isValid = false;
      }

      return isValid;
    }


    , isValidRemoveRackCode: function() {
      return this.removeRackCodeValidation;
    }

  }

  , methods: {
      isCurrentWarehouse: function(warehouseId) {
        return this.currentWarehouseId == warehouseId;
    }
    , clearSearchCondition: function () {
      this.searchKeywordLocation = '';
      this.searchStockMin = '';
      this.searchStockMax = '';
    }

    , search: function() {

      vmGlobalMessage.clear();
      vmGlobalMessage.clearFlashMessage();

      var url = this.url.replace(/\/$/, "") + '/1';
      if (this.searchKeywordLocation && this.searchKeywordLocation.length > 0) {
        url = url + '/' + this.searchKeywordLocation;
      }

      var addParams = [];

      if (this.searchWarehouse && this.searchWarehouse.toString().length > 0) {
        addParams.push('warehouse_id=' + this.searchWarehouse.toString());
      }
      if (this.searchLikeMode && this.searchLikeMode.length > 0) {
        addParams.push('mode=' + this.searchLikeMode);
      }
      if (this.searchStockMin !== null) {
        addParams.push('stock_min=' + this.searchStockMin);
      }
      if (this.searchStockMax !== null) {
        addParams.push('stock_max=' + this.searchStockMax);
      }

      if (this.sortStock && this.sortStock.length) {
        addParams.push('o=stock&od=' + this.sortStock);
      }
      if (this.sortWarehouse && this.sortWarehouse.length) {
        addParams.push('o=warehouse&od=' + this.sortWarehouse);
      }
      if (this.sortLocationCode && this.sortLocationCode.length) {
        addParams.push('o=locationCode&od=' + this.sortLocationCode);
      }

      if (addParams.length > 0) {
        url = url + '?' + '&' + new Date().getTime() + addParams.join('&');
      }

      window.location.href = window.location.origin + url;
      return true;
    }

    , toggleSort: function(field) {

      var target;
      var current;
      switch(field) {
        case 'stock':
          target = 'sortStock';
          current = this.sortStock;
          break;
        case 'warehouse':
          target = 'sortWarehouse';
          current = this.sortWarehouse;
          break;
        case 'locationCode':
          target = 'sortLocationCode';
          current = this.sortLocationCode;
          break;
        default:
          return;
      }

      // リセット
      this.sortStock = null;
      this.sortWarehouse = null;
      this.sortLocationCode = null;

      switch(current) {
        case 'asc':
          this.$set(target, 'desc');
          break;
        case 'desc':
          this.$set(target, null);
          break;
        default:
          this.$set(target, 'asc');
          break;
      }

      this.search();
    }

    , toggleCheckAll: function() {
      this.checkAll = ! (this.checkAll);

      for (var i = 0; i < this.locations.length; i++) {

        this.locations[i].checked = this.checkAll;
      }
    }

    // 倉庫移動プルダウン
    , getWarehousesWithoutCurrent: function(currentId) {
      return this.warehouses.filter(function(ele) {
        return (!currentId || currentId != ele.id);
      });
    }

    /// ロケーション統合、棚番号一括変換 処理前チェック
    , validateSameWarehouseCode: function() {
      if (this.checkedCode.length <= 0) {
        return false;
      }

      var firstWarehouseId = null;
      for (var i = 0; i < this.checkedCode.length; i++) {
        var location = this.checkedCode[i];
        if (!firstWarehouseId) {
          firstWarehouseId = location.warehouseId;
        }
        if (firstWarehouseId != location.warehouseId) {
          return false;
        }
      }

      return true;
    }

    /// 倉庫移動 確認
    , moveWarehouseConfirm: function() {
      // 同一倉庫のロケーションのみがチェックされているか。
      if (! this.validateSameWarehouseCode()) {
        alert('ロケーションがチェックされていないか、別倉庫のロケーションが混ざっています。');
        return;
      }

      $('#modalMoveWarehouse', this.$el).modal().show();
    }

    /// 倉庫移動 実行
    , moveWarehouseSubmit: function() {
      var self = this;

      // 更新処理・結果表示処理
      // Show loading
      $.Vendor.WaitingDialog.show('更新中 ...');

      vmGlobalMessage.clear();
      vmGlobalMessage.clearFlashMessage();

      var data = {
          locations: self.checkedCode
        , moveTo: self.warehouseMoveTo
      };

      $.ajax({
          type: "POST"
        , url: self.warehouseMoveUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            alert(result.message);
            self.search();

          } else {
            alert(result.message);
          }
        })
        .fail(function(stat) {
          console.log(stat);
          alert('エラー：更新に失敗しました。');
        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });
    }


    /// ロケーション統合 確認
    , mergeLocationConfirm: function() {

      // 同一倉庫のロケーションのみがチェックされているか。
      if (! this.validateSameWarehouseCode()) {
        alert('ロケーションがチェックされていないか、別倉庫のロケーションが混ざっています。');
        return;
      }

      // 必要最小限のチェック
      // 選択数が2以上
      // ロケーションコードが存在
      // 各ロケーションコードが-で3パートに分かれている場合は、棚番号と箱番号は2文字以上で棚番号の2文字目以降は数字
      //
      // ロケーションコードのいずれか1つでも3パートに分かれていない場合は、エラーチェックをスキップする（正常メッセージを表示する）
      let skipCheck = false;
      if (this.orderedCheckedCode.length < 2) {
        return;
      }
      for (let i = 0; i < this.orderedCheckedCode.length; i++) {
        const c = this.orderedCheckedCode[i];
        if (!c.locationCode) {
          return;
        }
        const parts = c.locationCode.split("-");
        if (parts.length < 3) {
          skipCheck = true;
          break;
        }

        if (parts[0].length < 2 || parts[2].length < 2) {
          return;
        }
        const num = +parts[0].substring(1);
        if (isNaN(num)) {
          return;
        }
      }

      // 警告メッセージはnull（チェックOK）で初期化
      this.mergeTargetMessage = null;

      if (!skipCheck) {
        // orderedCheckedCodeでロケーションコード順になっているので、一番離れているもの同士で比較する
        // （this.validateSameWarehouseCode()で同一倉庫は保証されている）
        const cFirst = this.orderedCheckedCode[0];
        const cLast = this.orderedCheckedCode[
          this.orderedCheckedCode.length - 1
        ];
        const firstLocationParts = cFirst.locationCode.split("-");
        const lastLocationParts = cLast.locationCode.split("-");

        // 警告メッセージ（離れた棚）
        const ERROR_RACK =
          "離れた棚が選択されています。統合してもよろしいですか？";

        // 警告メッセージ（異なる箱番号）
        const ERROR_BOX =
          "箱番号の頭のアルファベットが違います。統合してもよろしいですか？";

        // 先頭のアルファベットが違う場合
        if (
          firstLocationParts[0].substring(0, 1) !==
          lastLocationParts[0].substring(0, 1)
        ) {
          this.mergeTargetMessage = ERROR_RACK;
        }

        if (!this.mergeTargetMessage) {
          // 先頭のアルファベットが同じで棚番号が2つ以上離れている場合
          const num1 = +firstLocationParts[0].substring(1);
          const num2 = +lastLocationParts[0].substring(1);
          if (Math.abs(num1 - num2) >= 2) {
            this.mergeTargetMessage = ERROR_RACK;
          }
        }

        if (!this.mergeTargetMessage) {
          // 箱番号の先頭のアルファベットが違う場合
          const boxPreFirst = firstLocationParts[2].substring(0, 1);
          for (let i = 1; i < this.orderedCheckedCode.length; i++) {
            const parts = this.orderedCheckedCode[i].locationCode.split("-");
            if (boxPreFirst !== parts[2].substring(0, 1)) {
              this.mergeTargetMessage = ERROR_BOX;
            }
          }
        }
      }
      
      this.mergeTargetId = this.orderedCheckedCode[0].id;
      $('#modalMergeLocationConfirm', this.$el).modal().show();
    }

    , mergeLocationSubmit: function() {
      var self = this;

      // 更新処理・結果表示処理
      // Show loading
      $.Vendor.WaitingDialog.show('更新中 ...');

      vmGlobalMessage.clear();
      vmGlobalMessage.clearFlashMessage();

      var data = {
          locations: self.checkedCode
        , merge_target: self.mergeTargetId
      };

      $.ajax({
          type: "POST"
        , url: self.mergeUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            self.search();

          } else {
            alert(result.message);
          }
        })
        .fail(function(stat) {
          console.log(stat);

          alert('エラー：更新に失敗しました。');
        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });

    }

    , clickRadio: function(event) {
      $('input[type=radio]', event.target).trigger('click');
    }


    /// 棚番号一括変更 ポップアップ
    , changeRackOpen: function() {

      // 同一倉庫のロケーションのみがチェックされているか。
      if (! this.validateSameWarehouseCode()) {
        alert('ロケーションがチェックされていないか、別倉庫のロケーションが混ざっています。');
        return;
      }

      // フォームリセット
      this.changeRackMessage = '変更元と変更先の棚番号を入力し、「確認」を押してください。';
      this.changeRackMessageCss = 'alert-info';
      this.changeRackFrom = '';
      this.changeRackTo = '';
      this.changeRackTargetCount = 0;
      this.changeRackDuplicateCount = 0;

      $('#modalChangeRackConfirm', this.$el).modal().show();
    }

    // 棚番号一括変更 確認
    , changeRackConfirm: function() {
      var self = this;

      if (! this.isValidChangeRackCode) {
        this.changeRackMessage = '変更元と変更先の棚番号が正しくありません。修正して「確認」を押して下さい。';
        this.changeRackMessageCss = 'alert-warning';
        return;
      }

      // 更新処理・結果表示処理
      // Show loading
      $.Vendor.WaitingDialog.show('更新中 ...');

      var targetIds = [];
      for (var i = 0; i < self.checkedCode.length; i++) {
        targetIds.push(self.checkedCode[i].id);
      }

      var data = {
          from: this.changeRackFrom
        , to: this.changeRackTo
        , targets: targetIds
      };

      $.ajax({
          type: "POST"
        , url: self.validateChangeRackUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.changeRackTargetCount = result.fromCount;
            self.changeRackDuplicateCount = result.toCount;

            if (self.isValidChangeRack) {
              self.changeRackMessage = '棚番号の一括更新を行いますか？';
              self.changeRackMessageCss = 'alert-info';

            } else {
              self.changeRackMessage = '変更元が存在しない、あるいは変更先の重複などのエラーがあります。';
              self.changeRackMessageCss = 'alert-danger';

            }

          } else {

            self.changeRackMessage = result.message;
            self.changeRackMessageCss = 'alert-danger';
          }
        })
        .fail(function(stat) {
          console.log(stat);

          self.changeRackMessage = '棚番号の確認に失敗しました。';
          self.changeRackMessageCss = 'alert-danger';

        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });
    }

    , changeRackSubmit: function() {
      var self = this;

      if (! this.isValidChangeRack) {
        this.changeRackMessage = '現在の設定にエラーがあります。変更元と変更先を確認してください。';
        this.changeRackMessageCss = 'alert-danger';
        return;
      }

      // 更新処理・結果表示処理
      // Show loading
      $.Vendor.WaitingDialog.show('更新中 ...');

      var targetIds = [];
      for (var i = 0; i < self.checkedCode.length; i++) {
        targetIds.push(self.checkedCode[i].id);
      }

      var data = {
          from: this.changeRackFrom
        , to: this.changeRackTo
        , targets: targetIds
      };

      $.ajax({
          type: "POST"
        , url: self.changeRackUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            self.search();

          } else {
            alert(result.message);
            // vmGlobalMessage.setMessage(result.message, 'alert-danger');
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          }
        })
        .fail(function(stat) {
          console.log(stat);

          alert('エラー：更新に失敗しました。');

          // vmGlobalMessage.setMessage('エラー：更新に失敗しました。', 'alert-danger');
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        })
        .always(function() {
        });

    }


    /// 棚番号・位置コード一括削除 ポップアップ
    , removeRackCodeOpen: function() {

      // 同一倉庫のロケーションのみがチェックされているか。
      if (! this.validateSameWarehouseCode()) {
        alert('ロケーションがチェックされていないか、別倉庫のロケーションが混ざっています。');
        return;
      }

      // フォームリセット
      this.removeRackCodeMessage = '下記ロケーションの棚番号・位置コードを一括で削除し、箱番号のみにします。' + "\n\n" + '実行してよろしいですか？';
      this.removeRackCodeMessageCss = 'alert-info';
      this.removeRackCodeValidation = null;
      this.removeRackCodeList = [];
      this.removeRackCodeDuplicated = [];


      $('#modalRemoveRackCodeConfirm', this.$el).modal().show();
    }


    // 棚番号・位置コード一括削除 確認
    , removeRackCodeConfirm: function() {
      var self = this;

      // 更新処理・結果表示処理
      // Show loading
      $.Vendor.WaitingDialog.show('更新チェック中 ...');

      var targetIds = [];
      for (var i = 0; i < self.checkedCode.length; i++) {
        targetIds.push(self.checkedCode[i].id);
      }

      var data = {
         targets: targetIds
      };

      $.ajax({
          type: "POST"
        , url: self.validateRemoveRackCodeUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          console.log(result);
          self.removeRackCodeList = result.list;
          self.removeRackCodeDuplicated = result.duplicated;

          if (result.status == 'ok') {
            self.removeRackCodeValidation = true;

            if (self.isValidRemoveRackCode) {
              self.removeRackCodeMessage = '棚番号・位置コードの一括削除を行いますか？';
              self.removeRackCodeMessageCss = 'alert-info';

            } else {
              self.removeRackCodeMessage = '何らかのエラーがあります。';
              self.removeRackCodeMessageCss = 'alert-danger';

            }

          } else {

            self.removeRackCodeMessage = result.message;
            self.removeRackCodeMessageCss = 'alert-danger';
          }
        })
        .fail(function(stat) {
          console.log(stat);

          self.removeRackCodeMessage = '棚番号・位置コード削除の確認に失敗しました。';
          self.removeRackCodeMessageCss = 'alert-danger';

        })
        .always(function() {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });
    }

    , removeRackCodeSubmit: function() {
      var self = this;

      if (! this.isValidRemoveRackCode) {
        this.removeRackCodeMessage = '現在の設定にエラーがあります。';
        this.removeRackCodeMessageCss = 'alert-danger';
        return;
      }

      // 更新処理・結果表示処理
      // Show loading
      $.Vendor.WaitingDialog.show('更新中 ...');

      var targetIds = [];
      for (var i = 0; i < self.checkedCode.length; i++) {
        targetIds.push(self.checkedCode[i].id);
      }

      var data = {
        targets: targetIds
      };

      $.ajax({
          type: "POST"
        , url: self.removeRackCodeUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {
          if (result.status == 'ok') {

            self.search();

          } else {
            alert(result.message);
            // vmGlobalMessage.setMessage(result.message, 'alert-danger');
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          }
        })
        .fail(function(stat) {
          console.log(stat);

          alert('エラー：更新に失敗しました。');

          // vmGlobalMessage.setMessage('エラー：更新に失敗しました。', 'alert-danger');
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        })
        .always(function() {
        });

    }

    , findRemoveRackCodeResult: function(locationCode) {
      var result = '';
      if (!this.removeRackCodeList) {
        return result;
      }

      for (var i = 0; i < this.removeRackCodeList.length; i++) {
        var item = this.removeRackCodeList[i];
        if (item['location_code'] == locationCode) {
          result = item['box_code'];
          break;
        }
      }

      return result;
    }

  }
});
