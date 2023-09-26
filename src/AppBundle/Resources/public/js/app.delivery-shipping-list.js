/**
 * 出荷リスト一覧用 JS
 */

var vmDeliveryShippingVoucherList = new Vue({
  el: '#deliveryShippingVoucherList'
  , data: {
      messageState: new PartsGlobalMessageState()
    , listUrl: null
    , warehouseList: []
    , pagingLimitList: [100, 250]
    , urlRecreatePickingListBase: null
    , searchConditions: {
        warehouseId: WAREHOUSE_ID
        , pagingLimit: PAGING_LIMIT
      }
    , urlEditComment : null
    , urlMergePackingGroup : null
    , mergePackingList : []
    , urlDownloadLabelPdf : null
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.listUrl = $(this.$el).data('listUrl');
      this.urlRecreatePickingList = $(this.$el).data('urlRecreatePickingList');
      this.urlDownloadLabelPdf = $(this.$el).data('urlDownloadLabelPdf');

      var dateOptions = {
        language: 'ja'
        , format: 'yyyy-mm-dd'
        , autoclose: true
      };

      $('#importDateFrom').datepicker(dateOptions);
      $('#importDateTo').datepicker(dateOptions);

      this.loadListData();
    });
  }
  , methods: {
    loadListData: function () {
      var self = this;

      var data = {
        search: self.searchConditions
      }

      $.ajax({
        type: "GET"
        , url: self.listUrl
        , dataType: "json"
        , data: data
      })
        .done(function (result) {
          if (result.status == 'ok') {
            self.warehouseList = result.warehouseList || [];
          } else {
            var message = result.message.length > 0 ? result.message : 'データを取得できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function (stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
        });
    }
    , selectToday: function() {
      $('#importDateFrom').datepicker('setDate', new Date);
      $('#importDateTo').datepicker('setDate', new Date);
    }
    , createLabelPdf: function(packing_id, delivery_method_id) {
      var self = this;
      var url = self.urlDownloadLabelPdf + '?packing_id=' + packing_id + '&delivery_method_id=' + delivery_method_id;
      window.open(url, '_blank');
    }
    , recreatePickingList: function(id) {

      if (!confirm('ピッキングリストを再作成します。よろしいですか？')) {
        return;
      }

      var self = this;
      var data = {
        id: id
      };

      $.ajax({
          type: "POST"
        , url: self.urlRecreatePickingList
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            alert(result.message);
            window.location.reload(); // 変更反映

          } else {
            var message = result.message.length > 0 ? result.message : '再作成に失敗しました。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
        });
    }
    , editComment: function(packing_id) {
      this.urlEditComment = $(this.$el).data('urlEditComment');
      var self = this;
      self.messageState.clear();
      var data = {
        packing_id: packing_id
        , comment: $('#comment_'+packing_id).val()
      };
      $.ajax({
        type: "POST"
        , url: self.urlEditComment
        , dataType: "json"
        , data: data
      })
      .done(function(result) {
        if (result.status == 'ng') {
          var message = result.message.length > 0 ? result.message : 'コメント登録に失敗しました。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      });
    }
    , changePageLimit: function() {
      $('input:hidden[name="page_limit"]').val($("#pageLimit").val());
      $('#shippingListSearch').submit();
    }
    , mergePackingGroup: function() {
        this.urlMergePackingGroup = $(this.$el).data('urlMergePackingGroup');
        var self = this;
        self.messageState.clear();
        var data = {
          mergePackingIdList : self.mergePackingList
        };
        if (self.mergePackingList.length < 2) {
          alert("マージ対象を複数チェックしてください。");
          return;
        }
        // 1つ前のコメントと異なる場合確認メッセージを表示
        for (var i = 1; i < self.mergePackingList.length; i++) {
          var packingId = self.mergePackingList[i];
          var comment = $('#comment_'+packingId).val();
          var oldPackingId = self.mergePackingList[i-1];
          var oldComment = $('#comment_'+oldPackingId).val();
          if (comment != oldComment) {
            if (!confirm('マージすると一番上のコメントのみが適用されますが、マージしてもよろしいでしょうか？')) {
              return;
            } else {
              break;
            }
          }
        }
        $.ajax({
          type: "POST"
          , url: self.urlMergePackingGroup
          , dataType: "json"
          , data: data
        })
        .done(function(result) {
          if (result.status == 'ng') {
            var message = result.message.length > 0 ? result.message : 'マージに失敗しました。';
            self.messageState.setMessage(message, 'alert alert-danger');
            $("html,body").animate({scrollTop:0},600);
          } else {
            window.location.reload(); // 変更反映
          }
        })
        .fail(function(stat) {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
          $("html,body").animate({scrollTop:0},600);
        });
      }
  }
});
