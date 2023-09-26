$(function() {

  /**
   * 梱包グループリスト
   */
  if ($('#packingGroupList').size() > 0) {
    new Vue({
      el: '#packingGroupList',
      data: {
        isTodayOnly: 1,
        isUnfinishOnly: 1,
        warehouseId: WAREHOUSE_ID,
        list: [],
        searchUrl: null,
        messageState: {}
      },
      ready: function() {
        const self = this;
        self.searchUrl = $(self.$el).data('searchUrl');
        self.messageState = new PartsGlobalMessageState();
        self.search();
      },
      methods: {
        search: function() {
          const self = this;
          self.messageState.clear();
          self.list = [];
          $.ajax({
            type: "POST",
            url: self.searchUrl,
            dataType: "json",
            data: {
              warehouseId: self.warehouseId,
              isTodayOnly: self.isTodayOnly,
              isUnfinishOnly: self.isUnfinishOnly
            },
          })
          .done(function(result) {
            if (result.status == 'ok') {
              let list = [];
              result.list.forEach(item => {
                switch (item.status) {
                  case '1':
                    item.status = '処理中';
                    item.css = 'list-group-item-info';
                    break;
                  case '2':
                    item.status = '完了';
                    item.css = 'list-group-item-success';
                    break;
                  default:
                    item.status = '未処理';
                    item.css = '';
                    break;
                }
                list.push(item);
              });
              self.list = list;
            } else {
              const message = result.message ? result.message : '検索処理でエラーが発生しました';
              self.messageState.setMessage(message, 'alert alert-danger');
            }
          })
          .fail(function() {
            self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
          })
        },
      }
    });
  }

});
