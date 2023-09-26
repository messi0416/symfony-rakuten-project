/**
 * モール価格一覧 画面
 */
$(function () {

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#globalMessage',
    data: {
      message: ''
      , messageCssClass: ''
      , loadingImageUrl: null
    },
    ready: function () {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    },
    methods: {
      setMessage: function (message, cssClass, autoHide) {
        cssClass = cssClass || 'alert alert-info';
        autoHide = autoHide || true;

        this.message = message;
        this.setCssClass(cssClass);

        if (autoHide) {
          setTimeout(function () {
            vmGlobalMessage.clear()
          }, 5000);
        }
      },
      setCssClass: function (cssClass) {
        this.messageCssClass = cssClass;
      },
      clear: function () {
        this.message = '';
        this.messageCssClass = '';
      },
      closeWindow: function () {
        window.close();
      }

      , loadData: function () {
        vmMallPriceList.loadData();
      }

      , checkAll: function() {
        vmMallPriceList.checkAll();
      }
      , unCheckAll: function() {
        vmMallPriceList.unCheckAll();
      }
      , csvDownload: function() {
        vmMallPriceList.csvDownload();
      }

      , test: function() {
        vmMallPriceList.test();
      }
    }
  });

  // モール価格一覧テーブル
  var vmMallPriceList = new Vue({
    el: '#mallPriceList'
    , data: {
        loadUrl: null
      , imageParentUrl: null

      // , indexedDB: null
      , db: null
      , dbServer: null
      , dbName: 'mallPriceList'

      , page: 1
      , pageItemNum: 100
      , pageListMaxLength: 20

      , sortField: 'daihyo_syohin_code'
      , sortOrder: 1

      , dataNum: 0
      , pageData: []
    }
    , ready: function () {
      var self = this;

      self.loadUrl = $(self.$el).data('loadUrl');
      self.imageParentUrl = $(self.$el).data('imageParentUrl');

      // DB初期化
      self.db = db;
      self.db.delete(self.dbName);
      self.db.open({
        server: self.dbName,
        version: 1.0,
        schema: {
          priceList: {
            key: { keyPath: 'daihyo_syohin_code', autoIncrement: false },
            indexes: {
                daihyo_syohin_code: { unique: true }
              , rakuten_price: {}
              , shoplist_price: {}
              , shoplist_current_price: {}
              , diff_rate: {}
              , checked: {}
            }
          }
        }
      }).catch(function (err) {
        if (err.type === 'blocked') {
          oldConnection.close();
          console.log('blocked!!!');
          return err.resume;
        }
        // Handle other errors here
        throw err;
      }).then(function (s) {

        self.dbServer = s;

        // データ読み込み
        self.loadData();
      });
    }
    , computed: {

      pageOffset: function () {
        return (this.page - 1) * this.pageItemNum;
      },

      isStartPage: function () {
        return (this.page == 1);
      },

      isEndPage: function () {
        return (this.page == this.pageNum);
      },

      /// 最大ページ数 （現在のフィルタ条件を考慮）
      pageNum: function () {
        return Math.ceil(this.dataNum / this.pageItemNum);
      },

      pageList: function () {
        var pages = [];
        var i;
        if (this.pageNum <= this.pageListMaxLength) {
          for (i = 1; i <= this.pageNum; i++) {
            pages.push(i);
          }
        } else {

          var listHalf = Math.floor(this.pageListMaxLength / 2);
          if (!listHalf) {
            listHalf = 1;
          }

          var isSkipForward = this.page <= (this.pageNum - listHalf); // 大きい方をスキップ
          var isSkipBackward = this.page >= listHalf; // 小さい方をスキップ

          var showNum = this.pageListMaxLength - 2  // start & end
            - (isSkipForward ? 1 : 0) // 「...」
            - (isSkipBackward ? 1 : 0); // 「...」

          var prePageNum = Math.floor((showNum - 1) / 2);
          var postPageNum = (showNum - 1) - prePageNum;
          var start = isSkipBackward ? this.page - prePageNum : 2;
          var end = isSkipForward ? this.page + postPageNum : (this.pageNum - 1);

          if (this.page - prePageNum < 2) {
            end += (2 - (this.page - prePageNum));
          }
          if (this.page + postPageNum > (this.pageNum - 1)) {
            start -= (this.page + postPageNum - (this.pageNum - 1));
          }

          pages.push(1); // 先頭ページ
          if (isSkipBackward) {
            pages.push('…')
          }
          for (i = start; i <= end; i++) {
            pages.push(i);
          }
          if (isSkipForward) {
            pages.push('…')
          }
          pages.push(this.pageNum); // 最終ページ
        }

        return pages;
      }

    }

    , methods: {

      showError: function (message) {
        vmGlobalMessage.setMessage(message, 'alert alert-danger');
      }

      , loadData: function () {
        var self = this;

        if (!self.db) {
          self.showError("データベースが利用できません。");
        }

        // Show loading
        $.Vendor.WaitingDialog.show('データ取得中 ...');

        // DB更新
        $.ajax({
            type: "GET"
          , url: self.loadUrl
          , dataType: "json"
          , data: {}
        })
          .done(function (result) {
            if (result.status == 'ok') {

              self.dbServer.priceList.clear();

              for (var i = 0; i < result.data.length; i++) {
                var row = result.data[i];
                row['rakuten_price'] = Number(row['rakuten_price']);
                row['shoplist_price'] = Number(row['shoplist_price']);
                row['shoplist_current_price'] = Number(row['shoplist_current_price']);
                row['diff_rate'] = Number(row['diff_rate']);
                row['picture'] = row['picture'].length > 0 ? self.imageParentUrl + '/' + row['picture'] : '';

                row['checked'] = 0;

                result.data[i] = row;
              }

              self.dbServer.priceList.add(result.data).then(function() {
                $.Vendor.WaitingDialog.hide();
                self.showPage(self.page);
              });

            } else {
              self.showError('データの取得に失敗しました。' + result.message);
              $.Vendor.WaitingDialog.hide();
            }
          })
          .fail(function (stat) {
            self.showError('データが取得できませんでした。');
            $.Vendor.WaitingDialog.hide();
          })
          .always(function () {
          });

      }

      , showPage: function (page, event) {
        var self = this;

        // aタグなどでは$eventを渡してリンク挙動を抑制
        if (event) {
          event.preventDefault();
        }

        self.getQuery().execute().then(function(all) {

          // ページ数を先に更新する必要がある。
          self.dataNum = all.length;

          if (page >= 1 && page <= self.pageNum) {
            self.page = page;
            self.$set('pageData', all.slice(self.pageOffset, self.pageOffset + self.pageItemNum));
          }

        });

      }

      /// 一覧取得用query
      , getQuery: function () {
        var query = this.dbServer.priceList.query(this.sortField);
        query = query.all();
        if (this.sortOrder == -1) {
          query = query.desc();
        }
        return query;
      }

      /**
       * ページ送り
       */
      , showPrev: function (event) {
        event.preventDefault();
        if (!this.isStartPage) {
          this.page--;
          this.showPage(this.page);
        }
      }

      , showNext: function (event) {
        event.preventDefault();
        if (!this.isEndPage) {
          this.page++;
          this.showPage(this.page);
        }
      }

      /**
       * ページ判定
       */
      , isPage: function (num) {
        return (this.page === parseInt(num));
      }

      /**
       * ソートアイコンCSSクラス
       */
      , getSortMarkCssClass: function(field) {
        return (field == this.sortField)
          ? (this.sortOrder == 1 ? 'sortAsc' : 'sortDesc' )
          : '';
      }

      /**
       * 一覧 並び順変更
       * @param fieldName
       */
      , switchSort: function(fieldName) {

        if (this.sortField == fieldName) {
          // 降順 -> 昇順
          if (this.sortOrder == -1) {
            this.sortOrder = 1;

          } else if (this.sortField == 'daihyo_syohin_code') {
            this.sortOrder = -1;

            // デフォルトに戻る
          } else {
            this.sortField = 'daihyo_syohin_code';
            this.sortOrder = 1;
          }

        } else {
          this.sortField = fieldName;
          this.sortOrder = -1; // 降順が先
        }

        this.showPage(1);
      }

      , toggleCheck: function(item, flag) {

        if (flag === undefined) {
          flag = item.checked ? 0 : 1;
        }
        item.checked = flag;

        this.dbServer.priceList.update(item);
      }

      , pageCheckToggle: function(event) {

        var flag = $(event.target).prop('checked') ? 1 : 0;

        for (var i = 0; i < this.pageData.length; i++) {
          this.toggleCheck(this.pageData[i], flag);
        }
      }

      , checkAll: function() {

        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('チェック更新中(ON) ...');

        self.dbServer.priceList
          .query('checked')
          .only(0)
          .modify({checked: 1})
          .execute()
          .then(function(result) {
            $.Vendor.WaitingDialog.hide();
            self.showPage(self.page);
          });
      }

      , unCheckAll: function() {
        var self = this;

        // Show loading
        $.Vendor.WaitingDialog.show('チェック更新中(OFF) ...');

        self.dbServer.priceList
          .query('checked')
          .only(1)
          .modify({checked: 0})
          .execute()
          .then(function(result) {
            $.Vendor.WaitingDialog.hide();
            self.showPage(self.page);
          });
      }

      , csvDownload: function() {
        var self = this;

        self.dbServer.priceList
          .query('checked')
          .only(1)
          .execute()
          .then(function (checked) {

            if (checked.length == 0) {
              alert("出力対象が指定されていません。");
              return;
            }

            var $form = $(self.$el).find('#csvDownloadForm');
            $('input', $form).remove();

            for (var i = 0; i < checked.length; i++) {
              $form.append($('<input type="hidden" name="daihyo_syohin_code_list[]">').val(checked[i].daihyo_syohin_code));
            }

            $form.submit();
          });
      }



      , test: function() {
        var db = this.dbServer.getIndexedDB();

        console.log(db.objectStoreNames);

        var transaction = db.transaction(['priceList'], 'readonly');
        var objectStore = transaction.objectStore('priceList');

        var myIndex = objectStore.index('daihyo_syohin_code');
        var countRequest = myIndex.count();
        countRequest.onsuccess = function() {
          console.log(countRequest.result);

          var keyRangeValue = IDBKeyRange.bound("A", "F");

          countRequest = myIndex.count(keyRangeValue);
          countRequest.onsuccess = function() {
            console.log(countRequest.result);
          };

        };
      }
    }
  });

  // アップロードモーダル
  var vmUploadCsvModal = new Vue({
    el: '#modalUploadCsv'
    , data: {
        uploadUrl: null
      , message: null
      , messageClass : null
    }
    , ready: function () {
      var self = this;

      self.message = `SHOPLIST管理画面\n「ショップ管理・設定」＞「CSVダウンロード」 画面から\nCSVタイプ: 「詳細タイプCSV」のデータをダウンロードしてください。`;
      self.messageClass = 'alert alert-info';

      self.uploadUrl = $(self.$el).data('uploadUrl');

      // アップロードフォーム
      $('#shoplistCsvUpload').fileinput({
          uploadUrl: self.uploadUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false

        , fileActionSettings: {
            showZoom: false
          , showUpload: false
        }
        , allowedFileExtensions: ['csv']
      })

        .on('filebatchuploadsuccess', function (event, data, previewId, index) {

          if (data.response && data.response.message) {
            self.$set('message', data.response.message);
            self.$set('messageClass', 'alert alert-success');
          } else {
            self.$set('message', null);
          }

          $('#shoplistCsvUpload').fileinput('clear');

          vmMallPriceList.loadData();
        })
      ;
    }
    , methods: {}

  });


});
