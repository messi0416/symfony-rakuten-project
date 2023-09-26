/**
 * 統計処理画面用 JS
 */
$(function() {

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
        if (autoHide === undefined) {
          autoHide = true;
        }

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


  // 原価率一覧テーブル 行コンポーネント
  Vue.component('result-item', {
    template: "#result-item",
    props: [
        'item'
      , 'currentMode'
    ],
    computed: {
      displayItemCount: function() {
        return $.Plusnao.String.numberFormat(this.item.itemCount);
      },
      displayStockAmount: function() {
        return $.Plusnao.String.numberFormat(this.item.stockAmount);
      },
      displayProfitA: function() {
        return $.Plusnao.String.numberFormat(this.item.dataA.profit);
      },
      displayAmountA: function() {
        return $.Plusnao.String.numberFormat(this.item.dataA.amount);
      },
      displayVoucherNumA: function() {
        return $.Plusnao.String.numberFormat(this.item.dataA.voucherNum);
      },
      displayProfitB: function() {
        return $.Plusnao.String.numberFormat(this.item.dataB.profit);
      },
      displayAmountB: function() {
        return $.Plusnao.String.numberFormat(this.item.dataB.amount);
      },
      displayVoucherNumB: function() {
        return $.Plusnao.String.numberFormat(this.item.dataB.voucherNum);
      },
      costRateMove: function() {
        if (this.item.costRate == this.item.costRateForm) {
          return 'hidden';
        } else {
          return this.item.costRate < this.item.costRateForm ? 'fa fa-chevron-up text-info mr3' : 'fa fa-chevron-down text-info mr3';
        }
      },
      profitMove: function() {
        if (this.item.dataA.profit == this.item.dataB.profit) {
          return 'hidden';
        } else {
          return this.item.dataA.profit < this.item.dataB.profit ? 'fa fa-arrow-up text-success mr3' : 'fa fa-arrow-down text-danger mr3';
        }
      },
      isModeCostRate: function() {
        return this.currentMode == 'costRate';
      }
    }
  });

  // 原価率一覧テーブル
  var vmCostRatesTable = new Vue({
    el: '#costRatesTable',
    data: {
      currentMode: 'costRate',
      modeList: [
          'costRate'
        , 'allProducts'
      ],
      dataLoaded: {
          costRate: false
        , allProducts: false
      },
      loadParams: null,
      nowLoading: false,

      list: {
          costRate: [] /* 原価率設定用データ */
        , allProducts: [] /* 全商品データ */
      },

      apiUrl: null,
      urlLoadDataCostRate: null,
      urlLoadDataAllProducts: null,

      // 揺さぶり更新済み
      isUnsettled: false,

      // 並び順指定
      sortField: 'dataB.profit',
      sortReverse: -1,
      sortMarks: {
          "sireCode"              : { cssClass: "", show: false }
        , "sireName"              : { cssClass: "", show: false }
        , "itemCount"             : { cssClass: "", show: false }
        , "stockAmount"           : { cssClass: "", show: false }
        , "costRate"              : { cssClass: "", show: false }
        , "costRateForm"          : { cssClass: "", show: false }
        , "dataB.costRateAverage" : { cssClass: "", show: false }
        , "dataB.profit"          : { cssClass: "fa fa-sort-amount-desc text-info", show: true }
        , "dataB.amount"          : { cssClass: "", show: false }
        , "dataA.costRateAverage" : { cssClass: "", show: false }
        , "dataA.profit"          : { cssClass: "", show: false }
        , "dataA.amount"          : { cssClass: "", show: false }
      }
    },
    created: function () {
    }
    , ready: function() {
      var self = this;

      self.apiUrl = $(self.$el).data('url');
      self.urlLoadDataCostRate = $(self.$el).data('urlLoadDataCostRate');
      self.urlLoadDataAllProducts = $(self.$el).data('urlLoadDataAllProducts');

    }
    , computed: {
      currentData: function() {
        return this.list[this.currentMode];
      },

      tableComment: function() {
        switch (this.currentMode) {
          case 'costRate':
            return "除外商品：商品別原価率あり・価格非連動（値下げ・モール別含む）/ 除外受注：SHOPLIST・フリーオーダー";
          default:
            return '';
        }
      }
    }
    , methods: {

      changeMode: function(mode) {
        this.currentMode = mode;

        // 未ロードならデータロード
        if (this.loadParams && !this.dataLoaded[mode]) {
          this.loadData();
        }
      },

      resetLoadParams: function(params) {
        // 原価率設定データ、全商品データともリセット
        for (var i in this.modeList) {
          var mode = this.modeList[i];
          this.list[mode] = [];
          this.dataLoaded[mode] = false;
        }

        this.loadParams = params;
      },

      loadData: function() {
        var self = this;

        var url;
        switch (self.currentMode) {
          case 'costRate':
            url = self.urlLoadDataCostRate;
            break;
          case 'allProducts':
            url = self.urlLoadDataAllProducts;
            break;
        }
        if (!url) {
          return;
        }

        var params = {
          app_sales_research_cost_rate_term : {
              dateAStart: this.loadParams.dateAStart
            , dateAEnd: this.loadParams.dateAEnd
            , dateBStart: this.loadParams.dateBStart
            , dateBEnd: this.loadParams.dateBEnd
          }
        };

        self.nowLoading = true;

        $.ajax({
            type: "POST"
          , url: url
          , dataType: "json"
          , data: params
        }).done(function(result) {

          if (result.status == 'ok') {

            self.list[self.currentMode] = [];

            // とりあえず配列に変換しなければ始まらない
            $(Object.keys(result.a).sort(function(first, second) {
              var firstOrder = Number(result.a[first]["表示順"]);
              var secondOrder = Number(result.a[second]["表示順"]);
              if (firstOrder == secondOrder) { return 0; }
              return  firstOrder > secondOrder ? 1 : -1;
            })).each( function(i, sireCode) {
              var dataA = result.a[sireCode];
              var dataB = result.b[sireCode];
              self.list[self.currentMode].push({
                sireCode: dataA.sire_code,
                sireName: dataA.sire_name,
                costRate: Number(dataA.cost_rate),
                costRateForm: Number(dataB.cost_rate_average), // Bの値を基準に動く
                itemCount: Number(dataA.item_count) || 0,
                stockAmount: Number(dataA.stock_amount) || 0,
                displayOrder: Number(dataA["表示順"]),
                dataA: {
                  costRateAverage: Number(dataA.cost_rate_average) || 0,
                  profit: Number(dataA["粗利額"]) || 0,
                  amount: Number(dataA["伝票金額"]) || 0,
                  voucherNum: Number(dataA.voucher_num) || 0
                },
                dataB: {
                  costRateAverage: Number(dataB.cost_rate_average) || 0,
                  profit: Number(dataB["粗利額"]) || 0,
                  amount: Number(dataB["伝票金額"]) || 0,
                  voucherNum: Number(dataB.voucher_num) || 0
                },
                isTarget: true,
                isUpdateTarget: false,
                isUnsettledTarget: false,
                arrowShowUp: false,
                arrowShowDown: false
              });
            });

            self.dataLoaded[self.currentMode] = true;
            self.isUnsettled = false;

          } else {
            var message = result.message ? result.message : 'データが取得できませんでした。'
            alert(message);
          }

        }).fail(function(stat) {
          console.log(stat);
          alert('データの読込に失敗しました。');

        }).always(function() {
          self.nowLoading = false;

        });

      },

      updateRates: function(amountUp, amountDown, amountAdditional, threshold, voucherNum) {
        var self = this;

        $(this.list.costRate).each(function(i, row){

          row.arrowShowUp = false;
          row.arrowShowDown = false;

          // 伝票数閾値チェック
          // A,Bの伝票数が両方とも閾値に達していなければ対象外
          if (row.dataA.voucherNum < voucherNum && row.dataB.voucherNum < voucherNum) {
            row.isTarget = false;
            return; // continue
          }

          var aProfit = row.dataA.profit;
          var bProfit = row.dataB.profit;

          // 閾値計算 Aが基準
          var diff = bProfit - aProfit;
          var diffRate = aProfit ? (diff / Math.abs(aProfit) * 100) : 999999;

          var aAverage = row.dataA.costRateAverage;
          var bAverage = row.dataB.costRateAverage;

          // 閾値判定
          if (Math.abs(diffRate) > threshold) {
            row.isTarget = true;

            // 固定変動
            row.costRateForm = Number(row.costRateForm) + amountAdditional;

            // 粗利がマイナスであれば、原価率を下げる（応急対応指示仕様 ※どんどん安くしても仕方がない？）
            if (bProfit < 0) {
              // 下げる
              row.arrowShowDown = true;
              row.costRateForm = Number(row.costRateForm) - amountDown;
              row.isUpdateTarget = true;

              // 下限は40%
              if (row.costRateForm < 40) {
                row.costRateForm = 40;
              }

            // 粗利が増えていれば b平均へ近づける方向で原価率を更新
            } else if (diffRate > 0) {
              if (bAverage > aAverage) {
                // 上げる
                row.arrowShowUp = true;
                row.costRateForm = Number(row.costRateForm) + amountUp;
                row.isUpdateTarget = true;

              } else if (bAverage < aAverage) {
                // 下げる
                row.arrowShowDown = true;
                row.costRateForm = Number(row.costRateForm) - amountDown;
                row.isUpdateTarget = true;

              } else {
                // 平均が変わっていなければ変えない
              }
            // 粗利が減っていれば、a平均へ近づける方向で原価率を更新
            } else {
              if (bAverage > aAverage) {
                // 下げる
                row.arrowShowDown = true;
                row.costRateForm = Number(row.costRateForm) - amountDown;
                row.isUpdateTarget = true;

              } else if (bAverage < aAverage) {
                // 上げる
                row.arrowShowUp = true;
                row.costRateForm = Number(row.costRateForm) + amountUp;
                row.isUpdateTarget = true;

              } else {
                // 平均が変わっていなければ変えない
              }
            }
          }

        });
      },

      /**
       * 揺さぶり更新
       */
      unsettleRates: function(e, amountUp, amountDown, amountAdditional, threshold, dateAStart, dateAEnd, dateBStart, dateBEnd) {
        var self = this;

        var $img = $('<img>').attr('src', vmGlobalMessage.loadingImageUrl);
        $(e.target).append($img);

        // 揺さぶり対象 取得
        var url = self.apiUrl + 'get_settled_cost_rate_vendors';
        var data = {
            threshold  : threshold
          , dateAStart : dateAStart
          , dateAEnd   : dateAEnd
          , dateBStart : dateBStart
          , dateBEnd   : dateBEnd
        };
        $.ajax({
            type: "GET"
          , url: url
          , dataType: "json"
          , data: data
        }).done(function(data) {

          // 数値扱いされる仕入コードが出てきたため(1000～)、全て文字列へ変換
          for (var i in data) {
            data[i] = data[i].toString()
          }

          $(self.list.costRate).each(function(i, row) {
            if ($.inArray(row.sireCode, data) !== -1) {

              // 伝票数範囲外だった場合、あるいは すでに自動更新されている場合はスキップ
              if (!row.isTarget || row.isUpdateTarget) {
                return; // continue;
              }
              row.isUnsettledTarget = true;

              var rand = Math.floor(Math.random() * 2);
              if (rand == 0) { // 下げる
                row.costRateForm -= amountDown;
                row.arrowShowUp = false;
                row.arrowShowDown = true;

              } else { // 上げる
                row.costRateForm += amountUp;
                row.arrowShowUp = true;
                row.arrowShowDown = false;
              }
            }
          });

        }).fail(function(st) {

          alert('対象データが取得できませんでした。');
        }).always(function() {
          $img.remove();
        });
      },

      resetForm: function() {
        $(this.list.costRate).each(function(i, row){
          row.costRateForm = row.dataB.costRateAverage;
          row.isTarget = true; // 伝票数条件に合致しているか（再計算するまでは常にtrue）
          row.isUpdateTarget = false; // 「再計算」で更新されたか
          row.isUnsettledTarget = false; // 揺さぶりで更新されたか
          row.arrowShowUp = false;
          row.arrowShowDown = false;
        });
      },

      /**
       * 一覧 並び順変更
       * @param fieldName
       */
      switchSort: function(fieldName) {

        // 現在のマークを削除
        if (this.sortMarks[this.sortField] != undefined) {
          this.sortMarks[this.sortField].cssClass = '';
          this.sortMarks[this.sortField].show = false;
        }

        if (this.sortField == fieldName) {
          // 降順 -> 昇順
          if (this.sortReverse == -1) {
            this.sortReverse = 1;

          // デフォルトに戻る
          } else {
            this.sortField = 'displayOrder';
            this.sortReverse = 1;
          }

        } else {
          this.sortField = fieldName;
          this.sortReverse = -1; // 降順が先
        }

        // 新しいマークを表示
        if (this.sortMarks[this.sortField] != undefined) {
          this.sortMarks[this.sortField].cssClass = this.sortReverse == -1 ? 'fa fa-sort-amount-desc text-info' : 'fa fa-sort-amount-asc text-info';
          this.sortMarks[this.sortField].show = true;
        }
      }
    }
  });

  // 期間絞込フォーム
  var vmFunctionBlock = new Vue({
    el: '#functionBlock',
    data: {
      rate_change_amount_up: 0,
      rate_change_amount_down: 0,
      rate_change_amount_additional: 0,
      change_threshold: 0,
      minimum_voucher: 0,
      settled_threshold: 0,
      isUnsettled: false,
      isSaveEnabled: false,
      dateAStart: null,
      dateAEnd: null,
      dateBStart: null,
      dateBEnd: null,
      moveDays: 1,


      urlSaveSetting: null
    },
    computed: {
      /// モード判定
      isModeCostRate: function() {
        return vmCostRatesTable.currentMode == 'costRate';
      }
    },
    ready: function() {
      var self = this;

      self.urlSaveSetting = $(this.$el).data('urlSaveSetting');
    },
    methods: {

      /// データ読込
      submitTermForm: function() {
        var params = {
            dateAStart: this.dateAStart
          , dateAEnd: this.dateAEnd
          , dateBStart: this.dateBStart
          , dateBEnd: this.dateBEnd
        };

        vmCostRatesTable.resetLoadParams(params);
        vmCostRatesTable.loadData();
      },
      updateRates: function(e) {
        var self = this;

        this.resetUpdateRatesForm();
        vmCostRatesTable.updateRates(this.rate_change_amount_up, this.rate_change_amount_down, this.rate_change_amount_additional, this.change_threshold, this.minimum_voucher);
        this.isSaveEnabled = true;

        console.log();

        // 設定値を保存する
        // 揺さぶり対象 取得
        var url = self.urlSaveSetting;
        var data = {
            change_amount_up   : this.rate_change_amount_up
          , change_amount_down : this.rate_change_amount_down
          , change_amount_additional : this.rate_change_amount_additional
          , change_threshold   : this.change_threshold
          , minimum_voucher    : this.minimum_voucher
          , settled_threshold  : this.settled_threshold
        };
        $.ajax({
            type: "POST"
          , url: url
          , dataType: "json"
          , data: data
        }).done(function(data) {
        }).fail(function(st) {
          console.log(st);
        }).always(function() {
        });
      },

      unsettleRates: function(e) {
        if (this.isUnsettled) {
          alert('すでに揺さぶり加算済みです。');
          return;
        }
        vmCostRatesTable.unsettleRates(e, this.rate_change_amount_up, this.rate_change_amount_down, this.rate_change_amount_additional, this.settled_threshold, this.dateAStart, this.dateAEnd, this.dateBStart, this.dateBEnd);
        this.isUnsettled = true;
        this.isSaveEnabled = true;
      },
      resetUpdateRatesForm: function() {
        vmCostRatesTable.resetForm();

        this.isUnsettled = false;
        this.isSaveEnabled = false;
      },
      saveRates: function() {
        if (!this.isSaveEnabled) {
          return;
        }

        if (confirm("現在の更新値で一括更新してよろしいですか？")) {
          $('#updateForm').submit();
        }
      },

      /**
       * 日付選択
       */
      /// 前々日 ～ 前日
      selectOneDay: function() {
        var yesterday = $.Plusnao.Date.getYesterday();
        this.dateBStart = $.Plusnao.Date.getDateString(yesterday);
        this.dateBEnd = $.Plusnao.Date.getDateString(yesterday);

        var twoDaysAgo = $.Plusnao.Date.getYesterday(yesterday);
        this.dateAStart = $.Plusnao.Date.getDateString(twoDaysAgo);
        this.dateAEnd = $.Plusnao.Date.getDateString(twoDaysAgo);

        this.moveDays = 1; // 初期値
      },
      /// 7日前 ～ 前日, 14日前 ～ 8日前
      selectOneWeek: function() {
        this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7));
        this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

        this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -14));
        this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -8) );

        this.moveDays = 7; // 初期値
      },
      /// 30日前 ～ 前日, 60日前 ～ 31日前 ※日数を各月同じにするために、30日固定とする
      selectOneMonth: function() {
        this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -30));
        this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

        this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -60));
        this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -31) );

        this.moveDays = 30; // 初期値
      },
      /// 1年前 ～ 前日, 2年前 ～ １年前 ※こちらも365日固定
      selectOneYear: function() {
        this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -365));
        this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

        this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -730));
        this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -366) );

        this.moveDays = 30; // 初期値
      },
      /// 日付範囲移動
      moveDayRange: function(direction, days) {
        if (!days) {
          days = direction == 'backward' ? -(this.moveDays) : this.moveDays;
        }

        this.dateBStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(this.dateBStart), days));
        this.dateBEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(this.dateBEnd), days));
        this.dateAStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(this.dateAStart), days));
        this.dateAEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(this.dateAEnd), days));
      }
    }
  });

  // 楽天キーワード検索URL
  const KEYWORD_SEARCH_URL = 'https://search.rakuten.co.jp/search/mall/';
  const KEYWORD_SEARCH_PARAM = '/?l-id=s_search&l2-id=shop_header_search';

  // 楽天キーワード一覧画面 一覧表
  var vmRakutenKeywordRankingListTable = new Vue({
    el: '#rakutenKeywordRankingListTable'
    , data: {
      dateComparisonSearchList: [] // 日付比較検索リスト
      , keywordSearchList: [] // キーワード検索リスト
      , dateComparisonSearchUrl: null
      , keywordSearchUrl: null
      , initialized: false
      , searchParameter: null
      , searchParams: {} 
      , showDateComparisonSearchList : false
      , showKeywordSearchList : false
      , conditions: null
      , targetDateList: []
    }
    , ready: function() {
      this.$nextTick(function () {
        this.dateComparisonSearchUrl = $(this.$el).data('dateComparisonSearchUrl');
        this.keywordSearchUrl = $(this.$el).data('keywordSearchUrl');
        var searchParameter = new $.Plusnao.SearchParameter;
        var dateOptions = {
          language: 'ja'
          , format: 'yyyy-mm-dd'
          , autoclose: true
        };
        $('#targetDate').datepicker(dateOptions);
        $('#diffTargetDate').datepicker(dateOptions);
        $('#targetDateFrom').datepicker(dateOptions);
        $('#targetDateTo').datepicker(dateOptions);

        this.searchParams = searchParameter.getParams();
        this.searchParameter = searchParameter;
        this.initialized = true;
      });
    }
    , computed: {
      showDateComparisonSearchArea: function() {
        return this.showDateComparisonSearchList;
      }
      , showKeywordSearchArea: function() {
        return this.showKeywordSearchList;
      }
    }
    , methods: {
      showSearchDateComparison: function() {
        // 初期化が済んでいない場合にはreturn
        if (!this.initialized) {
          return;
        }
        this.initialized = false;
        var self = this;
        self.dateComparisonSearchList.splice(-self.dateComparisonSearchList.length);
        self.keywordSearchList.splice(-self.keywordSearchList.length);
        self.showDateComparisonSearchList = false;
        self.showKeywordSearchList = false;
        vmGlobalMessage.clear();
        // データ読み込み処理
        var data = {
          conditions: {
              target_date           : self.searchParams.targetDate
              , diff_target_date    : self.searchParams.diffTargetDate
              , limit               : self.searchParams.limit
          }
        };
        self.conditions = data.conditions;
        $.ajax({
            type: "GET"
          , url: self.dateComparisonSearchUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status == 'ok') {
              self.list = [];
              if (result.list.length === 0) {
                vmGlobalMessage.setMessage('検索対象日、比較対象日どちらのデータも存在しません。', 'alert alert-info', false);
              } else {
                for (var i = 0; i < result.list.length; i++) {
                  var item = result.list[i];
                  var row = {};
                  if (result.resultListStatus === 'TARGET_DATE_ONLY') {
                    row = {
                      rank : item.rank
                      , keyword1 : ''
                      , url1 : ''
                      , keyword2 : item.keyword
                      , url2 : KEYWORD_SEARCH_URL + item.keyword + KEYWORD_SEARCH_PARAM
                      , fluctuation : '-'
                    };
                  } else if (result.resultListStatus === 'DIFF_TARGET_DATE_ONLY') {
                    row = {
                      rank : item.rank
                      , keyword1 : item.keyword
                      , url1 : KEYWORD_SEARCH_URL + item.keyword + KEYWORD_SEARCH_PARAM
                      , keyword2 : ''
                      , url2 : ''
                      , fluctuation : '-'
                    };
                  } else {
                    row = {
                      rank : item.rank
                      , keyword1 : item.keyword1
                      , url1 : KEYWORD_SEARCH_URL + item.keyword1 + KEYWORD_SEARCH_PARAM
                      , keyword2 : item.keyword2
                      , url2 : KEYWORD_SEARCH_URL + item.keyword2 + KEYWORD_SEARCH_PARAM
                      , fluctuation : item.fluctuation == null ? '-' : item.fluctuation
                    };
                  }
                  self.dateComparisonSearchList.push(row);
                }
                self.showDateComparisonSearchList = true;
              }
            } else {
              var message = result.message.length > 0 ? result.message : '検索できませんでした。';
              vmGlobalMessage.setMessage(message, 'alert alert-danger', false);
            }
          })
          .fail(function(stat) {
            vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger', false);
          })
          . always(function() {
            self.initialized = true;
          });
      }
      , showSearchKeyword: function() {
          // 初期化が済んでいない場合にはreturn
          if (!this.initialized) {
            return;
          }
          this.initialized = false;
          var self = this;
          self.dateComparisonSearchList.splice(-self.dateComparisonSearchList.length);
          self.keywordSearchList.splice(-self.keywordSearchList.length);
          self.showDateComparisonSearchList = false;
          self.showKeywordSearchList = false;
          vmGlobalMessage.clear();
          // データ読み込み処理
          var data = {
            conditions: {
              target_date_from    : self.searchParams.targetDateFrom
              , target_date_to    : self.searchParams.targetDateTo
              , keyword           : self.searchParams.keyword
            }
          };
          $.ajax({
              type: "GET"
            , url: self.keywordSearchUrl
            , dataType: "json"
            , data: data
          })
            .done(function(result) {
              if (result.status == 'ok') {
                self.list = [];
                if (result.list.length === 0) {
                  vmGlobalMessage.setMessage('検索結果は0件です。', 'alert alert-info', false);
                } else {
                  if (result.infoMessage != null) {
                    vmGlobalMessage.setMessage(result.infoMessage, 'alert alert-info', false);
                  }
                  for (var i = 0; i < result.list.length; i++) {
                    var item = result.list[i];
                    var row = {
                      keyword : item.keyword
                      , url : KEYWORD_SEARCH_URL + item.keyword + KEYWORD_SEARCH_PARAM
                    };
                    for (var j = 1; j <= result.targetDateList.length; j++) {
                      eval("var rank = item.rank" + j +";");
                      eval("row.rank" + j + "= rank  != null ? rank : '';");
                    }
                    self.keywordSearchList.push(row);
                  }
                  self.targetDateList = result.targetDateList;
                  self.showKeywordSearchList = true;
                }
              } else {
                var message = result.message.length > 0 ? result.message : '検索できませんでした。';
                vmGlobalMessage.setMessage(message, 'alert alert-danger', false);
              }
            })
            .fail(function(stat) {
              vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger', false);
            })
            . always(function() {
              self.initialized = true;
            });
      }
    }
  });
});
