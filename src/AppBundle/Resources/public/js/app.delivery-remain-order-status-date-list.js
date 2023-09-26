/**
 * 管理画面 注残ステータス設定日付 集計表用 JS
 *
 * Vue 2.x
 */


// 全体メッセージ
var vmGlobalMessage = new Vue({
    el: '#globalMessage'
  , delimiters: ['(%', '%)']
  , data: {
      message: ''
    , messageCssClass: ''
    , loadingImageUrl: null
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    });
  }
  , methods: {
    setMessage: function(message, cssClass, autoHide) {
      cssClass = cssClass || 'alert alert-info';
      autoHide = autoHide || true;

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
      }
    }
    , setCssClass: function(cssClass) {
      this.messageCssClass = cssClass;
    }
    , clear: function() {
      this.message = '';
      this.messageCssClass = '';
    }
    , closeWindow: function() {
      window.close();
    }
  }
});

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentDeliveryRemainOrderStatusDateListItem = {
    template: '#templateDeliveryRemainOrderStatusDateListTableRow'
  , props: [
      'item'
    , 'cursorCss'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
    displayDate: function() {
      return this.item.date ? $.Plusnao.Date.getDateString(this.item.date) : '';
    }
    , displayOrderedRowNum: function() { return $.Plusnao.String.numberFormat(this.item.orderedRowNum); }
    , displayOrderedSkuNum: function() { return $.Plusnao.String.numberFormat(this.item.orderedSkuNum); }
    , displayOrderedOrderNum: function() { return $.Plusnao.String.numberFormat(this.item.orderedOrderNum); }
    , displayOrderedPrice: function() { return $.Plusnao.String.numberFormat(this.item.orderedPrice); }
    , displayArrivedRowNum: function() { return $.Plusnao.String.numberFormat(this.item.arrivedRowNum); }
    , displayArrivedSkuNum: function() { return $.Plusnao.String.numberFormat(this.item.arrivedSkuNum); }
    , displayArrivedOrderNum: function() { return $.Plusnao.String.numberFormat(this.item.arrivedOrderNum); }
    , displayArrivedPrice: function() { return $.Plusnao.String.numberFormat(this.item.arrivedPrice); }
    , displayWaitingRowNum: function() { return $.Plusnao.String.numberFormat(this.item.waitingRowNum); }
    , displayWaitingSkuNum: function() { return $.Plusnao.String.numberFormat(this.item.waitingSkuNum); }
    , displayWaitingOrderNum: function() { return $.Plusnao.String.numberFormat(this.item.waitingOrderNum); }
    , displayWaitingPrice: function() { return $.Plusnao.String.numberFormat(this.item.waitingPrice); }
    , displayShippingRowNum: function() { return $.Plusnao.String.numberFormat(this.item.shippingRowNum); }
    , displayShippingSkuNum: function() { return $.Plusnao.String.numberFormat(this.item.shippingSkuNum); }
    , displayShippingOrderNum: function() { return $.Plusnao.String.numberFormat(this.item.shippingOrderNum); }
    , displayShippingPrice: function() { return $.Plusnao.String.numberFormat(this.item.shippingPrice); }
    , displayStockoutRowNum: function() { return $.Plusnao.String.numberFormat(this.item.stockoutRowNum); }
    , displayStockoutSkuNum: function() { return $.Plusnao.String.numberFormat(this.item.stockoutSkuNum); }
    , displayStockoutOrderNum: function() { return $.Plusnao.String.numberFormat(this.item.stockoutOrderNum); }
    , displayStockoutPrice: function() { return $.Plusnao.String.numberFormat(this.item.stockoutPrice); }

    , buttonCss: function() {
      var css = [];
      if (this.cursorCss) {
        css.push(this.cursorCss);
      }
      css.push(this.item.personLoaded ? (this.item.personList.length > 0 ? 'btn-success' : 'btn-danger') : 'btn-default');

      return css;
    }
  }
  , methods: {
    numberZeroGrayCss: function(key, target) {
      target = target || this.item;
      return target[key] === 0 ? 'gray' : null;
    }

    , numberFormat: function(key, target) {
      target = target || this.item;
      return target[key] ? $.Plusnao.String.numberFormat(target[key]) : '';
    }

    , showPersonList: function() {
      this.$emit('show-person-list', this.item);
    }
  }
};


// 一覧画面 一覧表
var vmDeliveryRemainOrderStatusDateListTable = new Vue({
    el: '#deliveryRemainOrderStatusDateListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ

    , pageItemNum: 50
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , filterAgentCode: null
    , filterDateStart: null
    , filterDateEnd: null

    , filterOptionMonth: ''
    , filterMonthList: {}
    , filterOptionMonths: []

    , orders: {
    }

    , initAgentCode: null

    , loadPersonUrl: null
    , personListTotal: []
    , personListTotalVisible: false

    , personLoadingDates: []

  }
  , components: {
      'result-item': vmComponentDeliveryRemainOrderStatusDateListItem // 一覧テーブル
  }
  , mounted: function() {
    var self = this;

    this.$nextTick(function () {
      var i;

      self.loadPersonUrl = $(this.$el).data('loadPersonUrl');
      self.initAgentCode = $(this.$el).data('initAgentCode');
      if (self.initAgentCode) {
        self.filterAgentCode = self.initAgentCode;
      }

      self.list = [];
      self.filterMonthList = {};
      self.filterOptionMonths = [];

      for (i = 0; i < REMAIN_ORDER_STATUS_DATE_LIST.length; i++) {
        var item = REMAIN_ORDER_STATUS_DATE_LIST[i];
        var row = {
            date              : (item.date ? new Date(item.date.replace(/-/g, "/")) : null) // replace for firefox, IE
          , agentCode         : item.agent_code
          , agentName         : item.agent_name
          , orderedRowNum     : Number(item.ordered_row_num)
          , orderedSkuNum     : Number(item.ordered_sku_num)
          , orderedOrderNum   : Number(item.ordered_order_num)
          , orderedPrice      : Number(item.ordered_price)
          , arrivedRowNum     : Number(item.arrived_row_num)
          , arrivedSkuNum     : Number(item.arrived_sku_num)
          , arrivedOrderNum   : Number(item.arrived_order_num)
          , arrivedPrice      : Number(item.arrived_price)
          , waitingRowNum    : Number(item.waiting_row_num)
          , waitingSkuNum    : Number(item.waiting_sku_num)
          , waitingOrderNum  : Number(item.waiting_order_num)
          , waitingPrice     : Number(item.waiting_price)
          , shippingRowNum    : Number(item.shipping_row_num)
          , shippingSkuNum    : Number(item.shipping_sku_num)
          , shippingOrderNum  : Number(item.shipping_order_num)
          , shippingPrice     : Number(item.shipping_price)
          , stockoutRowNum    : Number(item.stockout_row_num)
          , stockoutSkuNum    : Number(item.stockout_sku_num)
          , stockoutOrderNum  : Number(item.stockout_order_num)
          , stockoutPrice     : Number(item.stockout_price)

          , personList: []
          , personListVisible: false
          , personLoaded: false
        };

        self.list.push(row);

/*
        if (row.date) {
          var m = moment(row.date);
          var key = m.format('YYYY/MM');
          if (!self.filterMonthList[key]) {
            self.filterMonthList[key] = {
                key: key
              , display: m.format('YYYY/MM')
              , dateStart: m.format('YYYY-MM-01')
              , dateEnd: m.endOf('month').format('YYYY-MM-DD')
            };
          }
        }
*/
      }

      var keys = Object.keys(self.filterMonthList);
      keys.sort(function(a, b) {
        return a == b ? 0 : (a > b ? -1 : 1);
      });

      for(var k in keys) {
        self.filterOptionMonths.push(self.filterMonthList[keys[k]]);
      }

      $('#filterDateStart', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateStart = $(this).datepicker('getDate');
          // self.filterOptionMonth = '';
          self.resetTotal();
        }
        , clearDate: function() {
          self.filterDateStart = null;
          // self.filterOptionMonth = '';
          self.resetTotal();
        }
      });

      $('#filterDateEnd', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateEnd = $(this).datepicker('getDate');
          // self.filterOptionMonth = '';
          self.resetTotal();
        }
        , clearDate: function() {
          self.filterDateEnd = null;
          // self.filterOptionMonth = '';
          self.resetTotal();
        }
      });
    });
  }

  , computed: {

    totalItemNum: function() {
      return this.listData.length;
    }

    // sort, filter済みデータ
    , listData: function() {
      var self = this;
      var list = self.list.slice(); // 破壊防止

/*
      // 依頼先cdが未指定なら表示無し。（or 全表示。どちらかになると思うが今は安全側で非表示。）
      if (!self.filterAgentCode || self.filterAgentCode.length == 0) {
        return [];
      }


      // 絞込: 依頼先cd
      list = list.filter(function(item, i) {
        return item.agentCode == self.filterAgentCode || self.filterAgentCode == -1;
      });

      // 絞込: 日付from
      if (self.filterDateStart) {
        list = list.filter(function(item, i) {
          return item.date >= self.filterDateStart;
        });
      }
      // 絞込: 日付to
      if (self.filterDateEnd) {
        list = list.filter(function(item, i) {
          return item.date <= self.filterDateEnd;
        });
      }
*/

      return list;
    }

    , pageData: function() {
      var startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }

    , orderedRowNumTotal: function() { return this.calculateListDataTotal('orderedRowNum'); }
    , orderedSkuNumTotal: function() { return this.calculateListDataTotal('orderedSkuNum'); }
    , orderedOrderNumTotal: function() { return this.calculateListDataTotal('orderedOrderNum'); }
    , orderedPriceTotal: function() { return this.calculateListDataTotal('orderedPrice'); }
    , arrivedRowNumTotal: function() { return this.calculateListDataTotal('arrivedRowNum'); }
    , arrivedSkuNumTotal: function() { return this.calculateListDataTotal('arrivedSkuNum'); }
    , arrivedOrderNumTotal: function() { return this.calculateListDataTotal('arrivedOrderNum'); }
    , arrivedPriceTotal: function() { return this.calculateListDataTotal('arrivedPrice'); }
    , waitingRowNumTotal: function() { return this.calculateListDataTotal('waitingRowNum'); }
    , waitingSkuNumTotal: function() { return this.calculateListDataTotal('waitingSkuNum'); }
    , waitingOrderNumTotal: function() { return this.calculateListDataTotal('waitingOrderNum'); }
    , waitingPriceTotal: function() { return this.calculateListDataTotal('waitingPrice'); }
    , shippingRowNumTotal: function() { return this.calculateListDataTotal('shippingRowNum'); }
    , shippingSkuNumTotal: function() { return this.calculateListDataTotal('shippingSkuNum'); }
    , shippingOrderNumTotal: function() { return this.calculateListDataTotal('shippingOrderNum'); }
    , shippingPriceTotal: function() { return this.calculateListDataTotal('shippingPrice'); }
    , stockoutRowNumTotal: function() { return this.calculateListDataTotal('stockoutRowNum'); }
    , stockoutSkuNumTotal: function() { return this.calculateListDataTotal('stockoutSkuNum'); }
    , stockoutOrderNumTotal: function() { return this.calculateListDataTotal('stockoutOrderNum'); }
    , stockoutPriceTotal: function() { return this.calculateListDataTotal('stockoutPrice'); }

    , cursorCss: function() {
      return this.personLoadingDates.length > 0 ? 'cursorWait' : 'cursorAuto';
    }
  }


  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , toggleOrder: function(key) {
      if (this.orders[key]) {
        if (this.orders[key] == 1) {
          this.orders[key] = -1;
        } else {
          this.orders[key] = null;
        }
      } else {
        var k;
        for (k in this.orders) {
          this.orders[k] = null;
        }
        this.orders[key] = 1;
      }
    }

    , setFilterDateMonth: function() {

      this.resetTotal();

      var $dateStart = $('#filterDateStart', this.$el);
      var $dateEnd = $('#filterDateEnd', this.$el);

      // リセット
      if (! this.filterOptionMonth.length) {
        $dateStart.datepicker('update', '');
        $dateEnd.datepicker('update', '');
        return;
      }

      var month = this.filterMonthList[this.filterOptionMonth];
      if (month) {
        $dateStart.datepicker('setDate', month.dateStart);
        $dateEnd.datepicker('setDate', month.dateEnd);
      }
    }

    , resetTotal: function() {
      this.personListTotal = [];
      this.personListTotalVisible = false;
    }

    , numberZeroGrayCss: function(key, target) {
      target = target || this;
      return target[key] === 0 ? 'gray' : null;
    }

    , numberFormat: function(key, target) {
      target = target || this;
      return target[key] ? $.Plusnao.String.numberFormat(target[key]) : '';
    }

    /// 集計：絞込合計
    , calculateListDataTotal: function(key) {
      return this.listData.reduce(function(result, item) {
        return result + item[key];
      }, 0);
    }



    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function(key) {
      if (!this.orders[key]) {
        return '';
      }
      return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    }

    /**
     * 作業者別リスト取得・表示切り替え
     */
    , showPersonList: function(item) {
      var self = this;

      if (!item) {
        return;
      }

      // 仕入先がない。
      if (!item.agentCode) {
        return;
      }
      // 閉じるだけ
      if (item.personListVisible) {
        item.personListVisible = false;
        return;
      }

      // すでに取得済み
      if (item.personLoaded) {
        item.personListVisible = true;
        return;
      }

      self.loadPersonList(item)
        .done(function(result) {

          item.personList = result.list;

          item.personListVisible = true;
          item.personLoaded = true;
        })
        .fail(function(result) {
          item.personList = [];
          item.personListVisible = false;
          item.personLoaded = false;
        });
    }

    /**
     * 作業者別リスト（全体）取得・表示切り替え
     */
    , showPersonListTotal: function() {
      var self = this;

      // 仕入先未選択
      if (!self.filterAgentCode) {
        return;
      }

      // 閉じるだけ
      if (self.personListTotalVisible) {
        self.personListTotalVisible = false;
        return;
      }

      // 毎回取得
      self.loadPersonList()
        .done(function(result) {
          self.personListTotal = result.list;
          self.personListTotalVisible = true;
        })
        .fail(function(result) {
          self.personListTotal = [];
          self.personListTotalVisible = false;
        });
    }

    , loadPersonList: function(item) {
      var self = this;

      var data = {};

      // 全取得
      if (!item) {
        data.agent = self.filterAgentCode;
        data.dateFrom = self.filterDateStart ? $.Plusnao.Date.getDateString(self.filterDateStart) : '';
        data.dateTo = self.filterDateEnd ? $.Plusnao.Date.getDateString(self.filterDateEnd) : '';
      // 個別取得
      } else {
        data.agent = item.agentCode;
        data.date = item.date ? $.Plusnao.Date.getDateString(item.date) : '';
      }

      var deferred = $.Deferred();

      var loadingKey = (item && item.date) ? $.Plusnao.Date.getDateString(item.date) : 'total';
      if (self.personLoadingDates.indexOf(loadingKey) === -1) {
        self.personLoadingDates.push(loadingKey);
      }

      // データ取得処理
      $.ajax({
          type: "GET"
        , url: self.loadPersonUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var list = [];
            for (var i = 0; i < result.list.length; i++) {
              list.push({
                    date              : (result.list[i].date ? new Date(result.list[i].date.replace(/-/g, "/")) : null) // replace for firefox, IE
                  , person            : result.list[i].person
                  , orderedRowNum     : Number(result.list[i].ordered_row_num)
                  , orderedSkuNum     : Number(result.list[i].ordered_sku_num)
                  , orderedOrderNum   : Number(result.list[i].ordered_order_num)
                  , orderedPrice      : Number(result.list[i].ordered_price)
                  , arrivedRowNum     : Number(result.list[i].arrived_row_num)
                  , arrivedSkuNum     : Number(result.list[i].arrived_sku_num)
                  , arrivedOrderNum   : Number(result.list[i].arrived_order_num)
                  , arrivedPrice      : Number(result.list[i].arrived_price)
                  , waitingRowNum    : Number(result.list[i].waiting_row_num)
                  , waitingSkuNum    : Number(result.list[i].waiting_sku_num)
                  , waitingOrderNum  : Number(result.list[i].waiting_order_num)
                  , waitingPrice     : Number(result.list[i].waiting_price)
                  , shippingRowNum    : Number(result.list[i].shipping_row_num)
                  , shippingSkuNum    : Number(result.list[i].shipping_sku_num)
                  , shippingOrderNum  : Number(result.list[i].shipping_order_num)
                  , shippingPrice     : Number(result.list[i].shipping_price)
                  , stockoutRowNum    : Number(result.list[i].stockout_row_num)
                  , stockoutSkuNum    : Number(result.list[i].stockout_sku_num)
                  , stockoutOrderNum  : Number(result.list[i].stockout_order_num)
                  , stockoutPrice     : Number(result.list[i].stockout_price)
              });
            }
            result.list = list;

            deferred.resolve(result);

          } else {
            alert('データが取得できませんでした。' + result.message);
            deferred.reject(result);

          }
        })
        .fail(function(stat) {
          alert('データ取得時にエラーがありました。');
          console.log(stat);
          deferred.reject(stat);

        })
        . always(function() {
          var index = self.personLoadingDates.indexOf(loadingKey);
          if (index !== -1) {
            self.personLoadingDates.splice(index, 1);
          }
        });

      return deferred.promise();
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------


  }

});

