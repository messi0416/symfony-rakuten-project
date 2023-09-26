/**
 * 管理画面 商品画像設定
 */

function generateNewImageCode(item) {
  return 'p' + $.Plusnao.String.zeroPadding(item.newNumber, 3);
}



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
      displayCreated: function() {
      return this.item.created ? $.Plusnao.Date.getDateString(this.item.created) : '';
    }
    , displayUpdated: function() {
      return this.item.updated ? $.Plusnao.Date.getDateString(this.item.updated) : '';
    }
    , insertAreaCss: function() {
      return { 'alert-success' : this.isInsertAreaDragOver };
    }
    , imageUrlWithRandom: function() {
      if (this.item.uploadFile) { // アップロード分はそのまま
        return this.item.imageUrl;
      }
      return this.item.imageUrl ? this.item.imageUrl + '?' + Math.random().toString(36).slice(-8) : '';
    }

    , newCode: function() {
      var newCode = generateNewImageCode(this.item);
      return this.item.code != newCode ? newCode : '';
    }

    , displayFileSize: function() {
      return this.item.size ? ($.Plusnao.String.numberFormat(this.item.size) + ' bytes') : '-';
    }

    , displayImageDimensions: function() {
      return (this.item.width && this.item.height)
            ? ($.Plusnao.String.numberFormat(this.item.width) + ' x ' + $.Plusnao.String.numberFormat(this.item.height) + ' px')
            : '-'
      ;
    }

  }
  , methods: {
    setCurrentImage: function() {
      this.$emit('set-current-image', this.item);
    }

    , dragStart: function(event) {
      event.dataTransfer.setData('text/image-code', this.item.code);
      event.dataTransfer.setData('text/image-from-list', 'edit');
    }

    /// 移動エリア dragOver
    , insertDragOver: function(event) {
      event.preventDefault(); // これをしないとdropイベントが発生しない
    }
    /// 移動エリア dragEnter
    , insertDragEnter: function() {
      event.preventDefault();
      this.isInsertAreaDragOver = true;
    }
    /// 移動エリア dragLeave
    , insertDragLeave: function() {
      event.preventDefault();
      this.isInsertAreaDragOver = false;
    }
    /// 移動エリア drop
    , insertDrop: function(event) {
      event.preventDefault();
      this.isInsertAreaDragOver = false;

      var code = event.dataTransfer.getData('text/image-code');
      var fromListName = event.dataTransfer.getData('text/image-from-list');
      if (!code || !fromListName) {
        return;
      }

      this.$emit('insert-row', this.item, code, fromListName);
    }

    , moveToTemporaryList: function() {
      this.$emit('move-to-temporary', this.item.code, 'edit');
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
    , insertRow: function(item, code, fromListName) {
      this.$emit('insert-row', item, code, fromListName);
    }
    ,moveToTemporary : function(code, fromListName) {
      this.$emit('move-to-temporary', code, fromListName);
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


// 削除予定一覧コンポーネント
var vmComponentProductImageEditDeleteItem = {
  template: '#templateProductImageEditDeleteItem'
  , props: [
      'item'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
  }
  , methods: {
    dragStart: function(event) {
      event.dataTransfer.setData('text/image-code', this.item.code);
      event.dataTransfer.setData('text/image-from-list', 'delete');
    }

    , moveToEditList: function() {
      this.$emit('move-to-edit', null, this.item.code, 'delete');
    }
  }
};

// 一時置き場コンポーネント
var vmComponentProductImageEditTemporaryItem = {
  template: '#templateProductImageEditTemporaryItem'
  , props: [
    'item'
  ]
  , data: function() {
    return {
    };
  }
  , computed: {
  }
  , methods: {
    dragStart: function(event) {
      event.dataTransfer.setData('text/image-code', this.item.code);
      event.dataTransfer.setData('text/image-from-list', 'temporary');
    }

    , moveToEditList: function() {
      this.$emit('move-to-edit', null, this.item.code, 'temporary');
    }
  }
};


// 画像設定 メイン
var vmProductImageEdit = new Vue({
    el: '#productImageEdit'
  , delimiters: ['(%', '%)']
  , data: {
      editList: [] // 登録一覧
    , deleteList: [] // 削除予定一覧
    , temporaryList: [] // 一時置き場一覧

    , saveUrl: null
    , touchAllUrl: null

    , messageState: {}

    , currentImage: null

    , daihyoSyohinCode: null
    , imageUrlParent: null

    , isUploadAreaDragOver: false
    , isDeleteAreaDragOver: false
    , isTemporaryAreaDragOver: false

    , editUrlBase: null
    , changeDaihyoSyohinCode: null

    , editListComponent: 'image-icon-list'
  }
  , components: {
      'image-list': vmComponentProductImageEditList // 画像一覧
    , 'image-icon-list': vmComponentProductImageEditIconList // 画像一覧 アイコン版
    , 'image-delete-list': vmComponentProductImageEditDeleteItem // 削除予定一覧
    , 'image-temporary-list': vmComponentProductImageEditTemporaryItem // 一時置き場一覧
  }
  , mounted: function() {
    this.$nextTick(function () {
      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      this.saveUrl = $(this.$el).data('saveUrl');
      this.touchAllUrl = $(this.$el).data('touchAllUrl');

      // データ当て込み
      this.daihyoSyohinCode = DAIHYO_SYOHIN_CODE;
      this.imageUrlParent = IMAGE_URL_PARENT;
      if ($.isArray(IMAGE_EDIT_DATA)) {
        for (var i = 0; i < IMAGE_EDIT_DATA.length; i++) {
          this.addRow(IMAGE_EDIT_DATA[i]);
        }

        this.renumberEditList();
      }

      this.editUrlBase = $(this.$el).data('editUrlBase');
      this.changeDaihyoSyohinCode = DAIHYO_SYOHIN_CODE;
    });
  }

  , computed: {
    currentImageUrl: function() {
      if (this.currentImage && this.currentImage.imageUrl) {
        if (this.currentImage.uploadFile) { // アップロード分はそのまま
          return this.currentImage.imageUrl;
        }

        return this.currentImage.imageUrl + '?' + Math.random().toString(36).slice(-8) ;
      }
    }

    , uploadAreaCss: function() {
      return { 'alert-info': this.isUploadAreaDragOver };
    }
    , deleteAreaCss: function() {
      return { 'alert-danger': this.isDeleteAreaDragOver };
    }
    , temporaryAreaCss: function() {
      return { 'alert-success': this.isTemporaryAreaDragOver };
    }

    , allList: function() {
      return this.editList.concat(this.deleteList, this.temporaryList);
    }

    , maxNumber: function() {
      var max = 0;
      for (var i = 0; i < this.allList.length; i++) {
        if (max < this.allList[i].number) {
          max = this.allList[i].number;
        }
      }
      return max;
    }

    , maxNewNumber: function() {
      var max = 0;
      for (var i = 0; i < this.allList.length; i++) {
        if (max < this.allList[i].newNumber) {
          max = this.allList[i].newNumber;
        }
      }
      return max;
    }

  }

  , methods: {

    /// データ保存処理
    save: function() {

      var self = this;
      self.messageState.clear();

      if (self.temporaryList.length > 0) {
        self.messageState.setMessage('一時置き場に画像が残っているため、保存できません。', 'alert-warning');
        return;
      }

      if (!confirm('商品画像を保存してよろしいですか？')) {
        return;
      }

      // Show loading
      $.Vendor.WaitingDialog.show('保存しています ...');

      // データ保存処理
      var data = {
      };

      // 画像を渡すために processData: false とするため、データは自分で組み立てる。
      // code, newCode, deleted のみで大丈夫、なはず。
      // delete, move, new の3種の操作
      var formData = new FormData();
      var i, item;
      for (i = 0; i < self.editList.length; i++) {
        item = self.editList[i];
        var newCode = generateNewImageCode(item);

        if (item.uploadFile) {
          formData.append('upload[' + newCode + ']', item.uploadFile);
        } else {
          if (item.code != newCode) {
            formData.append('move[' + item.code + ']', newCode);
          }
        }
      }
      for (i = 0; i < self.deleteList.length; i++) {
        item = self.deleteList[i];
        formData.append('delete[]', item.code);
      }


      $.ajax({
          type: "POST"
        , url: self.saveUrl
        , dataType: "json"
        , data: formData

        , contentType: false
        , processData: false
      })
        .done(function(result) {

          var message;
          if (result.status == 'ok') {

            message = result.message.length > 0 ? result.message : '商品画像を保存しました。';
            self.messageState.setMessage(message, 'alert-success');

            // リロード
            window.location.reload();

          } else {
            message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
          // Show loading
          $.Vendor.WaitingDialog.hide();
        });
    }

    /// 移動元リスト取得
    , getListByName: function(listName) {
      return {
          edit: this.editList
        , delete: this.deleteList
        , temporary: this.temporaryList
      }[listName];
    }

    /// コードで取得
    , findRow: function(code, list) {
      if (!list) {
        list = this.editList;
      }

      for (var i = 0; i < list.length; i++) {
        if (list[i].code == code) {
          return list[i];
        }
      }
    }

    /// リストへ1件追加
    , addRow: function(addItem, mode) {

      // 初期読み込み
      var item;
      if (!mode) {
        item = {
            daihyoSyohinCode: addItem.daihyoSyohinCode
          , code            : addItem.code
          , directory       : addItem.directory
          , filename        : addItem.filename
          , fileDirPath     : addItem.fileDirPath
          , address         : addItem.address
          , created         : (addItem.created ? new Date(addItem.created.replace(/-/g, "/")) : null) // replace for firefox, IE
          , updated         : (addItem.updated ? new Date(addItem.updated.replace(/-/g, "/")) : null) // replace for firefox, IE

          , size  : addItem.size ? Number(addItem.size) : null
          , width : addItem.width ? Number(addItem.width) : null
          , height: addItem.height ? Number(addItem.height) : null

          , imageUrl : addItem.fileDirPath ? this.imageUrlParent + addItem.fileDirPath : null
          , deleted : false // 削除予定
          , uploadFile : null
          , number: parseInt(addItem.code.replace(/^p/g, ''))
        };

        this.editList.push(item);

      } else if (mode == 'upload') {

        var number = this.maxNumber + 1;
        item = {
            daihyoSyohinCode: this.daihyoSyohinCode
          , code            : 'n' + $.Plusnao.String.zeroPadding(number, 3) // 新規なのでn00x
          , directory       : null
          , filename        : null
          , fileDirPath     : null
          , address         : null
          , created         : null
          , updated         : null

          , size : null
          , width: null
          , height: null

          , imageUrl: null
          , deleted : false // 削除予定
          , uploadFile : addItem // 新規追加ファイル
          , number: number
        };

        var fileReader = new FileReader();
        fileReader.onload = function( event ) {
          item.imageUrl = event.target.result;
        };
        fileReader.readAsDataURL( addItem );

        this.temporaryList.push(item);
      }

    }

    , insertRow: function(targetItem, insertCode, fromListName) {

      var fromList = this.getListByName(fromListName);
      var insertItem = this.findRow(insertCode, fromList);
      if (!insertItem) {
        return;
      }

      var fromIndex, insertIndex;

      fromIndex = fromList.indexOf(insertItem);
      var list = this.editList;
      if (!list.length || !targetItem || !targetItem.code) {
        // 元配列から削除
        if (fromIndex !== -1) {
          fromList.splice(fromIndex, 1);
        }
        list.push(insertItem);

      } else {
        insertIndex = list.indexOf(targetItem);
        if (insertIndex === -1) { // イレギュラー。ひとまず追加しておく
          // 元配列から削除
          if (fromIndex !== -1) {
            fromList.splice(fromIndex, 1);
          }
          list.push(insertItem);

        // エリア内移動
        } else if (fromListName === 'edit') {
          if (fromIndex === insertIndex) {
            return;

          // 隣の場合にはswap （感覚的なもの）
          } else if (Math.abs(fromIndex - insertIndex) == 1) {
            var tmp = list[insertIndex];

            this.$set(list, insertIndex, insertItem);
            this.$set(list, fromIndex, tmp);

          } else {
            // 削除してから移動先を再取得
            if (fromIndex !== -1) {
              list.splice(fromIndex, 1);
            }
            insertIndex = list.indexOf(targetItem); // 削除後の移動先を再取得
            if (insertIndex === -1) { // イレギュラー。ひとまず追加しておく
              list.push(insertItem);
            }

            list.splice(insertIndex, 0, insertItem);
          }

        // 別エリアからの移動
        } else {
          // 元配列から削除
          if (fromIndex !== -1) {
            fromList.splice(fromIndex, 1);
          }

          list.splice(insertIndex, 0, insertItem);
        }
      }

      this.renumberEditList();
    }

    , renumberEditList: function() {
      var i, list;
      list = this.editList;
      for (i = 0; i < list.length; i++) {
        list[i].newNumber = i + 1;
      }

      list = this.deleteList;
      for (i = 0; i < list.length; i++) {
        list[i].newNumber = 9999;
      }

      list = this.temporaryList;
      for (i = 0; i < list.length; i++) {
        list[i].newNumber = 9999;
      }
    }

    , moveToTemporary: function(code, fromListName) {
      var fromList = this.getListByName(fromListName);
      var item = this.findRow(code, fromList);
      if (!item) {
        return;
      }
      var fromIndex = fromList.indexOf(item);
      if (fromIndex === -1) {
        return;
      }

      // 削除して追加
      fromList.splice(fromIndex, 1);
      this.temporaryList.push(item);

      this.renumberEditList();
    }

    , deleteAll: function() {
      var item, i;

      for (i = 0; i < this.editList.length; i++) {
        item = this.editList[i];
        if (item.uploadFile === null) {
          this.deleteList.push(item);
        }
      }
      this.editList = [];

      for (i = 0; i < this.temporaryList.length; i++) {
        item = this.temporaryList[i];
        if (item.uploadFile === null) {
          this.deleteList.push(item);
        }
      }
      this.temporaryList = [];

      this.renumberEditList();
    }

    , moveTemporaryAll: function() {
      var item, i;
      for (i = 0; i < this.editList.length; i++) {
        item = this.editList[i];
        this.temporaryList.push(item);
      }
      this.editList = [];
      this.renumberEditList();
    }

    , deleteFromTemporaryAll: function() {
      var item, i;
      for (i = 0; i < this.temporaryList.length; i++) {
        item = this.temporaryList[i];
        if (item.uploadFile === null) {
          this.deleteList.push(item);
        }
      }

      this.temporaryList = [];
      this.renumberEditList(); // editList関係ないからいらないと言えばいらない
    }

    , returnFromTemporaryAll: function() {
      var item, i;
      for (i = 0; i < this.temporaryList.length; i++) {
        item = this.temporaryList[i];
        this.editList.push(item);
      }

      this.temporaryList = [];
      this.renumberEditList();
    }

    , reload: function() {
      window.location.reload();
    }

    /// 全更新
    , touchAll: function() {

      var self = this;
      self.messageState.clear();

      if (!confirm('商品画像の最終更新日時を全て現時刻に設定します。よろしいですか？')) {
        return;
      }

      // Show loading
      $.Vendor.WaitingDialog.show('更新しています ...');

      // データ保存処理
      var data = {
      };


      $.ajax({
          type: "POST"
        , url: self.touchAllUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          var message;
          if (result.status == 'ok') {

            message = result.message.length > 0 ? result.message : '最終更新日時を更新しました。';
            self.messageState.setMessage(message, 'alert-success');

            // リロード
            window.location.reload();

          } else {
            message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageState.setMessage(message, 'alert-danger');
          }

        })
        .fail(function(stat) {
          console.log(stat);
          self.messageState.setMessage('エラーが発生しました。', 'alert-danger');

        })
        . always(function() {
          // Show loading
          $.Vendor.WaitingDialog.hide();
        });
    }

    , changeProduct: function() {
      if (this.changeDaihyoSyohinCode && this.changeDaihyoSyohinCode.length > 0) {
        window.location.href = this.editUrlBase.replace(/__DUMMY__/, this.changeDaihyoSyohinCode);
      }
    }

    , selectAll: function(event) {
      event.target.select();
    }

    , toggleEditListComponent: function() {
      this.editListComponent = this.editListComponent == 'image-list' ? 'image-icon-list' : 'image-list';
    }


    // ----------------------------------
    // イベントハンドラ
    // ----------------------------------
    , setCurrentImage: function(item) {
      this.currentImage = item;
    }

    /// アップロードエリア dragOver
    , uploadDragOver: function(event) {
      event.preventDefault(); // これをしないとdropイベントが発生しない
    }
    /// アップロードエリア dragEnter
    , uploadDragEnter: function() {
      event.preventDefault();
      this.isUploadAreaDragOver = true;
    }
    /// アップロードエリア dragLeave
    , uploadDragLeave: function() {
      event.preventDefault();
      this.isUploadAreaDragOver = false;
    }
    /// アップロードエリア drop
    , uploadDrop: function(event) {
      event.preventDefault();
      this.isUploadAreaDragOver = false;

      var files = event.dataTransfer.files;
      if (!files || !files.length) {
        return;
      }

      for (var i = 0; i < files.length; i++) {
        // jpeg画像じゃない場合はスルー
        if (!files[i] || files[i].type.indexOf('image/') < 0 || !files[i].type.match(/jpg|jpeg/i)) {
          continue;
        }

        this.addRow(files[i], 'upload');
      }

      this.renumberEditList();
    }

    /// 削除エリア dragOver
    , deleteDragOver: function(event) {
      event.preventDefault(); // これをしないとdropイベントが発生しない
    }
    /// 削除エリア dragEnter
    , deleteDragEnter: function(event) {
      event.preventDefault();
      this.isDeleteAreaDragOver = true;
    }
    /// 削除エリア dragLeave
    , deleteDragLeave: function(event) {
      event.preventDefault();
      this.isDeleteAreaDragOver = false;
    }
    /// 削除エリア drop
    , deleteDrop: function(event) {
      event.preventDefault();
      this.isDeleteAreaDragOver = false;

      var code = event.dataTransfer.getData('text/image-code');
      var fromListName = event.dataTransfer.getData('text/image-from-list');
      if (!code || !fromListName) {
        return;
      }

      var fromList = this.getListByName(fromListName);
      var item = this.findRow(code, fromList);

      if (item) {
        // 元リストから削除
        var index = fromList.indexOf(item);
        if (index != -1) {
          fromList.splice(index, 1);
        }

        // 登録済みのファイルなら削除リストへ追加
        if (item.uploadFile === null) {
          this.deleteList.push(item);
        }

        this.renumberEditList();
      }
    }

    /// 一時置き場エリア dragOver
    , temporaryDragOver: function(event) {
      event.preventDefault(); // これをしないとdropイベントが発生しない
    }
    /// 一時置き場エリア dragEnter
    , temporaryDragEnter: function() {
      event.preventDefault();
      this.isTemporaryAreaDragOver = true;
    }
    /// 一時置き場エリア dragLeave
    , temporaryDragLeave: function() {
      event.preventDefault();
      this.isTemporaryAreaDragOver = false;
    }
    /// 一時置き場エリア drop
    , temporaryDrop: function(event) {
      event.preventDefault();
      this.isTemporaryAreaDragOver = false;

      var code = event.dataTransfer.getData('text/image-code');
      var fromListName = event.dataTransfer.getData('text/image-from-list');
      if (!code || !fromListName) {
        return;
      }

      var fromList = this.getListByName(fromListName);
      var item = this.findRow(code, fromList);

      if (item) {
        var index = fromList.indexOf(item);
        if (index != -1) {
          fromList.splice(index, 1);
        }

        this.temporaryList.push(item);
        this.renumberEditList();
      }
    }    

  }

});

