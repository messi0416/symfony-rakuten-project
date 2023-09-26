/**
 * 納品書印刷補助画面用 JS
 */
$(function() {

  var state = {
    jobKey: null
  };

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#globalMessage',
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
        autoHide = (typeof autoHide === 'undefined') ? false : !!autoHide;

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

  var vmDeliveryDetailProductNumList = new Vue({
      el: '#deliveryDetailProductNumList'
    , data: {
        refreshUrl: null
      , state: state

      , pageItemNum: 40
      , changeLocationOrder: 0

      , refreshWarehouseStockMoveUrl: null
      , stockMoveRemainCheckUrl: null
      , deliveryEnqueueCsvDownloadAndUpdateShippingVoucherUrl: null

      , searchVoucherNumber: null
      , searchVoucherNumberDone: null
      , searchVoucherNumberNotExists: null
      , updateShippingVoucherStartPage: 1
      , updateShippingVoucherEndPage: 1
      , selectMethod: null
      , data: null,
    }
    , computed: {
      changeLocationOrderIconOff: function() {
        return  this.changeLocationOrder == 0 ? [ 'fa', 'fa-fw', 'fa-check' ]: 'hidden' ;
      }

      , changeLocationOrderIconOn: function() {
        return  this.changeLocationOrder == 1 ? [ 'fa', 'fa-fw', 'fa-check' ]: 'hidden' ;
      }
    }


    , ready: function() {
      $('#shippingDate').datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
      });

      this.refreshUrl = $(this.$el).data('refreshUrl');
      this.refreshWarehouseStockMoveUrl = $(this.$el).data('refreshWarehouseStockMoveUrl');
      this.stockMoveRemainCheckUrl = $(this.$el).data('stockMoveRemainCheckUrl');
      this.deliveryEnqueueCsvDownloadAndUpdateShippingVoucherUrl = $(this.$el).data('deliveryEnqueueCsvDownloadAndUpdateShippingVoucherUrl');
      this.selectMethod = METHODS;
      if (METHODS.length > 0) {
        this.selectMethod = METHODS[0].method;
      }
      this.data = DATA;
    }
    , methods: {
      showNextRow: function(event) {
        var $target = $(event.target).closest('tr').next('tr.dayList');
        if ($target.size() > 0) {
          $target.toggle();
        }
      }
      , updateFormSubmit: function(settingId) {
        var self = this;

        // 倉庫在庫移動に残件があると、その分が出荷不能になる場合があるためチェック。
        // エラーは握りつぶす。また同期処理のため、たまに遅いので1秒でタイムアウト

        let stockMoveMessage = ''; // 返却するエラーメッセージ　残件がなければ空文字
        $.ajax({
            type: "POST"
          , url: self.stockMoveRemainCheckUrl
          , async: false // 同期処理
          , timeout: 1000
        }).done(function(result) {
            if (result.status == 'ok') {
              if (result.remain_list != null && result.remain_list.length > 0) {
                let remainWarehouseNameArray = [];
                for (var i = 0; i < result.remain_list.length; i++) {
                  remainWarehouseNameArray.push(result.remain_list[i].warehouse_name);
                }
                stockMoveMessage = remainWarehouseNameArray.join(',') + 'に倉庫移動ピッキング残件があります。\nこの分は出荷対象になりません。\n\n';
              }
            }
        }).fail(function(xhr, status) {
          if( status == "timeout" ) {
            stockMoveMessage = '倉庫移動ピッキング残件のチェックはタイムアウトしました。\n\n';
          }
        });

        let settingName = settingId == 2 ? 'SHOPLIST' : '通常';
        if (! confirm(stockMoveMessage + 'データを削除し、再集計します。よろしいですか？ ( '+ settingName +' )')) {
          return;
        }

        // 更新処理 キュー追加
        let shippingDate = $('#shippingDate').datepicker('getDate');
        if (!shippingDate) {
          alert('日付の指定がありません。');
          return;
        }

        let data = {
            shippingDate : $.Plusnao.Date.getDateString(shippingDate)
          , pageItemNum: self.pageItemNum
          , changeLocationOrder: self.changeLocationOrder
        };

        $.ajax({
            type: "POST"
          , url: self.refreshUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {

              // alert('再集計処理のキューを追加しました。完了後、画面を再読込してください。');
              self.state.jobKey = result.key;
              vmModalDeliveryStatementListRefreshProgress.open();

            } else {
              self.state.jobKey = null;
             alert('エラーで処理を中止しました。 ' + result.message);
            }
          })
          .fail(function(stat) {
            self.state.jobKey = null;
            alert('エラーが発生しました。');
          })
          . always(function() {
          });
      }

      , setChangeLocationOrder: function(value) {
        this.changeLocationOrder = value;
      }


      , openProgress: function() {
        if (this.state.jobKey) {
          vmModalDeliveryStatementListRefreshProgress.open();
        }
      }

      , updateWarehouseStockMoveSubmit: function(warehouseId, warehouseName) {
        var self = this;

        if (! confirm(`倉庫在庫ピッキングのデータを削除し、再集計します。※もし現在倉庫在庫ピッキング中ならそのデータは失われます。よろしいですか？ \n\n ※「 ${warehouseName} 」に在庫移動します。`)) {
          return;
        }

        // 更新処理 キュー追加
        var data = {
          warehouseId: warehouseId
        };

        $.ajax({
            type: "POST"
          , url: self.refreshWarehouseStockMoveUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {

              // alert('再集計処理のキューを追加しました。完了後、画面を再読込してください。');
              alert(result.message);

            } else {
              alert('エラーで処理を中止しました。 ' + result.message);
            }
          })
          .fail(function(stat) {
            console.log(stat);

            alert('エラーが発生しました。');
          })
          . always(function() {
          });

      }

      , selectAll: function(event) {
        event.target.select();
      }

      , searchVoucher: function(force) {
        if (!this.searchVoucherNumber || this.searchVoucherNumber.length == 0) {
          this.searchVoucherNumberDone = null;
          this.searchVoucherNumberNotExists = false;
          return;
        }
        if (!force && this.searchVoucherNumberDone == this.searchVoucherNumber) {
          return;
        }

        this.searchVoucherNumberNotExists = false;

        var self = this;
        var hit = false;
        // 探す
        $("td.listVoucherNumber", this.$el).each(function(i, item) {
          var num = $.Plusnao.String.trim($(item).text());

          if (num == self.searchVoucherNumber) {
            // テキスト選択
            var range = document.createRange();
            range.selectNodeContents(item);
            var selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            // タブ取得・切替
            var deliveryMethod = $(item).closest("div[id^='delivery_']");
            var listNumber = $(item).closest("div[id^='voucherList_']");

            var tabDeliveryMethod = $('.nav-tabs a[href="#' + deliveryMethod.attr('id') + '"]', self.$el);
            if (tabDeliveryMethod.length > 0) {
              tabDeliveryMethod.tab('show');
            }
            var tabListNumber = $('.nav-tabs a[href="#' + listNumber.attr('id') + '"]', self.$el);
            if (tabListNumber.length > 0) {
              tabListNumber.tab('show');
            }

            self.searchVoucherNumberDone = self.searchVoucherNumber;
            hit = true;
          }

        });

        if (!hit) {
          this.searchVoucherNumberNotExists = true;
        }
      }
      , updateShippingVoucher: function (warehouseId) {
        vmGlobalMessage.clear();
        const self = this;
        // ページ数入力チェック
        if (!this.updateShippingVoucherStartPage || !this.updateShippingVoucherEndPage) {
          vmGlobalMessage.setMessage('ページ数は必須です。', 'alert alert-danger');
          return;
        }
        this.updateShippingVoucherStartPage = Number(this.updateShippingVoucherStartPage);
        this.updateShippingVoucherEndPage = Number(this.updateShippingVoucherEndPage);

        // ページ数の大小チェック
        if (this.updateShippingVoucherStartPage > this.updateShippingVoucherEndPage) {
          vmGlobalMessage.setMessage('ページ数の値は最初の値の方が低くなるように入力してください。', 'alert alert-danger');
          return;
        }
        // 開始ページと終了ページの存在チェック
        const pages = Object.keys(this.data[this.selectMethod]).map(Number);
        if (!pages.includes(this.updateShippingVoucherStartPage) || !pages.includes(this.updateShippingVoucherEndPage)) {
          vmGlobalMessage.setMessage('存在するページを入力してください。', 'alert alert-danger');
          return;
        }
        // 確認メッセージ
        const confirmMessage = this.selectMethod + 'の' +
          this.updateShippingVoucherStartPage +
          ' ～ ' +
          this.updateShippingVoucherEndPage +
          'ページの出荷リストを一括生成します。\nよろしいですか？';
        if (!confirm(confirmMessage)) {
          return;
        }

        // 一括生成
        const form = {
          warehouseId: warehouseId,
          deliveryMethod: self.selectMethod,
          startPage: self.updateShippingVoucherStartPage,
          endPage: self.updateShippingVoucherEndPage
        }
        $.ajax({
          type: "POST",
          url: self.deliveryEnqueueCsvDownloadAndUpdateShippingVoucherUrl,
          dataType: "json",
          data: {
            form: form
          }
        }).done(function (result) {
          if (result.status == 'ok') {
            vmGlobalMessage.setMessage(result.message, 'alert alert-info', true);
          } else {
            const message = result.message ? result.message : '出荷リスト一括自動生成時にエラーが発生しました。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        }).fail(function (stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');
        });
      }
      , changeMethod: function (method) {
        this.selectMethod = method;
      }
    }
  });


  var vmModalDeliveryStatementListRefreshProgress = new Vue({
      el: '#modalDeliveryStatementListRefreshProgress'
    , data: {
        caption: '納品書印刷待ち 再集計'
      , message: ''
      , messageClass: ''

      , state: state

      , dummyKey: null
      , checkUrlBase: null
      , setFlashUrl: null

      , timerPast: null
      , timerLoad: null

      , status: null
      , started: null
      , past: null
      , finished: null
      , info: {}
    }
    , ready: function() {
      var self = this;

      self.dummyKey = $(this.$el).data('dummyKey');
      self.checkUrlBase = $(this.$el).data('checkUrlBase');
      self.setFlashUrl = $(this.$el).data('setFlashUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        self.loadData();
      });
      $(self.$el).on('hide.bs.modal', function() {
        self.clearTimer();
      });

    }

    , computed: {
      progressRate: function() {
        var rate = null;
        if (this.info.total && this.info.done) {
          rate = Math.ceil(this.info.done / this.info.total * 100);
        }

        return rate;
      }

      , progressRateStyle: function() {
        return this.progressRate ? { width: this.progressRate + "%" } : null;
      }

      , displayStatus: function() {
        var text = '';
        switch (this.status) {
          case 'NEW':
            text = 'キュー追加待ち';
            break;
          case 'QUEUED':
            text = 'キュー追加済み';
            break;
          case 'STARTED':
            text = '処理開始済み';
            break;
          case 'FINISHED':
            text = '完了';
            break;
          case 'ERROR':
            text = 'エラー終了';
            break;
        }

        return text;
      }
    }

    , methods: {
      open: function() {
        var self = this;
        self.nowLoading = true;

        $(self.$el).modal('show');
      }

      , resetDialog: function() {
        this.message = '';
        this.messageClass = '';
      }

      , loadData: function() {
        var self = this;

        if (!self.state.jobKey) {
          self.message = '集計処理がされていないか、処理の情報が失われました。';
          self.messageClass = 'alert alert-warning';
          return;
        }

        var url = self.checkUrlBase.replace(self.dummyKey, self.state.jobKey);
        var data = {};

        $.ajax({
            type: "GET"
          , url: url
          , dataType: "json"
          , data: data
        })
          .done(function(result) {

            if (result.status == 'ok') {

              self.status = result.jobRequest.status;
              self.started = result.jobRequest.started;
              self.finished = result.jobRequest.finished;
              self.info = result.jobRequest.info ? result.jobRequest.info : {};

              self.past = 0;
              if (self.started) {
                // replace for firefox, IE
                var started = new Date(self.started.replace(/-/g, "/"));
                var finished = self.finished ? new Date(self.finished.replace(/-/g, "/")) : new Date();

                var SECOND_MILLISECOND = 1000;
                /*
                 MINUTE_MILLISECOND = 60 * SECOND_MILLISECOND,
                 HOUR_MILLISECOND = 60 * MINUTE_MILLISECOND,
                 DAY_MILLISECOND = 24 * HOUR_MILLISECOND,
                 WEEK_MILLISECOND = 7 * DAY_MILLISECOND,
                 YEAR_MILLISECOND = 365 * DAY_MILLISECOND;
                 */

                var deltaMilliSecond = finished.getTime() - started.getTime();
                self.past = Math.floor(deltaMilliSecond / SECOND_MILLISECOND);
              }

              if (
                   self.status === 'NEW'
                || self.status === 'QUEUED'
                || self.status === 'STARTED'
              ) {
                // past更新タイマー
                if (!self.timerPast) {
                  self.timerPast = setInterval(function () {
                    if (self.past > 0) {
                      self.past = self.past + 1;
                    }
                  }, 1000);
                }


                // 次回更新予約 （NEW, QUEUED, STARTED でのみ）
                self.timerLoad = setTimeout(self.loadData, 3000);

              } else {
                self.state.jobKey = null;
                self.clearTimer();
              }

              // メッセージ更新
              if (self.info.message) {
                self.message = self.info.message;
                self.messageClass = self.info.messageType
                                  ? "alert alert-" + self.info.messageType
                                  : "alert alert-info"
              }

            } else {
            self.message = result.message.length > 0 ? result.message : '処理状況を取得できませんでした。';
            self.messageClass = 'alert alert-danger';

            self.state.jobKey = null;
            self.clearTimer();
          }

        })
        .fail(function(stat) {
          self.message = 'エラーが発生しました。';
          self.messageClass = 'alert alert-danger';

          self.state.jobKey = null;
          self.clearTimer();
        })
        . always(function() {
        });

      }

      , clearTimer: function() {
        if (this.timerPast) {
          clearInterval(this.timerPast);
        }
        if (this.timerLoad) {
          clearTimeout(this.timerLoad);
        }

        this.timerPast = null;
        this.timerLoad = null;
      }

      , finish: function() {
        window.location.reload();

      }

    }
  });




});


