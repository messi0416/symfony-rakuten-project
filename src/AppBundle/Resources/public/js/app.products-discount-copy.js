/**
 * 商品値下げ一覧 JS
 */
$(function() {

  Vue.config.debug = true;

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

  // 値下げ一覧テーブル 行コンポーネント
  Vue.component('result-item', {
    template: "#result-item",
    props: [
      'item'
    ],
    data: function() {
        return {
          daihyoSyohinCode  : this.item.daihyoSyohinCode
          , imageUrl          : this.item.imageUrl
          , stockAmount       : this.item.stockAmount
          , defaultWarehouseStockAmount : this.item.defaultWarehouseStockAmount
          , expectedDailySalesAmount : this.item.expectedDailySalesAmount
          , lastOrderdate     : this.item.lastOrderdate
          , discountBaseDate  : this.item.discountBaseDate
          , estimatedSalesDays : this.item.estimatedSalesDays
          , salesRate         : this.item.salesRate
          , basePrice         : this.item.basePrice
          , costTotal         : this.item.costTotal
          , discountDestinationPrice: this.item.discountDestinationPrice
          , currentPrice      : this.item.currentPrice
          , discountPrice     : this.item.discountPrice
          , priceDiff         : this.item.priceDiff
          , pricedownFlg      : this.item.pricedownFlg ? true : false
          , rakutenDetailUrl  : this.item.rakutenDetailUrl
          , discountSeasonSetting : this.item.discountSeasonSetting
        };
    },
    computed: {
      displayInventoryCost : function() {
        return $.Plusnao.String.numberFormat(this.stockAmount * this.costTotal);
      },
      displayStockAmount: function() {
        return $.Plusnao.String.numberFormat(this.stockAmount);
      },
      displayDefaultWarehouseStockAmount: function() {
        return $.Plusnao.String.numberFormat(this.defaultWarehouseStockAmount);
      },
      displayExpectedDailySalesAmount: function() {
        return $.Plusnao.String.numberFormat(this.expectedDailySalesAmount, 2);
      },
      displayLastOrderdate: function() {
        return this.item.lastOrderdate ? $.Plusnao.Date.getDateString(this.lastOrderdate) : '';
      },
      displayDiscountBaseDate: function() {
        return this.item.discountBaseDate ? $.Plusnao.Date.getDateString(this.discountBaseDate) : '';
      },
      displayEstimatedSalesDays: function() {
        return $.Plusnao.String.numberFormat(this.estimatedSalesDays, 2);
      },
      displaySalesRate: function() {
        return $.Plusnao.String.numberFormat(this.salesRate, 2);
      },
      displayBasePrice: function() {
        return $.Plusnao.String.numberFormat(this.basePrice);
      },
      displayCostTotal: function() {
        return $.Plusnao.String.numberFormat(this.costTotal);
      },
      displayDiscountDestinationPrice: function() {
        return $.Plusnao.String.numberFormat(this.discountDestinationPrice);
      },
      displayCurrentPrice: function() {
        return $.Plusnao.String.numberFormat(this.currentPrice);
      },
      displayDiscountPrice: function() {
        return $.Plusnao.String.numberFormat(this.discountPrice);
      },

      displayPriceDiff: function() {
        return this.priceDiff > 0
               ? '+' + $.Plusnao.String.numberFormat(this.priceDiff)
               : $.Plusnao.String.numberFormat(this.priceDiff);
      },
      rowCssClass: function() {
        var cssClass = '';

        // 赤字
        if (this.discountPrice < this.costTotal) {
          // 大赤字
          if (this.discountPrice < (this.costTotal / 2)) {
            cssClass = 'danger';
          } else {
            cssClass = 'warning';
          }
        }
        return cssClass;
      }
    },
    ready: function() {
    },
    methods: {
      /**
       * 値下げ許可・不許可 チェック変更
       */
      updatePricedownFlg: function() {
        var self = this;

        var originalValue = self.item.pricedownFlg;
        var flagNewValue = self.pricedownFlg ? -1 : 0;

        // DB更新
        var url = vmProductsDiscountListTable.updatePricedownFlgUrl;
        var data = {
            daihyo_syohin_code: this.item.daihyoSyohinCode
          , pricedown_flg: flagNewValue
        };

        $.ajax({
            type: "POST"
          , url: url
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.valid) {
              // DB更新に成功すれば、JS側親データの値も更新
              self.item.pricedownFlg = flagNewValue;

            } else {
              alert('値下げフラグの更新に失敗しました。');
              self.pricedownFlg = originalValue ? true : false; // チェックを元に戻す
            }
          })
          .fail(function(stat) {
            alert('値下げフラグの更新でエラーが発生しました。');
            self.pricedownFlg = originalValue ? true : false; // チェックを元に戻す
          })
          . always(function() {
          });
      },


      changeDiscountSeasonSetting: function(setting) {
        var newSetting = this.discountSeasonSetting;
        this.item.discountSeasonSetting = newSetting;
        this.$parent.isApplyButtonDisabled = false;
      },

      changePricedownFlg: function(setting) {
        var flagNewValue = this.pricedownFlg ? -1 : 0;
        this.item.pricedownFlg = flagNewValue;
        this.$parent.isApplyButtonDisabled = false;
      }

    }
  });

  // 値下げ一覧画面 メイン
  var vmProductsDiscountListTable = new Vue({
    el: '#productsDiscount',
    data: {
      // データ
        list: []

      , discountExcludedDays: null

      // 合計金額
      , totalPrices: {
          base : 0
        , destination: 0
        , current: 0
        , discount: 0
        , display: {
            base: ''
          , destination: ''
          , current: ''
          , discount: ''
        }
      }

      // ページ送り設定
      , pageItemNum: 20 // 設定値: 1ページ表示件数
      //, pageItemNum: 5 // ページ遷移のデバッグのため
      , pageListMaxLength: 20 // 設定値: ページリンク 表示最大件数
      , page: 1 // 現在のページ

      // 並び順指定
      , sortField: null
      , sortOrder: -1

      // 絞込
      , keyword: null
      , searchPriceDown: 'all' // all, permitted, not_permitted
      , searchTargetOnly: false // false: show all, true: show only target
      , searchTargetDiscountRateMin: 0.01
      , searchTargetDiscountRateMax: 100
      , searchSeason: 'all' // all, in, off

      // 処理URL（基本URL）
      , baseUrl: null
      , updatePricedownFlg: null
      , imageUrl: null
      , rakutenDetailUrl: null
      , UpdatePricedownSettingsUrl: null

      //
      , isApplyButtonDisabled: true

    },
    ready: function () {
      var self = this;

      // URL文字列取得
      self.baseUrl = $(self.$el).data('url');
      self.updatePricedownFlgUrl = $(self.$el).data('updatePricedownFlgUrl');
      self.imageUrl = $(self.$el).data('imageUrl');
      self.rakutenDetailUrl = $(self.$el).data('rakutenDetailUrl');
      self.UpdatePricedownSettingsUrl = $(self.$el).data('updatePricedownSettingsUrl')

      // ソート処理のため、ここで数値変換をしておく。
      var list = [];
      var i; var testIndex;
      for (i in discountListTableData) {
        var item = discountListTableData[i];
        var row = {
            'daihyoSyohinCode'  :  item.daihyo_syohin_code
          , 'imageUrl'          :  (item.pic_directory.length && item.pic_filename.length ? self.imageUrl + item.pic_directory + '/' + item.pic_filename : '')
          , 'inventoryCost'     : Number(item.stock_amount) * Number(item.cost_total) || 0
          , 'stockAmount'       :  Number(item.stock_amount) || 0
          , 'defaultWarehouseStockAmount' : Number(item.default_warehouse_stock_amount) || 0
          , 'expectedDailySalesAmount' : Number(item.expected_daily_sales_amount) || 0
          , 'lastOrderdate'     :  (item.last_orderdate ? new Date(item.last_orderdate.replace(/-/g, "/")) : null) // replace for firefox, IE
          , 'discountBaseDate'     :  (item.discount_base_date ? new Date(item.discount_base_date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , 'estimatedSalesDays' : Number(item.estimated_sales_days) || 0
          , 'salesRate'          : Number(item.sales_rate) // 消化日数/販売完了日数
          , 'basePrice'         :  Number(item.base_price) || 0
          , 'costTotal'         :  Number(item.cost_total) || 0
          , 'currentPrice'      :  Number(item.current_price) || 0
          , 'discountDestinationPrice':  Number(item.discount_destination_price) || 0
          , 'discountPrice'     :  Number(item.discount_price) || 0
          , 'priceDiff'         :  0
          , 'pricedownFlg'      :  Number(item.pricedown_flg) || 0
          , 'seasonFlg'         :  Number(item.season_flg) || 0
          , 'rakutenDetailUrl'  : self.rakutenDetailUrl + item.daihyo_syohin_code.toLowerCase()
          , 'discountSeasonSetting' : item.discount_season_setting
        };
        // 差額
        row.priceDiff = row.discountPrice - row.currentPrice;

        list.push(row);
      }

      self.$set('list', list);

      // tooltip 有効化: th
      $(self.$el).find('th span').tooltip();

      // tooltip 有効化: エラーメッセージ
      $(self.$el).find('form .form-control').tooltip({
          html: true
        , template: '<div class="tooltip error" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div>'
      });


      var showPopup = function(e) {
        // 今は任意のメッセージを表示することはできくなったらしい
        var confirmationMessage = "チェックボックスが反映されていません";
        e.returnValue = confirmationMessage;     // Gecko and Trident
        return confirmationMessage;              // Gecko and WebKit
      }

      self.$watch('isApplyButtonDisabled', function (btnDisabled) {
        if (btnDisabled) {
          console.log("add")
          window.removeEventListener('beforeunload', showPopup, false);
        } else {
          console.log("remove")
          window.addEventListener('beforeunload', showPopup, false);
        };
      })

    },
    computed: {
      /// ソート・フィルター済みリスト
      // ※ページング処理のため、Vue.js v-for の filterBy, orderBy が利用できない。
      listData: function() {
        var self = this;

        var list = self.list.slice(); // 破壊防止

        // ソート
        if (self.sortField) {
          list.sort(function(a, b) {
            if (a[self.sortField] > b[self.sortField]) { return 1 * self.sortOrder; }
            if (a[self.sortField] < b[self.sortField]) { return -1 * self.sortOrder; }
            return 0;
          });
        }

        // 絞込: キーワード
        if (self.keyword) {
          var pattern = new RegExp($.Plusnao.String.regexQuote($.Plusnao.String.trim(self.keyword)), 'i');
          list = list.filter(function(item, i) {
            return item.daihyoSyohinCode.match(pattern) !== null;
          });
        }
        // 絞込: 値下げ許可
        if (self.searchPriceDown == 'permitted') {
          list = list.filter(function(item, i) {
            return item.pricedownFlg != 0;
          });

        } else if (self.searchPriceDown == 'not_permitted') {
          list = list.filter(function(item, i) {
            return item.pricedownFlg == 0;
          });
        }
        // 絞込: 値下げ（・戻し）対象のみ表示
        if (self.searchTargetOnly) {
          list = list.filter(function(item, i) {
            currentDiscountRatePercent = (item.discountPrice / item.basePrice) * 100;
            return currentDiscountRatePercent >= self.searchTargetDiscountRateMin
                && currentDiscountRatePercent <= self.searchTargetDiscountRateMax;
          });
        }
        // 絞込: 値下げシーズン設定
        if (self.searchSeason == 'in') {
          list = list.filter(function(item, i) {
            return item.seasonFlg != 0;
          });

        } else if (self.searchSeason == 'off') {
          list = list.filter(function(item, i) {
            return item.seasonFlg == 0;
          });
        }

        // 合計金額計算 ＆ 更新
        self.totalPrices.base = 0;
        self.totalPrices.destination = 0;
        self.totalPrices.current = 0;
        self.totalPrices.discount = 0;
        for (var i in list) {
          var item = list[i];
          self.totalPrices.base += item.basePrice * item.stockAmount;
          self.totalPrices.destination += item.discountDestinationPrice * item.stockAmount;
          self.totalPrices.current += item.currentPrice * item.stockAmount;
          self.totalPrices.discount += item.discountPrice * item.stockAmount;
        }
        self.totalPrices.display.base = $.Plusnao.String.numberFormat(self.totalPrices.base);
        self.totalPrices.display.destination = $.Plusnao.String.numberFormat(self.totalPrices.destination);
        self.totalPrices.display.current = $.Plusnao.String.numberFormat(self.totalPrices.current);
        self.totalPrices.display.discount = $.Plusnao.String.numberFormat(self.totalPrices.discount);

        return list;
      },

      pageData: function() {
        var startPage = (this.page - 1) * this.pageItemNum;
        return this.listData.slice(startPage, startPage + this.pageItemNum);
      },

      isStartPage: function(){
        return (this.page == 1);
      },

      isEndPage: function(){
        return (this.page == this.pageNum);
      },

      /// 最大ページ数 （現在のフィルタ条件を考慮）
      pageNum: function() {
        return Math.ceil(this.listData.length / this.pageItemNum);
      },

      pageList: function() {
        var pages = [];
        if (this.pageNum <= this.pageListMaxLength) {
          for (var i = 1; i <= this.pageNum; i++) {
            pages.push(i);
          }
        } else {

          var listHalf = Math.floor(this.pageListMaxLength / 2);
          if (!listHalf) {
            listHalf = 1;
          }
          var listQuarter = Math.floor(this.pageListMaxLength / 4);
          if (!listQuarter) {
            listQuarter = 1;
          }

          var isSkipForward = this.page <= (this.pageNum - listHalf); // 大きい方をスキップ
          var isSkipBackward = this.page >= listHalf; // 小さい方をスキップ

          var showNum = this.pageListMaxLength - 2  // start & end
                      - (isSkipForward ? 1 : 0) // 「...」
                      - (isSkipBackward ? 1 : 0); // 「...」

          var prePageNum = Math.floor((showNum -1) / 2);
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
          for (var i = start; i <= end; i++) {
            pages.push(i);
          }
          if (isSkipForward) {
            pages.push('…')
          }
          pages.push(this.pageNum); // 最終ページ
        }

        return pages;
      },

      sortMarks: function() {

        var fields = [
          'daihyoSyohinCode'
        , 'inventoryCost'
        , 'stockAmount'
        , 'defaultWarehouseStockAmount'
        , 'expectedDailySalesAmount'
        , 'lastOrderdate'
        , 'discountBaseDate'
        , 'salesRate'
        , 'estimatedSalesDays'
        , 'basePrice'
        , 'costTotal'
        , 'currentPrice'
        , 'discountDestinationPrice'
        , 'discountPrice'
        , 'discountRate'
        , 'priceDiff'
        , 'pricedownFlg'
        , 'seasonFlg'
      ];

        var ret = {};
        var i;
        for (i in fields) {
          ret[fields[i]] = this.getSortMarkCssClass(fields[i]);
        }

        return ret;
      }
    },
    methods: {

      /**
       * 値下げ（・戻し）対象判定
       */
      isDiscountTarget: function(item) {
        var today = $.Plusnao.Date.getAddDate(null, 0);

        return item.priceDiff != 0
          && item.pricedownFlg != 0
          && item.seasonFlg != 0
        ;
      },

      /**
       * 件数取得
       */
      getNumInfo: function() {
        var self = this;

        var result = {
            total: 0
          , targetTotal: 0
          , targetDown: 0
          , targetUp: 0
          , seasonOff: 0
          // , targetKeep: 0 これは変更なしということで不要
        };

        for (var i in self.list) {
          var item = self.list[i];
          result.total++;
          if (self.isDiscountTarget(item)) {
            result.targetTotal++;
            if (item.priceDiff > 0) {
              result.targetUp++;
            } else if (item.priceDiff < 0) {
              result.targetDown++;
            }
          }
          if (item.seasonFlg == 0) {
            result.seasonOff++;
          }
        }

        return result;
      },

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
      },

      /**
       * ページ送り
       */
      showPrev: function(event) {
        event.preventDefault();
        if (! this.isStartPage) {
          this.page--;
        }
      },
      showNext: function(event) {
        event.preventDefault();
        if (! this.isEndPage) {
          this.page++;
        }
      },
      showPage: function(page, event) {
        event.preventDefault();
        if (page >= 1 && page <= this.pageNum) {
          this.page = page;
        }
      },

      /**
       * ページ判定
       */
      isPage: function(num) {
        return (this.page === parseInt(num));
      },

      /**
       * 再計算、値下げ確定 フォーム実行
       * @param mode
       * @param event
       * @returns {boolean}
       */
      submitForm: function(mode, event) {
        var label = event.target;
        var actionUrl = $(label).data('url');

        var $form = $(this.$el).find('#functionForm');
        $form.attr('action', actionUrl);

        $form.submit();
      },


      UpdateDiscountSeasonSetting: function() {
        var self = this;

        //　ボタンの表示が無効になっててもクリックは効いてしまうようなのでこれで回避
        if (self.isApplyButtonDisabled) return;

        // Show loading
        $.Vendor.WaitingDialog.show('loading ...');

        var url = self.UpdatePricedownSettingsUrl;
        var data = [];
        for (var i = 0; i < self.list.length; i++) {
          data.push({
              'daihyo_syohin_code': self.list[i].daihyoSyohinCode
            , 'discount_season_setting': self.list[i].discountSeasonSetting
            , 'pricedown_flg': self.list[i].pricedownFlg
          })
        }

        $.ajax({
            type: "POST"
          , url: url
          , dataType: "json"
          , data: JSON.stringify(data)
        })
          .done(function(result) {
            if (result.valid) {
              self.isApplyButtonDisabled = true;
              $('#modalCompleteDialog').modal(
                {'show':true}
              );

            } else {
              alert('チェックボックスの反映に失敗しました。');

            }
          })
          .fail(function(stat) {
            alert('チェックボックスの反映でエラーが発生しました。');

          })
          . always(function() {

            // Show loading
            $.Vendor.WaitingDialog.hide();

          });
      }

    }
  });

  // 値下確定 モーダル
  var vmDiscountProcessModal = new Vue({
    el: '#modalDiscountProcess',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert-info'
      , notices: []
      , noticeClass: 'alert-warning'
      , noticeHidden: true
      , queueUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {

        // 件数取得
        var info = vmProductsDiscountListTable.getNumInfo();

        var message = '値下げ確定を行います。\n\n';

        message += '対象件数: ' + info.targetTotal + ' 件\n';
        message += '  内 値下げ: ' + info.targetDown + '\n';
        message += '  内 値上げ: ' + info.targetUp + '\n';

        message += '\n';
        message += '※絞込で表示されていない商品も一括で対象になります。\n\nよろしいですか？';

        self.message = message;
        self.messageClass = 'alert-success';

        /*
        if (result.notices.length > 0) {
          self.notices = result.notices;
          self.noticeHidden = false;
        }
        */

        $('.modal-footer button.btn-primary', self.$el).show();
      });
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });

          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 画面遷移時にチェックボックスの反映がボタンが押されてなかったときに警告を出す
  /*window.addEventListener("beforeunload", function (e) {
    //if (!vmProductsDiscountListTable.isApplyButtonDisabled) return;

    // 今は任意のメッセージを表示することはできくなったらしい
    var confirmationMessage = "チェックボックスが反映されていません";

    e.returnValue = confirmationMessage;     // Gecko and Trident
    return confirmationMessage;              // Gecko and WebKit
  });*/




});
