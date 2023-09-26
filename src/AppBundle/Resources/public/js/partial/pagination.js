/**
 * ページ送り コンポーネント用JS
 * Vue.js >= 2.0.0
 */
Vue.component('parts-table-pagination', {
    template: '#partsTablePagination'
  , delimiters: ['(%', '%)']
  , props: {
       // 設定値: 1ページ表示件数
      'initPageItemNum' : {
          type: Number
        , default: 20
    }
    // 設定値: ページリンク 表示最大件数
    , 'initPageListMaxLength' : {
        type: Number
      , default: 20
    }
    // 設定値: 1ページ表示件数 プルダウン
    , initPageItemNumList: {
        type: Array
      , default: function() { return []; }
    }

    // 現在のページ
    , 'page' : {
        type: Number
      , default: 1
    }
    // データ件数
    , 'itemNum' : {
        type: Number
      , required: true
    }

    // pagination テーブルのID (CSS指定などに利用)
    , elementId: {
        type: String
      , required: false
      , default: null
    }

    // pageItemNumを変更するための差し込みプロパティ
    , relatedPageItemNum: {
        type: Number
      , required: false
      , default: null
    }
  }

  , data: function() {
    return {
        pageItemNum: this.initPageItemNum
      , pageListMaxLength: this.initPageListMaxLength
      , pageItemNumList: this.initPageItemNumList
    };
  }
  , mounted: function() {
    this.$nextTick(function () {
      // console.log(this.pageNum);
      // console.log(this.pageList);
    });
  }
  , watch: {
    relatedPageItemNum: function() {
      if (this.relatedPageItemNum) {
        this.pageItemNum = this.relatedPageItemNum;
      }
    }
  }

  , computed: {
    displayItemNum: function() {
      return (this.itemNum.toLocaleString());
    }

    , isStartPage: function(){
      return (this.page == 1);
    }

    , isEndPage: function(){
      return (this.page == this.pageNum);
    }

    /// 最大ページ数
    , pageNum: function() {
      return Math.ceil(this.itemNum / this.pageItemNum);
    }

    , pageList: function() {

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
        //var listQuarter = Math.floor(this.pageListMaxLength / 4);
        //if (!listQuarter) {
        //  listQuarter = 1;
        //}

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

    , pageInfo: function() {
        return {
            pageItemNum: this.pageItemNum
          , pageListMaxLength: this.pageListMaxLength
          , page: this.page
          , itemNum: this.itemNum
          , pageNum: this.pageNum
      }
    }

    , pageFirstItemIndex: function() {
      return this.pageItemNum * ( this.page - 1 ) + 1;
    }
    , pageLastItemIndex: function() {
      var last = this.pageFirstItemIndex - 1 + this.pageItemNum;
      return last < this.itemNum ? last : this.itemNum;
    }
  }

  , methods: {
    isNaN: function() { return false; }

    /**
     * ページ送り
     */
    , showPrev: function(event) {
      event.preventDefault();
      if (! this.isStartPage) {
        this.showPage(this.page - 1);
      }
    }
    , showNext: function(event) {
      event.preventDefault();
      if (! this.isEndPage) {
        this.showPage(this.page + 1);
      }
    }
    , showPage: function(page, event) {
      if (event) {
        event.preventDefault();
      }
      if (page >= 1 && page <= this.pageNum) {
        var info = this.pageInfo;
        info.page = page;
        this.$emit('change-page', info);
      }
    }

    // 表示件数変更
    // ※1ページ目に戻る。
    , changePageItemNum: function() {
      this.showPage(1);
    }

    /**
     * ページ判定
     */
    , isPage: function(num) {
      return (this.page === parseInt(num));
    }
  }
})
;
