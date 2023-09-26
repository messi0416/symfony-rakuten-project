/**
 * 伝票毎注残管理 JS
 */

$(function() {
  // ページ送り
  Vue.component('page-navigation', {
    template: '#page-navigation',
    props: [
      'row_count',
      'start_row_count',
      'end_row_count'
    ],
    data: function() {
      return {
        voucherPerPage: 20, // 1ページ20伝票
        displayingPage: 1,

        firstIndex: 1,
        lastIndex: 0,
        pageNavigationLength: 20, // 最大20個表示する
        displayingIndexes: []
      }
    },
    ready: function () {
      this.lastIndex = this.calculateLastIndex();
      this.buildDisplayingIndexes();
    },
    watch: {
      voucherPerPage: function () {
        // 一ページ当たりの表示行数が変わるので、それに伴う最終ページの算出
        this.lastIndex = this.calculateLastIndex();
        this.buildDisplayingIndexes();
        this.toggleStatus();
      },
      displayingPage: function () {
        this.buildDisplayingIndexes();
        this.toggleStatus();
      }
    },
    computed: {
      nextButtonClass: function () {
        return this.displayingPage === this.lastIndex ? 'disabled' : '';
      },
      previousButtonClass: function () {
        return this.displayingPage === this.firstIndex ? 'disabled' : '';
      }
    },
    methods: {
      showPrevious: function () {
        if (this.displayingPage === this.firstIndex) {
          return;
        }
        this.displayingPage -= 1;
      },
      showNext: function () {
        if (this.displayingPage === this.lastIndex) {
          return;
        }
        this.displayingPage += 1;
      },
      showIndexPage: function (index) {
        if(0 < index) {
          this.displayingPage = index;
        }
      },
      showFirstPage: function () {
        this.displayingPage = 1;
      },
      calculateLastIndex: function () {
        return Math.ceil(this.row_count / this.voucherPerPage);
      },
      toggleStatus: function() {
        for (const indexData of this.displayingIndexes) {
          // 表示しているページと同じindexのみ有効化
          indexData.active = this.displayingPage === indexData['index'];
        }
      },
      buildDisplayingIndexes: function () {
        this.displayingIndexes = [];

        // ページナビ自体の開始インデックス
        const displayingFirstIndex = this.calculateDisplayingFirstIndex();
        // ページナビ自体の終了インデックス
        const displayingLastIndex = this.pageNavigationLength + displayingFirstIndex - 1;
        // 最大 pageNavigationLength 件 or 最終ページまでのインデックスを生成
        for (let displayingIndex = displayingFirstIndex; displayingIndex <= Math.min(this.lastIndex, displayingLastIndex); displayingIndex++) {
          this.displayingIndexes.push({
            index: displayingIndex,
            active: displayingIndex === displayingFirstIndex
          });
        }
      },
      calculateDisplayingFirstIndex: function () {
        if (this.displayingPage === this.firstIndex) {
          return this.firstIndex;
        } else if (this.displayingPage === this.lastIndex) {
          return Math.max(this.lastIndex - this.pageNavigationLength + 1, this.firstIndex);
        } else {
          return Math.max(this.displayingPage - Math.floor(this.pageNavigationLength / 2), this.firstIndex);
        }
      }
    }
  });

  // ページ送り インデックス
  Vue.component('page-navigation-index', {
    template: '#page-navigation-index',
    props: [
      'index',
      'active'
    ],
    computed: {
      activeClass: function () {
        return this.active ? 'page-item active' : 'page-item'
      }
    }
  });

  // 注残テーブル
  Vue.component('remain-order-table', {
    template: '#remain-order-table',
    props: [
      'list',
      'remain_status_list',
      'shipping_types'
    ],
    data: function () {
      return {
        sortField: '',
        sortOrder: 0,

        ASC: 1,
        DESC: -1
      };
    },
    computed: {
      sortMarks: function () {
        const fields = [
          'voucherNumber',
          'agentCodes',
          'orderDate',
          'oldestUpdateDate',
          'totalRemainNum'
        ];
        let result = {};
        for (let key of fields) {
          result[key] = this.getSortMarkCssClass(key);
        }
        return result;
      }
    },
    methods: {
      getSortMarkCssClass: function (field) {
        return (field === this.sortField)
          ? (this.sortOrder === this.ASC ? 'fa fa-sort-amount-asc' : 'fa fa-sort-amount-desc' )
          : 'hidden';
      },
      switchSortOrder: function (fieldName) {
        if (this.sortField === fieldName) {
          // 降順 -> 昇順
          if (this.sortOrder === this.DESC) {
            this.sortOrder = this.ASC;

            // 初期状態に戻る
          } else {
            this.sortField = "";
            this.sortOrder = this.ASC;
          }

        } else {
          this.sortField = fieldName;
          this.sortOrder = this.DESC; // 降順が先
        }
      }
    }
  });

  // 注残テーブル 行
  Vue.component('remain-order-table-row', {
    template: '#remain-order-table-row',
    props: [
      'row',
      'remain_status_list',
      'shipping_types'
    ],
    data: function () {
      return {
        index            : this.row.index,
        voucherNumber    : this.row.voucherNumber,
        shippingType     : this.shipping_types[this.row.shippingType],
        agentCodes       : this.row.agentCodes,
        agents           : this.row.agents,
        agentCount       : Object.keys(this.row.agents).length,
        orderDate        : this.row.orderDate,
        minRemainStatus  : this.remain_status_list[this.row.minRemainStatus],
        oldestUpdateDate : this.row.oldestUpdateDate,
        productCode      : this.row.productCode,
        imageUrl         : this.row.imageUrl,
        totalRemainNum   : this.row.totalRemainNum
      }
    }
  });

  // 注残テーブル 行 コメント
  Vue.component('remain-order-table-comment', {
    template: '#remain-order-table-comment',
    props: [
      'agent_name',
      'agent_comment',
      'agent_count',
      'key',
      'voucher_number',
      'index'
    ],
    data: function () {
      return {
        // null対策
        comment: this.agent_comment === null ? "" : this.agent_comment,
        updateCommentUrl: '',
        originComment: '',
        // 依頼先が一件だけならば2行で表示する
        textareaRows: this.agent_count === 1 ? 2 : 1
      }
    },
    methods: {
      setOriginComment () {
        this.originComment = this.comment;
      },
      updateComment () {
        this.updateCommentUrl = $(this.$el).data('updateCommentUrl');
        // 変更がなければ更新しない
        if (this.comment === this.originComment) {
          return;
        }

        const updateData = {
          voucherNumber: this.voucher_number,
          agentCode: this.key,
          comment: this.comment
        };
        $.ajax({
          type: 'POST',
          url: this.updateCommentUrl,
          dataType: 'json',
          data: updateData
        })
          .done((result) => {
            if (result.status === 'ok') {
              // コメント欄にフォーカスした状態でページを変えると$emitで動作しないため直接実行
              vmRemainOrder.updateComment(this.comment, this.key, this.index);
            } else {
              alert(result.message);
            }
          })
          .fail((error) => {
            alert(error.message);
          })
      }
    }
  });

  // 親
  const vmRemainOrder = new Vue({
    el: '#remain-order',
    data: {
      remainOrderList: [],

      remainStatusList: {},
      shippingTypes: {},
      parameter: {},
      voucherPerPage: 0,

      rowCount: REMAIN_ORDERS_JSON.length,
      startRowCount: 0,
      endRowCount: 0
    },
    ready: function () {
      this.remainStatusList = REMAIN_STATUS_LIST;
      this.shippingTypes = SHIPPING_TYPES;

      this.initParameter();

      REMAIN_ORDERS_JSON.forEach((remainOrder, index) => {
        const row = this.toDisplayObject(remainOrder, index);
        this.remainOrderList.push(row);
      });
    },
    computed: {
      /**
       * テーブル表示用データ
       * ページ・表示行数の変更とテーブルソートで動作
       */
      visibleRemainOrderList: function () {
        const copyList = this.remainOrderList.slice();
        const sortField = this.$refs['remain_order_table'].sortField;
        if (sortField) {
          this.sortList(copyList);
        }

        this.rebuildParam();
        return this.extractingPageList(copyList);
      }
    },
    methods: {
      toDisplayObject: function (remainOrder, index) {
        return {
          // コメント欄からremainOrderListを更新するために利用
          index,
          voucherNumber    : Number(remainOrder.voucherNumber),
          shippingType     : remainOrder.shippingType || "",
          agentCodes       : this.getAgentNames(remainOrder.agents).join('\n'),
          orderDate        : remainOrder.orderDate || "",
          minRemainStatus  : remainOrder.minRemainStatus || "",
          oldestUpdateDate : remainOrder.oldestUpdateDate || "",
          productCode      : remainOrder.productCode || "",
          imageUrl         : remainOrder.imageUrl || "",
          totalRemainNum   : Number(remainOrder.totalRemainNum),
          agents           : remainOrder.agents
        }
      },
      initParameter: function () {
        this.parameter = new $.Plusnao.SearchParameter;
        this.parameter.addParam('displayingPage', 'integer', 'd', this.$refs['page_navigation'].displayingPage);
        this.parameter.addParam('voucherPerPage', 'integer', 'v', this.$refs['page_navigation'].voucherPerPage);
        this.parameter.addParam('sortField', 'string', 'f', this.$refs['remain_order_table'].sortField);
        this.parameter.addParam('sortOrder', 'integer', 'o', this.$refs['remain_order_table'].sortOrder);
        this.setParams();
      },
      /**
       * パラメータの値をそれぞれ値に
       */
      setParams: function () {
        // URLからパラメータ取得
        const qp = $.Plusnao.QueryString.parse();
        if (Object.keys(qp).length > 0) {
          this.parameter.setValuesWithAlias(qp);
        }

        // 該当するパラメータがあればそれぞれの初期値としてセット
        const params = this.parameter.getParams();
        if (params.hasOwnProperty('displayingPage')) {
          this.$refs['page_navigation'].displayingPage = params.displayingPage;
        }
        if (params.hasOwnProperty('voucherPerPage')) {
          this.$refs['page_navigation'].voucherPerPage = params.voucherPerPage;
        }
        if (params.hasOwnProperty('sortField')) {
          this.$refs['remain_order_table'].sortField = params.sortField;
        }
        if (params.hasOwnProperty('sortOrder')) {
          this.$refs['remain_order_table'].sortOrder = params.sortOrder;
        }
      },
      /**
       * それぞれの値をパラメータにセットし、URLを書き換える
       */
      rebuildParam: function () {
        this.parameter.setValue('displayingPage', this.$refs['page_navigation'].displayingPage);
        this.parameter.setValue('voucherPerPage', this.$refs['page_navigation'].voucherPerPage);
        this.parameter.setValue('sortField', this.$refs['remain_order_table'].sortField);
        this.parameter.setValue('sortOrder', this.$refs['remain_order_table'].sortOrder);

        // URLの書き換え
        history.replaceState(null, document.title, this.getReplacingURL());
      },
      getReplacingURL: function () {
        const queryString = $.Plusnao.QueryString.stringify(this.parameter.getParamsWithAlias());
        // replaceStateはURLの最後の部分が置換されるので、その部分に追加する形で置換する
        const endOfURL = location.pathname.split('/').pop();
        return `${endOfURL}?${queryString}`;
      },
      sortList: function (remainOrderList) {
        const sortField = this.$refs['remain_order_table'].sortField;
        const sortOrder = this.$refs['remain_order_table'].sortOrder;
        remainOrderList.sort(function (current, next) {
          if (current[sortField] > next[sortField]) return 1 * sortOrder;
          if (current[sortField] < next[sortField]) return -1 * sortOrder;
          return 0;
        });
      },
      getAgentNames: function (agents) {
        const result = [];
        for (const agentCode in agents) {
          result.push(agents[agentCode].loginName);
        }
        return result;
      },
      extractingPageList: function (remainOrderList) {
        const voucherPerPage = parseInt(this.$refs['page_navigation'].voucherPerPage);
        const displayingPage = parseInt(this.$refs['page_navigation'].displayingPage);

        const startRowCount = (displayingPage - 1) * voucherPerPage;
        const endRowCount = Math.min(displayingPage * voucherPerPage, this.rowCount);
        const showingRemainList = remainOrderList.slice(startRowCount, endRowCount);

        // sliceの第一引数は開始位置の添え字なので表示用の+1する
        this.startRowCount = startRowCount + 1;
        this.endRowCount = endRowCount;

        return showingRemainList;
      },
      updateComment: function (comment, agentCode, index) {
        this.remainOrderList[index].agents[agentCode].comment = comment;
      }
    }
  });
});
