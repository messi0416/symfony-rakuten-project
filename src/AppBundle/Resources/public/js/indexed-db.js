/**
 * Indexed DB 操作クラス
 */
(function ($) {
  $.extend({
    //
    Plusnao: {
      IndexedDb: function (dbName) {

        return {

          isInitialized: false
          , db: null
          , error: null

          , indexedDB: null
          , IDBTransaction: null
          , IDBKeyRange: null
          , IDBCursor: null

          , dbName: dbName

          /// 初期化
          , init: function () {

            if (this.isInitialized) {
              return;
            }

            this.db = null;
            this.error = null;

            this.indexedDB = window.indexedDB || window.webkitIndexedDB || window.mozIndexedDB || window.msIndexedDB;
            this.IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction || window.mozIDBTransaction || window.msIDBTransaction;
            this.IDBKeyRange = window.IDBKeyRange || window.webkitIDBKeyRange || window.mozIDBKeyRange || window.msIDBKeyRange;
            this.IDBCursor = window.IDBCursor || window.webkitIDBCursor;

            if (!this.indexedDB) {
              this.setError("no indexed db.");
            }

            this.isInitialized = true;
          }

          /// 接続
          , connect: function () {

          }

          , openDatabase: function () {

          }

          , closeDatabase: function () {

          }

          , setError: function (message) {
            this.error = message;
            if (window.console && typeof window.console.log === 'function') {
              console.log(message);
            }
          }
        }

      }
    }
  });
})(jQuery);
