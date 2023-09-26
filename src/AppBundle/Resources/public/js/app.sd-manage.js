/**
 * SD商品情報管理画面
 */
$(function() {

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#globalMessage',
    data: {
        message: ''
      , messageCssClass: ''
      , loadingImageUrl: null
    },
    ready: function() {
      this.loadingImageUrl = $(this.$el).data('loadingImageUrl');
    },
    methods: {
      setMessage: function(message, cssClass, autoHide) {
        cssClass = cssClass || 'alert alert-info';
        autoHide = autoHide || true;

        this.message = message;
        this.setCssClass(cssClass);

        if (autoHide) {
          setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
        }
      },
      setCssClass: function(cssClass) {
        this.messageCssClass = cssClass;
      },
      clear: function() {
        this.message = '';
        this.messageCssClass = '';
      },
      closeWindow: function() {
        window.close();
      }
    }
  });
  
  // アップロードモーダル
  var vmUploadCsvModal = new Vue({
      el: '#modalUploadCsv'
    , data: {
        uploadUrl: null
      , result: null
    }
    , ready: function() {
      var self = this;

      self.uploadUrl = $(self.$el).data('uploadUrl');

      // アップロードフォーム
      $('#impCsv').fileinput({
          uploadUrl: self.uploadUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false

        , fileActionSettings: {
            showZoom: false
          , showUpload: false
        }
        // , allowedFileTypes: ['csv', 'text']
        , allowedFileExtensions: ['csv', 'txt']
      })

        .on('filebatchuploadsuccess', function(event, data, previewId, index) {

          if (data.response && data.response.info) {
            self.$set('result', data.response.info);
          } else {
            self.$set('result', null);
          }

          $('#impCsv').fileinput('clear');
        })


    }
    , methods: {
    }

  });

 // チェックボックス全選択の機能
  $('.check_all').on('click', function() {
    $('.check').prop('checked', this.checked);
  });
  $('.check').on('click', function() {
    if ($(".check:not(:checked)").size() == 0) {
      $('.check_all').prop('checked', true);
    } else {
      $('.check_all').prop('checked', false);
    }
  });

});

function changeNumber(baseRoute){
  var num = document.getElementById('chg_num').value;
  if(num.match(/[^0-9]+/)){
    alert('半角数字で入力してください');
  }else{
    location.href = baseRoute + '/' + num;
  }
}

