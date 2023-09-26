/**
 * 管理画面 商品情報・仕入備考 JS
 */

/** メイン画面 */
var goodsInfo = new Vue({
  el: '#goodsInfo',
  data:{
    daihyoSyohinCode: "", // 検索用の代表商品コード
    searchUrl: null, // 検索URL
    updateUrl: null, // 保存URL
    messageState: {}, // エラーメッセージ
    product: null,
    isUpdatable: true,

    modalState: {
      message: ''
      , messageCssClass: ''
      , currentItem: {}
      , show: false
    }
  },
  mounted: function() {
    this.$nextTick(function () {
      const self = this;
  
      // URL取得
      self.searchUrl = $(self.$el).data('searchUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
  
      self.messageState = new PartsGlobalMessageState();
        
      if (SEARCH_CODE) {
        self.daihyoSyohinCode = SEARCH_CODE;
        self.search();
      }
    });
  },
  methods: {
    // 検索
    search: function() {
      var self = this;
      self.messageState.clear();
      self.product = null;
      self.isUpdatable = true;

      // Show loading
      $.Vendor.WaitingDialog.show();

      $.ajax({
        type: "GET",
        url: self.searchUrl,
        dataType: "json",
        data: {
          code: self.daihyoSyohinCode,
        },
      })
      .done(function(result) {
        if (result.status == 'ok') {
          self.updateUrlByCode(self.daihyoSyohinCode);
          self.messageState.setMessage(result.message, 'alert alert-danger');
          self.isUpdatable = !result.message;
          self.product = result.product || null;
        } else {
          var message = result.message ? result.message : '検索でエラーが発生しました';
          self.isUpdatable = false;
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
      .always(function() {
        // Hide loading
        $.Vendor.WaitingDialog.hide();
      });
    },
    updateUrlByCode: function(code) {
      var url = window.location.href;
      var urlParts = url.split('?');
      if (urlParts.length > 0) {
        var baseUrl = urlParts[0];
        var vars = {}, hash;
        if(url.indexOf('?') >= 0 && url.indexOf('?') + 1 !== url.length){
          var hashes = url.substr(url.indexOf('?') + 1).split('&');
          for(var i = 0; i < hashes.length; i++)
          {
            hash = hashes[i].split('=');
            vars[hash[0]] = hash[1];
          }
        }
        vars['code'] = code || '';

        var updatedQueryStrArray = [];
        for (key of Object.keys(vars)) {
          updatedQueryStrArray.push(key + '=' + vars[key]);
        }
        var updatedUri = baseUrl + '?' + updatedQueryStrArray.join('&');
        window.history.replaceState({}, document.title, updatedUri);
      }
    },
    // 保存
    update: function() {
      var self = this;
      this.messageState.clear();
      
      const data = {
          code: self.daihyoSyohinCode,
          description: self.product.description,
          aboutSize: self.product.aboutSize,
          aboutMaterial: self.product.aboutMaterial,
          aboutColor: self.product.aboutColor,
          aboutBrand: self.product.aboutBrand,
          usageNote: self.product.usageNote,
          supplementalExplanation: self.product.supplementalExplanation,
          shortDescription: self.product.shortDescription,
          shortSupplementalExplanation: self.product.shortSupplementalExplanation,
          sireDescription: self.product.sireDescription,
          memo: self.product.memo,
      }
      Object.keys(data).forEach(key => {
        if (data[key]) {
          data[key] = data[key].replace(/([^\r])\n/g, "$1\r\n");
        }
      });
      
      $.ajax({
        type: "POST",
        url: self.updateUrl,
        dataType: "json",
        data: data
      })
      .done(function(result) {
        if (result.status == 'ok') {
          self.messageState.setMessage(result.message, 'alert-success');
        } else {
          var message = result.message ? result.message : '更新でエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      })
    },
  },
});