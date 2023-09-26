/**
 * 管理画面 コンシェルジュ用 JS
 */
$(function() {
  var clipboard = new Clipboard('.btnCopy');
  clipboard.on('success', function(e) {
    // e.clearSelection();
  });
});
//
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
      cssClass = cssClass || 'alert-info';
      autoHide = (autoHide === null) ? 5000 : Number(autoHide);

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, autoHide);
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
var vmComponentConciergeMailTemplateListItem = {
    template: '#templateConciergeMailTemplateListTableRow'
  , props: [
      'item'
  ]
  , data: function() {
    return {
    };
  }
  /* もし、this.item の直接参照がいやなら、下記のwatchでインスタンスのプロパティを更新する必要がある。
  , watch: {
    item: function() {
      this.orderDate          = this.item.orderDate;
    }
  }
  */
  , computed: {
  }
  , methods: {
    openDetail: function() {
      this.$emit('open-detail', this.item);
      return false;
    }
  }
};


// 一覧画面 一覧表
var vmConciergeMailTemplateListTable = new Vue({
    el: '#mailTemplateList'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , totalItemNum: 0 // データ総件数

    , pageItemNum: 20
    , pageItemNumList: [ 20, 50, 100 ]
    , page: 1

    , listUrl: null
    , findUrl: null
    , saveUrl: null

    , choiceList: {
        choices3: []
      , choices4: []
      , choices5: []
      , choices6: []
      , choices7: []
      , choices8: []
      , choices9: []
    }

    , filterIncludeNonActive: 0
    , filterChoices3: ''
    , filterChoices4: ''
    , filterChoices5: ''
    , filterChoices6: ''
    , filterChoices7: ''
    , filterChoices8: ''
    , filterChoices9: ''

    , activeOnly: true

    // 現在選択されている詳細item
    , currentItem: {}

    , orders: {
      //  syohinCode: null
    }

  }
  , components: {
      'result-item': vmComponentConciergeMailTemplateListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.listUrl = $(this.$el).data('listUrl');
      this.findUrl = $(this.$el).data('findUrl');
      this.saveUrl = $(this.$el).data('saveUrl');
      this.loadListData();
    });
  }
  //
  , computed: {

    filteredChoices3: function() {
      var ret = this.choiceList.choices3.slice();

      // 絞込
      if (this.activeOnly) {
        ret = ret.filter(function(choice, i) {
          return Number(choice.active) != 0;
        });
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    , filteredChoices4ByChoice3: function() {
      var ret = [];
      var choices = this.choiceList.choices4;
      if (choices && choices[this.filterChoices3]) {
        ret = choices[this.filterChoices3];

        // 絞込
        if (this.activeOnly) {
          ret = ret.filter(function(choice, i) {
            return Number(choice.active) != 0;
          });
        }
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    , filteredChoices5ByChoice4: function() {
      var ret = [];
      var choices = this.choiceList.choices5;
      if (choices && choices[this.filterChoices3] && choices[this.filterChoices3][this.filterChoices4]) {
        ret = choices[this.filterChoices3][this.filterChoices4];

        // 絞込
        if (this.activeOnly) {
          ret = ret.filter(function(choice, i) {
            return Number(choice.active) != 0;
          });
        }
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    , filteredChoices6ByChoice5: function() {
      var ret = [];
      var choices = this.choiceList.choices6;
      if (choices && choices[this.filterChoices3] && choices[this.filterChoices3][this.filterChoices4] && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5]) {
        ret = choices[this.filterChoices3][this.filterChoices4][this.filterChoices5];

        // 絞込
        if (this.activeOnly) {
          ret = ret.filter(function(choice, i) {
            return Number(choice.active) != 0;
          });
        }
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    , filteredChoices7ByChoice6: function() {
      var ret = [];
      var choices = this.choiceList.choices7;
      if (choices
        && choices[this.filterChoices3]
        && choices[this.filterChoices3][this.filterChoices4]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6]
      ) {
        ret = choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6];

        // 絞込
        if (this.activeOnly) {
          ret = ret.filter(function(choice, i) {
            return Number(choice.active) != 0;
          });
        }
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    , filteredChoices8ByChoice7: function() {
      var ret = [];
      var choices = this.choiceList.choices8;
      if (choices
        && choices[this.filterChoices3]
        && choices[this.filterChoices3][this.filterChoices4]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6][this.filterChoices7]
      ) {
        ret = choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6][this.filterChoices7];

        // 絞込
        if (this.activeOnly) {
          ret = ret.filter(function(choice, i) {
            return Number(choice.active) != 0;
          });
        }
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    , filteredChoices9ByChoice8: function() {
      var ret = [];
      var choices = this.choiceList.choices9;
      if (choices
        && choices[this.filterChoices3]
        && choices[this.filterChoices3][this.filterChoices4]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6][this.filterChoices7]
        && choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6][this.filterChoices7][this.filterChoices8]
      ) {
        ret = choices[this.filterChoices3][this.filterChoices4][this.filterChoices5][this.filterChoices6][this.filterChoices7][this.filterChoices8];

        // 絞込
        if (this.activeOnly) {
          ret = ret.filter(function(choice, i) {
            return Number(choice.active) != 0;
          });
        }
      }

      ret = ret.reduce(function(result, choice) {
        result.push(choice.name);
        return result;
      }, []);

      return ret;
    }

    // sort, filter済みデータ
    , listData: function() {
      var self = this;
      var list = self.list.slice(); // 破壊防止

      // ソート （画面表示に合わせ、 lv3, lv4, lv5, ... => lv1, lv2）
      list.sort(function(a, b) {
        if (a.choices3 > b.choices3){ return 1; } else if (a.choices3 < b.choices3) { return -1; }
        if (a.choices4 > b.choices4){ return 1; } else if (a.choices4 < b.choices4) { return -1; }
        if (a.choices5 > b.choices5){ return 1; } else if (a.choices5 < b.choices5) { return -1; }
        if (a.choices6 > b.choices6){ return 1; } else if (a.choices6 < b.choices6) { return -1; }
        if (a.choices7 > b.choices7){ return 1; } else if (a.choices7 < b.choices7) { return -1; }
        if (a.choices8 > b.choices8){ return 1; } else if (a.choices8 < b.choices8) { return -1; }
        if (a.choices9 > b.choices9){ return 1; } else if (a.choices9 < b.choices9) { return -1; }

        if (a.choices1 > b.choices1){ return 1; } else if (a.choices1 < b.choices1) { return -1; }
        if (a.choices2 > b.choices2){ return 1; } else if (a.choices2 < b.choices2) { return -1; }
        return 0;
      });

      // 絞込: キーワード
      // activeOnly
      if (self.activeOnly) {
        list = list.filter(function(item, i) {
          return item.active != 0;
        });
      }

      var keyword;
      if (self.filterChoices3.length > 0) {
        keyword = self.filterChoices3 == '（空白）' ? '' : self.filterChoices3;
        list = list.filter(function(item, i) {
          return item.choices3 == keyword;
        });
      }

      if (self.filterChoices4.length > 0) {
        keyword = self.filterChoices4 == '（空白）' ? '' : self.filterChoices4;
        list = list.filter(function(item, i) {
          return item.choices4 == keyword;
        });
      }

      if (self.filterChoices5.length > 0) {
        keyword = self.filterChoices5 == '（空白）' ? '' : self.filterChoices5;
        list = list.filter(function(item, i) {
          return item.choices5 == keyword;
        });
      }

      if (self.filterChoices6.length > 0) {
        keyword = self.filterChoices6 == '（空白）' ? '' : self.filterChoices6;
        list = list.filter(function(item, i) {
          return item.choices6 == keyword;
        });
      }

      if (self.filterChoices7.length > 0) {
        keyword = self.filterChoices7 == '（空白）' ? '' : self.filterChoices7;
        list = list.filter(function(item, i) {
          return item.choices7 == keyword;
        });
      }

      if (self.filterChoices8.length > 0) {
        keyword = self.filterChoices8 == '（空白）' ? '' : self.filterChoices8;
        list = list.filter(function(item, i) {
          return item.choices8 == keyword;
        });
      }

      if (self.filterChoices9.length > 0) {
        keyword = self.filterChoices9 == '（空白）' ? '' : self.filterChoices9;
        list = list.filter(function(item, i) {
          return item.choices9 == keyword;
        });
      }

      return list;
    }

    , showAllCssClass: function() {
      return this.activeOnly ? 'fa-square-o' : 'fa-check-square-o';
    }

  }
  , methods: {

      loadListData: function() {

      var self = this;

      // Show loading
      $.Vendor.WaitingDialog.show();

      var data = {};
      $.ajax({
          type: "GET"
        , url: self.listUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {
            self.choiceList = result.choiceList;

            self.list = [];
            for (var i = 0; i < result.list.length; i++) {
              var item = result.list[i];
              var row = {
                  id            : Number(item.id)
                , choices1      : item.choices1
                , choices2      : item.choices2
                , choices3      : item.choices3
                , choices4      : item.choices4
                , choices5      : item.choices5
                , choices6      : item.choices6
                , choices7      : item.choices7
                , choices8      : item.choices8
                , choices9      : item.choices9
                , active        : Number(item.active)
                , created       : (item.created ? new Date(item.created.replace(/-/g, "/")) : null) // replace for firefox, IE
                , updated       : (item.updated ? new Date(item.updated.replace(/-/g, "/")) : null) // replace for firefox, IE
              };

              self.list.push(row);
            }

            // self.totalItemNum = Number(result.count);
            // self.page = page;
            // self.pageItemNum = pageInfo.pageItemNum; // リセットされてしまうので再セット

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {

          // Show loading
          $.Vendor.WaitingDialog.hide();

        });
    }

    , loadCurrentData: function(id) {

      var self = this;

      // Show loading
      // $.Vendor.WaitingDialog.show();

      var differed = new $.Deferred();
      var data = {
        id: id
      };
      $.ajax({
          type: "GET"
        , url: self.findUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok' && result.item) {

            var item = result.item;
            self.currentItem = {
                id            : Number(item.id)
              , choices1      : item.choices1
              , choices2      : item.choices2
              , choices3      : item.choices3
              , choices4      : item.choices4
              , choices5      : item.choices5
              , choices6      : item.choices6
              , choices7      : item.choices7
              , choices8      : item.choices8
              , choices9      : item.choices9
              , title         : item.title
              , body          : item.body
              , active        : Number(item.active)
              , created       : (item.created ? new Date(item.created.replace(/-/g, "/")) : null) // replace for firefox, IE
              , updated       : (item.updated ? new Date(item.updated.replace(/-/g, "/")) : null) // replace for firefox, IE
            };

            // self.totalItemNum = Number(result.count);
            // self.page = page;

            differed.resolve();

          } else {
            var message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert-danger');

            differed.reject();
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert-danger');
          console.log(stat);

          differed.reject();
        })
        . always(function() {

          // Show loading
          // $.Vendor.WaitingDialog.hide();
        });

      return differed.promise();
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

    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function(key) {
      if (!this.orders[key]) {
        return '';
      }
      return this.orders[key] == 1 ? 'sortAsc' : 'sortDesc';
    }

    , filterChoices3Changed: function() {
      this.filterChoices4 = '';
      this.filterChoices5 = '';
      this.filterChoices6 = '';
      this.filterChoices7 = '';
      this.filterChoices8 = '';
      this.filterChoices9 = '';
    }

    , filterChoices4Changed: function() {
      this.filterChoices5 = '';
      this.filterChoices6 = '';
      this.filterChoices7 = '';
      this.filterChoices8 = '';
      this.filterChoices9 = '';
    }

    , filterChoices5Changed: function() {
      this.filterChoices6 = '';
      this.filterChoices7 = '';
      this.filterChoices8 = '';
      this.filterChoices9 = '';
    }

    , filterChoices6Changed: function() {
      this.filterChoices7 = '';
      this.filterChoices8 = '';
      this.filterChoices9 = '';
    }

    , filterChoices7Changed: function() {
      this.filterChoices8 = '';
      this.filterChoices9 = '';
    }

    , filterChoices8Changed: function() {
      this.filterChoices9 = '';
    }

    , toggleActiveOnly: function() {
      this.activeOnly = ! (this.activeOnly);
    }


    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------

    /// 現在のページを再読込（編集後など）
    , reloadCurrentPage: function() {
      alert('未実装！');
    }

    /// 詳細タブを開く
    , openDetail: function(item) {
      var self = this;

      this.loadCurrentData(item.id).done(function() {
        $('#conciergeMailTemplateNavTab a[href="#tabMailTemplateDetail"]', this.$el).tab('show');
      })
        .fail(function() {
          alert('データの読み込みに失敗しました。');
        });
    }

    /// 一覧タブを開く
    , openList: function() {
      this.currentItem = {}; // 事故防止のため、戻しておく
      $('#collapseForm').collapse('hide'); // フォームを閉じる
      $('#conciergeMailTemplateNavTab a[href="#tabMailTemplateList"]', this.$el).tab('show');
    }

    /// 新規作成フォーム（詳細タブ）を開く
    , openDetailForNew: function(item) {
      this.currentItem = {
        active: -1
      };
      $('#collapseForm').collapse('show'); // フォームを開く
      $('#conciergeMailTemplateNavTab a[href="#tabMailTemplateDetail"]', this.$el).tab('show');
    }


    /// 詳細画面 パンくずからの検索処理
    , searchByCurrentChoice: function(level) {

      if (level == 3) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = '';
        this.filterChoices5 = '';
        this.filterChoices6 = '';
        this.filterChoices7 = '';
        this.filterChoices8 = '';
        this.filterChoices9 = '';
      } else if (level == 4) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = this.currentItem.choices4;
        this.filterChoices5 = '';
        this.filterChoices6 = '';
        this.filterChoices7 = '';
        this.filterChoices8 = '';
        this.filterChoices9 = '';
      } else if (level == 5) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = this.currentItem.choices4;
        this.filterChoices5 = this.currentItem.choices5;
        this.filterChoices6 = '';
        this.filterChoices7 = '';
        this.filterChoices8 = '';
        this.filterChoices9 = '';
      } else if (level == 6) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = this.currentItem.choices4;
        this.filterChoices5 = this.currentItem.choices5;
        this.filterChoices6 = this.currentItem.choices6;
        this.filterChoices7 = '';
        this.filterChoices8 = '';
        this.filterChoices9 = '';
      } else if (level == 7) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = this.currentItem.choices4;
        this.filterChoices5 = this.currentItem.choices5;
        this.filterChoices6 = this.currentItem.choices6;
        this.filterChoices7 = this.currentItem.choices7;
        this.filterChoices8 = '';
        this.filterChoices9 = '';
      } else if (level == 8) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = this.currentItem.choices4;
        this.filterChoices5 = this.currentItem.choices5;
        this.filterChoices6 = this.currentItem.choices6;
        this.filterChoices7 = this.currentItem.choices7;
        this.filterChoices8 = this.currentItem.choices8;
        this.filterChoices9 = '';
      } else if (level == 9) {
        this.filterChoices3 = this.currentItem.choices3;
        this.filterChoices4 = this.currentItem.choices4;
        this.filterChoices5 = this.currentItem.choices5;
        this.filterChoices6 = this.currentItem.choices6;
        this.filterChoices7 = this.currentItem.choices7;
        this.filterChoices8 = this.currentItem.choices8;
        this.filterChoices9 = this.currentItem.choices9;
      } else {
        // do nothing
        return;
      }

      this.openList();
    }

    , saveItem: function() {
      if (!confirm('このデータを保存してよろしいですか？')) {
        return;
      }

      var self = this;
      var data = {
          id: self.currentItem.id
        , choices1: self.currentItem.choices1
        , choices2: self.currentItem.choices2
        , choices3: self.currentItem.choices3
        , choices4: self.currentItem.choices4
        , choices5: self.currentItem.choices5
        , choices6: self.currentItem.choices6
        , choices7: self.currentItem.choices7
        , choices8: self.currentItem.choices8
        , choices9: self.currentItem.choices9
        , title: self.currentItem.title
        , body: self.currentItem.body
        , active: self.currentItem.active
      };
      $.ajax({
          type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var item = result.item;
            self.currentItem = {
                id            : Number(item.id)
              , choices1      : item.choices1
              , choices2      : item.choices2
              , choices3      : item.choices3
              , choices4      : item.choices4
              , choices5      : item.choices5
              , choices6      : item.choices6
              , choices7      : item.choices7
              , choices8      : item.choices8
              , choices9      : item.choices9
              , title         : item.title
              , body          : item.body
              , active        : Number(item.active)
              , created       : (item.created ? new Date(item.created.replace(/-/g, "/")) : null) // replace for firefox, IE
              , updated       : (item.updated ? new Date(item.updated.replace(/-/g, "/")) : null) // replace for firefox, IE
            };

            // 一覧データ再読込
            self.loadListData();

            vmGlobalMessage.setMessage(result.message + "\n\n一覧データを再読込しました。", 'alert-success', 500);

            // self.totalItemNum = Number(result.count);
            // self.page = page;

          } else {
            var message = result.message.length > 0 ? result.message : 'データを保存できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert-danger', 0);
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert-danger', 0);
          console.log(stat);
        })
        . always(function() {
          $('body').scrollTop(0);

        });

    }

  }

});

