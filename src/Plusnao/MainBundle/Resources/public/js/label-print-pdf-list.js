/**
 * ラベル印刷商品一覧 ポップアップ画面
 */

var vmLabelPrintList;
$(function() {

  // 一覧テーブル 行コンポーネント
  Vue.component('pdf-label-items', {
    template: "#pdf-label-items",
    props: [
      'item'
    ],
    data: function () {
      return {}
    }
    , methods: {
    }
  });

  window.vmLabelPrintList = new Vue({
        el: '#modalLabelPrintPdfList'
      , data: {
        caption: null

      // データ
      , list: []

      // ページ送り設定
      , pageItemNum: 12 // 設定値: 1ページ表示件数
      , pageListMaxLength: 8 // 設定値: ページリンク 表示最大件数
      , page: 1 // 現在のページ

      // toggle チェックボックス
      , allCheck: true

      // 並び順指定
      , sortField: null
      , sortOrder: -1

      , filterSyohinCode: ""
      , filterCheckOn: false
      , filterCheckOff: false

      , printStartPosition: 1
      , updateRemainNum: ""

      , nowLoading: false
    }

    , mounted: function() {
      this.$nextTick(function () {
        let self = this;
  
        self.caption = $(self.$el).data('caption');
        // イベント登録
        $(self.$el).on('show.bs.modal', function() {
          self.resetDialog();
          self.nowLoading = false;
        });
      }); 
    }

    , computed: {
      itemCount: function() {
        return this.list.length;
      }

      , filteredItemCount: function() {
        return this.listData.length;
      }

      , filteredItemPrintNumCount: function() {
        var sum = 0;
        for (var i = 0; i < this.listData.length; i++) {
          sum += Number(this.listData[i].remainNum) || 0;
        }
        return sum;
      }


      // ソート・フィルター済みリスト
      , listData: function() {

        let self = this;
        let list = self.list.slice();

        //  絞込
        list = list.filter(function(item, i) {
          let result = true;

          // 絞込: 商品コード
          if (self.filterSyohinCode.length) {
            result = result && item.syohinCode.match(self.filterSyohinCode);
          }

          // 絞込： チェックON
          if (self.filterCheckOn) {
            result = result && item.checked;
          }

          // 絞込： チェックOff
          if (self.filterCheckOff) {
            result = result && (! item.checked);
          }

          return result;
        });

        return list;
      }

      , pageData: function() {
        let startPage = (this.page - 1) * this.pageItemNum;
        return this.listData.slice(startPage, startPage + this.pageItemNum);
      }

      , isStartPage: function(){
        return (this.page == 1);
      }

      , isEndPage: function(){
        return (this.page == this.pageNum);
      }

      /// 最大ページ数 （現在のフィルタ条件を考慮）
      , pageNum: function() {
        return Math.ceil(this.listData.length / this.pageItemNum);
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

      , sortMarks: function() {

        var fields = [
          //'category'
        ];

        var ret = {};
        for (var i = 0; i < fields.length; i++) {
          ret[fields[i]] = this.getSortMarkCssClass(fields[i]);
        }

        return ret;
      }

      , cssFilterCheckOn: function() {
        return this.filterCheckOn ? 'btn-primary' : 'btn-default';
      }

      , cssFilterCheckOff: function() {
        return this.filterCheckOff ? 'btn-primary' : 'btn-default';
      }
    }
    , methods: {

      open: function(data) {
        let self = this;
        if (!data) {
          data = [];
        }

        // データに連番IDを振る。（チェックボックスtoggle用） その他初期値も設定
        for (var i = 0; i < data.length; i++) {
          data[i].originalIndex = i;
          data[i].checked = true;
        }

        self.list = data;
        
        // ページングや検索条件なども初期化
        self.pageItemNum = 12;
        self.pageListMaxLength = 8;
        self.page = 1;
        self.allCheck = true;
        self.sortField = null;
        self.sortOrder = -1;
        self.filterSyohinCode = "";
        self.filterCheckOn = false;
        self.filterCheckOff = false;
        self.printStartPosition = 1;
        self.updateRemainNum = "";
        
        $(this.$el).modal('show');
      }

      , resetDialog: function() {
        this.filterSyohinCode = "";
        this.filterCheckOn = false;
        this.filterCheckOff = false;
        this.allCheck = true;
      }


      /**
       * ページ送り
       */
      , showPrev: function(event) {
        event.preventDefault();
        if (! this.isStartPage) {
          this.page--;
        }
      }

      , showNext: function(event) {
        event.preventDefault();
        if (! this.isEndPage) {
          this.page++;
        }
      }

      , showPage: function(page, event) {
        // aタグなどでは$eventを渡してリンク挙動を抑制
        if (event) {
          event.preventDefault();
        }
        if (page >= 1 && page <= this.pageNum) {
          this.page = page;
        }
      }

      /**
       * ページ判定
       */
      , isPage: function(num) {
        return (this.page === parseInt(num));
      }

      /**
       * チェックボックス toggle
       * 対象は、現在絞りこまれているもののみ
       */
      , toggleCheckBoxes: function() {

        // listDataは更新中も頑張って(checkedの)フィルターを利かすため取りこぼしがでる。その回避で更新前に対象を抽出する。
        var targetIndexes = this.listData.map(function(item, i) {
          return item.originalIndex;
        });

        for (var i = 0; i < targetIndexes.length; i++) {
          this.list[targetIndexes[i]].checked = this.allCheck;
        }
      }

      /**
       * フィルター：チェックボックスON クリック (toggle)
       */
      , toggleFilterCheckOn: function() {
        this.filterCheckOff = false;
        this.filterCheckOn = ! this.filterCheckOn;
        this.allCheck = true;
      }

      /**
       * フィルター：チェックボックスOFF クリック (toggle)
       */
      , toggleFilterCheckOff: function() {
        this.filterCheckOn = false;
        this.filterCheckOff = ! this.filterCheckOff;
        this.allCheck = false;
      }

      /**
       * ラベル印刷PDF出力
       */
      , downloadLabelPdf: function() {

        var self = this;

        var $form = $('#labelDownloadForm');
        $form.find('input').remove(); // input(hidden) 全て削除

        // データ取得
        var listData = self.listData;
        var i;
        var row;
        for (i = 0; i < listData.length; i++) {
          row = listData[i];
          row = {
              category    : row.category.replace(/\n/g, ">")
            , productCode : row.syohinCode
            , colname     : row.colName
            , rowname     : row.rowName
            , remainNum   : row.remainNum
          };
          for (var k in row) {
            var name = "data[" + i.toString() + "][" + k + "]";
            $form.append($('<input type="hidden">').attr('name', name).val(row[k]));
          }
        }

        // 用紙印刷開始位置
        $form.append($('<input type="hidden">').attr('name', 'print_start_position').val(self.printStartPosition));

        // エラー時リダイレクト先
        $form.append($('<input type="hidden" name="redirect">').val(window.location.href));

        $form.submit();
      }

      /**
       * 全行数量変更
       */
      , reflectionRemainNum: function() {
        if (this.updateRemainNum != "" && Math.sign(this.updateRemainNum) == 1) {
          for (var i = 0; i < this.listData.length; i++) {
            this.listData[i].remainNum = Number(this.updateRemainNum);
          }
        }
      }
    }
  });

});

