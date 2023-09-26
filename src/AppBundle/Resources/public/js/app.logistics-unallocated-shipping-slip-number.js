/**
 * 未引当　発送伝票番号リスト画面 JS
 */
$(function() {

  Vue.component('result-item', {
    template: "#result-item",
    props: [
      'row'
    ],
    data: function () {
      return {
        shippingSlipNumber: this.row.shippingSlipNumber,
        waitDate: this.row.waitDate,
        shipDate: this.row.shipDate,
        requesterName: this.row.requesterName,
        checkListComment: this.row.checkListComment,
        checkListGw: this.row.checkListGw,
        checkListMeas: this.row.checkListMeas,
        shippingOperationNumber: this.row.shippingOperationNumber,
        unallocatedFlg: this.row.unallocatedFlg,

        updateFlg : 0,

        rowClass : this.row.unallocatedFlg === '1' ? 'bg-danger' : ''
      }
    },
    methods: {
      includeInTheUpdate: function () {
        this.row.updateFlg = 1;
      }
    }
  });

  // 未引当リスト
  const vmUnallocatedTable = new Vue({
    el: '#unallocatedTable',
    data: {
      list: [],

      sortKey: 'shippingSlipNumber',  // ソート対象のカラム
      sortOrder: 0,  // 昇降順のフラグ

      // 絞込
      filterOrder: "non",
      updateCommentUrl: null,

      downloadCsvData: null
    },
    mounted: function () {
      const self = this;

      self.updateCommentUrl = $(self.$el).data('updateUnallocatedListUrl');
      
      for (let row of ITEMS) {
        const objectRow = self.convertJsonToObject(row);
        self.list.push(objectRow);
      }

      self.downloadCsvData = self.setDownloadCsvData()
      self.$watch('filterOrder', self.filter);
    },
    computed: {
      /**
       * ソートアイコンCSSクラス
       */
      sortClass: function () {
        // TODO switchで対応予定
        const fields = [
          'shippingSlipNumber',
          'waitDate',
          'shipDate',
          'requesterName'
        ];

        let sortClass = {};
        for (let key of fields) {
          sortClass[key] = this.getSortMarkCssClass(key);
        }
        return sortClass;
      }
    },
    methods: {
      /**
       * 配列を行オブジェジェクトに変換
       */
      convertJsonToObject: function (row) {
        return {
          id : row.id,
          shippingSlipNumber: row.shippingSlipNumber,
          waitDate: row.waitDate,
          shipDate: row.shipDate,
          requesterName: row.requesterName,
          checkListComment: row.checkListComment,
          checkListGw: row.checkListGw,
          checkListMeas: row.checkListMeas,
          shippingOperationNumber: row.shippingOperationNumber,
          unallocatedFlg: row.unallocatedFlg,
          updateFlg: 0
        }
      },

      filter: function () {
        const self = this;
        const isUnallocatedFlgOn = '1';

        switch(self.filterOrder) {
          case 'non':
            for(let i = 0;i < self.$children.length;i++) {
              const rowClass = self.$children[i].unallocatedFlg === isUnallocatedFlgOn ? 'bg-danger' : '';
              self.$children[i].rowClass = rowClass;
            }
            break;
          case 'unallocated':
            for(let i = 0;i < self.$children.length;i++) {
              const rowClass = self.$children[i].unallocatedFlg === isUnallocatedFlgOn ? 'bg-danger' : 'hidden';
              self.$children[i].rowClass = rowClass;
            }
            break;
        }
        self.setDownloadCsvData();
      },
      /**
       * ソートアイコンCSSクラス
       */
      getSortMarkCssClass: function (field) {
        let result;
        if (field === this.sortKey) {
          if (this.sortOrder === 1) {
            result = 'fa fa-sort-amount-asc';
          } else if (this.sortOrder === -1) {
            result = 'fa fa-sort-amount-desc';
          } else {
            result = 'hidden'
          }
        } else {
          result = 'hidden';
        }
        return result;
      },
      /**
       * ソート方法（昇順降順）変更
       */
      sortBy: function (key) {
        let self = this;

        if (self.sortKey === key) { 
          if (self.sortOrder === 0) {
            self.sortOrder = 1;
          } else if (self.sortOrder === 1) {
            self.sortOrder = -1;
          } else {
            // 発注伝票番号で昇順ソート
            self.sortOrder = 1;
            self.sortKey = 'shippingSlipNumber'; 
          }
        } else { 
          self.sortKey = key;
          self.sortOrder = 1; 
        }
        self.sort();
      },
      sort: function  () {
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
      },

      onSubmit: async function () {
        const self = this;

        $.Vendor.WaitingDialog.show('Wait a moment ...');

        // 連絡事項を変更したものだけに変更する
        let updateList = self.list.filter(row => row.updateFlg === 1);
        if(updateList.length === 0){
          alert('更新対象がありません。');

          // Hide loading
          $.Vendor.WaitingDialog.hide();
          return;
        }

        let status = true;
        let failShippingNumberList = [];
        for(const rowData of updateList) {
          rowData.checkListGw = rowData.checkListGw === null ? "" : rowData.checkListGw; // 入力をしていなければnull あった値を消したら空文字になるため
          if(rowData.checkListGw !== "") {
            const match = rowData.checkListGw.match(/^\d{1,10}\.?\d{0,2}$/);
            if(match === null || rowData.checkListGw <= 0.2) {
              failShippingNumberList.push(rowData.shippingSlipNumber);
              continue;
            }
          }
          rowData.checkListMeas = rowData.checkListMeas === null ? "" : rowData.checkListMeas; // 入力をしていなければnull あった値を消したら空文字になるため
          if(rowData.checkListMeas !== "") {
            const match = rowData.checkListMeas.match(/^\d{1,10}\.?\d{0,2}$/);
            if(match === null) {
              failShippingNumberList.push(rowData.shippingSlipNumber);
              continue;
            }
          }
          await $.ajax({
            type: "POST",
            url: self.updateCommentUrl,
            dataType: "json",
            data: { 
              checklistGw: rowData.checkListGw,
              checklistMeas : rowData.checkListMeas,
              checklistComment : rowData.checkListComment,
              shippingNumber : rowData.shippingSlipNumber,
              shippingOperationNumber : rowData.shippingOperationNumber
            }
          })
          .done(function (result) {
            if(result.status == "ng") {
              failShippingNumberList.push(rowData.shippingSlipNumber);
            }
          })
        }

        // Hide loading
        $.Vendor.WaitingDialog.hide();
        let message = "";
        if(failShippingNumberList.length > 0) {
          message = "以下の発送伝票番号の更新に失敗しました。\nMEASとG.Wは数値で、またG.Wについては0.2より大きい値を入力してください。\n";
          for(shippingNumber of failShippingNumberList) {
            message += shippingNumber + "\n";
          }
        }
        else {
          message = "全ての更新が終了しました";
        }
        alert(message);
        location.reload(true);
      },
      setDownloadCsvData: function() {
        const self = this;
        let targets = self.list;
        if (self.filterOrder == 'unallocated') {
          targets = targets.filter(row => row.unallocatedFlg === '1');
        }
        const sendParams = targets.map(function (row) {
          return row.shippingSlipNumber;
        });
  
        return self.downloadCsvData = sendParams;
      },
    }
  });
});
