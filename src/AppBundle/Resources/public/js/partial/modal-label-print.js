/**
 * ラベル印刷モーダル
 * for Vue2.x
 */

/**
 * メインブロック
 */
Vue.component('parts-modal-label-print', {
    template: '#templateModalLabelPrint'
  , delimiters: ['(%', '%)']
  , components: {
  }
  , props: [
      'state' // { show: true|false, initialList: [] }
  ]
  , data: function() {
    return {
        searchSkuUrl: null
      , message: ''
      , messageCssClass: 'alert-info'

      , searchCode: null
      , list: []
    };
  }
  , computed: {
    caption: function() {
      return '商品ラベル印刷';
    }
  }

  , watch : {
  }

  , mounted: function() {
    this.$nextTick(function (){
      var self = this;
      var modal = $(self.$el);

      self.searchSkuUrl = $(self.$el).data('searchSkuUrl');

      // イベント登録
      self.$watch('state.show', function(newValue) {
        if (newValue && modal.is(':hidden')) {
          modal.modal('show');
        } else if (!newValue && !modal.is(':hidden')) {
          modal.modal('hide');
        }
      });

      // -- open前
      modal.on('show.bs.modal', function(e) {
        self.reset();
        // 初期データ
        if (self.state.initialList && self.state.initialList.length > 0) {
          for (var i = 0; i < self.state.initialList.length; i++) {
            self.addSku(self.state.initialList[i]);
          }
        }

      });
      // -- open後
      modal.on('shown.bs.modal', function(e) {
        if (!self.state.show) {
          self.state.show = true;
        }
      });

      // -- close後
      modal.on('hidden.bs.modal', function(e) {
        if (self.state.show) {
          self.state.show = false; // 外部から閉じられた時のステータス手当
        }
      });

    });
  }
  , methods: {
    hideModal: function() {
      this.state.show = false;
    }

    , reset: function() {
      this.list = [];
      this.clearMessage();
      $('#modalLabelPrintUploadCsvFile', this.$el).val(null);
    }

    , setMessage: function(message, css, autoHide) {
      if (css === undefined) {
        css = 'alert-info';
      }
      if (autoHide === undefined) {
        autoHide = true;
      }
      this.message = message;
      this.messageCssClass = css;

      if (autoHide) {
        var self = this;
        setTimeout(function(){ self.message = ''; } , 3000);
      }
    }

    , clearMessage: function() {
      this.message = '';
      this.messageCssClass = 'alert-info';
    }

    , search: function() {
      var self = this;

      // データ読み込み処理
      var data = {
          code: self.searchCode
      };

      $.ajax({
          type: "GET"
        , url: self.searchSkuUrl
        , dataType: "json"
        , data: data
      })
        .done(function(result) {

          if (result.status == 'ok') {

            var i;

            var item = result.data;

            // 0件
            if (!item) {
              self.setMessage('商品が見つかりませんでした。', 'alert-warning');
            } else {
              self.addSku(item);
            }

          } else {
            var message = result.message.length > 0 ? result.message : 'データが取得できませんでした。';
            self.setMessage(message, 'alert-danger');
          }
        })
        .fail(function(stat) {
          console.log(stat);
          self.setMessage('エラーが発生しました。', 'alert-danger');
        })
        . always(function() {
        });
    }

    , addSku: function(data) {
      var sku = data.neSyohinSyohinCode;
      if (!sku) {
        return;
      }
      var num = data.num ? Number(data.num) : 1;
      var item;

      // 一致するものがあれば、数量を加算する。
      var hit = false;
      for (var i = 0; i < this.list.length; i++) {
        item = this.list[i];
        if (item.neSyohinSyohinCode == sku) {
          item.num += num;
          hit = true;
          break;
        }
      }

      // 一致しなければ、リスト末尾に追加
      if (!hit) {
        item = {
          neSyohinSyohinCode: sku
          , num: num
        };

        this.list.push(item);
      }
    }


    /**
     * ラベル印刷CSV出力
     */
    , downloadLabel: function(type) {

      var self = this;

      if (!type) {
        type = 'normal';
      }

      var $form = $('#labelDownloadForm');
      $form.find('input').remove(); // input(hidden) 全て削除

      if (!self.list.length) {
        self.setMessage('商品コードが入力されていません。', 'alert-warning');
        return;
      }

      // データ取得
      var i;
      var row;
      for (i = 0; i < self.list.length; i++) {
        row = self.list[i];
        row = {
            code: row.neSyohinSyohinCode
          , num: row.num
        };
        for (var k in row) {
          var name = "data[" + i.toString() + "][" + k + "]";
          $form.append($('<input type="hidden">').attr('name', name).val(row[k]));
        }
      }

      $form.append($('<input type="hidden">').attr('name', 'type').val(type));

      // エラー時リダイレクト先
      $form.append($('<input type="hidden" name="redirect">').val(window.location.href));

      $form.submit();
    }

    /**
     * CSVファイルからリスト作成
     */
    , addFromCsvFile: function(event) {

      var file = event.target.files[0];
      if (!file) {
        return;
      }

      var name = file.name;
      if (!name.match(/\.csv$/)) {
        this.setMessage('拡張子 .csv のファイルを指定してください。', 'alert-warning');
        return;
      }

      this.setMessage('CSVファイルから一覧を作成します。', 'alert-info', false);

      var self = this;
      var reader = new FileReader;
      reader.readAsText(file, 'shift-jis');
      reader.onload = function(e) {
        var content = e.target.result;

        // 本当はcsv-parserが使いたい。簡易実装
        var rows = content.split(/\r?\n/);
        var list = [];
        for (var i = 0; i < rows.length; i++) {
          var line = rows[i];
          line = line.replace(/"/g, '');
          var row = line.split(/,/);

          var code = row[0];
          var num = row[1];
          if (!num || !num.match(/^\d+$/)) {
            continue;
          }

          var item = {
            neSyohinSyohinCode: code
            , num: Number(num)
          };

          self.addSku(item);
        }

      }

    }



  }
});
