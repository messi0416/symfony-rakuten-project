/**
 * 汎用全体メッセージブロック コンポーネント
 *
 * Vue 2.x 以上
 */

Vue.component('parts-global-message', {
    template: '\
      <div v-show="state.message && state.message.length > 0">\
        <div class="alert" v-bind:class="state.css" v-text="state.message" style="white-space: pre"></div>\
      </div>\
    '
  , delimiters: ['(%', '%)']
  , props: [
    'state' // { message: null, css: null }
  ]
  //, data: function () {
  //}
  //, mounted: function() {
  //  this.$nextTick(function () {
  //  });
  //}
  //, methods: {
  //}
});

/**
 * メッセージ用 state 雛形
 * @constructor
 */
const PartsGlobalMessageState = function() {
  this.message = '';
  this.css = 'alert-info';
};
PartsGlobalMessageState.prototype.setMessage = function(message, css, autoHide) {
  css = css || 'alert-info';
  autoHide = (typeof autoHide === 'undefined') ? false : !!autoHide;

  this.message = message;
  this.css = css;

  if (autoHide) {
    const self = this;
    setTimeout(function(){ self.clear() }, 5000);
  }
};
PartsGlobalMessageState.prototype.clear = function() {
  this.message = '';
  this.css = 'alert-info';
};
