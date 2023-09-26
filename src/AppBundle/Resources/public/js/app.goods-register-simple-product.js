/**
 * 商品 簡易商品登録画面用 JS
 */

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
      cssClass = cssClass || 'alert alert-info';
      autoHide = autoHide || true;

      this.message = message;
      this.setCssClass(cssClass);

      if (autoHide) {
        setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
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

/// 登録フォーム
var vmRegisterSimpleProduct = new Vue({
    el: '#goodsRegisterSimpleProduct'
  , delimiters: ['(%', '%)']
  , data: {
      skuCols: []
    , skuRows: []
    , colTypeName: ''
    , rowTypeName: ''

    , replaceWordList: {}
    , replaceWordUrl: ''
  }
  , mounted: function() {
      const self = this;
      self.$nextTick(function () {
        self.skuCols = SKU_DATA.cols;
        self.skuRows = SKU_DATA.rows;
        self.colTypeName = 'カラー';
        self.rowTypeName = 'サイズ';

        self.replaceWordListUrl = $(self.$el).data('replaceWordListUrl');
        self.fetchReplaceWordList();
      });
  }
  , methods: {
    addLine: function(type) {
      var target = type == 'col' ? this.skuCols : this.skuRows;
      target.push({
          code: ''
        , name: ''
      });
    }
    , removeLine: function(type) {
      var target = type == 'col' ? this.skuCols : this.skuRows;
      if (target.length <= 1) {
        return;
      }
      target.pop();
    }
    , setReplaceWord: function($event) {
      const word = $event.target.value;
      const nameSelector = $event.target;
      const codeSelector = nameSelector.parentNode.parentNode.children[1].children[0];
      codeSelector.value = this.replaceWord(word);
    }
    // 置換処理
    // 【】とその中身はコードに利用しないので削除
    // ×(かける)はx(小文字エックス)に置換
    // 置換できる文字列をすべて置換
    // 半角英数とハイフン以外を削除
    , replaceWord: function(word){
      const self = this;

      word = word.replace(/【(.*)】/g,'');
      word = word.replace(/×/g,'x');

      let matchList = [];
      for(let i = 0; i < self.replaceWordList.length; i++){
        const regexp = new RegExp(self.replaceWordList[i]["before_word"]);
        const matchWord = word.match(regexp,self.replaceWordList[i]["after_word"]);
        if(matchWord) {
          matchList.push({before_word : self.replaceWordList[i]["before_word"] , after_word : self.replaceWordList[i]["after_word"]});
        }
      }

      // 文字数が多い順にソート ->  文字の一致数が多い方を優先で置換させるため
      matchList.sort(function(next, match){
        return match.before_word.length - next.before_word.length; 
      });

      for(let i = 0; i < matchList.length; i++) {
        const regexp = new RegExp(matchList[i]["before_word"], 'g');
        word = word.replace(regexp,matchList[i]["after_word"]);
      }
      const replacedWord = word.replace(/[^A-Za-z0-9\-]/g,'');
      return replacedWord;
    }

    , fetchReplaceWordList: function () {
      const self = this;
      $.ajax({
        type: "POST"
        , url: self.replaceWordListUrl
        , dataType: "json"
      })
      .done(function(result) {
        if(result.status === "ok"){
          self.replaceWordList = result.list;
        }else{
          console.error(result.message);
          alert(result.message);
        }
      })
      .fail(function(stat) {
        console.error(stat.message);
        alert(stat.message);
      });
    }
  }
});


