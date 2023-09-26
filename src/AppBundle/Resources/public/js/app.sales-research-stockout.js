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

  // 期間絞込フォーム
  var vmFunctionBlock = new Vue({
    el: '#functionBlock',
    data: {
      dateStart: null,
      dateEnd: null,
      moveDays: 1
    },
    methods: {
      submitTermForm: function() {
        $('#termForm', this.$el).submit();
      },

      /**
       * 日付選択
       */
      /// 前々日 ～ 前日
      selectOneDay: function() {
        var yesterday = $.Plusnao.Date.getYesterday();
        this.dateStart = $.Plusnao.Date.getDateString(yesterday);
        this.dateEnd = $.Plusnao.Date.getDateString(yesterday);

        this.moveDays = 1; // 初期値
      },
      /// 7日前 ～ 前日, 14日前 ～ 8日前
      selectOneWeek: function() {
        this.dateStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7));
        this.dateEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

        this.moveDays = 7; // 初期値
      },
      /// 30日前 ～ 前日, 60日前 ～ 31日前 ※日数を各月同じにするために、30日固定とする
      selectOneMonth: function() {
        this.dateStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -30));
        this.dateEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

        this.moveDays = 30; // 初期値
      },
      /// 1年前 ～ 前日, 2年前 ～ １年前 ※こちらも365日固定
      selectOneYear: function() {
        this.dateStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -365));
        this.dateEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday());

        this.moveDays = 30; // 初期値
      },
      /// 日付範囲移動
      moveDayRange: function(direction, days) {
        if (!days) {
          days = direction == 'backward' ? -(this.moveDays) : this.moveDays;
        }

        this.dateStart = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(this.dateStart), days));
        this.dateEnd = $.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date(this.dateEnd), days));
      }
    }
  });

  // キャンセル率一覧テーブル 行コンポーネント
  Vue.component('result-item', {
    template: "#result-item",
    props: [
      'item'
    ],
    data: function() {
        return {
            sireCode: this.item.sireCode
          , sireName: this.item.sireName
          , itemCount: this.item.itemCount
          , stockAmount: this.item.stockAmount
          , estimatedStockCost: this.item.estimatedStockCost
          , profitNum: this.item.profitNum
          , stockoutProfitNum: this.item.stockoutProfitNum
          , stockoutProfitRate: this.item.stockoutProfitRate
          , displayOrder: this.item.displayOrder
        };
    },
    computed: {
      displayItemCount: function() {
        return $.Plusnao.String.numberFormat(this.itemCount);
      },
      displayStockAmount: function() {
        return $.Plusnao.String.numberFormat(this.stockAmount);
      },
      displayEstimatedStockCost: function() {
        return $.Plusnao.String.numberFormat(this.estimatedStockCost);
      },
      displayProfitNum: function() {
        return $.Plusnao.String.numberFormat(this.profitNum);
      },
      displayStockoutProfitNum: function() {
        return $.Plusnao.String.numberFormat(this.stockoutProfitNum);
      },
      displayStockoutProfitRate: function() {
        return ($.Plusnao.Math.round(this.stockoutProfitRate, 0)).toString();
      }
    },
    ready: function() {
    },
    methods: {
    }
  });

  // キャンセル率一覧テーブル
  var vmVendorStockoutListTable = new Vue({
    el: '#vendorStockoutListTable',
    data: {
      // データ
      list: null,

      // 並び順指定
      sortField: 'profitNum',
      sortOrder: -1
    },
    ready: function () {
      var self = this;

      // ソート処理のため、ここで数値変換をしておく。
      var list = [];
      var i;
      for (i in vendorStockoutListTableData) {
        var item = vendorStockoutListTableData[i];
        var row = {
            'sireCode': item.sire_code
          , 'sireName': item.sire_name
          , 'itemCount': Number(item.item_count) || 0
          , 'stockAmount': Number(item.stock_amount) || 0
          , 'estimatedStockCost': Number(item.estimated_stock_cost) || 0
          , 'profitNum': Number(item.profit_num) || 0
          , 'stockoutProfitNum': Number(item.stockout_profit_num) || 0
          , 'stockoutProfitRate': 0
          , 'displayOrder': Number(item.display_order) || 0
        };
        // 欠品明細率
        if (row.profitNum > 0) {
          row.stockoutProfitRate = row.stockoutProfitNum / row.profitNum * 100;
        }

        list.push(row);
      }

      this.$set('list', list);

      // tooltip 有効化
      $(this.$el).find('th span').tooltip();
    },
    computed: {
      sortMarks: function() {

        var fields = [
            'sireCode'
          , 'sireName'
          , 'itemCount'
          , 'stockAmount'
          , 'estimatedStockCost'
          , 'profitNum'
          , 'stockoutProfitNum'
          , 'stockoutProfitRate'
          , 'displayOrder'
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




});
