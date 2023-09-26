/**
 * 管理画面 SKU別送料設定画面 JS
 */

/* 一覧画面 一覧テーブル 行コンポーネント */
var vmComponentSkuListItem = {
  template: '#templateSkuListTableRow'
  , props: [
     'sku'
  ]
}

/** メイン画面 */
var productItem = new Vue({
  el: '#skuShippingSetting',
  data: {
    skuCode: ""
    , searchedSkuCode: "" // 検索実行後に設定される。検索～更新の間にNE商品コードが書き替えられないように
    , searchedDaihyoSyohinCode: ""
    , item: null
    , messageState: {}

    // 処理URL（基本URL）
    , searchUrl: null
    , updateUrl: null

    // 送料設定
    , updateShippingGroupCode : ""
    , shippingGroupList : SHIPPING_GROUP_LIST
  }
  , computed: {
    shippingGroupOptions : function() { // 配送方法リスト（選択可能なプルダウン）
      var result = [];
      for (key in SHIPPING_GROUP_LIST) {
        var option = { value : key, text : SHIPPING_GROUP_LIST[key],}
        result.push(option);
      }
      return result;
    }
  }
  , ready: function () {
    var self = this;

    // URL文字列取得
    self.searchUrl = $(self.$el).data('searchUrl');
    self.updateUrl = $(self.$el).data('updateUrl');

    // 配送方法 [id => 名称] の表示用リストと、セレクトボックス用リストの2つを作成する
    for (shippingDivision in SHIPPINGDIVISION_LIST) {
      self.shippingdivisionList[shippingDivision.id] = shippingDivision.name;
      if (shippingDivision.terminateFlg != '0') {
        var option = { value : shippingDivision.id, text : shippingDivision.name,}
        self.shippingdivisionOptions.push(option);
      }
    }
  }
  , components: {
    'skuList': vmComponentSkuListItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function (){
      var self = this;

      // URL文字列取得
      self.searchUrl = $(self.$el).data('searchUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.messageState = new PartsGlobalMessageState();
    })
  },
  methods: {
    // 検索
    search: function() {
      var self = this;
      self.messageState.clear();
      $.ajax({
          type: "POST"
        , url: self.searchUrl
        , dataType: "json"
        , data: {
            neSyohinSyohinCode : self.skuCode,
        }
      })
      .done(function(result) {
        if (result.status == 'ok') {
          if (result.item) {
            self.item = result.item;
            self.searchedDaihyoSyohinCode = result.item.product.daihyoSyohinCode;
            self.searchedSkuCode = self.skuCode;
          } else {
            var message = result.message.length > 0 ? result.message : 'データがありません';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        } else {
          var message = result.message.length > 0 ? result.message : '検索でエラーが発生しました';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        console.log(stat);
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

      })
    }

    // 更新
    , update: function() {
      var self = this;
      self.messageState.clear();
      $.ajax({
        type: "POST"
      , url: self.updateUrl
      , dataType: "json"
      , data: {
        neSyohinSyohinCode : self.searchedSkuCode
        , shippingGroupCode : self.updateShippingGroupCode
      }
    })
    .done(function(result) {
        if (result.status == 'ok') {
          self.messageState.setMessage(result.message, 'alert-success');
        } else {
          var message = result.message.length > 0 ? result.message : '更新できませんでした。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      })
      .fail(function(stat) {
        console.log(stat);
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

      })
    }
  }
})
