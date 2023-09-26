/** シュシュクレール様 在庫連携画面 JS */
$(function() {

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#header',
    data: {
        message: ''
      , messageCssClass: ''
      , loadingImageUrl: null
    },
    ready: function() {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    },
    methods: {
      setMessage: function(message, cssClass, autoHide) {
        cssClass = cssClass || 'alert alert-info';
        autoHide = autoHide || true;

        this.message = message;
        this.setCssClass(cssClass);

        if (autoHide) {
          setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
        }
      },
      setCssClass: function(cssClass) {
        this.messageCssClass = cssClass;
      },
      clear: function() {
        this.message = '';
        this.messageCssClass = '';
      },
      closeWindow: function() {
        window.close();
      }
    }
  });

  // 絞込フォーム
  var vmFunctionBlock = new Vue({
    el: '#functionBlock',
    data: {

      searchConditions: {
          code: null
        , keyword: null
        , target: null
      }
    },
    ready: function() {
      var self = this;
    },
    methods: {
      /**
       * CSVアップロード 表示ポップアップ
       */
      openCsvUploadModal: function () {
        vmCsvUploadModal.open();
      },

      /**
       * CSVダウンロード 表示ポップアップ
       */
      openCsvDownloadModal: function () {
        vmCsvDownloadModal.open();
      }
    }
  });

  // 商品一覧テーブル 行コンポーネント
  Vue.component('result-item', {
    template: "#result-item",
    props: [
      'item'
    ],
    data: function() {
      return {
        code            : this.item.code,
        name            : this.item.name,
        branchCode      : this.item.branchCode,
        detail          : this.item.detail,
        wholesalePrice  : this.item.wholesalePrice,
        stock           : this.item.stock,
        preStock        : this.item.preStock,
        stockModified   : this.item.stockModified,
        displayOrder    : this.item.displayOrder
      }
    },
    computed: {
      displayWholesalePrice: function() {
        return $.Plusnao.String.numberFormat(this.item.wholesalePrice);
      },
      displayStock: function() {
        return $.Plusnao.String.numberFormat(this.item.stock);
      },
      displayPreStock: function() {
        return $.Plusnao.String.numberFormat(this.item.preStock);
      },
      displayStockModified: function() {
        return this.item.stockModified ? $.Plusnao.Date.getDateTimeString(this.item.stockModified) : '';
      },
      isModified: function() {
        return this.item.stockModified != null;
      },

      isLoading: function() {
        return this.item.nowLoading;
      },

      stockCssClass: function() {
        return (this.item.stockModified != null) ? 'modifiedStock' : '';
      }

    },
    ready: function() {
    },
    methods: {

      /**
       * 在庫を0に
       */
      modifyStockToZero: function(target) {
        var data = {
            code: this.code
          , branch_code: this.branchCode
          , action: 'to_zero'
        };

        this.processModifyStock(data);
      },

      /**
       * 在庫修正を元に戻す
       */
      undoModifyStock: function(target) {
        var data = {
            code: this.code
          , branch_code: this.branchCode
          , action: 'undo'
        };

        this.processModifyStock(data);
      },

      /**
       * 在庫数更新 実処理
       */
      processModifyStock: function(data) {
        var self = this;

        self.item.nowLoading = true;

        // DB更新
        var url = vmChouchouClairStockListTable.updateStockUrl;

        $.ajax({
            type: "POST"
          , url: url
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.valid) {
              // DB更新に成功すれば、JS側親データ・子データの値も更新
              self.item.stock = result.data.stock;
              self.item.stockModified = (result.data.stock_modified ? new Date(result.data.stock_modified.replace(/-/g, "/").replace(/\.\d+.*$/, "")) : null) // replace for firefox, IE;

            } else {
              var message = result.message ? result.message : '在庫数の更新に失敗しました。';
              alert(message);
            }
          })
          .fail(function(stat) {
            alert('在庫数の更新でエラーが発生しました。');
          })
          . always(function() {
            self.item.nowLoading = false;
          });
      }
    }
  });

  // 商品一覧テーブル
  var vmChouchouClairStockListTable = new Vue({
    el: '#chouchouClairStockListTable',
    data: {
        list: []

      , updateStockUrl: null

      // 並び順指定
      , sortField: 'displayOrder'
      , sortOrder: 1
    },
    computed: {
      sortMarks: function() {

        var fields = [
            'code'
          , 'name'
          , 'branchCode'
          , 'detail'
          , 'wholesalePrice'
          , 'stock'
          , 'preStock'
          , 'stockModified'
        ];

        var ret = {};
        var i;
        for (i in fields) {
          ret[fields[i]] = this.getSortMarkCssClass(fields[i]);
        }

        return ret;
      }

    },

    ready: function () {
      var self = this;

      // URL文字列取得
      self.updateStockUrl = $(self.$el).data('updateStockUrl');

      // ソート処理のため、ここで数値変換をしておく。
      var list = [];
      var i;
      for (i in productListData) {
        var item = productListData[i];
        var row = {
            'code'            : item.code
          , 'name'            : item.name
          , 'branchCode'      : item.branch_code
          , 'detail'          : item.detail
          , 'wholesalePrice'  : Number(item.wholesale_price) || 0
          , 'stock'           : Number(item.stock) || 0
          , 'preStock'        : Number(item.pre_stock) || 0
          , 'stockModified'   : (item.stock_modified ? new Date(item.stock_modified.date.replace(/-/g, "/").replace(/\.\d+.*$/, "")) : null) // replace for firefox, IE
          , 'displayOrder'    : Number(i) // 初期並び順

          , 'nowLoading'      : false
        };

        list.push(row);
      }

      self.$set('list', list);

      // tooltip 有効化: th
      $(self.$el).find('th span').tooltip();
    },

    methods: {

      /**
       * ソートアイコンCSSクラス
       */
      getSortMarkCssClass: function(field) {
        return (field == this.sortField)
          ? (this.sortOrder == 1 ? 'fa fa-sort-amount-asc' : 'fa fa-sort-amount-desc' )
          : 'hidden';
      },

      /**
       * 一覧 並び順変更
       * @param fieldName
       */
      switchSort: function(fieldName) {
        if (this.sortField == fieldName) {
          // 降順 -> 昇順
          if (this.sortOrder == -1) {
            this.sortOrder = 1;

            // デフォルトに戻る
          } else {
            this.sortField = 'displayOrder';
            this.sortOrder = 1;
          }

        } else {
          this.sortField = fieldName;
          this.sortOrder = -1; // 降順が先
        }
      }
    }
  });


  /**
   * ポップアップ
   */
  var vmCsvUploadModal = new Vue({
    el: "#modalCsvUpload",
    data: {
        caption: 'アップロードするファイル(.zip ファイル)を選択してください。'
      , message: ''
      , messageClass: 'info'

      , searchConditions: {
          code: null
        , keyword: null
        , target: null
      }

      , nowLoading: true
    },
    ready: function() {
      var self = this;

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        // 検索フォーム 値引き継ぎ
        self.$set('searchConditions', vmFunctionBlock.searchConditions);

        self.nowLoading = false;
      });
    },

    methods: {
      open: function(callbackSuccess) {
        this.callbackSuccess = callbackSuccess;

        self.nowLoading = true;
        $(this.$el).modal('show');
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';

        this.list = [];
      },

      onSubmit: function() {

        var $file = $(this.$el).find('input[type=file]');
        if (!$file.val()) {
          alert('アップロードするファイルが選択されていません。');
          return false;
        }

        this.nowLoading = true;
        this.caption = "アップロード中 ...";
        return true;
      }
    }
  });



  /**
   * ポップアップ
   */
  var vmCsvDownloadModal = new Vue({
    el: "#modalCsvDownload",
    data: {
      caption: '期間を指定してダウンロード'
      , message: ''
      , messageClass: 'info'

      , searchConditions: {
          code: null
        , keyword: null
        , target: null
      }

      , nowLoading: true
    },
    ready: function() {
      var self = this;

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        // 検索フォーム 値引き継ぎ
        self.$set('searchConditions', vmFunctionBlock.searchConditions);

        self.nowLoading = false;
      });
    },

    methods: {
      open: function(callbackSuccess) {
        this.callbackSuccess = callbackSuccess;

        self.nowLoading = true;
        $(this.$el).modal('show');
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';

        this.list = [];
      },

      onSubmit: function() {
        // this.nowLoading = true;
        // this.caption = "ダウンロード中 ...";
        return true;
      }

    }
  });


});

