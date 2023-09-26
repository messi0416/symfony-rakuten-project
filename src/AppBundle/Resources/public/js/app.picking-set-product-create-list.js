/**
 * セット商品作成リスト一覧画面 JS
 */

/**
 * 一覧
 */
var vmSetProductCreateListSearchForm = new Vue({
  el: '#setProductCreateListSearchForm'
  , data: {
    showDeleteButtons: false
  }
  , computed: {
    deleteButtonCss: function() {
      return this.showDeleteButtons ? 'btn-danger' : 'btn-default'
    }
  }

  , mounted: function() {
    this.$nextTick(function() {
      const self = this;

      $('#searchDate', this.$el).datepicker({
        language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.search();
        }
        , clearDate: function() {
          self.search();
        }
      });
    });

  }
  , methods: {
    search: function() {
      $(this.$el).submit();
    }
    , toggleDeleteButtons: function() {
      this.showDeleteButtons = ! (this.showDeleteButtons);
    }
  }
});

var vmSetProductCreateList = new Vue({
  el: '#setProductCreateList'
  , data: {
    deleteUrl: null
  }

  , mounted: function() {
    this.$nextTick(function() {
      this.deleteUrl = $(this.$el).data('deleteUrl');
    });
  }

  , computed: {
    showDeleteButtons: function() {
      return vmSetProductCreateListSearchForm.showDeleteButtons;
    }
  }
  , methods: {
    deleteList: function(date, number, event) {
      const self = this;

      event.preventDefault();

      if (!confirm("このセット商品作成リストを削除します。よろしいですか？")) {
        return;
      }

      if (date.length > 0 && number.length > 0) {

        // Show loading
        $.Vendor.WaitingDialog.show('処理中 ...');

        var data = {
            date: date
          , number: number
        };

        console.log(self.deleteUrl);

        $.ajax({
            type: "POST"
          , url: self.deleteUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.status == 'ok') {

              vmGlobalMessage.setMessage(result.message, 'alert-success');
              window.location.reload();

            } else {
              vmGlobalMessage.setMessage(result.message, 'alert-danger');
            }
          })
          .fail(function (stat) {
            console.log(stat);
            vmGlobalMessage.setMessage("ピッキング削除に失敗しました。（通信エラー）", 'alert-danger');

          })
          .always(function () {
            // Hide loading
            $.Vendor.WaitingDialog.hide();
          });

      }
    }
  }
});


///**
// * 一覧ブロック 行コンポーネント
// */
//Vue.component('result-item', {
//  template: "#result-item",
//  props: [
//      'item'
//    , 'listStates'
//  ],
//  data: function() {
//    return {
//        itemIndex: this.item.itemIndex
//      , neSyohinSyohinCode : this.item.neSyohinSyohinCode
//      , locationId : this.item.locationId
//      , locationCode : this.item.locationCode
//      , position : this.item.position
//      , stock : this.item.stock
//      , moveNum : this.item.moveNum
//      , status : this.item.status
//      , pictDirectory : this.item.pictDirectory
//      , pictFilename : this.item.pictFilename
//      , imageUrl : this.item.imageUrl
//      , linkUrl : this.item.linkUrl
//      , created : this.item.created
//      , updated : this.item.updated
//
//      , errorMessage: this.item.errorMessage
//    };
//  },
//  computed: {
//    isActiveItem: function() {
//      return this.listStates.currentDetailItemIndex === this.itemIndex
//    }
//    , hasError: function() {
//      return this.item.errorMessage != "";
//    }
//    , rowCss: function() {
//      var css = "";
//      if (this.isActiveItem) {
//        css = "list-group-item-info";
//      } else if (this.hasError) {
//        css = "list-group-item-danger";
//      } else if (this.item.status == 1) {
//        css = "list-group-item-success";
//      } else if (this.item.status == 3) {
//        css = "list-group-item-warning";
//      }
//      return css;
//    }
//    , statusCss: function() {
//      var css = "";
//      if (this.hasError) {
//        css = "label-danger";
//      } else if (this.item.status == 1) {
//        css = "label-success";
//      } else if (this.item.status == 3) {
//        css = "label-warning";
//      }
//      return css;
//    }
//    , statusWord: function() {
//      var word = "";
//      if (this.hasError) {
//        word = "エラー";
//      } else if (this.item.status == 1) {
//        word = "OK";
//      } else if (this.item.status == 3) {
//        word = "PASS";
//      }
//      return word;
//    }
//  },
//  ready: function() {
//  },
//  methods: {
//    openDetailModal: function() {
//      this.$dispatch('open-detail', this.item);
//    }
//  }
//});
//
///**
// * 詳細モーダルコンポーネント
// */
//Vue.component('detail-modal', {
//  template: "#detail-modal",
//  props: [
//      'item'
//    , 'listStates'
//    , 'info' // 双方向バインド用
//  ],
//  data: function() {
//    return {
//        itemIndex: this.item.itemIndex
//      , neSyohinSyohinCode : this.item.neSyohinSyohinCode
//      , locationId : this.item.locationId
//      , locationCode : this.item.locationCode
//      , position : this.item.position
//      , stock : this.item.stock
//      , moveNum : this.item.moveNum
//      , status : this.item.status
//      , pictDirectory : this.item.pictDirectory
//      , pictFilename : this.item.pictFilename
//      , imageUrl : this.item.imageUrl
//      , linkUrl : this.item.linkUrl
//      , created : this.item.created
//      , updated : this.item.updated
//
//      , errorMessage: this.item.errorMessage
//    };
//  },
//  computed: {
//    isStatusOk: function() {
//      return this.item.status == 1;
//    }
//    , hasError: function() {
//      return this.item.errorMessage != "";
//    }
//    , statusCss: function() {
//      var css = "";
//      if (this.hasError) {
//        css = "label-danger";
//      } else if (this.item.status == 1) {
//        css = "label-success";
//      } else if (this.item.status == 3) {
//        css = "label-warning";
//      }
//      return css;
//    }
//    , statusWord: function() {
//      var word = "";
//      if (this.hasError) {
//        word = "エラー";
//      } else if (this.item.status == 1) {
//        word = "OK";
//      } else if (this.item.status == 3) {
//        word = "PASS";
//      }
//      return word;
//    }
//  },
//  ready: function() {
//  },
//  methods: {
//    /// 次へ
//    movePrev: function() {
//      this.$dispatch('move-prev');
//    }
//    /// 前へ
//    , moveNext: function() {
//      this.$dispatch('move-next');
//    }
//    /// OK / PASS
//    , submit: function(button) {
//      switch (button) {
//        case 'ok':
//          this.$dispatch('submit-ok');
//          break;
//        case 'pass':
//          this.$dispatch('submit-pass');
//          break;
//      }
//    }
//  }
//});
//
//
///**
// * 一覧ブロック
// */
//var vmSetProductPickingList = new Vue({
//    el: "#setProductPickingList"
//  , data: {
//      dataUrl: null
//    , submitUrl: null
//    , pickingList: []
//
//    // 詳細モーダルコンポーネントと双方向バインド
//    , detailInfo: {
//    }
//
//    // ページ送り設定
//    , pageItemNum: 20 // 設定値: 1ページ表示件数
//    , pageListMaxLength: 6 // 設定値: ページリンク 表示最大件数
//    , page: 1 // 現在のページ
//
//    // フィルター
//    , filterProcessed: 'all'
//
//    , currentListIndex: 0 // 一覧でのインデックス
//
//    // 各itemで共有する状態オブジェクト
//    , listStates: {
//        currentDetailItemIndex: null
//      , inProcess: false
//      , inProcessItemIndex: null
//      , processMessage: ""
//      , processMessageCss: "alert-info"
//    }
//
//  }
//  , ready: function() {
//    var self = this;
//
//    self.dataUrl = $(this.$el).data('dataUrl');
//    self.submitUrl = $(this.$el).data('submitUrl');
//
//    // Show loading
//    $.Vendor.WaitingDialog.show('読み込み中 ...');
//
//    // 初期データ読込
//    self.loadData().then(function(){
//      $.Vendor.WaitingDialog.hide();
//    });
//
//  }
//  , computed: {
//
//      currentDetailItem : function() {
//        if (this.listStates.currentDetailItemIndex >= 0 && this.pickingList[this.listStates.currentDetailItemIndex]) {
//          return this.pickingList[this.listStates.currentDetailItemIndex];
//        }
//        return {};
//    }
//    , itemCount: function() {
//      return this.pickingList.length;
//    }
//
//    , filteredItemCount: function() {
//      return this.listData.length;
//    }
//    ///// ソート・フィルター済みリスト
//    //// ※ページング処理のため、Vue.js v-for の filterBy, orderBy が利用できない。
//    , listData: function() {
//      var self = this;
//      var list;
//      list = self.pickingList.slice(); // 破壊防止
//
//      // 絞込
//      list = list.filter(function(item, i) {
//        var result = true;
//
//        // 絞込 処理状態
//        if (self.filterProcessed == 'notProcessed') {
//          result = result && item.status == 0;
//        } else if (self.filterProcessed == 'processed') {
//          result = result && item.status != 0;
//        }
//
//        return result;
//      });
//
//      return list;
//    }
//
//    , pageData: function() {
//      var startPage = (this.page - 1) * this.pageItemNum;
//      return this.listData.slice(startPage, startPage + this.pageItemNum);
//    },
//
//    isStartPage: function(){
//      return (this.page == 1);
//    },
//
//    isEndPage: function(){
//      return (this.page == this.pageNum);
//    },
//
//    /// 最大ページ数 （現在のフィルタ条件を考慮）
//    pageNum: function() {
//      return Math.ceil(this.listData.length / this.pageItemNum);
//    },
//
//    pageList: function() {
//      var pages = [];
//      var i;
//      if (this.pageNum <= this.pageListMaxLength) {
//        for (i = 1; i <= this.pageNum; i++) {
//          pages.push(i);
//        }
//      } else {
//
//        var listHalf = Math.floor(this.pageListMaxLength / 2);
//        if (!listHalf) {
//          listHalf = 1;
//        }
//        var listQuarter = Math.floor(this.pageListMaxLength / 4);
//        if (!listQuarter) {
//          listQuarter = 1;
//        }
//
//        var isSkipForward = this.page <= (this.pageNum - listHalf); // 大きい方をスキップ
//        var isSkipBackward = this.page >= listHalf; // 小さい方をスキップ
//
//        var showNum = this.pageListMaxLength - 2  // start & end
//          - (isSkipForward ? 1 : 0) // 「...」
//          - (isSkipBackward ? 1 : 0); // 「...」
//
//        var prePageNum = Math.floor((showNum -1) / 2);
//        var postPageNum = (showNum - 1) - prePageNum;
//        var start = isSkipBackward ? this.page - prePageNum : 2;
//        var end = isSkipForward ? this.page + postPageNum : (this.pageNum - 1);
//
//        if (this.page - prePageNum < 2) {
//          end += (2 - (this.page - prePageNum));
//        }
//        if (this.page + postPageNum > (this.pageNum - 1)) {
//          start -= (this.page + postPageNum - (this.pageNum - 1));
//        }
//
//        pages.push(1); // 先頭ページ
//        if (isSkipBackward) {
//          pages.push('…')
//        }
//        for (i = start; i <= end; i++) {
//          pages.push(i);
//        }
//        if (isSkipForward) {
//          pages.push('…')
//        }
//        pages.push(this.pageNum); // 最終ページ
//      }
//
//      return pages;
//    }
//  }
//  , methods: {
//    loadData: function() {
//
//      var self = this;
//      var deferred = new $.Deferred();
//
//      $.ajax({
//          type: "GET"
//        , url: self.dataUrl
//        , dataType: "json"
//        , data: {}
//      })
//        .done(function(result) {
//          if (result.status == 'ok') {
//
//            var list = [];
//            var i, row;
//            for (i = 0; i < result.list.length; i++) {
//              var item = result.list[i];
//              row = {
//                  itemIndex: i // IDとして利用
//                , neSyohinSyohinCode : item.ne_syohin_syohin_code
//                , locationId : Number(item.location_id) || 0
//                , locationCode : item.location_code
//                , position : Number(item.position) || 0
//                , stock : Number(item.location_stock) || 0
//                , moveNum : Number(item.move_num) || 0
//                , status : Number(item.status) || 0
//                , pictDirectory : item.pict_directory
//                , pictFilename : item.pict_filename
//                , imageUrl : item.image_url
//                , linkUrl : item.link_url
//                , created : (item.created ? new Date(item.created.replace(/-/g, "/")) : null) // replace for firefox, IE
//                , updated : (item.updated ? new Date(item.updated.replace(/-/g, "/")) : null) // replace for firefox, IE
//
//                , labelType: item.label_type
//
//                , errorMessage: ""
//              };
//              list[i] = row;
//            }
//
//            self.$set('pickingList', list);
//            deferred.resolve();
//
//          } else {
//            vmGlobalMessage.setMessage(result.message, 'alert-danger');
//            deferred.reject();
//          }
//        })
//        .fail(function(stat) {
//          console.log(stat);
//          vmGlobalMessage.setMessage('エラー：更新に失敗しました。', 'alert-danger');
//          deferred.reject();
//        })
//        .always(function() {
//        });
//
//      return deferred.promise();
//    }
//
//    // ==============================
//    // OK / PASS処理
//    // ==============================
//    , submitOk: function() {
//      return this.submit('ok');
//    }
//    , submitPass: function() {
//      return this.submit('pass');
//    }
//    , submit: function(button) {
//      var self = this;
//
//      if (! self.currentDetailItem.neSyohinSyohinCode) {
//        alert('商品データが取得できませんでした。');
//        return;
//      }
//
//      // 処理中確認
//      if (self.listStates.inProcess) {
//        alert("現在、データ更新処理中です。完了するまで次の処理はできません。");
//        return;
//      }
//
//      // 更新状態 セット
//      self.listStates.inProcess = true;
//      self.listStates.inProcessItemIndex = self.listStates.currentDetailItemIndex;
//      self.listStates.processMessage = "現在、データ更新処理を実行中です。";
//      self.listStates.processMessageCss = "alert-warning";
//
//      // 処理実行
//      var data = {
//          syohin_code: self.currentDetailItem.neSyohinSyohinCode
//        , src_location: self.currentDetailItem.locationCode
//        , move_num: self.currentDetailItem.moveNum
//        , button: button
//      };
//      var processedItem = self.pickingList[self.listStates.inProcessItemIndex];
//
//      $.ajax({
//          type: "POST"
//        , url: self.submitUrl
//        , dataType: "json"
//        , data: data
//      })
//        .done(function(result) {
//          if (result.status == 'ok') {
//
//            processedItem.status = result.picking_status;
//            processedItem.errorMessage = "";
//
//            self.listStates.processMessage = "";
//            self.listStates.processMessageCss = "alert-info";
//
//            // status の変更により、listData が移動している可能性があるため、再度listIndexを取り直す。
//            var currentItem = self.pickingList[self.listStates.currentDetailItemIndex];
//            if (currentItem) {
//              var i = self.listData.indexOf(currentItem);
//              if (i == -1) {
//                return;
//              }
//
//              if (i != self.currentListIndex) {
//                self.moveDetail(i, true);
//              }
//            }
//
//          } else {
//            processedItem.errorMessage = result.message;
//            self.listStates.processMessage = "エラー：更新できませんでした。";
//            self.listStates.processMessageCss = "alert-danger";
//          }
//        })
//        .fail(function(stat) {
//          console.log(stat);
//          self.listStates.processMessage = "エラー：更新に失敗しました。";
//          self.listStates.processMessageCss = "alert-danger";
//
//          processedItem.errorMessage = result.message;
//        })
//        .always(function() {
//          self.listStates.inProcessItemIndex = null;
//          self.listStates.inProcess = false;
//        });
//
//      // 特に結果を待たずに、次へ進む。
//      self.moveDetailNext();
//    }
//
//
//    // ==============================
//    // 詳細モーダル
//    // ※注意： 詳細に関する index は常に、listData に対してのみ操作する。（itemIndexとは別もの。絞込・ソート等に対応するため）
//    // ==============================
//    /**
//     * 詳細モーダル
//     */
//    , openDetail: function(item) {
//      var self = this;
//      var i = this.listData.indexOf(item);
//
//      if (i == -1) {
//        alert("データが見つかりませんでした。");
//        return;
//      }
//
//      self.moveDetail(i, true);
//      $('#modalPickingDetail', this.$el).modal().show();
//    }
//
//    /**
//     * 詳細モーダル移動
//     */
//    , moveDetail: function(index, noFade) {
//      var self = this;
//
//      var moveProcess = function() {
//        self.currentListIndex = index;
//        self.listStates.currentDetailItemIndex = self.listData[self.currentListIndex].itemIndex;
//        self.moveToPageForIndex(self.currentListIndex);
//      };
//
//      if (noFade) {
//        moveProcess();
//      } else {
//        var $modalBody = $('#modalPickingDetail .modal-body');
//        $modalBody.fadeTo(200, 0.2, function() {
//          moveProcess();
//          $modalBody.fadeTo(100, 1);
//        });
//      }
//    }
//    , moveDetailPrev: function() {
//      if (this.currentListIndex > 0) {
//        this.moveDetail(this.currentListIndex - 1);
//      } else {
//        alert('すでに先頭か、あるいはデータがありません。');
//      }
//    }
//    , moveDetailNext: function() {
//      if (this.currentListIndex < (this.listData.length - 1)) {
//        this.moveDetail(this.currentListIndex + 1);
//      } else {
//        alert('すでに最後か、あるいはデータがありません。');
//      }
//    }
//
//    // -------------------------
//    // 以下、ページ操作など汎用
//    // -------------------------
//    /**
//     * ページ送り
//     */
//    , showPrev: function(event) {
//      event.preventDefault();
//      if (! this.isStartPage) {
//        this.page--;
//      }
//    }
//
//    , showNext: function(event) {
//      event.preventDefault();
//      if (! this.isEndPage) {
//        this.page++;
//      }
//    }
//
//    , showPage: function(page, event) {
//      // aタグなどでは$eventを渡してリンク挙動を抑制
//      if (event) {
//        event.preventDefault();
//      }
//      if (page >= 1 && page <= this.pageNum) {
//        this.page = page;
//      }
//    }
//
//    /**
//     * ページ判定
//     */
//    , isPage: function(num) {
//      return (this.page === parseInt(num));
//    }
//
//    /**
//     * インデックスに対応するページ番号取得
//     */
//    , calculatePageForIndex: function(index) {
//      return Math.ceil((index + 1) / this.pageItemNum);
//    }
//    /**
//     * インデックスに対応するページを表示
//     */
//    , moveToPageForIndex: function(index) {
//      var pageNum = this.calculatePageForIndex(index);
//      if (pageNum !== this.page) {
//        this.showPage(pageNum);
//      }
//    }
//
//  }
//
//});
