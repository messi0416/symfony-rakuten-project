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
const vmPickingScoreUserLogListTable = new Vue({
  el: '#pickingScoreUserLogListTable'
  , delimiters: ['(%', '%)']
  , data: {
      firstColumnRecords  : ''
    , secondColumnRecords : ''
    , thirdColumnRecords  : ''

    , firstColumnMyAveragePickingTimeSC  : ''
    , secondColumnMyAveragePickingTimeSC : ''
    , thirdColumnMyAveragePickingTimeSC  : ''

    , firstColumnMyAveragePickingTimeV  : ''
    , secondColumnMyAveragePickingTimeV : ''
    , thirdColumnMyAveragePickingTimeV  : ''

    , firstColumnMyAveragePickingTimeOTHERS  : ''
    , secondColumnMyAveragePickingTimeOTHERS : ''
    , thirdColumnMyAveragePickingTimeOTHERS  : ''

    , overallAveragePickingTimeSC : ''
    , overallAveragePickingTimeV : ''
    , overallAveragePickingTimeOTHERS : ''

    , fastestAveragePickingTimeSC : ''
    , fastestAveragePickingTimeV : ''
    , fastestAveragePickingTimeOTHERS : ''
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
              // Myピッキングスコア
              self.firstColumnRecords  = 'レコード' + result['firstColumnRecords'];
              self.firstColumnMyAveragePickingTimeSC = self.formatPickingTime(result.pickingScore['SC'].firstColumnAverageTime);
              self.firstColumnMyAveragePickingTimeV = self.formatPickingTime(result.pickingScore['V'].firstColumnAverageTime);
              self.firstColumnMyAveragePickingTimeOTHERS = self.formatPickingTime(result.pickingScore['OTHERS'].firstColumnAverageTime);

              self.secondColumnRecords = 'レコード' + result['secondColumnRecords'];
              self.secondColumnMyAveragePickingTimeSC = self.formatPickingTime(result.pickingScore['SC'].secondColumnAverageTime);
              self.secondColumnMyAveragePickingTimeV = self.formatPickingTime(result.pickingScore['V'].secondColumnAverageTime);
              self.secondColumnMyAveragePickingTimeOTHERS = self.formatPickingTime(result.pickingScore['OTHERS'].secondColumnAverageTime);

              self.thirdColumnRecords  = 'レコード' + result['thirdColumnRecords'];
              self.thirdColumnMyAveragePickingTimeSC = self.formatPickingTime(result.pickingScore['SC'].thirdColumnAverageTime);
              self.thirdColumnMyAveragePickingTimeV = self.formatPickingTime(result.pickingScore['V'].thirdColumnAverageTime);
              self.thirdColumnMyAveragePickingTimeOTHERS = self.formatPickingTime(result.pickingScore['OTHERS'].thirdColumnAverageTime);

              // 全体平均
              self.overallAveragePickingTimeSC = self.formatPickingTime(result.averageTime['SC']);
              self.overallAveragePickingTimeV = self.formatPickingTime(result.averageTime['V']);
              self.overallAveragePickingTimeOTHERS = self.formatPickingTime(result.averageTime['OTHERS']);

              // トップレコード
              self.fastestAveragePickingTimeSC = self.formatPickingTime(result.fastestTime['SC']);
              self.fastestAveragePickingTimeV = self.formatPickingTime(result.fastestTime['V']);
              self.fastestAveragePickingTimeOTHERS = self.formatPickingTime(result.fastestTime['OTHERS']);
            } else {
              const message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              vmGlobalMessage.setMessage(message, 'alert alert-danger');
            }
          })
          .fail(function(stat) {
            vmGlobalMessage.setMessage('エラーが発生しました。', 'alert alert-danger');
          })
          .always(function() {
            // Show loading
            $.Vendor.WaitingDialog.hide();

          });
    }
    , formatPickingTime : function (pickingTime) {
      const numberPickingTime = parseInt(pickingTime);
      if (numberPickingTime <= 0) {
        return '';
      }

      const oneMinutes = 60;
      const pickingTimeMinutes = parseInt(numberPickingTime / oneMinutes);
      const pickingTimeSeconds = parseInt(numberPickingTime % oneMinutes);

      const strPickingTimeMinutes = ("00" + pickingTimeMinutes.toString()).slice(-2);
      const strPickingTimeSeconds = ("00" + pickingTimeSeconds.toString()).slice(-2);
      return strPickingTimeMinutes + '分' + strPickingTimeSeconds + '秒';
    }
  }
});
