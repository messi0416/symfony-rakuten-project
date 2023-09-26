// 全体メッセージ
const vmGlobalMessage = new Vue({
  el: '#globalMessage'
  , delimiters: ['(%', '%)']
  , data: {
    message         : ''
    , messageCssClass : ''
    , loadingImageUrl : null
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

// 一覧画面 一覧テーブル 行コンポーネント
const vmComponentBoxedScoreUserItem = {
  template: '#templateBoxedScoreUserListTableRow'
  , props: [
    'item'
  ]
  , data: function() {
    return {
        username : this.item.username
      , firstColumnMyAverageBoxedRefillTime  : this.formatRefillTime(this.item.firstColumnMyAverageBoxedRefillTime)
      , secondColumnMyAverageBoxedRefillTime : this.formatRefillTime(this.item.secondColumnMyAverageBoxedRefillTime)
      , thirdColumnMyAverageBoxedRefillTime  : this.formatRefillTime(this.item.thirdColumnMyAverageBoxedRefillTime)
    };
  }
  , methods: {
    formatRefillTime : function (refillTime) {
      refillTime = parseInt(refillTime);
      if (refillTime === 0) {
        return '';
      }

      const oneMinutes = 60;
      const oneHours   = oneMinutes * 60;

      const refillTimeHours   = parseInt(refillTime / oneHours);
      const refillTimeMinutes = parseInt((refillTime - refillTimeHours * oneHours) / oneMinutes);
      const refillTimeSeconds = refillTime - refillTimeHours * oneHours - refillTimeMinutes * oneMinutes;

      const strRefillTimeHours   = ("00" + refillTimeHours.toString()).slice(-2);
      const strRefillTimeMinutes = ("00" + refillTimeMinutes.toString()).slice(-2);
      const strRefillTimeSeconds = ("00" + refillTimeSeconds.toString()).slice(-2);
      return strRefillTimeHours + '時間' + strRefillTimeMinutes  + '分' + strRefillTimeSeconds + '秒';
    }
  }
};


// 一覧画面 一覧表
const vmBoxedScoreUserListTable = new Vue({
  el: '#boxedScoreUserListTable'
  , data: {
      list: [] // データ
    , url: null
    , sortKey: 'username'
    , sortOrder: 0

    , firstColumnRecords  : '0レコード'
    , secondColumnRecords : '0レコード'
    , thirdColumnRecords  : '0レコード'

  }
  , components: {
    'result-item': vmComponentBoxedScoreUserItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.showPage();
    });
  }
  , computed: {
    /**
     * ソートアイコンCSSクラス
     */
    sortClass: function () {
      const fields = [
        'username',
        'firstColumnMyAverageBoxedRefillTime',
        'secondColumnMyAverageBoxedRefillTime',
        'thirdColumnMyAverageBoxedRefillTime'
      ];

      let sortClass = {};
      for (let key of fields) {
        sortClass[key] = this.getSortMarkCssClass(key);
      }
      return sortClass;
    }
  }
  , methods: {
    showPage: function() {
      const self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('loading ...');

      $.ajax({
          type     : "GET"
        , url      : self.url
        , dataType : "json"
      })
        .done(function(result) {
          if (result.status === 'ok') {

            // データ
            self.list = [];
            self.firstColumnRecords  = 'レコード' + result['firstColumnRecords'];
            self.secondColumnRecords = 'レコード' + result['secondColumnRecords'];
            self.thirdColumnRecords  = 'レコード' + result['thirdColumnRecords'];

            for (const item of result.list) {
              const row = {
                  username : item.username
                , firstColumnMyAverageBoxedRefillTime  : item.firstColumnAverageTime
                , secondColumnMyAverageBoxedRefillTime : item.secondColumnAverageTime
                , thirdColumnMyAverageBoxedRefillTime  : item.thirdColumnAverageTime
              };

              self.list.push(row);
            }
          } else {
            const message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmGlobalMessage.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function(stat) {
          vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
        . always(function() {
          // Show loading
          $.Vendor.WaitingDialog.hide();
        });
    }
    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function (field) {
      let result;
      if (field === this.sortKey) {
        if (this.sortOrder === 1) {
          result = 'fa fa-sort-amount-asc';
        } else if (this.sortOrder === -1) {
          result = 'fa fa-sort-amount-desc';
        } else {
          result = ''
        }
      } else {
        result = '';
      }
      return result;
    }
    /**
     * ソート方法（昇順降順）変更
     */
    , sortBy: function (key) {
      const self = this;

      if (self.sortKey === key) {
        if (self.sortOrder === 0) {
          self.sortOrder = 1;
        } else if (self.sortOrder === 1) {
          self.sortOrder = -1;
        } else {
          self.sortOrder = 1;
          self.sortKey = 'username';
        }
      } else {
        self.sortKey = key;
        self.sortOrder = 1;
      }
      self.sort();
    }
    , sort: function  () {
      const self = this;
      self.list.sort((a, b) => {
        const  x = a[self.sortKey] == null ? "" : a[self.sortKey];
        const  y = b[self.sortKey] == null ? "" : b[self.sortKey];

        if(x === y) {
          return 0;
        }
        else if(x > y) {
          return 1 * self.sortOrder;
        }
        else {
          return -1 * self.sortOrder;
        }
      });
    }
  }

});