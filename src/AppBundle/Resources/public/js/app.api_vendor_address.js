$(function() {
  var vmSearchVendorAddress = new Vue({
    el: '#searchVendorAddress',
    data: {
      message: ''
      , messageClass: 'alert'
      , nowLoading: false

      , url: null
      , searchVendorAddress: ''
      , addresses: []
    },
    mounted: function() {
      var self = this;
      self.url = $(self.$el).data('url');
    },

    methods: {
      search: function() {
        var self = this;
        self.nowLoading = true;

        // Ajaxでキュー追加
        var data = {
          'address': this.searchVendorAddress
        };
        $.ajax({
          type: "POST"
          , url: self.$data.url
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;

            if (result.status == 'ng') {
              self.$data.messageClass = 'alert alert-warning';
            } else {
              self.$data.messageClass = 'alert alert-success';
            }

            self.$data.addresses = result.results;
          })
          .fail(function(stat) {
            self.$data.message = '検索に失敗しました。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;

            self.nowLoading = false;
          });
      }
      , keyPress: function($event) {
        if ($event.which === 13) {
          return this.search();
        } else {
          return false;
        }
      }
    }
  });
});

