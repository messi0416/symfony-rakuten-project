/**
 * 梱包グループ JS
 */

/** メイン画面 */
const packingGroup = new Vue({
  el: '#packingGroup',
  data: {
    item: {
      packing_group: null,
      shippingVoucherList: []
    },
  },
  ready: function () {
    this.item = ITEM;
  },
  computed: {
  },
  methods: {
    // ステータスによって背景色のclassを変える
    decideClass: function(status) {
      let result = "";
      switch (status) {
      case 0: // ピッキング待ち
        break;
      case 1: // 梱包未処理

        break;
      case 2: // 梱包中
        result = "list-group-item-info";
        break;
      case 3: // 完了
        result = "list-group-item-success";
        break;
      default:
        break;
      }
      return result;
    },
  },
});