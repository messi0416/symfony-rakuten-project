/**
 * エンターキーでのsubmit無効化
 */
$(document).on("keypress", "input[type=text]:not(.allowSubmit)", function(event) {
  return event.which !== 13;
});

$(function() {
  // Vue.js 設定： デリミタ変更 ※ twigとの衝突を避けるため (v2では、コンポーネントレベルのオプションになったためこれは無視される)
  Vue.config.delimiters = ['(%', '%)'];
});

const SCROLL_ELEMENT = (function () {
  if ('scrollingElement' in document) {
    return document.scrollingElement;
  }
  if (navigator.userAgent.indexOf('WebKit') != -1) {
    return document.body;
  }
  return document.documentElement;
})();


/**
 * ユーティリティ および グローバルオブジェクト
 */
(function($) {
  $.extend({
    //
    Plusnao: {

      /// 定数もどき
      Const: {
        StorageKey: {
            LOCATION_STORE_IMPORT_PRODUCT_LOCATION_HISTORY: 'location.store_import_product.location_history'
          , LOCATION_STORE_IMPORT_PRODUCT_MOVE_FROM: 'location.store_import_product.move_from'
          , LOCATION_STORE_IMPORT_PRODUCT_MOVE_TO: 'location.store_import_product.move_to'
          , LOCATION_STORE_IMPORT_PRODUCT_LAST_SUBMITTED: 'location.store_import_product.last_submitted'
          , GOODS_ENGLISH_DATA_SEARCH_PARAMS: 'goods.english_data.search_params'
        }
      },

      /// 日付関連
      Date: {
        /**
         * 1日前の Date を取得
         * @param now
         * @return Date
         */
        getYesterday: function(now) {
          return this.getAddDate(now, -1);
        },

        /**
         * X日前の Date を取得
         * @param add
         * @param now
         * @return Date
         */
        getAddDate: function(now, add) {
          now = now || new Date();
          return new Date(now.getFullYear(), now.getMonth(), now.getDate() + parseInt(add));
        },

        /**
         * addの値を日付の月に加算したDateを取得
         * @param add
         * @param now
         * @return Date
         */
        getAddMonth: function (now, add) {
          now = now || new Date();
          return new Date(now.getFullYear(), now.getMonth() + parseInt(add), now.getDate());
        },
        /**
         * 日付文字列取得
         * @param date
         * @param withTime
         * @param removeZero
         * @return String
         */
        getDateString: function(date, withTime, removeZero) {
          var year = date.getFullYear().toString();
          var month = date.getMonth() + 1;

          month = (month < 10 && !removeZero) ? month = '0' + month.toString() : month.toString();

          var day = date.getDate();
          day = (day < 10 && !removeZero) ? day = '0' + day.toString() : day.toString();

          if (withTime) {
            var hour = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();

            hour = (hour < 10 && !removeZero) ? hour = '0' + hour.toString() : hour.toString();
            minutes = (minutes < 10 && !removeZero) ? minutes = '0' + minutes.toString() : minutes.toString();
            seconds = (seconds < 10 && !removeZero) ? seconds = '0' + seconds.toString() : seconds.toString();

            return [year, month, day].join( '-' )
                 + ' '
                 + [hour, minutes, seconds].join(':');
          } else {
            return [year, month, day].join( '-' );
          }
        },
        /**
         * 日時文字列取得
         * @param date
         * @param removeZero
         * @return String
         */
        getDateTimeString: function(date, removeZero) {
          return this.getDateString(date, true, removeZero);
        }
      },

      /// 計算関連
      Math: {
        round: function(val, precision) {
          var digit = Math.pow(10, precision);
          return Math.round(val * digit) / digit;
        }
      },

      Binary: {
        toBlob(base64, type) {
          var bin = atob(base64.replace(/^.*,/, ''));
          var buffer = new Uint8Array(bin.length);
          for (var i = 0; i < bin.length; i++) {
            buffer[i] = bin.charCodeAt(i);
          }
          // Blobを作成
          try{
            var blob = new Blob([buffer.buffer], {
              type: type // 'text/csv'
            });
          }catch (e){
            return false;
          }
          return blob;
        }
      },

      /// 文字列関連
      String: {
        numberFormat: function (str, decimals) {
          var str_arr = str.toString().split(".");
          var num = str_arr[0].replace(/,/g, "");
          while(num != (num = num.replace(/^(-?\d+)(\d{3})/, "$1,$2"))) {
            // do nothing
          }
          // 小数点は切り捨て
          if (decimals > 0 && str_arr[1]) {
            var add = str_arr[1];
            if (add.length > decimals) {
              add = add.substr(0, decimals);
            }
            num = num + "." + add;
          }
          return num;
        },

        zeroPadding: function(num, len)
        {
          return ( (new Array(len)).join('0') + num ).slice( -len );
        },

        /// from jkl-dumper.js
        dump: function ( data, offset )
        {
          if ( typeof(offset) == "undefined" ) offset = "";
          var nextoff = offset + "  ";
          switch ( typeof(data) ) {
            case "string":
              return '"'+this.escapeBackSlash(data)+'"';
              break;
            case "number":
              return data;
              break;
            case "boolean":
              return data ? "true" : "false";
              break;
            case "undefined":
              return "null";
              break;
            case "object":
              var array;
              if ( data == null ) {
                return "null";
              } else if ( data.constructor == Array ) {
                array = [];
                for ( var i=0; i<data.length; i++ ) {
                  array[i] = this.dump( data[i], nextoff );
                }
                return "[\n"+nextoff+array.join( ",\n"+nextoff )+"\n"+offset+"]";
              } else {
                array = [];
                for ( var key in data ) {
                  var val = this.dump( data[key], nextoff );
                  key = '"' + this.escapeBackSlash( key ) + '"';
                  array[array.length] = key+": "+val;
                }
                if ( array.length == 1 && ! array[0].match( /[\n\{\[]/ ) ) {
                  return "{ "+array[0]+" }";
                }
                return "{\n"+nextoff+array.join( ",\n"+nextoff )+"\n"+offset+"}";
              }
              break;
            default:
              return data;
              // unsupported data type
              break;
          }
        },

        escapeBackSlash: function ( str )
        {
          return str.replace( /\\/g, "\\\\" ).replace( /\"/g, "\\\"" );
        },

        regexQuote: function(str, delimiter) {
          // Quote regular expression characters plus an optional character
          //
          // version: 1107.2516
          // discuss at: http://phpjs.org/functions/preg_quote
          // +   original by: booeyOH
          // +   improved by: Ates Goral (http://magnetiq.com)
          // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
          // +   bugfixed by: Onno Marsman
          // +   improved by: Brett Zamir (http://brett-zamir.me)
          // *     example 1: preg_quote("$40");
          // *     returns 1: '\$40'
          // *     example 2: preg_quote("*RRRING* Hello?");
          // *     returns 2: '\*RRRING\* Hello\?'
          // *     example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");
          // *     returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'
          return (str + '').replace(new RegExp('[.\\\\+*?\\[\\^\\]$(){}=!<>|:\\' + (delimiter || '') + '-]', 'g'), '\\$&');
        },

        // 全角スペースも含めたtrim
        trim: function(str) {
          return str.replace(/^[\s　]+|[\s　]+$/g, "");
        }

      },

      /// クエリパラメータ操作
      QueryString: {
        parse: function(text, doDecode, sep, eq) {
          text = text || location.search.substr(1);
          doDecode = doDecode === undefined ? true : doDecode; // 未定義の時はtrue
          sep = sep || '&';
          eq = eq || '=';
          if (!text || text.length < 0) {
            return {};
          }

          return text.split(sep).reduce(function(obj, v) {
            var pair = v.split(eq);
            obj[pair[0]] = doDecode ? decodeURIComponent(pair[1]) : pair[1];
            return obj;
          }, {});
        },

        stringify: function(value, doEncode, sep, eq) {
          doEncode = doEncode === undefined ? true : doEncode; // 未定義の時はtrue
          sep = sep || '&';
          eq = eq || '=';
          return Object.keys(value).map(function(key) {
            return key + eq + (doEncode ? encodeURIComponent(value[key]) : value[key]);
          }).join(sep);
        }
      },

      /// 検索パラメータ取り扱いクラス定義
      /// new して利用する。メソッドは別途下段で定義。
      SearchParameter: function() {
        this.params = [];
        this.queryPrefix = 's';
      },

      /// Vue.js 関連ユーティリティ
      Vue: {
        /// 最低限の確認ダイアログ 実装
        createCommonModalViewModel: function(modalId, caption, defaultMessage, onSubmit) {
          return new Vue({
            el: modalId,
            data: {
                caption: caption
              , message: ''
              , messageClass: 'alert'
              , notices: []
              , noticeClass: 'alert-warning'
              , noticeHidden: true
              , url: null

              , nowLoading: true
            },
            ready: function() {
              var self = this;
              self.url = $(self.$el).data('url');

              // イベント登録
              $(self.$el).on('show.bs.modal', function(e) {
                self.resetDialog();

                self.message = defaultMessage;

                self.messageClass = 'alert alert-info multiLine';
                self.nowLoading = false;
              });
            },

            methods: {
              open: function() {
                self.nowLoading = true;
                $(this.$el).modal('show');
              },

              onSubmit: onSubmit ? onSubmit : function() {
                $('.modal-footer button.btn-primary', self.$el).hide();
                window.location.href = this.url;
              },

              resetDialog: function() {
                this.caption = caption;
                this.$data.message = '';
                this.$data.messageClass = '';
                $('.modal-footer button.btn-primary', self.$el).show();
              }
            }
          });
        }
      }

      /// レポジトリオブジェクト（変数引き渡しなどに利用）
      , Repository: {
      }

      , Storage: {
        Local: function(namespace) {
          return {
            get: function () {
              var data = window.localStorage.getItem(namespace);
              if (data) {
                return JSON.parse(data);
              }
            }
            , set: function (data) {
              window.localStorage.setItem(namespace, JSON.stringify(data));
            }
          }
        }
      }

      /// NextEngine関連処理
      , NextEngine: {
        openVoucherWindow: function (voucherNumber, target) {
          var width = 1024;
          var height = 700;

          target = target || '_blank';

          // 本当は、ホスト名をどこかから取得してURLを組み立てるべき・・・どこから？
          var url = 'https://main.next-engine.com/Userjyuchu/jyuchuInp?jyuchu_meisai_order=jyuchu_meisai_gyo&kensaku_denpyo_no=' + voucherNumber;
          var options = [
              'menubar=no'
            , 'toolbar=no'
            , 'width=' + width
            , 'height=' + height
            , 'resizable=yes'
            , 'scrollbars=yes'
          ];

          window.open(
              url
            , target
            , options.join(',')
          );
        }
      }

    }
  });

  // クラス（?）メソッド定義
  // -- 検索パラメータクラス
  $.Plusnao.SearchParameter.prototype = {
    addParam: function(key, type, alias, value) {
      type = type || 'string';
      alias = alias || key;
      if (value === undefined) {
        value = null;
      }

      this.params.push({
          key: key
        , type: type
        , alias: alias
        , value: this.adjustParamValue(type, value)
      });
    }

    , removeParam: function(key) {
      for (var i = 0; i < this.params.length; i++) {
        if (this.params[i].key === key) {
          this.params.splice(i, 1);
          return;
        }
      }
    }

    , setValue: function(key, value) {
      for (var i = 0; i < this.params.length; i++) {
        if (this.params[i].key === key) {
          this.params[i].value = this.adjustParamValue(this.params[i].type, value);
        }
      }
    }

    , setValues: function(values) {
      var keys = Object.keys(values);
      if (keys.length > 0) {
        for (var i in keys) {
          if (keys.hasOwnProperty(i)) {
            this.setValue(keys[i], values[keys[i]]);
          }
        }
      }
    }

    , setValueWithAlias: function(alias, value) {
      for (var i = 0; i < this.params.length; i++) {
        if (this.params[i].alias === alias) {
          this.params[i].value = this.adjustParamValue(this.params[i].type, value);
        }
      }
    }

    , setValuesWithAlias: function(values) {
      var keys = Object.keys(values);
      if (keys.length > 0) {
        for (var i in keys) {
          if (keys.hasOwnProperty(i)) {
            this.setValueWithAlias(keys[i], values[keys[i]]);
          }
        }
      }
    }

    , getParams: function() {
      var ret = {};
      for (var i = 0; i < this.params.length; i++) {
        ret[this.params[i].key] = this.params[i].value;
      }
      return ret;
    }

    , getParamsWithAlias: function() {
      var ret = {};
      for (var i = 0; i < this.params.length; i++) {
        ret[this.params[i].alias] = this.params[i].value;
      }
      return ret;
    }

    , generateQueryString: function(removeEmptyParam) {

      removeEmptyParam = removeEmptyParam === undefined ? true : removeEmptyParam;

      var params = {};
      for (var i = 0; i < this.params.length; i++) {
        var item = this.params[i];
        var value = this.convertParamValueToString(item.type, item.value);
        if (value.length > 0 || ! removeEmptyParam) {
          params[item.alias] = value;
        }
      }

      return $.Plusnao.QueryString.stringify(params, true);
    }


    , adjustParamValue: function(type, value) {
      var ret;
      switch  (type) {
        case 'string':
          ret = value === null ? '' : String(value);
          break;
        case 'boolean': // fallthrough
        case 'bool':
          ret = Boolean(value);
          break;
        case 'integer':
          ret = (value === null || value === '') ? null : parseInt(value, 10);
          if (isNaN(ret)) {
            ret = null;
          }
          break;
        case 'number':
          ret = (value === null || value === '') ? null : Number(value);
          if (isNaN(ret)) {
            ret = null;
          }
          break;
        default:
          ret = value;
          break;
      }

      return ret;
    }

    , convertParamValueToString: function(type, value) {
      var ret;
      switch  (type) {
        case 'boolean': // fallthrough
        case 'bool':
          ret = value === null ? '' : String(Number(value));
          break;
        case 'string':
        default:
          ret = value === null ? '' : String(value);
          break;
      }

      return ret;
    }

  };

  // 定数 freeze
  Object.freeze($.Plusnao.Const);

})(jQuery);

// その他モジュール
(function($) {
  $.extend({

    Vendor: {
      /**
       * Module for displaying "Waiting for..." dialog using Bootstrap
       *
       * @author Eugene Maslovich <ehpc@em42.ru>
       */
      WaitingDialog: (function () {
        'use strict';

        // Creating modal dialog's DOM
        var $dialog = $(
          '<div class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-hidden="true" style="padding-top:15%; overflow-y:visible;">' +
          '<div class="modal-dialog modal-m">' +
          '<div class="modal-content">' +
          '<div class="modal-header"><h3 style="margin:0;"></h3></div>' +
          '<div class="modal-body">' +
          '<div class="progress progress-striped active" style="margin-bottom:0;"><div class="progress-bar" style="width: 100%"></div></div>' +
          '</div>' +
          '</div></div></div>');

        return {
          /**
           * Opens our dialog
           * @param message Custom message
           * @param options Custom options:
           * 				  options.dialogSize - bootstrap postfix for dialog size, e.g. "sm", "m";
           * 				  options.progressType - bootstrap postfix for progress bar type, e.g. "success", "warning".
           */
          show: function (message, options) {
            // Assigning defaults
            if (typeof options === 'undefined') {
              options = {};
            }
            if (typeof message === 'undefined') {
              message = 'Loading';
            }
            var settings = $.extend({
              dialogSize: 'm',
              progressType: '',
              onHide: null // This callback runs after the dialog was hidden
            }, options);

            // Configuring dialog
            $dialog.find('.modal-dialog').attr('class', 'modal-dialog').addClass('modal-' + settings.dialogSize);
            $dialog.find('.progress-bar').attr('class', 'progress-bar');
            if (settings.progressType) {
              $dialog.find('.progress-bar').addClass('progress-bar-' + settings.progressType);
            }
            $dialog.find('h3').text(message);
            // Adding callbacks
            if (typeof settings.onHide === 'function') {
              $dialog.off('hidden.bs.modal').on('hidden.bs.modal', function (e) {
                settings.onHide.call($dialog);
              });
            }
            // Opening dialog
            $dialog.modal();
          },
          /**
           * Closes dialog
           */
          hide: function () {
            $dialog.modal('hide');
          }
        };
      })()
    }
  });
})(jQuery);

