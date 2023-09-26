/**
 * 管理画面 出荷実績情報 JS
 */

const monthArray = {
  3:"3ヶ月以内",
  6:"6ヶ月以内",
  12:"1年以内",
  24:"2年以内",
  36:"3年以内",
  48:"4年以内",
  60:"5年以内",
  72:"6年以内",
  84:"7年以内",
  96:"8年以内",
};

// 一覧画面 一覧テーブル 行コンポーネント
var vmComponentLogisticsWarehouseResultHistoryListItem = {
  template: '#templateLogisticsWarehouseResultHistoryListTableRow'
  , props: [
    'item'
  ]
  , methods: {
    inputOperationTimeSum: function(warehouseKey, $event) {
      this.item[warehouseKey].operationTimeSum = $event.target.value;
      this.item[warehouseKey].modified = true;
      this.item[warehouseKey].cssClass = 'operationTimeSumChanged';
    }
  }
};

// 一覧画面 一覧表
const vmResultHistoryList = new Vue({
    el: '#warehouseResultHistoryList'
  , data: {
      list: [] // データ
    , warehouses: WAREHOUSES

    , listUrl: null
    , updateUrl: null

    , filterDateStart: null
    , filterDateEnd: null
    , coefficientShoplist: COEFFICIENT_SHOPLIST
    , coefficientRslSagawaYamato: COEFFICIENT_RSL_SAGAWA_YAMATO

    , filterOptionMonth: ''
    , filterMonthList: {}
    , filterOptionMonths: []

    , messageState: {}
  }
  , components: {
    'result-item': vmComponentLogisticsWarehouseResultHistoryListItem // 一覧テーブル
  }
  , mounted: function() {
    var self = this;

    this.$nextTick(function () {
      this.listUrl = $(this.$el).data('listUrl');
      this.updateUrl = $(this.$el).data('updateUrl');

      // メッセージオブジェクト
      this.messageState = new PartsGlobalMessageState();

      for(var monthKey in monthArray){
        var dt = new Date();
        var lastMonth = dt.setMonth(dt.getMonth() - monthKey);
        var m = moment(lastMonth);
        var key = m.format('YYYY/MM');
        if (!self.filterMonthList[key]) {
          self.filterMonthList[key] = {
              key: key
            , display: monthArray[monthKey]
            , dateStart: m.format('YYYY-MM-01')
            , dateEnd: m.endOf('month').format('YYYY-MM-DD')
          };
        }
      }

      var keys = Object.keys(self.filterMonthList);

      for(var k in keys) {
        self.filterOptionMonths.push(self.filterMonthList[keys[k]]);
      }

      $('#filterDateStartLocation', this.$el).datepicker({
        format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateStart = $(this).val();
        }
        , clearDate: function() {
          self.filterDateStart = null;
        }
      });

      $('#filterDateEndLocation', this.$el).datepicker({
        format: 'yyyy-mm-dd'
        , autoclose: true
      }).on({
        changeDate: function() {
          self.filterDateEnd = $(this).val();
        }
        , clearDate: function() {
          self.filterDateEnd = null;
        }
      });

      // フォームの初期表示もデータに合わせて「3ヶ月以内」にする
      var date = new Date();
      date.setMonth(date.getMonth() - 3);
      this.filterOptionMonth = moment(date).format('YYYY/MM')
      this.filterDateStart = moment(date).format('YYYY-MM-01')

      this.loadListData();
    });
  }
  , computed: {
    pageData: function () {
      var self = this;
      return self.list;
    }
  }
  , methods: {
    loadListData: function () {
      var self = this;

      var data = {
        filterDateStart: self.filterDateStart
        , filterDateEnd: self.filterDateEnd
        , coefficientShoplist: self.coefficientShoplist
        , coefficientRslSagawaYamato: self.coefficientRslSagawaYamato
      }

      $.ajax({
        type: "GET"
        , url: self.listUrl
        , dataType: "json"
        , data: data
      })
        .done(function (result) {

          if (result.status == 'ok') {
            var listArray = Object.values(result.list);

            // 一度dataに登録されているlistを初期化
            self.list = [];

            for (i = 0; i < listArray.length; i++) {
              var item = listArray[i];
              item.warehouseKeyList = [];
              for (j = 0; j < self.warehouses.length; j++) {
                var warehouse = self.warehouses[j];
                var warehouseId = warehouse.id;
                var warehouseKey = 'warehouse' + warehouseId;
                item.warehouseKeyList.push(warehouseKey);
                if (! item[warehouseKey]) {
                  item[warehouseKey] = {};
                  item[warehouseKey].targetDate = item.targetDate;
                  item[warehouseKey].warehouseId = warehouseId;
                  item[warehouseKey].pickingSum = 0;
                  item[warehouseKey].warehousePickingSum = 0;
                  item[warehouseKey].shippingSum = 0;
                  item[warehouseKey].shippingSumShoplist = 0;
                  item[warehouseKey].shippingSumRsl = 0;
                  item[warehouseKey].shippingAdjustment = '0.00';
                  item[warehouseKey].operationTimeSum = null;
                } else {
                  var shippingAdjustment =
                      Number(item[warehouseKey].shippingSumShoplist) * Number(self.coefficientShoplist)
                      + Number(item[warehouseKey].shippingSumRsl) * Number(self.coefficientRslSagawaYamato)
                      + Number(item[warehouseKey].shippingSumSagawa) * Number(self.coefficientRslSagawaYamato)
                      + Number(item[warehouseKey].shippingSumYamato) * Number(self.coefficientRslSagawaYamato)
                      + Number(item[warehouseKey].shippingSum)
                      - Number(item[warehouseKey].shippingSumShoplist)
                      - Number(item[warehouseKey].shippingSumRsl)
                      - Number(item[warehouseKey].shippingSumSagawa)
                      - Number(item[warehouseKey].shippingSumYamato);
                  item[warehouseKey].shippingAdjustment = shippingAdjustment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                  item[warehouseKey].operationTimeSum = item[warehouseKey].operationTimeSum / 60;
                }
                item[warehouseKey].modified = false;
                item[warehouseKey].cssClass = '';
              }
              self.list.push(item);
            }

            self.messageState.setMessage();

            self.$nextTick(function () {
              FixedMidashi.create(); // ヘッダ固定テーブル 再計算
            });

          } else {
            var message = result.message.length > 0 ? result.message : 'データを取得できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function () {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
    }
    , updateData: function () {
      var modifiedList = [];
      var list = Object.values(this.list);
      var self = this;
      for (i = 0; i < list.length; i++) {
        var item = list[i];
        for (j = 0; j < self.warehouses.length; j++) {
          var warehouse = self.warehouses[j];
          var warehouseKey = 'warehouse' + warehouse.id;
          var targetDate = item.targetDate;
          var operationTimeSum = item[warehouseKey].operationTimeSum;
          const operationTimeSumNum = Number(operationTimeSum);
          if (Number.isNaN(operationTimeSumNum)) {
            self.messageState.setMessage(`稼働時間合計は数値を入力してください。 ${targetDate} ・ ${warehouse.name} ・ 「${operationTimeSum}」`, 'alert alert-danger');
            return;
          }
          if (! Number.isInteger(operationTimeSumNum)) {
            const array = String(operationTimeSum).split('.');
            const length = array[1].length;
            if (length > 1) {
              self.messageState.setMessage(`稼働時間合計は小数第一位までで指定してください。 ${targetDate} ・ ${warehouse.name} ・ 「${operationTimeSum}」`, 'alert alert-danger');
              return;
            }
          }
          if (item[warehouseKey].modified === true) {
            var data = {
              targetDate: targetDate
              , warehouseId: item[warehouseKey].warehouseId
              , operationTimeSum: operationTimeSumNum * 60
            };
            modifiedList.push(data);
          }
        }
      }

      if(!modifiedList.length) {
        self.messageState.setMessage('変更データがありません。', 'alert alert-danger');
        return;
      }

      var data = {
        modifiedList: modifiedList
      }

      $.ajax({
        type: "GET"
        , url: self.updateUrl
        , dataType: "json"
        , data: data
      })
        .done(function (result) {

          if (result.status == 'ok') {
            self.loadListData();
          } else {
            var message = result.message.length > 0 ? result.message : 'データを取得できませんでした。';
            self.messageState.setMessage(message, 'alert alert-danger');
          }
        })
        .fail(function () {
          self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');

        })
    }
    , setFilterDateMonth: function() {
      var self = this;

      var month = this.filterMonthList[this.filterOptionMonth];
      if (month) {
        self.filterDateStart = month.dateStart;
        self.filterDateEnd = '';
      }
    }
  }
});
