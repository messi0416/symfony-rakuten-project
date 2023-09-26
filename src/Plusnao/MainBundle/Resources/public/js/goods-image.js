// 登録画像一覧 item コンポーネント
var vmComponentProductImageEditListItem = {
    template: '#templateProductImageEditListItem'
  , props: [
    'item'
  ]
  , data: function() {
    return {
      isInsertAreaDragOver: false
    };
  }
  , computed: {
    insertAreaCss: function() {
      return { 'alert-success' : this.isInsertAreaDragOver };
    }
    , imageUrlWithRandom: function() {
      return this.item.imageUrl ? this.item.imageUrl + '?' + Math.random().toString(36).slice(-8) : '';
    }
  }
  , methods: {
    setCurrentImage: function() {
      this.$emit('set-current-image', this.item);
    }
  }
};
// 登録画像一覧 item コンポーネント アイコン版
var vmComponentProductImageEditIconListItem = $.extend(true, {}, vmComponentProductImageEditListItem, {
  template: '#templateProductImageEditIconListItem'
});


// 登録画像一覧コンポーネント
var vmComponentProductImageEditList = {
    template: '#templateProductImageEditList'
  , components: {
    'image-list-item': vmComponentProductImageEditListItem
  }
  , props: [
    'editList'
  ]
  , data: function() {
    return {};
  }
  , methods: {
    setCurrentImage: function(item) {
      this.$emit('set-current-image', item);
    }
  }
};

// 登録画像一覧コンポーネント アイコン版
var vmComponentProductImageEditIconList = $.extend(true, {}, vmComponentProductImageEditList, {
    template: '#templateProductImageEditIconList'
  , components: {
    'image-list-item': vmComponentProductImageEditIconListItem
  }
});

// 画像設定 メイン
var vmProductImageEdit = new Vue({
    el: '#productImageEdit'
    , data: {
      editList: [] // 登録一覧

    , messageState: {}

    , currentImage: null

    , daihyoSyohinCode: null
    , imageUrlParent: null

    , editListComponent: 'image-icon-list'
  }
  , components: {
      'image-list': vmComponentProductImageEditList // 画像一覧
    , 'image-icon-list': vmComponentProductImageEditIconList // 画像一覧 アイコン版
  }
  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      // データ当て込み
      this.daihyoSyohinCode = DAIHYO_SYOHIN_CODE;
      this.imageUrlParent = IMAGE_URL_PARENT;
      if ($.isArray(IMAGE_EDIT_DATA)) {
        for (var i = 0; i < IMAGE_EDIT_DATA.length; i++) {
          this.addRow(IMAGE_EDIT_DATA[i]);
        }

      }
    });
  }

  , computed: {
    currentImageUrl: function() {
      if (this.currentImage && this.currentImage.imageUrl) {
        return this.currentImage.imageUrl + '?' + Math.random().toString(36).slice(-8) ;
      }
    }
  }

  , methods: {

    // リストへ1件追加
    addRow: function(addItem, mode) {

      // 初期読み込み
      var item;
      if (!mode) {
        item = {
            daihyoSyohinCode: addItem.daihyoSyohinCode
          , code            : addItem.code
          , directory       : addItem.directory
          , filename        : addItem.filename
          , fileDirPath     : addItem.fileDirPath

          , size  : addItem.size ? Number(addItem.size) : null
          , width : addItem.width ? Number(addItem.width) : null
          , height: addItem.height ? Number(addItem.height) : null

          , imageUrl : addItem.fileDirPath ? this.imageUrlParent + addItem.fileDirPath : null
        };

        this.editList.push(item);
      }
    }

    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------
    , setCurrentImage: function(item) {
      this.currentImage = item;
    }
  }

});
