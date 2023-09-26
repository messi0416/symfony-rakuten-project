/**
 * 管理画面 商品 縦横コード管理 JS
 */

/**
 * 横軸／縦軸並び順確認画面
 */
Vue.component('axis-code-order-form-modal', {
  template: '#templateAxisCodeOrderForm'
  , delimiters: ['(%', '%)']
  , props: [
    'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
  ]

  , data: function () {
    return {
      saveUrl: null
      , messageState: {}
      , nowLoading: false
      , item: {}
    };
  }

  , computed: {
    caption: function () {
      var caption = '軸コード並び順確認画面';
      return caption;
    }
  }

  , watch: {
  }

  , mounted: function () {
    this.$nextTick(function () {
      var self = this;
      var modal = $(self.$el);

      self.messageState = new PartsGlobalMessageState();
      self.saveUrl = $(self.$el).data('saveUrl');

      // イベント登録
      self.$watch('state.show', function (newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });
      self.$watch('state.currentItem', function (newValue) {
        self.item = newValue;
      });

      // -- open前
      modal.on('show.bs.modal', function (e) {
        self.messageState.clear();
      });
      // -- open後
      modal.on('shown.bs.modal', function (e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function (e) {
        if (self.state.show) {
          self.hideModal(); // 外部から閉じられた時の手当
        }
      })
    });
  }
  , methods: {
    hideModal: function () {
      this.state.show = false;
      this.reset();
    }

    , save: function () {
      const self = this;

      self.messageState.clear();
      self.nowLoading = true;

      let axes = [];

      // 横軸優先／縦軸優先で並びかえる
      if (self.item.isAxisColSelected) { // 横軸優先
        for (let col of self.item.colList) {
          for (let row of self.item.rowList) {
            let axis = {};
            axis.daihyoSyohinCode = self.item.daihyoSyohinCode;
            axis.colcode = col.colcode;
            axis.rowcode = row.rowcode;
            axes.push(axis);
          }
        }
      } else { // 縦軸優先
        for (let row of self.item.rowList) {
          for (let col of self.item.colList) {
            let axis = {};
            axis.daihyoSyohinCode = self.item.daihyoSyohinCode;
            axis.colcode = col.colcode;
            axis.rowcode = row.rowcode;
            axes.push(axis);
          }
        }
      }

      $.ajax({
        type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: {
          axes: axes
        }
      })
        .done(function (result) {

          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert-success');
            // 保存に成功したら表示も更新する
            self.$emit('update-axis-order', self.item);
          } else {
            var message = result.message.length > 0 ? result.message : '更新できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        .always(function () {
          self.nowLoading = false;
        });
    }

    , reset: function () {
      this.item = {};
      this.state.currentItem = null;
    }

  }
});


/**
 * 横軸／縦軸追加画面
 */
Vue.component('axis-code-add-form-modal', {
  template: '#templateAxisCodeInsertForm'
  , delimiters: ['(%', '%)']
  , props: [
    'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
  ]

  , data: function () {
    return {
      saveUrl: null
      , messageState: {}
      , nowLoading: false
      , axisCode: ""
      , axisName: ""
      , isAxisColSelected: true
      , daihyoSyohinCode: ""
      , item: {}
    };
  }

  , computed: {
  }

  , watch: {
  }

  , mounted: function () {
    this.$nextTick(function () {
      var self = this;
      var modal = $(self.$el);

      self.messageState = new PartsGlobalMessageState();
      self.saveUrl = $(self.$el).data('saveUrl');

      // イベント登録
      self.$watch('state.show', function (newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });
      self.$watch('state.currentItem', function (newValue) {
        self.item = newValue;
      });
      self.$watch('state.isAxisColSelected', function (newValue) {
        self.isAxisColSelected = newValue;
      });
      self.$watch('state.daihyoSyohinCode', function (newValue) {
        self.daihyoSyohinCode = newValue;
      });

      // -- open前
      modal.on('show.bs.modal', function (e) {
        self.messageState.clear();
      });
      // -- open後
      modal.on('shown.bs.modal', function (e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function (e) {
        if (self.state.show) {
          self.hideModal(); // 外部から閉じられた時の手当
        }
      })
    });
  }
  , methods: {
    hideModal: function () {
      this.state.show = false;
      this.reset();
    }

    , save: function () {
      const self = this;

      self.messageState.clear();
      self.nowLoading = true;

      // 軸コードprefixとして'-'を追加
      let axisCodeWithPrefix = '-' + self.axisCode;

      // 入力チェック
      var isValid = true;
      if (!self.axisCode || self.axisCode.length == 0) {
        self.messageState.setMessage('コードが指定されていない行があります。', 'alert-warning');
        isValid = false;
      }
      if (!self.axisName || self.axisName.length == 0) {
        self.messageState.setMessage('項目名が指定されていない行があります。', 'alert-warning');
        isValid = false;
      }
      // 既存重複チェック
      if (self.isAxisColSelected) { // 横軸
        for (let col of self.item.colList) {
          if (col.colcode === axisCodeWithPrefix || col.colname === self.axisName) {
            self.messageState.setMessage('コードまたは項目名が既存と重複しています', 'alert-warning');
            isValid = false;
            break;
          }
        }
      } else { // 縦軸
        for (let row of self.item.rowList) {
          if (row.rowcode === axisCodeWithPrefix || row.rowname === self.axisName) {
            self.messageState.setMessage('コードまたは項目名が既存と重複しています', 'alert-warning');
            isValid = false;
            break;
          }
        }
      }


      if (!isValid) {
        self.nowLoading = false;
        return;
      }

      if (!confirm('このデータで保存してよろしいですか？')) {
        self.nowLoading = false;
        return;
      }


      let colList = [];
      let rowList = [];
      if (self.isAxisColSelected) {
        let content = {};
        content.daihyoSyohinCode = self.daihyoSyohinCode;
        content.colcode = axisCodeWithPrefix;
        content.colname = self.axisName;
        colList.push(content);
      } else {
        let content = {};
        content.daihyoSyohinCode = self.daihyoSyohinCode;
        content.rowcode = axisCodeWithPrefix;
        content.rowname = self.axisName;
        rowList.push(content);
      }

      $.ajax({
        type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: {
          colList: colList
          , rowList: rowList
        }
      })
        .done(function (result) {

          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert-success');
            // 保存に成功したら親画面をリロード
            self.$emit('refresh-axis-code-list');
          } else {
            var message = result.message.length > 0 ? result.message : '追加できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        .always(function () {
          self.nowLoading = false;
        });
    }

    , reset: function () {
      this.axisCode = "";
      this.axisName = "";
      this.item = {};
      this.state.currentItem = null;
    }

  }
});

/**
 * 横軸／縦軸削除確認画面
 */
Vue.component('axis-code-del-form-modal', {
  template: '#templateAxisCodeDeleteForm'
  , delimiters: ['(%', '%)']
  , props: [
    'state' // { show: true|false, eventOnChoiceProduct: null|event-name }
  ]

  , data: function () {
    return {
      saveUrl: null
      , messageState: {}
      , nowLoading: false
      , item: {}
    };
  }

  , computed: {
    caption: function () {
      var caption = '軸削除確認画面';
      return caption;
    }
  }

  , watch: {
  }

  , mounted: function () {
    this.$nextTick(function () {
      var self = this;
      var modal = $(self.$el);

      self.messageState = new PartsGlobalMessageState();
      self.saveUrl = $(self.$el).data('saveUrl');

      // イベント登録
      self.$watch('state.show', function (newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });
      self.$watch('state.currentItem', function (newValue) {
        self.item = newValue;
      });

      // -- open前
      modal.on('show.bs.modal', function (e) {
        self.messageState.clear();
      });
      // -- open後
      modal.on('shown.bs.modal', function (e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function (e) {
        if (self.state.show) {
          self.hideModal(); // 外部から閉じられた時の手当
        }
      })
    });
  }
  , methods: {
    hideModal: function () {
      this.state.show = false;
      this.reset();
    }

    , save: function () {
      const self = this;

      self.messageState.clear();
      self.nowLoading = true;

      let col = {};
      let row = {};
      if (self.item.isAxisColSelected) {
        col.daihyoSyohinCode = self.item.daihyoSyohinCode;
        col.colcode = self.item.colList[0].colcode;
      } else {
        row.daihyoSyohinCode = self.item.daihyoSyohinCode;
        row.rowcode = self.item.rowList[0].rowcode;
      }

      $.ajax({
        type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: {
          col: col
          , row: row
        }
      })
        .done(function (result) {

          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert-success');
            // 保存に成功したら親画面をリロード
            self.$emit('refresh-axis-code-list');
          } else {
            var message = result.message.length > 0 ? result.message : '削除できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        .always(function () {
          self.nowLoading = false;
        });
    }

    , reset: function () {
      this.item = {};
      this.state.currentItem = null;
    }

  }
});


/** メイン画面 */
var axisCode = new Vue({
  el: '#axisCode',
  data: {
    skuCode: "", // 検索用の代表商品コード
    searchUrl: null, // 検索URL
    updateUrl: null, // 保存URL
    messageState: {}, // エラーメッセージ
    product: null,
    copyProduct: null, // 差分表示用
    isUpdatable: true,
    isColModified: {},
    isRowModified: {},

    modalState: {
      message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    },
    addModalState: {// 軸追加用
      message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
      , isAxisColSelected: true
      , daihyoSyohinCode: ''
    },
    delModalState: {// 軸削除用
      message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  },
  mounted: function () {
    this.$nextTick(function () {
      const self = this;

      // URL取得
      self.searchUrl = $(self.$el).data('searchUrl');
      self.updateUrl = $(self.$el).data('updateUrl');

      self.messageState = new PartsGlobalMessageState();

      if (SEARCH_CODE) {
        self.skuCode = SEARCH_CODE;
        self.search();
      }

    });
  },
  methods: {

    showColOrderModal: function () {
      var self = this;
      // 表示と並び換えデータを分離するためにJSONオブジェクトをコピー
      var copyProduct = self.copyProductJson();

      copyProduct.colList.sort((a, b) => {
        return a.並び順No - b.並び順No;
      })

      self.modalState.currentItem = copyProduct;
      self.modalState.show = true;
      self.modalState.currentItem.isAxisColSelected = true; // 横軸優先
    },
    showRowOrderModal: function () {
      var self = this;
      // 表示と並び換えデータを分離するためにJSONオブジェクトをコピー
      var copyProduct = self.copyProductJson();

      copyProduct.rowList.sort((a, b) => {
        return a.並び順No - b.並び順No;
      })

      self.modalState.currentItem = copyProduct;
      self.modalState.show = true;
      self.modalState.currentItem.isAxisColSelected = false; // 縦軸優先
    },
    showAddColModal: function () {
      var self = this;
      // チェック用にJSONオブジェクトをコピー
      var copyProduct = self.copyProductJson();
      self.addModalState.currentItem = copyProduct;
      self.addModalState.show = true;
      self.addModalState.isAxisColSelected = true; // 横軸選択
      self.addModalState.daihyoSyohinCode = self.skuCode;
    },
    showAddRowModal: function () {
      var self = this;
      // チェック用にJSONオブジェクトをコピー
      var copyProduct = self.copyProductJson();
      self.addModalState.currentItem = copyProduct;
      self.addModalState.show = true;
      self.addModalState.isAxisColSelected = false; // 横軸非選択
      self.addModalState.daihyoSyohinCode = self.skuCode;
    },
    showDelColModal: function (colcode) {
      var self = this;
      // 表示と並び換えデータを分離するためにJSONオブジェクトをコピー
      var copyProduct = self.copyProductJson();

      // 削除対象skuのみのリストを作成
      copyProduct.colList = copyProduct.colList.filter(item => {
        return item.colcode === colcode;
      });

      self.delModalState.currentItem = copyProduct;
      self.delModalState.show = true;
      self.delModalState.currentItem.isAxisColSelected = true; // 横軸選択
    },
    showDelRowModal: function (rowcode) {
      var self = this;
      // 表示と並び換えデータを分離するためにJSONオブジェクトをコピー
      var copyProduct = self.copyProductJson();

      // 削除対象skuのみのリストを作成
      copyProduct.rowList = copyProduct.rowList.filter(item => {
        return item.rowcode === rowcode;
      });

      self.delModalState.currentItem = copyProduct;
      self.delModalState.show = true;
      self.delModalState.currentItem.isAxisColSelected = false; // not横軸選択
    },

    // 検索
    search: function () {
      var self = this;
      self.messageState.clear();
      self.product = null;
      self.isUpdatable = true;

      // Show loading
      $.Vendor.WaitingDialog.show();

      $.ajax({
        type: "GET",
        url: self.searchUrl,
        dataType: "json",
        data: {
          code: self.skuCode,
        },
      })
        .done(function (result) {
          if (result.status == 'ok') {
            self.updateUrlByCode(self.skuCode);
            self.messageState.setMessage(result.message, 'alert alert-danger');
            self.isUpdatable = !result.message;
            self.product = result.product || null;
            // 変更箇所表示をOFFに設定
            self.clearModified();
          } else {
            var message = result.message ? result.message : '検索でエラーが発生しました';
            self.isUpdatable = false;
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
        })
        .always(function () {
          // Hide loading
          $.Vendor.WaitingDialog.hide();
        });
    },
    updateUrlByCode: function (code) {
      var url = window.location.href;
      var urlParts = url.split('?');
      if (urlParts.length > 0) {
        var baseUrl = urlParts[0];
        var vars = {}, hash;
        if (url.indexOf('?') >= 0 && url.indexOf('?') + 1 !== url.length) {
          var hashes = url.substr(url.indexOf('?') + 1).split('&');
          for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            vars[hash[0]] = hash[1];
          }
        }
        vars['code'] = code || '';

        var updatedQueryStrArray = [];
        for (key of Object.keys(vars)) {
          updatedQueryStrArray.push(key + '=' + vars[key]);
        }
        var updatedUri = baseUrl + '?' + updatedQueryStrArray.join('&');
        window.history.replaceState({}, document.title, updatedUri);
      }
    },
    // 保存
    update: function () {
      var self = this;
      this.messageState.clear();
      // 更新されたレコードのみsku_numberをNULLに設定
      let paramColList = [...[], ...self.product.colList]
      let paramRowList = [...[], ...self.product.rowList]
      paramColList = paramColList.map(col => {
        if (this.hasColModified(col.colcode, 'colname') || this.hasColModified(col.colcode, 'colname_en') || this.hasColModified(col.colcode, 'support_colname')) col.sku_number = undefined

        return col
      })
      paramRowList = paramRowList.map(row => {
        if (this.hasRowModified(row.rowcode, 'rowname') || this.hasRowModified(row.rowcode, 'rowname_en') || this.hasRowModified(row.rowcode, 'support_rowname')) row.sku_number = undefined

        return row
      })
      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: {
          colList: paramColList,
          rowList: paramRowList,
          daihyoSyohinCode: self.product.daihyoSyohinCode,
        }
      })
        .done(function (result) {
          if (result.status == 'ok') {
            self.messageState.setMessage(result.message, 'alert-success');
            // 変更箇所表示をOFFに設定
            self.clearModified();
          } else {
            var message = result.message ? result.message : '更新でエラーが発生しました';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
        })
    },
    // 並び順更新（画面のみ）
    updateAxisOrder: function (item) {
      var self = this;

      self.product = item;
    },
    // 横軸値が変更されたタイミングで、コピーしておいた変更前の値と比較する
    changeAxisColValue: function (event, colcode, colItem) {
      var self = this;
      const value = event.target.value;

      // 差分確認
      self.checkAxisColTextDiff(value, colcode, colItem);
    },
    checkAxisColTextDiff: function (value, colcode, colItem) {
      var self = this;

      const currentColData = self.copyProduct.colList.find((data) => data.colcode === colcode);
      self.isColModified[colcode][colItem] = !(String(value) === String(currentColData[colItem]));
    },
    hasColModified: function (colcode, colItem) {
      return this.isColModified[colcode]?.[colItem] ?? false;
    },
    // 縦軸値が変更されたタイミングで、コピーしておいた変更前の値と比較する
    changeAxisRowValue: function (event, rowcode, rowItem) {
      var self = this;
      const value = event.target.value;

      // 差分確認
      self.checkAxisRowTextDiff(value, rowcode, rowItem);
    },
    checkAxisRowTextDiff: function (value, rowcode, rowItem) {
      var self = this;

      const currentRowData = self.copyProduct.rowList.find((data) => data.rowcode === rowcode);
      self.isRowModified[rowcode][rowItem] = !(String(value) === String(currentRowData[rowItem]));
    },
    hasRowModified: function (rowcode, rowItem) {
      return this.isRowModified[rowcode]?.[rowItem] ?? false;
    },
    clearModified: function () {
      var self = this;

      // 差分表示用に検索した時点のデータをコピーしておく
      self.copyProduct = self.copyProductJson();
      // 変更箇所表示をOFFに設定
      self.isColModified = {};
      self.isRowModified = {};

      for (let col of self.product.colList) {
        let flags = { colname: false, colname_en: false, support_colname: false };
        self.isColModified[col.colcode] = flags;
      }

      for (let row of self.product.rowList) {
        let flags = { rowname: false, rowname_en: false, support_rowname: false };
        self.isRowModified[row.rowcode] = flags;
      }
    },
    copyProductJson: function () {
      var self = this;
      // 差分表示用にオブジェクトのコピーを取得する
      return Object.assign({}, JSON.parse(JSON.stringify(self.product)));
    },
    reloadPage() {
      location.reload()
    }
  },
});