/**
 * SKU別重量・サイズ編集
 */
// モジュールロード試験
// => 2018/03/20現在
// => このjsだけ scriptタグに type="module" をつけることで一応は可能。
// => ただしFireFoxでは about:config の enableModuleScript を Trueにする必要がある。（つまりはまだ実験的機能）
// => 今からBabelなりを利用するのも無駄の様なので、正式対応まではimport/exportは忘れる
//
//import Person from '/bundles/app/js/modules/test_module.js';
//(function(){
//  const person = new Person('name', 20);
//  person.talk();
//})();
ELEMENT.locale(ELEMENT.lang.ja);

/**
 * SKU一行データオブジェクト
 * @param item
 * @param wsLimits
 * @constructor
 */

const EntitySkuWeight = function(item, wsLimits) {

  item = item || {};
  wsLimits = wsLimits || {};

  this.item = item;
  this.neSyohinSyohinCode = item.neSyohinSyohinCode;
  this.colname = item.colname;
  this.rowname = item.rowname;
  this.weight  = item.weight;
  this.depth   = item.depth / 10;
  this.width   = item.width / 10;
  this.height  = item.height / 10;
  this.descriptionEn  = item.descriptionEn;
  this.descriptionCn  = item.descriptionCn;
  this.hintJa  = item.hintJa;
  this.hintCn  = item.hintCn;

  this.originalWeight = item.weight;
  this.originalDepth = item.depth;
  this.originalWidth = item.width;
  this.originalHeight = item.height;
  this.originalDescriptionEn = item.descriptionEn;
  this.originalDescriptionCn = item.descriptionCn;
  this.originalHintJa = item.hintJa;
  this.originalHintCn = item.hintCn;

  this.wsLimits = wsLimits;

  this.showSettings = false;
  this.showSettingsDES = false;
  this.listDesc = false;
  this.listDesHint = false;

  // this.weightCheckBase = 0.002; // 水 1mm3 で 0.001 g (1mg) の2倍
  // this.weightCheckThreshold = 0.5; // 上下50%の範囲でチェック
};

EntitySkuWeight.prototype.isWeightModified = function() {
  return this.weight !== this.originalWeight;
};
EntitySkuWeight.prototype.isDepthModified = function() {
  return this.depth*10 !== this.originalDepth;
};
EntitySkuWeight.prototype.isWidthModified = function() {
  return this.width*10 !== this.originalWidth;
};
EntitySkuWeight.prototype.isHeightModified = function() {
  return this.height*10 !== this.originalHeight;
};
EntitySkuWeight.prototype.isDescriptionEnModified = function() {
  return this.descriptionEn !== this.originalDescriptionEn;
};
EntitySkuWeight.prototype.isDescriptionCnModified = function() {
  return this.descriptionCn !== this.originalDescriptionCn;
};
EntitySkuWeight.prototype.isHintJaModified = function() {
  return this.hintJa !== this.originalHintJa;
};
EntitySkuWeight.prototype.isHintCnModified = function() {
  return this.hintCn !== this.originalHintCn;
};
EntitySkuWeight.prototype.isModified = function() {
  return (
       this.isWeightModified()
    || this.isDepthModified()
    || this.isWidthModified()
    || this.isHeightModified()
    || this.isDescriptionEnModified()
    || this.isDescriptionCnModified()
    || this.isHintJaModified()
    || this.isHintCnModified()
  );
};
EntitySkuWeight.prototype.isSet = function() {
  return (
       this.weight
    && this.depth
    && this.width
    && this.height
    && this.descriptionEn
    && this.descriptionCn
    && this.hintJa
    && this.hintCn
  );
};
EntitySkuWeight.prototype.isOriginalSet = function() {
  return (
       this.originalWeight
    && this.originalDepth
    && this.originalWidth
    && this.originalHeight
    && this.originalDescriptionEn
    && this.originalDescriptionCn
    && this.originalHintJa
    && this.originalHintCn
  );
};
EntitySkuWeight.prototype.getUpdateItem = function() {
  return {
      neSyohinSyohinCode: this.neSyohinSyohinCode
    , weight: this.weight
    , depth: this.depth*10
    , width: this.width*10
    , height: this.height*10
    , descriptionEn: this.descriptionEn
    , descriptionCn: this.descriptionCn
    , hintJa: this.hintJa
    , hintCn: this.hintCn
  }
};
EntitySkuWeight.prototype.getVolume = function() {
  return this.isSet() ? this.depth * this.width * this.height : 0;
};

EntitySkuWeight.prototype.isClickpostWeightOver = function() {
  if (!this.isSet()) { return false; }
  return this.weight >= this.wsLimits.weight_aubound;
};
EntitySkuWeight.prototype.isClickpostSizeOver = function() {
  if (!this.isSet()) { return false; }

  const sizeList = [
      Number(this.depth)
    , Number(this.width)
    , Number(this.height)
  ].sort(function(a, b) { return b - a; });

  return sizeList[0] >= Number(this.wsLimits.side1_ubound)
      || sizeList[1] >= Number(this.wsLimits.side2_ubound)
      || sizeList[2] >= Number(this.wsLimits.side3_ubound)
  ;
};
EntitySkuWeight.prototype.isTooLight = function() {
  if (!this.isSet()) { return false; }
  const checkWeight = this.wsLimits.weight_lbound / (10 * 10 * 10) * this.getVolume();
  return this.weight <= checkWeight;
};
EntitySkuWeight.prototype.isTooHeavy = function() {
  if (!this.isSet()) { return false; }
  const checkWeight = this.wsLimits.weight_ubound / (10 * 10 * 10) * this.getVolume();
  return this.weight >= checkWeight;
};
// テンプレートのv-ifで判定するときになぜかうまく動かないので利用なし。
EntitySkuWeight.prototype.isOutOfSpec = function() {
  return (
      this.isClickpostSizeOver()
   || this.isClickpostWeightOver
  );
};

//確認ダイアログの動作設定
var modalConfirm = function(){
  var defer = $.Deferred();
  var targetModal = $('#modalInputCheckConfirm');

  targetModal.find('.btn-default').on('click', function(){
    targetModal.modal('hide');
    defer.reject();
  });

  targetModal.find('.btn-primary').on('click', function(){
    targetModal.modal('hide');
    defer.resolve();
  });
  return defer.promise();
};

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentGoodsWeightSizeSkuListItem = {
    template: '#templateGoodsWeightSizeSkuListTableRow'
  , props: [
      'item'
    , 'state'
  ]
  , data: function() {
    return {
      dataListDesc: null,
    };
  }
  , computed: {
      rowCss: function() {
        if (this.isSelected) {
          return 'info';
        }
        if (! this.item.isSet()) {
          return 'danger';
        }
        return this.item.isModified() ? 'warning' : '';
      }

      , isSelected: function() {
        return this.item && (this.item === this.state.currentSku);
      }

  }
  , mounted: function() {
    this.$nextTick(function() {
      //this.userListUrlBase = $(this.$el).data('userListUrlBase');
      //this.plusnaoLoginUrlBase = $(this.$el).data('plusnaoLoginUrlBase');
      const self = this;
      self.dataListDesc = $(self.$el).data('listDesc');
    });
  }
  , methods: {
      changeCurrentSku: function() {
        this.state.currentSku = this.item;
        const listDescOrigin = this.dataListDesc;
        for (let i = 0; i < listDescOrigin.length; i++) {
          if(listDescOrigin[i]['description_en'] == this.state.currentSku.descriptionEn) {
            this.idDesc = listDescOrigin[i]['id'];
          }
          if(listDescOrigin[i]['hint_ja'] == this.state.currentSku.hintJa) {
            this.idHint = listDescOrigin[i]['id'];
          }
        }
        this.state.selectedDesc = this.idDesc;
        this.$emit('clicked-show-detail', {'idSelectedDesc': this.idDesc, 'idSelectedHint': this.idHint});
        $(SCROLL_ELEMENT).animate({
          scrollTop: 0
        }, 100);
    }

  }
};

// 詳細一覧
const vmComponentGoodsWeightSizeEditSkuList = {
    template: '#templateGoodsWeightSizeSkuListTable'
  , props: [
      'skuList'
    , 'state'
  ]
  , data: function() {
    return {
    };
  }
  , components: {
    'listItem': vmComponentGoodsWeightSizeSkuListItem // 一覧行
  }

  , mounted: function() {
    this.$nextTick(function () {

    });
  }

  , computed: {
  }
  , methods: {
    clickedShowDetail: function (value) {
      this.$emit('clicked-show-parent', value);
    },
  }
};


// メイン
const vmComponentGoodsWeightSizeEdit = new Vue({
    el: '#goodsWeightSizeEdit'
  , delimiters: ['(%', '%)']
  , data: {
      messageState: {}
    , updateUrl: null
    , createUrl: null
    , findSkuUrl: null
    , updateSettingUrl: null
    , updateDescUrl: null
    , createDescUrl: null
    , createDescriptionUrl: null
    , createHintUrl: null
    , deleteDescUrl: null
    , deleteDescriptionUrl: null
    , deleteHintUrl: null
    , skuList: []
    , state: {
        product: {}
      , currentSku: {
        isClickpostSizeOver: function() { return false; }
        , isClickpostWeightOver: function() { return false; }
        , isTooLight: function() { return false; }
        , isTooHeavy: function() { return false; }
        , isOutOfSpec: function() { return false; }
      }
      , selectedDesc: null
    }
    , changeSyohinCode: null
    , listUnsetOnly: false
    , wsLimits: {}
    , showSettings: null
    , showSettingsDES: null
    , listDesc: []
    , listDescHint: null
    , listDescOrder: null
    , idDesc: null
    , idHint: null
  }
  , components: {
    'skuListTable': vmComponentGoodsWeightSizeEditSkuList // 一覧テーブル
  },
  ready: function() {
  }
  , mounted: function() {
    this.$nextTick(function () {
      const self = this;

      // メッセージオブジェクト
      self.messageState = new PartsGlobalMessageState();
      self.updateUrl = $(self.$el).data('updateUrl');
      self.createUrl = $(self.$el).data('createUrl');
      self.findSkuUrl = $(self.$el).data('findSkuUrl');
      self.updateSettingUrl = $(self.$el).data('updateSettingUrl');
      self.updateDescUrl = $(self.$el).data('updateDescUrl');
      self.createDescUrl = $(self.$el).data('createDescUrl');
      self.createDescriptionUrl = $(self.$el).data('createDescriptionUrl');
      self.createHintUrl = $(self.$el).data('createHintUrl');
      self.deleteDescUrl = $(self.$el).data('deleteDescUrl');
      self.deleteDescriptionUrl = $(self.$el).data('deleteDescriptionUrl');
      self.deleteHintUrl = $(self.$el).data('deleteHintUrl');
      self.wsLimits = $(self.$el).data('wsLimits');
      self.listDesc = $(self.$el).data('listDesc');

      // 初期データ読み込み
      if (SEARCH_CODE) {
        self.changeSyohinCode = SEARCH_CODE;
        self.changeProduct();
      }

    });
  }

  , computed: {
      currentSkuCode: function() {
      return this.state.currentSku ? this.state.currentSku.neSyohinSyohinCode : null;
    }
    , currentSkuColname: function() {
      return this.state.currentSku ? this.state.currentSku.colname : null;
    }
    , currentSkuRowname: function() {
      return this.state.currentSku ? this.state.currentSku.rowname : null;
    }

    , listUnsetOnlyIconOn: function() {
        return this.listUnsetOnly ? [ 'fa', 'fa-fw', 'fa-check' ]: 'hidden' ;
    }
    , listUnsetOnlyIconOff: function() {
      return (! this.listUnsetOnly) ? [ 'fa', 'fa-fw', 'fa-check' ]: 'hidden' ;
    }

    , displaySkuList: function() {
        let list = this.skuList.slice();
        if (this.listUnsetOnly) {
          list = list.filter(function(item) {
            return !item.isOriginalSet()
          });
        }
        return list;
    },
    displayDescList: function() {
      let list = this.listDesc.slice();
      return list;
    }

  }

  , methods: {
    updateDesc: function () {
      const self = this;
      self.messageState.clear();
      $.Vendor.WaitingDialog.show();
      $.ajax({
        type: "POST"
        , url: self.updateDescUrl
        , dataType: "json"
        , data: {data: JSON.stringify(self.listDesc)}
      }).done(function (result) {
        $.Vendor.WaitingDialog.hide();
        if (result.status == 'ok') {
          self.listDesc = result.listDesc;
        } else if (result.status == 'ng') {
          self.messageState.setMessage(result.message, 'alert alert-danger');
          $("html,body").animate({scrollTop:0},600);
        }
      });
    },
    createDesc: function () {
      const self = this;
      $.ajax({
        type: "POST"
        , url: self.createDescUrl
        , dataType: "json"
        , data: {}
      }).done(function (result) {
        if (result.status == 'ok') {
          var desc = {
            id: result.data
            , description_en       : ''
            , description_cn       : ''
            , hint_ja              : ''
            , hint_cn              : ''
          };
        }
        self.listDesc.push(desc);
      });
    },
    createDescription: function () {
      const self = this;
      $.ajax({
        type: "POST"
        , url: self.createDescriptionUrl
        , dataType: "json"
        , data: {}
      }).done(function (result) {
        if (result.status == 'ok') {
          var desc = {
            id: result.data
            , description_en               : ''
            , description_cn               : ''
            , description_delete_flg       : 0
            , hint_delete_flg              : 1
            , hint_ja                      : ''
            , hint_cn                      : ''
          };
        }
        self.listDesc.push(desc);
      });
    },
    createHint: function () {
      const self = this;
      $.ajax({
        type: "POST"
        , url: self.createHintUrl
        , dataType: "json"
        , data: {}
      }).done(function (result) {
        if (result.status == 'ok') {
          var desc = {
            id: result.data
            , description_en               : ''
            , description_cn               : ''
            , description_delete_flg       : 1
            , hint_delete_flg              : 0
            , hint_ja                      : ''
            , hint_cn                      : ''
          };
        }
        self.listDesc.push(desc);
      });
    },
    removeDesc: function (index) {
      const self = this;
      var arr = self.listDesc;
      $.ajax({
        type: "POST"
        , url: self.deleteDescUrl
        , dataType: "json"
        , data: {data: self.listDesc[index]}
      }).done(function (result) {
        if (result.status == 'ok') {
          self.$delete(arr, index);
          self.listDesc = arr;
        }
      });
    },
    removeDescription: function (index) {
      const self = this;
      var arr = self.listDesc;
      $.ajax({
        type: "POST"
        , url: self.deleteDescriptionUrl
        , dataType: "json"
        , data: {data: self.listDesc[index]}
      }).done(function (result) {
        if (result.status == 'ok') {
          self.listDesc[index].description_delete_flg = 1;
        }
      });
    },
    removeHint: function (index) {
      const self = this;
      var arr = self.listDesc;
      $.ajax({
        type: "POST"
        , url: self.deleteHintUrl
        , dataType: "json"
        , data: {data: self.listDesc[index]}
      }).done(function (result) {
        if (result.status == 'ok') {
          self.listDesc[index].hint_delete_flg = 1;
        }
      });
    },
    clickedShowParent: function(value) {
      this.idDesc = value.idSelectedDesc;
      this.onChangeDesc();
      this.idHint = value.idSelectedHint;
      this.onChangeHint();
    },
    getDescById: function(id) {
      if (this.idDesc == null) {
        return null;
      }
      for (let i = 0; i < this.listDesc.length; i++) {
        if (this.listDesc[i]['id'] == id) {
          return this.listDesc[i];
        }
      }
      return null;
    },
    getHintById: function(id) {
      if (this.idHint == null) {
        return null;
      }
      for (let i = 0; i < this.listDesc.length; i++) {
        if (this.listDesc[i]['id'] == id) {
          return this.listDesc[i];
        }
      }
      return null;
    },
    onChangeDesc: function() {
      let itemDesc = this.getDescById(this.idDesc);
      this.state.currentSku.descriptionEn = (itemDesc && itemDesc.description_en) ? itemDesc.description_en : null;
      this.state.currentSku.descriptionCn = (itemDesc && itemDesc.description_cn) ? itemDesc.description_cn: null;
    },
    onChangeHint: function() {
      let itemHint = this.getHintById(this.idHint);
      this.state.currentSku.hintJa = (itemHint && itemHint.hint_ja) ? itemHint.hint_ja : null;
      this.state.currentSku.hintCn = (itemHint && itemHint.hint_cn) ? itemHint.hint_cn : null;
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
    changeProduct: function() {
      const self = this;

      self.messageState.clear();

      if (!self.changeSyohinCode || !self.changeSyohinCode.length) {
        return;
      }

      // Show loading
      $.Vendor.WaitingDialog.show();

      const data = {
        code: self.changeSyohinCode
      };

      $.ajax({
          type: "GET"
        , url: self.findSkuUrl
        , dataType: "json"
        , data: data
      }).done(function(result) {

        if (result.status === 'ok') {
          self.updateUrlByCode(self.changeSyohinCode);
          const product = result.product;
          self.state.product = {};
          self.skuList = [];
          self.changeCurrentSku(new EntitySkuWeight());

          if (!result.product || !result.choices || result.choices.length === 0) {
            self.messageState.setMessage('商品データがありません。', 'alert alert-danger');
            return;
          }

          self.state.product = result.product;
          for (let i = 0; i < result.choices.length; i++) {
            self.skuList.push(new EntitySkuWeight(result.choices[i], self.wsLimits));
          }

          if (result.choice) {
            for (let i = 0; i < self.skuList.length; i++) {
              if (self.skuList[i].neSyohinSyohinCode === result.choice.neSyohinSyohinCode) {
                self.changeCurrentSku(self.skuList[i]);
              }
            }
          } else {
            self.changeCurrentSku(self.skuList[0]);
          }

        } else {
          self.messageState.setMessage(result.message, 'alert alert-danger');
        }

      }).fail(function() {
        self.messageState.setMessage('商品データ取得時にエラーが発生しました。', 'alert alert-danger');

      }).always(function() {
        // Show loading
        $.Vendor.WaitingDialog.hide();

      });

    }
    , clearAll: function() { // clear all visible rows in table
      const list = this.listUnsetOnly ? this.displaySkuList : this.skuList;
      const startSku = this.state.currentSku;

      for (let i = 0; i < list.length; i++) {
        let item = list[i];
        this.changeCurrentSku(item);
        this.clearCurrentItem();
      }

      this.changeCurrentSku(startSku)
    }
    , clearCurrentItem: function() {
      this.state.currentSku.height = 0;
      this.state.currentSku.width = 0;
      this.state.currentSku.depth = 0;
      this.state.currentSku.weight = 0;
    }

    , updateLimits: function() {
      const self = this;
      self.messageState.setMessage("ok", 'alert alert-success');
      const data = {
        wsLimits: self.wsLimits
      };
      $.ajax({
        type: "POST"
      , url: this.updateSettingUrl
      , dataType: "json"
      , data: data
      }).done(function(result) {
       self.messageState.setMessage(result.message, 'alert alert-success');
      })
    }
    , toggleSettings: function() {
      this.showSettings=!this.showSettings
    }
    , toggleSettingsDES: function() {
      this.showSettingsDES=!this.showSettingsDES;
    }

    /* 全データ保存 */
    , updateAll: function() {
      const self = this;
      const updateSkuList = [];
      for (let i = 0; i < self.skuList.length; i++) {
        let item = self.skuList[i];
        if (item.isModified()) {
          updateSkuList.push(item);
        }
      }
      self.update(updateSkuList);
    }

    /**
     *  更新実行　パラメータで渡されたリストを更新する
     */
    , update: function(updateSkuList) {
      const self = this;

      const updateList = [];
      let validationMessage = "";
      let errorItem = null;

      for (let i = 0; i < updateSkuList.length; i++) {
        let item = updateSkuList[i];
        updateList.push(item.getUpdateItem());
        if (!errorItem) { // 最初に見つかったエラーのみ保持
          validationMessage = this.validateItem(item);
          if (validationMessage.length > 0) {
            errorItem = item;
          }
        }
      }

      //確認ダイアログ表示
      var confirmCurrentSku = $('#modalInputCheckConfirm').find('.confirmCurrentSku');
      confirmCurrentSku.empty();
      var confirmMessage = $('#modalInputCheckConfirm').find('.confirmMsg');
      confirmMessage.empty();
      var modalPromise;
      var confirmFlg = false;

      if (validationMessage) {
        // mm -> cm
        var confirmSkuMessage =  errorItem.neSyohinSyohinCode
          + " ( "
          + errorItem.weight + "g"
          + " / " + errorItem.width + "cm"
          + " x " + errorItem.depth + "cm"
          + " x " + errorItem.height + "cm"
          + " ) ";
        validationMessage += "<br>\nこのまま保存しますか？"

        confirmCurrentSku.append(confirmSkuMessage);
        confirmMessage.append(validationMessage);
        $('#modalInputCheckConfirm').modal('show');
        modalPromise = modalConfirm();
        confirmFlg = true;

      } else {
        confirmMessage.append('変更を保存しますか？');
        $('#modalInputCheckConfirm').modal('show');
        modalPromise = modalConfirm();
        confirmFlg = false
      }

      //OK処理
      modalPromise.done(function(){

        // Show loading
        $.Vendor.WaitingDialog.show();

        const data = {
          skuList: updateList
        };

        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        }).done(function(result) {

          if (result.status === 'ok') {
            if (!self.changeSyohinCode) {
              if (skuList.length > 0) {
                self.changeSyohinCode = skuList[0].neSyohinSyohinCode;
              }
            }
            self.changeProduct();
            self.messageState.setMessage(result.message, 'alert alert-success');

          } else {
            self.messageState.setMessage(result.message, 'alert alert-danger');
          }

        }).fail(function() {
          self.messageState.setMessage('更新時にエラーが発生しました。', 'alert alert-danger');

        }).always(function() {
          // Show loading
          $.Vendor.WaitingDialog.hide();
        });

      });//modalPromise.done end

      //キャンセル処理
      modalPromise.fail(function() {
        if(confirmFlg){
          self.changeCurrentSku(errorItem);
        }
      });
    }

    , changeCurrentSku: function(sku) {
      this.state.currentSku = sku;
      let flagDesc = false;
      for (let i = 0; i < this.listDesc.length; i++) {
        if (this.listDesc[i]['description_en'] == sku.descriptionEn) {
          this.idDesc = this.listDesc[i]['id'];
          flagDesc = true;
          this.onChangeDesc();
        }
      }
      if (!flagDesc) {
        this.idDesc = null;
      }

      let flagHint = false;
      for (let i = 0; i < this.listDesc.length; i++) {
        if (this.listDesc[i]['hint_ja'] == sku.hintJa) {
          this.idHint = this.listDesc[i]['id'];
          flagHint = true;
          this.onChangeHint();
        }
      }
      if (!flagHint) {
        this.idHint = null;
      }
    }

    , moveCurrentSku: function(direction) {

      const list = this.displaySkuList;

      if (list.length === 0 || !this.state.currentSku) {
        return;
      }

      let index = list.indexOf(this.state.currentSku);
      if (index === -1) {
        this.changeCurrentSku(list[0]);
        return;
      }

      if (direction === 'prev') {
        index -= 1;
      } else {
        index += 1;
      }
      if (index < 0) {
        index = list.length - 1;
      }
      if (index >= list.length) {
        index = 0;
      }

      this.changeCurrentSku(list[index]);
    }
    , syncAll: function(type) {  /** 第一引数はsizeかdescか。sizeなら重量サイズ、descならdescription・材質商品説明を更新 */
      const self = this;
      const skuList = [];

      if (!this.state.currentSku.neSyohinSyohinCode) {
        return;
      }

      let currentSku = this.state.currentSku;

      var confirmCurrentSku = $('#modalInputCheckConfirm').find('.confirmCurrentSku');
      confirmCurrentSku.empty();
      var confirmMessage = $('#modalInputCheckConfirm').find('.confirmMsg');
      confirmMessage.empty();
      var modalPromise;

      // 重量サイズ更新時は入力チェック
      let validationMessage = "";
      if (type == 'size') {
        validationMessage = this.validateItem(currentSku);
      }
      if (validationMessage.length > 0) {
        // mm -> cm
        var confirmSkuMessage = currentSku.neSyohinSyohinCode
            + " ( "
            + currentSku.weight + "g"
            + " / " + currentSku.width + "cm"
            + " x " + currentSku.depth + "cm"
            + " x " + currentSku.height + "cm"
            + " ) ";

        validationMessage += "<br/>\nこのまま数値をコピーしてよろしいですか？"

        confirmCurrentSku.append(confirmSkuMessage);
        confirmMessage.append(validationMessage);
        $('#modalInputCheckConfirm').modal('show');
        modalPromise = modalConfirm();

        //OK処理
        modalPromise.done(function(){
          for (let i = 0; i < self.skuList.length; i++) {
            let item = self.skuList[i];
            if (type === 'size') {
              item.weight = currentSku.weight;
              item.depth = currentSku.depth;
              item.width = currentSku.width;
              item.height = currentSku.height;
            } else if (type === 'desc') {
              item.descriptionEn = currentSku.descriptionEn;
              item.descriptionCn = currentSku.descriptionCn;
              item.hintJa = currentSku.hintJa;
              item.hintCn = currentSku.hintCn;
            }
          }
        });
      }else{
        for (let i = 0; i < self.skuList.length; i++) {
          let item = self.skuList[i];
          if (type === 'size') {
            item.weight = currentSku.weight;
            item.depth = currentSku.depth;
            item.width = currentSku.width;
            item.height = currentSku.height;
          } else if (type === 'desc') {
            item.descriptionEn = currentSku.descriptionEn;
            item.descriptionCn = currentSku.descriptionCn;
            item.hintJa = currentSku.hintJa;
            item.hintCn = currentSku.hintCn;
          }
        }
      }
    }
    , sdf: function(infloat) {
      return (Math.floor(infloat*10)/10) ;
    }

    , copySameSizeList: function(type, colRow) { /** 第一引数はsizeかdescか。sizeなら重量サイズ、descならdescription・材質商品説明を更新 */
      const self = this;
      const skuList = [];

      if (!colRow || !type) {
        return;
      }
      if (!this.state.currentSku.neSyohinSyohinCode) {
        return;
      }

      let currentSku = this.state.currentSku;

      var confirmCurrentSku = $('#modalInputCheckConfirm').find('.confirmCurrentSku');
      confirmCurrentSku.empty();
      var confirmMessage = $('#modalInputCheckConfirm').find('.confirmMsg');
      confirmMessage.empty();
      var modalPromise;

      // 重量サイズ更新時は入力チェック
      let validationMessage = "";
      if (type == 'size') {
        validationMessage = this.validateItem(currentSku);
      }
      if (validationMessage.length > 0) {
        // mm -> cm
        var confirmSkuMessage = currentSku.neSyohinSyohinCode
          + " ( "
          + currentSku.weight + "g"
          + " / " + currentSku.width + "cm"
          + " x " + currentSku.depth + "cm"
          + " x " + currentSku.height + "cm"
          + " ) ";

        validationMessage += "<br/>\nこのまま数値をコピーしてよろしいですか？"

        confirmCurrentSku.append(confirmSkuMessage);
        confirmMessage.append(validationMessage);
        $('#modalInputCheckConfirm').modal('show');
        modalPromise = modalConfirm();

        //OK処理
        modalPromise.done(function(){
          let fieldName = colRow === 'row' ? 'rowname' : 'colname';
          for (let i = 0; i < self.skuList.length; i++) {
            let item = self.skuList[i];
            if (item[fieldName] === currentSku[fieldName]) {
              if (type === 'size') {
                item.weight = currentSku.weight;
                item.depth = currentSku.depth;
                item.width = currentSku.width;
                item.height = currentSku.height;
              } else if (type === 'desc') {
                item.descriptionEn = currentSku.descriptionEn;
                item.descriptionCn = currentSku.descriptionCn;
                item.hintJa = currentSku.hintJa;
                item.hintCn = currentSku.hintCn;
              }
            }
          }
        });
      }else{
        let fieldName = colRow === 'row' ? 'rowname' : 'colname';
        for (let i = 0; i < self.skuList.length; i++) {
          let item = self.skuList[i];
          if (item[fieldName] === currentSku[fieldName]) {
            if (type === 'size') {
              item.weight = currentSku.weight;
              item.depth = currentSku.depth;
              item.width = currentSku.width;
              item.height = currentSku.height;
            } else if (type === 'desc') {
              item.descriptionEn = currentSku.descriptionEn;
              item.descriptionCn = currentSku.descriptionCn;
              item.hintJa = currentSku.hintJa;
              item.hintCn = currentSku.hintCn;
            }
          }
        }
      }
    }

    , validateItem: function(item) {

      const messages = {
          sizeOver: "ネコポスサイズを超えています。折りたたみ方の変更は出来ない商品ですか？"
        , tooLight: "軽過ぎます。3辺や重量など間違えはありませんか？"
        , tooHeavy: "重過ぎます。3辺や重量など間違えはありませんか？"
        , weightOver: "ネコポス重量を超えています。入力間違いはありませんか？"
      };

      let returnMessage = "";

      if (item.isClickpostSizeOver()) {
        returnMessage += messages.sizeOver + "<br/>\n";
      }
      if (item.isClickpostWeightOver()) {
        returnMessage += messages.weightOver + "<br/>\n";
      }
      if (item.isTooLight()) {
        returnMessage += messages.tooLight + "<br/>\n";
      }
      if (item.isTooHeavy()) {
        returnMessage += messages.tooHeavy + "<br/>\n";
      }


      return returnMessage;
    }


    , focusNext: function(event) {
      const $targets = $(event.target).closest('.focusInputGroup').find('input[type=text],input[type=number]');
      let index = $targets.index(event.target);
      index += 1;
      if (index > $targets.size() - 1) {
        index = 0;
      }

      $targets.eq(index).focus();
    }

    , setListUnsetOnly: function(flag) {
      this.listUnsetOnly = flag;
    }

    , selectAll: function(event) {
      event.target.select();
    }
  }
});

