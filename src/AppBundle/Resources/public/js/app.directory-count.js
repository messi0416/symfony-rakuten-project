/**
 * 出荷リスト一覧用 JS
 */

// 一覧画面 一覧テーブル 行コンポーネント
const vmDirectoryCountListItem = {
    template: '#templateDirectoryCountListTableRow'
  , props: [
     'item'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
    buttonCss: function() {
      var css = [];
      if (this.cursorCss) {
        css.push(this.cursorCss);
      }
      css.push(this.item.personLoaded ? (this.item.personList.length > 0 ? 'btn-success' : 'btn-danger') : 'btn-default');

      return css;
    }

    ,showRate: function() {
      return this.item.rate + "%";
    }
  }
  , methods: {
    
    showDirectoryChildrenCountList: function() {
      this.$emit('show-directory-children-count-list', this.item);
    }

  }
};

var dateOptions = {
  language: 'ja'
  , format: 'yyyy-mm-dd'
  , autoclose: true
};

var vmDirectoryCountList = new Vue({
  el: '#directoryCountList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , pageItemNum: 50
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1
    , modalState: {
        message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
    , messageState: new PartsGlobalMessageState()
      // 並び順指定
    , sortField: null
    , sortOrder: -1
    , personLoadingDates: []
    
    , filterField1: FIELD1_VALUE
    , filterField2: FIELD2_VALUE
    , filterField3: FIELD3_VALUE
    , filterField4: FIELD4_VALUE
    , filterField5: FIELD5_VALUE
    
    , moveDays: DAYS_VALUE
  }

  , components: {
      'result-item': vmDirectoryCountListItem // 一覧テーブル
  }



  , mounted: function() {
    var self = this;
  
    this.$nextTick(function () {
      $('#importDateFrom').datepicker(dateOptions);
      $('#importDateTo').datepicker(dateOptions);
      
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();
      this.list = [];

      if (DIRECTORY_FIELD1_LIST_DATA) {
        for (let i = 0; i < DIRECTORY_FIELD1_LIST_DATA.length; i++) {
          let item = DIRECTORY_FIELD1_LIST_DATA[i];
          let row = this.convertItem(item);

          this.list.push(row);
        }
      } else {
        this.messageState.setMessage('データがありません。', 'alert-info');
      }
    });
  }

  , computed: {

    totalItemNum: function() {
      return this.listData.length;
    }

    // sort, filter済みデータ
    , listData: function() {
      const self = this;
      const list = self.list.slice(); // 破壊防止

        // ソート
      if (self.sortField) {
        list.sort(function(a, b) {
          if (a[self.sortField] > b[self.sortField]) { return 1 * self.sortOrder; }
          if (a[self.sortField] < b[self.sortField]) { return -1 * self.sortOrder; }
          return 0;
        });
      }

      return list;
    }

    , pageData: function() {
      const startPage = (this.page - 1) * this.pageItemNum;
      return this.listData.slice(startPage, startPage + this.pageItemNum);
    }
    
    , sortMarks: function() {

        var fields = [
            'field'
          , 'cnt'
          , 'cnt_all'
          , 'cnt_sale'
        ];

        var ret = {};
        var i;
        for (i in fields) {
          ret[fields[i]] = this.getSortMarkCssClass(fields[i]);
        }

        return ret;
    }
      
    ,  filteredField1: function() {
      var ret = [];

      for(key in FIELD_LIST_DATA){
        ret.push(key);
      }

      return ret;
    }
    ,  filteredField2: function() {
      var ret = [];
      
      if(this.filterField1 != ""){
        for(key in FIELD_LIST_DATA[this.filterField1]){
          if(key.length) ret.push(key);
        }
      }

      return ret;
    }
    ,  filteredField3: function() {
      var ret = [];

      if(this.filterField2 != ""){
        for(key in FIELD_LIST_DATA[this.filterField1][this.filterField2]){
          if(key.length) ret.push(key);
        }
      }

      return ret;
    }
    ,  filteredField4: function() {
      var ret = [];

      if(this.filterField3 != ""){
        for(key in FIELD_LIST_DATA[this.filterField1][this.filterField2][this.filterField3]){
          if(key.length) ret.push(key);
        }
      }

      return ret;
    }
    ,  filteredField5: function() {
      var ret = [];

      if(this.filterField4 != ""){
        for(key in FIELD_LIST_DATA[this.filterField1][this.filterField2][this.filterField3][this.filterField4]){
          if(FIELD_LIST_DATA[this.filterField1][this.filterField2][this.filterField3][this.filterField4][key].length) ret.push(FIELD_LIST_DATA[this.filterField1][this.filterField2][this.filterField3][this.filterField4][key]);
        }
      }

      return ret;
    }

  }


  , methods: {
    selectToday: function() {
      $('#importDateFrom').datepicker('setDate', new Date);
      $('#importDateTo').datepicker('setDate', new Date);
    }
    
    , showPage: function(pageInfo) {
      this.page = pageInfo.page;
      this.pageItemNum = pageInfo.pageItemNum;
    }

    // 取得データをJS用に変換
    , convertItem: function(item) {
       return {
          field      : item.field
        , cnt: Number(item.cnt)
        , cnt_all: Number(item.cnt_all)
        , cnt_sale: Number(item.cnt_sale)
        , cnt_instant: Number(item.cnt_instant)
        , rate: Number(item.rate)
      };
    }
    
    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function(field) {
      return (field == this.sortField)
           ? (this.sortOrder == 1 ? 'fa fa-sort-amount-asc' : 'fa fa-sort-amount-desc' )
           : 'hidden';
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

    , filterField1Changed: function() {
      this.filterField2 = '';
      this.filterField3 = '';
      this.filterField4 = '';
      this.filterField5 = '';
    }
    , filterField2Changed: function() {
      this.filterField3 = '';
      this.filterField4 = '';
      this.filterField5 = '';
    }
    , filterField3Changed: function() {
      this.filterField4 = '';
      this.filterField5 = '';
    }
    , filterField4Changed: function() {
      this.filterField5 = '';
    }

      /**
       * 日付選択
       */
      /// 前々日 ～ 前日
      ,selectOneDay: function() {
        var yesterday = $.Plusnao.Date.getYesterday();
        
        $('#importDateFrom').val($.Plusnao.Date.getDateString(yesterday));
        $('#importDateTo').val($.Plusnao.Date.getDateString(yesterday));
        
        this.moveDays = 1; // 初期値
      }
      /// 7日前 ～ 前日, 14日前 ～ 8日前
      ,selectOneWeek: function() {
        $('#importDateFrom').val($.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -7)));
        $('#importDateTo').val($.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday()));

        this.moveDays = 7; // 初期値
      }
      /// 30日前 ～ 前日, 60日前 ～ 31日前 ※日数を各月同じにするために、30日固定とする
      ,selectOneMonth: function() {
        $('#importDateFrom').val($.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -30)));
        $('#importDateTo').val($.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday()));

        this.moveDays = 30; // 初期値
      }
      /// 1年前 ～ 前日, 2年前 ～ １年前 ※こちらも365日固定
      ,selectOneYear: function() {
        $('#importDateFrom').val($.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(null, -365)));
        $('#importDateTo').val($.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday()));

        this.moveDays = 30; // 初期値
      }
      /// 日付範囲移動
      , moveDayRange: function(direction, days) {
        if (!days) {
          days = direction == 'backward' ? -(this.moveDays) : this.moveDays;
        }

        $('#importDateFrom').val($.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date($('#importDateFrom').val()), days)));
        $('#importDateTo').val($.Plusnao.Date.getDateString($.Plusnao.Date.getAddDate(new Date($('#importDateTo').val()), days)));
      }

      /// 同月比較
      , selectCompareSameMonth: function(month) {

        var dateMonth = month - 1; // Dateで利用するので最初から1引いておく。

        var now = new Date();
        var targetMonthStart, targetMonthEnd, compareMonthStart, compareMonthEnd;

        // まだ過ぎていなければ 去年の月が基準
        if (now.getMonth() <= dateMonth) {
          targetMonthStart = new Date(now.getFullYear() - 1, dateMonth, 1);
        // 過ぎていれば今年の月が基準
        } else {
          targetMonthStart = new Date(now.getFullYear(), dateMonth, 1);
        }
        targetMonthEnd = new Date(targetMonthStart.getFullYear(), targetMonthStart.getMonth() + 1, 0);

        compareMonthStart = new Date(targetMonthStart.getFullYear() - 1, targetMonthStart.getMonth(), 1);
        compareMonthEnd = new Date(compareMonthStart.getFullYear(), compareMonthStart.getMonth() + 1, 0);

        $('#importDateFrom').val($.Plusnao.Date.getDateString(targetMonthStart));
        $('#importDateTo').val($.Plusnao.Date.getDateString(targetMonthEnd));

        this.moveDays = 365;
      }
  }
});
