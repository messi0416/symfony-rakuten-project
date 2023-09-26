/**
 * キッズモデル
 */
var vmKidsModelForm = new Vue({
    el: '#modelRecruitmentKidsForm'
  , delimiters: ['(%', '%)']
  , data: {

  }
  , mounted: function() {
    this.$nextTick(function() {
      // アップロードフォーム
      $('#images').fileinput({
          language: 'ja'
        , showCaption: false
        , showPreview: true
        , showUpload: false

        , maxFileCount: 10
        , browseClass: 'btn btn-info fileinput-browse-button'
        // , browseIcon: '<i class="fa fa-fw fa-folder"></i>'
        , browseLabel: 'ファイル選択'
        , removeClass: 'btn btn-default'
        // , removeIcon: '<i class="fa fa-fw fa-trush"></i>'
        , removeLabel: 'キャンセル'

        , fileActionSettings: {
            showZoom: true
          , showUpload: false
          , showDownload: false
          , showRemove: false

          , downloadIcon: ''
        }
        // , allowedFileTypes: ['csv', 'text']
        , allowedFileExtensions: ['jpg', 'jpeg', 'png']
      });

      console.log('moge--');

    });
  }
  , methods: {}
});
