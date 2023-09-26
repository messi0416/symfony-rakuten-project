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
const vmComponentPickingScoreUserItem = {
    template: '#templatePickingScoreUserListTableRow'
  , props: [
      'row'
  ]
  , data: function() {
      return {
          username : this.row.username
        , firstColumnAverageTimeSC : this.formatPickingTime(this.row.firstColumnAverageTimeSC)
        , secondColumnAverageTimeSC : this.formatPickingTime(this.row.secondColumnAverageTimeSC)
        , thirdColumnAverageTimeSC : this.formatPickingTime(this.row.thirdColumnAverageTimeSC)
        , firstColumnAverageTimeV : this.formatPickingTime(this.row.firstColumnAverageTimeV)
        , secondColumnAverageTimeV : this.formatPickingTime(this.row.secondColumnAverageTimeV)
        , thirdColumnAverageTimeV : this.formatPickingTime(this.row.thirdColumnAverageTimeV)
        , firstColumnAverageTimeOthers : this.formatPickingTime(this.row.firstColumnAverageTimeOthers)
        , secondColumnAverageTimeOthers : this.formatPickingTime(this.row.secondColumnAverageTimeOthers)
        , thirdColumnAverageTimeOthers : this.formatPickingTime(this.row.thirdColumnAverageTimeOthers)
      };
  }
  , methods: {
    formatPickingTime : function (pickingTime) {
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
};


// 一覧画面 一覧表
const vmPickingScoreUserListTable = new Vue({
    el: '#pickingScoreUserListTable'
  , delimiters: ['(%', '%)']
  , data: {
      list: [] // データ
    , url: null
    , sortKey: ''
    , sortOrder: 0
    , ASC: 1
    , DESC: -1

    , firstColumnRecords  : ''
    , secondColumnRecords : ''
    , thirdColumnRecords  : ''

  }
  , components: {
      'result-item': vmComponentPickingScoreUserItem // 一覧テーブル
  }
  , mounted: function() {
    this.$nextTick(function () {
      this.url = $(this.$el).data('url');
      this.showPage();
    });
  }
  , computed: {
      displayList: function () {
        return this.list.sort((current, next) => {
          if (current[this.sortKey] > next[this.sortKey]) return 1 * this.sortOrder;
          if (current[this.sortKey] < next[this.sortKey]) return -1 * this.sortOrder;
          return 0;
        });
      }
      /**
       * ソートアイコンCSSクラス
       */
      , sortClass: function () {
        const fields = [
          'username',
          'firstColumnAverageTimeSC',
          'secondColumnAverageTimeSC',
          'thirdColumnAverageTimeSC',
          'firstColumnAverageTimeV',
          'secondColumnAverageTimeV',
          'thirdColumnAverageTimeV',
          'firstColumnAverageTimeOthers',
          'secondColumnAverageTimeOthers',
          'thirdColumnAverageTimeOthers',
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
          type : "GET"
        , url : self.url
        , dataType : "json"
      })
        .done(function(result) {
          if (result.status === 'ok') {

            // データ
            self.list = [];
            self.firstColumnRecords  = 'レコード' + result['firstColumnRecords'];
            self.secondColumnRecords = 'レコード' + result['secondColumnRecords'];
            self.thirdColumnRecords  = 'レコード' + result['thirdColumnRecords'];

            for (const username of Object.keys(result.list)) {
              const userLog = result.list[username];
              const row = self.toDisplayObject({ username, ...userLog });
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
    , toDisplayObject: function (userLogs) {
        return {
          username : userLogs.username,
          firstColumnAverageTimeSC : Number(userLogs['SC'].firstColumnAverageTime),
          secondColumnAverageTimeSC : Number(userLogs['SC'].secondColumnAverageTime),
          thirdColumnAverageTimeSC : Number(userLogs['SC'].thirdColumnAverageTime),
          firstColumnAverageTimeV : Number(userLogs['V'].firstColumnAverageTime),
          secondColumnAverageTimeV : Number(userLogs['V'].secondColumnAverageTime),
          thirdColumnAverageTimeV : Number(userLogs['V'].thirdColumnAverageTime),
          firstColumnAverageTimeOthers : Number(userLogs['OTHERS'].firstColumnAverageTime),
          secondColumnAverageTimeOthers : Number(userLogs['OTHERS'].secondColumnAverageTime),
          thirdColumnAverageTimeOthers : Number(userLogs['OTHERS'].thirdColumnAverageTime)
        }
    }

    /**
     * ソートアイコンCSSクラス
     */
    , getSortMarkCssClass: function (field) {
      return (field === this.sortKey)
        ? (this.sortOrder === this.ASC ? 'fa fa-sort-amount-asc' : 'fa fa-sort-amount-desc' )
        : 'hidden';
    }
    , switchSortOrder: function (fieldName) {
      if (this.sortKey === fieldName) {
        // 降順 -> 昇順
        if (this.sortOrder === this.DESC) {
          this.sortOrder = this.ASC;

          // 初期状態に戻る
        } else {
          this.sortKey = "";
          this.sortOrder = this.ASC;
        }
      } else {
        this.sortKey = fieldName;
        this.sortOrder = this.DESC; // 降順が先
      }
    }
  }
});
