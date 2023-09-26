/**
 * 管理画面 在庫情報 JS
 */

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentStockListItem = {
    template: '#templateStockListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
    };
  }
};

const monthArray = {
  3:"3ヶ月以内",
  6:"6ヶ月以内",
  12:"1年以内",
  24:"2年以内",
  36:"3年以内",
  48:"4年以内",
  60:"5年以内",
  72:"6年以内",
  90:"7年以内",
  108:"8年以内",
};


// 一覧画面 一覧表
const vmStockList = new Vue({
    el: '#stockList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , removeUrl: null
    , filterDateStart: null
    , filterDateEnd: null

    , pageItemNum: 90
    , pageItemNumList: [ 30, 90, 365 ]
    , page: 1

    , filterOptionMonth: ''
    , filterMonthList: {}
    , filterOptionMonths: []

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentStockListItem // 一覧テーブル
  }

  , mounted: function() {
    var self = this;

    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.removeUrl = $(this.$el).data('removeUrl');

      if (STOCK_HISTORY_DATA) {
        for (let i = 0; i < STOCK_HISTORY_DATA.length; i++) {
          let item = STOCK_HISTORY_DATA[i];
          let row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }

      self.filterMonthList = {};
      self.filterOptionMonths = [];

      for(var monthKey in monthArray){
        var dt = new Date();
        var lastMonth = dt.setMonth(dt.getMonth() - monthKey);
        var m = moment(lastMonth);
        var key = m.format('YYYY/MM');
        if (!self.filterMonthList[key]) {
          self.filterMonthList[key] = {
              key: key
            , display: monthArray[monthKey]
            , dateStart: m.format('YYYY-MM-01')
            , dateEnd: m.endOf('month').format('YYYY-MM-DD')
          };
        }
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
          self.filterDateStart = $(this).val();
        }
        , clearDate: function() {
          self.filterDateStart = null;
        }
      });

      $('#filterDateEnd', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateEnd = $(this).val();
        }
        , clearDate: function() {
          self.filterDateEnd = null;
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

      // 絞込: 日付from
      if (self.filterDateStart) {
        list = list.filter(function(item, i) {
          return item.date >= self.filterDateStart + " 00:00:00";
        });
      }
      // 絞込: 日付to
      if (self.filterDateEnd) {
        list = list.filter(function(item, i) {
          return item.date <= self.filterDateEnd + " 23:59:59";
        });
      }

      return list;
    }

    , pageData: function() {
      const startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , setFilterDateMonth: function() {

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
        //$dateEnd.datepicker('setDate', month.dateEnd);
        $dateEnd.datepicker('setDate', '');
      }
    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
          date     : item.在庫日時
         ,total      :{ 
           stock    : item.現在庫数
          ,amount   : item.現在庫金額
          ,totalStock : item.総在庫数
          ,totalAmount : item.総在庫金額
         }
         ,free      :{
           stock    : item.フリー在庫数
          ,amount   : item.フリー在庫金額
         }
         ,season    :{
           stock    : item.季節内在庫数
          ,amount   : item.季節内在庫金額
         }
         ,notSeason :{
           stock    : item.季節外在庫数
          ,amount   : item.季節外在庫金額
         }
         ,move      :{
           stock    : item.移動中在庫数
          ,amount   : item.移動中在庫金額
         }
      };
    }
  }

});

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentStockIohListItem = {
    template: '#templateStockIohListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
    };
  }
};

// 一覧画面 一覧表
const vmStockIohList = new Vue({
    el: '#stockIohList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , removeUrl: null
    , filterDateStart: null
    , filterDateEnd: null

    , pageItemNum: 90
    , pageItemNumList: [ 30, 90, 365 ]
    , page: 1

    , filterOptionMonth: ''
    , filterMonthList: {}
    , filterOptionMonths: []

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentStockIohListItem // 一覧テーブル
  }

  , mounted: function() {
    var self = this;

    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.removeUrl = $(this.$el).data('removeUrl');

      if (STOCK_HISTORY_DATA) {
        for (let i = 0; i < STOCK_HISTORY_DATA.length; i++) {
          let item = STOCK_HISTORY_DATA[i];
          let row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }

      self.filterMonthList = {};
      self.filterOptionMonths = [];

      for(var monthKey in monthArray){
        var dt = new Date();
        var lastMonth = dt.setMonth(dt.getMonth() - monthKey);
        var m = moment(lastMonth);
        var key = m.format('YYYY/MM');
        if (!self.filterMonthList[key]) {
          self.filterMonthList[key] = {
              key: key
            , display: monthArray[monthKey]
            , dateStart: m.format('YYYY-MM-01')
            , dateEnd: m.endOf('month').format('YYYY-MM-DD')
          };
        }
      }
      
      var keys = Object.keys(self.filterMonthList);
      keys.sort(function(a, b) {
        return a == b ? 0 : (a > b ? -1 : 1);
      });

      for(var k in keys) {
        self.filterOptionMonths.push(self.filterMonthList[keys[k]]);
      }

      $('#filterDateStartIoh', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateStart = $(this).val();
        }
        , clearDate: function() {
          self.filterDateStart = null;
        }
      });

      $('#filterDateEndIoh', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateEnd = $(this).val();
        }
        , clearDate: function() {
          self.filterDateEnd = null;
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

      // 絞込: 日付from
      if (self.filterDateStart) {
        list = list.filter(function(item, i) {
          return item.date >= self.filterDateStart + " 00:00:00";
        });
      }
      // 絞込: 日付to
      if (self.filterDateEnd) {
        list = list.filter(function(item, i) {
          return item.date <= self.filterDateEnd + " 23:59:59";
        });
      }

      return list;
    }

    , pageData: function() {
      const startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , setFilterDateMonth: function() {

      var $dateStart = $('#filterDateStartIoh', this.$el);
      var $dateEnd = $('#filterDateEndIoh', this.$el);

      // リセット
      if (! this.filterOptionMonth.length) {
        $dateStart.datepicker('update', '');
        $dateEnd.datepicker('update', '');
        return;
      }

      var month = this.filterMonthList[this.filterOptionMonth];
      if (month) {
        $dateStart.datepicker('setDate', month.dateStart);
        //$dateEnd.datepicker('setDate', month.dateEnd);
        $dateEnd.datepicker('setDate', '');
      }
    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
          date     : item.在庫日時
         ,ordered      :{ 
           stock    : item.発注済在庫数
          ,amount   : item.発注済在庫金額
         }
         ,arrived      :{
           stock    : item.入荷済在庫数
          ,amount   : item.入荷済在庫金額
         }
         ,waited    :{
           stock    : item.出荷待在庫数
          ,amount   : item.出荷待在庫金額
         }
         ,shipped   :{
           stock    : item.出荷済在庫数
          ,amount   : item.出荷済在庫金額
         }
      };
    }
  }

});

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentStockLocationListItem = {
    template: '#templateStockLocationListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
    };
  }
};

// 一覧画面 一覧表
const vmStockLocationList = new Vue({
    el: '#stockLocationList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , removeUrl: null
    , filterDateStart: null
    , filterDateEnd: null

    , pageItemNum: 90
    , pageItemNumList: [ 30, 90, 365 ]
    , page: 1

    , filterOptionMonth: ''
    , filterMonthList: {}
    , filterOptionMonths: []

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentStockLocationListItem // 一覧テーブル
  }

  , mounted: function() {
    var self = this;

    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.removeUrl = $(this.$el).data('removeUrl');

      if (STOCK_HISTORY_DATA) {
        for (let i = 0; i < STOCK_HISTORY_DATA.length; i++) {
          let item = STOCK_HISTORY_DATA[i];
          let row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }

      self.filterMonthList = {};
      self.filterOptionMonths = [];

      for(var monthKey in monthArray){
        var dt = new Date();
        var lastMonth = dt.setMonth(dt.getMonth() - monthKey);
        var m = moment(lastMonth);
        var key = m.format('YYYY/MM');
        if (!self.filterMonthList[key]) {
          self.filterMonthList[key] = {
              key: key
            , display: monthArray[monthKey]
            , dateStart: m.format('YYYY-MM-01')
            , dateEnd: m.endOf('month').format('YYYY-MM-DD')
          };
        }
      }
      
      var keys = Object.keys(self.filterMonthList);
      keys.sort(function(a, b) {
        return a == b ? 0 : (a > b ? -1 : 1);
      });

      for(var k in keys) {
        self.filterOptionMonths.push(self.filterMonthList[keys[k]]);
      }

      $('#filterDateStartLocation', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateStart = $(this).val();
        }
        , clearDate: function() {
          self.filterDateStart = null;
        }
      });

      $('#filterDateEndLocation', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateEnd = $(this).val();
        }
        , clearDate: function() {
          self.filterDateEnd = null;
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

      // 絞込: 日付from
      if (self.filterDateStart) {
        list = list.filter(function(item, i) {
          return item.date >= self.filterDateStart + " 00:00:00";
        });
      }
      // 絞込: 日付to
      if (self.filterDateEnd) {
        list = list.filter(function(item, i) {
          return item.date <= self.filterDateEnd + " 23:59:59";
        });
      }

      return list;
    }

    , pageData: function() {
      const startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , setFilterDateMonth: function() {

      var $dateStart = $('#filterDateStartLocation', this.$el);
      var $dateEnd = $('#filterDateEndLocation', this.$el);

      // リセット
      if (! this.filterOptionMonth.length) {
        $dateStart.datepicker('update', '');
        $dateEnd.datepicker('update', '');
        return;
      }

      var month = this.filterMonthList[this.filterOptionMonth];
      if (month) {
        $dateStart.datepicker('setDate', month.dateStart);
        //$dateEnd.datepicker('setDate', month.dateEnd);
        $dateEnd.datepicker('setDate', '');
      }
    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
          date     : item.在庫日時
         ,minami      :{ // 廃止
           stock    : item.南京終在庫数
          ,amount   : item.南京終在庫金額
         }
         ,butai      :{ // 廃止
           stock    : item.舞台在庫数
          ,amount   : item.舞台在庫金額
         }
         ,tumekae    :{ // 現詰替無限
           stock    : item.詰替前在庫数
          ,amount   : item.詰替前在庫金額
         }
         ,fba   :{
           stock    : item.FBA在庫数
          ,amount   : item.FBA在庫金額
         }
         ,yabu   :{
           stock    : item.藪吉出荷在庫数
          ,amount   : item.藪吉出荷在庫金額
         }
         ,yabust   :{
           stock    : item.藪吉ストック在庫数
          ,amount   : item.藪吉ストック在庫金額
         }
         ,rsl   :{
           stock    : item.RSL在庫数
          ,amount   : item.RSL在庫金額
         }
         ,shoplist :{
           stock    : item.SHOPLIST在庫数
          ,amount   : item.SHOPLIST在庫金額
         }
         ,huru   :{
           stock    : item.古市在庫数
          ,amount   : item.古市在庫金額
         }
         ,tumehuru :{
           stock    : item.詰替古市在庫数
          ,amount   : item.詰替古市在庫金額
         }
         ,butai2   :{ // 現南京終
           stock    : item.舞台2在庫数
          ,amount   : item.舞台2在庫金額
         }
         ,byakugoji   :{
           stock    : item.白毫寺在庫数
          ,amount   : item.白毫寺在庫金額
         }
         ,nunome   :{
           stock    : item.布目在庫数
          ,amount   : item.布目在庫金額
         }
         ,yamadagawa :{
           stock    : item.山田川在庫数
          ,amount   : item.山田川在庫金額
         }
         ,kyumukai :{
           stock    : item.旧ムカイ在庫数
          ,amount   : item.旧ムカイ在庫金額
         }
        　,obitoke :{
           stock    : item.帯解在庫数
          ,amount   : item.帯解在庫金額
         }
      };
    }
  }

});

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentStockOrderListItem = {
    template: '#templateStockOrderListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
    };
  }
};

// 一覧画面 一覧表
const vmStockOrderList = new Vue({
    el: '#stockOrderList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , removeUrl: null
    , filterDateStart: null
    , filterDateEnd: null

    , pageItemNum: 90
    , pageItemNumList: [ 30, 90, 365 ]
    , page: 1

    , filterOptionMonth: ''
    , filterMonthList: {}
    , filterOptionMonths: []

    , messageState: {}
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  }
  , components: {
      'result-item': vmComponentStockOrderListItem // 一覧テーブル
  }

  , mounted: function() {
    var self = this;

    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];
      this.removeUrl = $(this.$el).data('removeUrl');

      if (STOCK_HISTORY_DATA) {
        for (let i = 0; i < STOCK_HISTORY_DATA.length; i++) {
          let item = STOCK_HISTORY_DATA[i];
          let row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }

      self.filterMonthList = {};
      self.filterOptionMonths = [];

      for(var monthKey in monthArray){
        var dt = new Date();
        var lastMonth = dt.setMonth(dt.getMonth() - monthKey);
        var m = moment(lastMonth);
        var key = m.format('YYYY/MM');
        if (!self.filterMonthList[key]) {
          self.filterMonthList[key] = {
              key: key
            , display: monthArray[monthKey]
            , dateStart: m.format('YYYY-MM-01')
            , dateEnd: m.endOf('month').format('YYYY-MM-DD')
          };
        }
      }
      
      var keys = Object.keys(self.filterMonthList);
      keys.sort(function(a, b) {
        return a == b ? 0 : (a > b ? -1 : 1);
      });

      for(var k in keys) {
        self.filterOptionMonths.push(self.filterMonthList[keys[k]]);
      }

      $('#filterDateStartOrder', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateStart = $(this).val();
        }
        , clearDate: function() {
          self.filterDateStart = null;
        }
      });

      $('#filterDateEndOrder', this.$el).datepicker({
          language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateEnd = $(this).val();
        }
        , clearDate: function() {
          self.filterDateEnd = null;
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

      // 絞込: 日付from
      if (self.filterDateStart) {
        list = list.filter(function(item, i) {
          return item.date >= self.filterDateStart + " 00:00:00";
        });
      }
      // 絞込: 日付to
      if (self.filterDateEnd) {
        list = list.filter(function(item, i) {
          return item.date <= self.filterDateEnd + " 23:59:59";
        });
      }

      return list;
    }

    , pageData: function() {
      const startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
  }
  , methods: {

      showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    , setFilterDateMonth: function() {

      var $dateStart = $('#filterDateStartOrder', this.$el);
      var $dateEnd = $('#filterDateEndOrder', this.$el);

      // リセット
      if (! this.filterOptionMonth.length) {
        $dateStart.datepicker('update', '');
        $dateEnd.datepicker('update', '');
        return;
      }

      var month = this.filterMonthList[this.filterOptionMonth];
      if (month) {
        $dateStart.datepicker('setDate', month.dateStart);
        //$dateEnd.datepicker('setDate', month.dateEnd);
        $dateEnd.datepicker('setDate', '');
      }
    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
          date     : item.在庫日時
         ,month3      :{ 
           stock    : item.在庫数_３ヶ月以内
          ,amount   : item.在庫金額_３ヶ月以内
         }
         ,month6      :{
           stock    : item.在庫数_６ヶ月以内
          ,amount   : item.在庫金額_６ヶ月以内
         }
         ,year1    :{
           stock    : item.在庫数_１年以内
          ,amount   : item.在庫金額_１年以内
         }
         ,year2    :{
           stock    : item.在庫数_２年以内
          ,amount   : item.在庫金額_２年以内
         }
         ,year3    :{
           stock    : item.在庫数_３年以内
          ,amount   : item.在庫金額_３年以内
         }
         ,year4    :{
           stock    : item.在庫数_４年以内
          ,amount   : item.在庫金額_４年以内
         }
         ,year5    :{
           stock    : item.在庫数_５年以内
          ,amount   : item.在庫金額_５年以内
         }
         ,year6    :{
           stock    : item.在庫数_６年以内
          ,amount   : item.在庫金額_６年以内
         }
         ,year7    :{
           stock    : item.在庫数_７年以内
          ,amount   : item.在庫金額_７年以内
         }
         ,year8    :{
           stock    : item.在庫数_８年以内
          ,amount   : item.在庫金額_８年以内
         }
      };
    }
  }

});


