// 全体メッセージ
const vmGlobalMessage = new Vue({
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

// 一覧画面 一覧表
const vmBoxedScoreUserLogListTable = new Vue({
  el: '#boxedScoreUserLogListTable'
  , data: {
      firstColumnRecords  : '0レコード'
    , secondColumnRecords : '0レコード'
    , thirdColumnRecords  : '0レコード'
    , firstColumnMyAverageBoxedRefillTime  : null
    , secondColumnMyAverageBoxedRefillTime : null
    , thirdColumnMyAverageBoxedRefillTime  : null
    , thirdColumnOverallAverageBoxedRefillTime : null
    , thirdColumnFastestAverageBoxedRefillTime : null
  }

  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.showPage();
    });
  }

  , methods: {
    showPage: function() {
      const self = this;

      // Show loading
      $.Vendor.WaitingDialog.show('loading ...');

      $.ajax({
        type: "GET"
        , url: self.url
        , dataType: "json"
      })
        .done(function(result) {
          if (result.status === 'ok') {
            self.firstColumnRecords  = 'レコード' + result['firstColumnRecords'];
            self.secondColumnRecords = 'レコード' + result['secondColumnRecords'];
            self.thirdColumnRecords  = 'レコード' + result['thirdColumnRecords'];
            self.firstColumnMyAverageBoxedRefillTime  = self.formatRefillTime(result["firstColumnMyAverageBoxedRefillTime"]);
            self.secondColumnMyAverageBoxedRefillTime = self.formatRefillTime(result["secondColumnMyAverageBoxedRefillTime"]);
            self.thirdColumnMyAverageBoxedRefillTime  = self.formatRefillTime(result["thirdColumnMyAverageBoxedRefillTime"]);
            self.thirdColumnOverallAverageBoxedRefillTime = self.formatRefillTime(result["thirdColumnOverallAverageBoxedRefillTime"]);
            self.thirdColumnFastestAverageBoxedRefillTime = self.formatRefillTime(result["thirdColumnFastestAverageBoxedRefillTime"]);
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
    , formatRefillTime : function (refillTime) {
      refillTime = parseInt(refillTime);
      if (refillTime === 0) {
        return '';
      }

      const oneMinutes = 60;
      const oneHours = oneMinutes * 60;

      const refillTimeHours   = parseInt(refillTime / oneHours);
      const refillTimeMinutes = parseInt((refillTime - refillTimeHours * oneHours) / oneMinutes);
      const refillTimeSeconds = refillTime - refillTimeHours * oneHours - refillTimeMinutes * oneMinutes;

      const strRefillTimeHours   = ("00" + refillTimeHours.toString()).slice(-2);
      const strRefillTimeMinutes = ("00" + refillTimeMinutes.toString()).slice(-2);
      const strRefillTimeSeconds = ("00" + refillTimeSeconds.toString()).slice(-2);
      return strRefillTimeHours + '時間' + strRefillTimeMinutes  + '分' + strRefillTimeSeconds + '秒';
    }
  }
});