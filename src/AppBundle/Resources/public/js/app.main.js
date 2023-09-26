/**
 * 主要処理 JS
 */
// 初期処理
$(function () {
  // Vue.config.debug = true;
  // トップページ表示時 タブ選択
  var defaultTarget = '#tabNotifications';
  var hashTabName = document.location.hash;
  var targetNav = null;
  if (! hashTabName) {
    hashTabName = defaultTarget;
  }
  targetNav = $('.nav-tabs a[href=' + hashTabName + ']');
  if (targetNav.size() > 0) {
    targetNav.tab('show');
  }

  // タブ変更時イベント（ハッシュをつけるだけ）
  $("#indexNavTab a[data-toggle=tab]").on("shown.bs.tab", function (e) {
    document.location.hash = $(e.currentTarget).attr('href');
    $('html, body').stop().animate({
      scrollTop: 0
    }, 0);
  });
});

/**
 * VM定義
 */
$(function() {

  /**
   * 通知サーバ 死活監視処理
   */
  function watchAndReconnectNotificationServer(socket)
  {
    // 通信切断 検出
    if (! socket.connected) {
      // メッセージ・アイコンの更新
      if (vmGlobalMessage.canNotify == true) {
        // vmGlobalMessage.setMessage('通知サーバへ接続できませんでした。', 'alert alert-danger');
        vmGlobalMessage.canNotify = false;
      }

      // 再接続
      socket.connect();
    }
  }

  /**
   * ポップアップ Ajax バリデーションコールバック
   *
   * vmSelf 必須プロパティ
   *   $el
   *   message
   *   messageClass
   *   notices
   *   noticeHidden
   *
   * vmSelf 必須メソッド
   *   resetDialog()
   */
  function generateVerifyCallback(vmSelf, url, verifyData, callbacks)
  {
    return function() {
      vmSelf.resetDialog();

      $.ajax({
          type: "GET"
        , url: url
        , dataType: "json"
        , data: verifyData
      })
        .done(function(result) {

          if (result.valid) {
            vmSelf.message = result.message;
            vmSelf.messageClass = 'alert alert-success';

            if (result.notices.length > 0) {
              vmSelf.notices = result.notices;
              vmSelf.noticeHidden = false;
            }

            // (NextEngineCSV出力の場合、)アップロード中か
            if (result.isUploading !== undefined) {
              vmSelf.isUploading = result.isUploading;
            }

            $('.modal-footer button.btn-primary', vmSelf.$el).show();

            // 成功コールバック
            if (callbacks && callbacks.success) {
              callbacks.success(result);
            }

          } else {
            vmSelf.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            vmSelf.messageClass = 'alert alert-danger';
            if (result.notices.length > 0) {
              vmSelf.notices = result.notices;
              vmSelf.noticeHidden = false;
            }
            $('.modal-footer button.btn-primary', vmSelf.$el).hide();

            // 失敗コールバック
            if (callbacks && callbacks.error) {
              callbacks.error(result);
            }
          }
        })
        .fail(function(stat) {
          vmSelf.message = 'エラーが発生しました。';
          vmSelf.messageClass = 'alert alert-danger';
          $('.modal-footer button.btn-primary', vmSelf.$el).hide();

          // 失敗コールバック
          if (callbacks && callbacks.error) {
            callbacks.error(stat);
          }
        })
        . always(function() {
          // 完了コールバック
          if (callbacks && callbacks.finally) {
            callbacks.finally();
          }
        });
    };
  }

  // 全体メッセージ
  var vmGlobalMessage = new Vue({
    el: '#globalMessage',
    data: {
        message: ''
      , messageCssClass: ''
      , canNotify: false

      , labelPrintModalState: {
          show: false
        , showRealShopButton: true
      }
    },
    computed: {
      notifyIconCssClass: function() {
        if (this.canNotify) {
          return 'fa fa-bell-o text-success';
        } else {
          return 'fa fa-ban text-danger';
        }
      },
      notifyIconTitle: function() {
        if (this.canNotify) {
          return '通知機能は有効です。';
        } else {
          return '通知機能は無効です。';
        }
      }
    },
    methods: {
      setMessage: function(message, cssClass, autoHide) {
        cssClass = cssClass || 'alert alert-info';
        autoHide = autoHide || true;

        this.message = message;
        this.setCssClass(cssClass);

        if (autoHide) {
          setTimeout(function(){ vmGlobalMessage.clear()}, 5000);
        }
      },
      setCssClass: function(cssClass) {
        this.messageCssClass = cssClass;
      },
      clear: function() {
        this.message = '';
        this.messageCssClass = '';
      },

      openAccountForm: function(id) {
        if ($.Plusnao.Repository.vmModalAccountForm) {
          $.Plusnao.Repository.vmModalAccountForm.open(id, function(){
            // do nothing
          });
        } else {
          throw new Error('編集フォームの読み込みができませんでした。');
        }
      },

      /// バッチロック解除
      unlockBatchLock: function (){
        if (vmUnlockBatchLockModal) {
          vmUnlockBatchLockModal.open();
        } else {
          throw new Error('エラーが発生しました。');
        }
      },

      /// ワーカー再起動
      openWorkerRebootModal: function() {
        if (vmJobWorkerRebootModal) {
          vmJobWorkerRebootModal.open();
        } else {
          throw new Error('エラーが発生しました。');
        }
      },

      /// キューのジョブ順変更
      openQueueChangePlacesModal: function() {
        if (vmQueueChangePlacesModal) {
          vmQueueChangePlacesModal.open();
        } else {
          throw new Error('エラーが発生しました。');
        }
      },

      /// ラベルダウンロードモーダル表示
      showLabelModal: function() {
        this.labelPrintModalState.show = true;
      }

    }
  });

  /**
   * バッチロック解除 モーダル
   */
  var vmUnlockBatchLockModal = new Vue({
    el: "#modalUnlockBatchLockConfirm",
    data: {
        caption: 'バッチ処理ロック解除'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeHidden: true
      , url: null
      , listUrl: null

      , locks: []

      , nowLoading: true
    },
    ready: function() {

      var self = this;
      self.url = $(self.$el).data('url');
      self.listUrl = $(self.$el).data('listUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        // データ取得
        $.ajax({
            type: "GET"
          , url: self.listUrl
          , dataType: "json"
        })
          .done(function(result) {

            if (result.status == 'ok') {
              self.$set('locks', result.list);

              if (result.list.length > 0) {
                self.message = '下記のバッチ処理ロックがかかっています。全て解除してよろしいですか？';
                self.messageClass = 'alert alert-warning multiLine';

                $('.modal-footer button.btn-primary', self.$el).show();

              } else {
                self.message = '現在、ロックされているバッチ処理はありません。';
                self.messageClass = 'alert alert-info';
              }

            } else {
              self.message = result.message.length > 0 ? result.message : 'ロック情報の取得に失敗しました。';
              self.messageClass = 'alert alert-danger';
              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }
              $('.modal-footer button.btn-primary', self.$el).hide();
            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();
          })
          . always(function() {
            self.nowLoading = false;
          });
      });
    },

    methods: {
      open: function() {
        this.nowLoading = true;
        $(this.$el).modal('show');
      },

      onSubmit: function() {
        var self = this;

        self.resetDialog();

        self.caption = "バッチ処理ロック解除中・・・";
        self.nowLoading = true;

        $.ajax({
            type: "GET"
          , url: self.url
          , dataType: "json"
          , data: {}
        })
          .done(function(result) {

            if (result.error) {
              self.message = result.error;
              self.messageClass = 'alert alert-danger';
            } else {
              self.messageClass = 'alert alert-success multiLine';

              self.message = 'バッチ処理のロックを解除しました。';
            }
          })
          .fail(function(stat) {
            self.message = 'バッチ処理のロック解除に失敗しました。';
            self.messageClass = 'alert alert-danger';

          }).always(function(){
            self.caption = 'バッチ処理ロック解除';
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.nowLoading = false;
          });
      },

      resetDialog: function() {
        this.caption = 'バッチ処理ロック解除';
        this.$data.message = '';
        this.$data.messageClass = '';
        this.$data.notices = [];
        this.noticeHidden = true;

        this.locks = [];

        $('.modal-footer button.btn-primary', self.$el).show();
      }
    }
  });

  /**
   * Job Worker 再起動モーダル
   */
  var vmJobWorkerRebootModal = new Vue({
    el: "#modalRebootJobWorkerConfirm",
    data: {
        caption: 'ワーカー再起動'
      , message: ''
      , messageClass: 'alert'
      , url: null

      , nowLoading: true
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        self.message = 'ジョブキューを処理するワーカーを再起動します。（現在実行中の処理は中断されません。）';
        self.message += "\nよろしいですか？";

        self.messageClass = 'alert alert-warning multiLine';
        self.nowLoading = false;
      });
    },

    methods: {
      open: function() {
        self.nowLoading = true;
        $(this.$el).modal('show');
      },

      onSubmit: function() {
        var self = this;

        self.caption = "ワーカー再起動中・・・";
        self.nowLoading = true;

        $.ajax({
            type: "GET"
          , url: self.url
          , dataType: "json"
          , data: {}
        })
          .done(function(result) {

            if (result.error) {
              self.message = result.error;
              self.messageClass = 'alert alert-danger';
            } else {
              self.messageClass = 'alert alert-success multiLine';

              self.message = '再起動を完了しました。';
              self.message += "\n\n" + result.output;
            }
          })
          .fail(function(stat) {
            self.message = '再起動に失敗しました。';
            self.messageClass = 'alert alert-danger';

          }).always(function(){
            self.caption = 'ワーカー再起動';
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.nowLoading = false;
          });
      },

      resetDialog: function() {
        this.caption = 'ワーカー再起動';
        this.$data.message = '';
        this.$data.messageClass = '';
        $('.modal-footer button.btn-primary', self.$el).show();
      }
    }
  });


  /**
   * キューのジョブ順変更
   */
  var vmQueueChangePlacesModal = new Vue({
    el: "#modalQueueChangePlacesConfirm",
    data: {
      caption: 'キューのジョブ順変更'
      , jobs: []
      , jobsNum: {}
      , duplicate: {}
      , message: ''
      , messageClass: 'alert'
      , selectedQueueName: 'main'
      , workerStatus: ''

      , queueChangePlacesUrl: null
      , isStopWorkerUrl: null
      , saveQueueChangedPlacesUrl: null

      , nowLoading: true
    },
    ready: function() {
      var self = this;
      self.queueChangePlacesUrl = $(self.$el).data('queueChangePlacesUrl');
      self.isStopWorkerUrl = $(self.$el).data('isStopWorkerUrl');
      self.saveQueueChangedPlacesUrl = $(self.$el).data('saveQueueChangedPlacesUrl');

      $('.nav-tabs-justified').children('li').on('click', function() {
        self.selectedQueueName = $(this).attr('id');
      });

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.getData();
      });
    },

    watch :{
      selectedQueueName: function(){
        // ワーカーの状態を設定
        this.selectedWorkerStatus();
      }
    },

    methods: {
      open: function() {
        self.nowLoading = true;
        $(this.$el).modal('show');
      },

      /**
       * データ取得
       */
      getData: function(){
        let self = this;
        self.message = 'ジョブの順番を変更してください。';
        self.messageClass = 'alert alert-info multiLine';

        // ワーカーの状態を設定
        self.selectedWorkerStatus();

        // キュー内容取得
        $.ajax({
          type: "POST"
          , url: self.queueChangePlacesUrl
          , dataType: "json"
        })
          .done(function(result) {
            self.jobs = result.jobs;
            // それぞれのキューのジョブ数を記録
            $.each(result.jobs,function(queue,value){
              self.jobsNum[queue] = value.length;
            });

            // ワーカーごとに追加時間が重複したものを記録(ジョブの入れ替え・削除をしてよいかの判断に利用)
            self.checkDuplicate();
          })
          .fail(function(stat) {
            console.error(stat.message);
            self.message = 'キューの取得に失敗しました。';
            self.messageClass = 'alert alert-danger multiLine'
          })
          .always(function(){
            $('.modal-footer button.btn-primary').show();
            self.nowLoading = false;
          });
      },

      // 選択しているワーカーの状態を設定
      selectedWorkerStatus: function(){
        let self = this;
        self.nowLoading = true;
        $.ajax({
          type: "POST"
          , url: self.isStopWorkerUrl
          , dataType: "json"
          , data: {
            workerName: self.selectedQueueName
          }
        })
          .done(function(result) {
            if(result.status === 'ok'){
              self.workerStatus = false;
              self.message = 'ジョブの順番を変更してください。';
              self.messageClass = 'alert alert-info multiLine';
            }else{
              self.workerStatus = true;
              self.message = result.message;
              self.messageClass = 'alert alert-warning multiLine';
            }
          })
          .fail(function(stat) {
            self.message = 'ワーカーの状態の取得に失敗しました。';
            self.messageClass = 'alert alert-danger multiLine'
          })
          .always(function(){
            self.nowLoading = false;
          });
      },

      /**
       * 削除
       * @param index
       */
      deleteJob: function(index){
        let self = this;
        // 入れ替え・削除不可のジョブであれば
        if(self.isNotBeChanged(index)){
          if(!confirm('このジョブを削除するとエラーが発生する可能性があります。\n' +
            'よろしいですか？')){
            return;
          }
        }
        self.jobs[self.selectedQueueName].splice(index,1);
      },

      /**
       * 上に移動
       * @param index
       */
      moveUpJob: function(index){
        let self = this;

        if(index <= 0){
          return;
        }

        // 移動先に入れ替え・削除不可のジョブがあれば
        if(self.isNotBeChanged(index-1)){
          if(!confirm('このジョブの移動先は変更できません。\n' +
            '順番を変更するとエラーが発生する可能性があります。\n' +
            'よろしいですか？')){
            return;
          }
        // 移動元
        }else if(self.isNotBeChanged(index)){
          if(!confirm('このジョブは変更できません。\n' +
            '順番を変更するとエラーが発生する可能性があります。\n' +
            'よろしいですか？')){
            return;
          }
        }
        self.swap(self.selectedQueueName,index,index-1);
      },

      /**
       * 下に移動
       * @param index
       */
      moveDownJob: function(index){
        let self = this;

        let queue = self.selectedQueueName;
        if(index + 1 >= self.jobs[queue].length){
          return;
        }

        // 移動先に入れ替え・削除不可のジョブがあれば
        if(self.isNotBeChanged(index+1)){
          if(!confirm('このジョブの移動先は変更できません。\n' +
            '順番を変更するとエラーが発生する可能性があります。\n' +
            'よろしいですか？')){
            return;
          }
          // 移動元
        }else if(self.isNotBeChanged(index)){
          if(!confirm('このジョブは変更できません。\n' +
            '順番を変更するとエラーが発生する可能性があります。\n' +
            'よろしいですか？')){
            return;
          }
        }
        self.swap(queue,index,index+1);
      },

      /**
       * 移動先と移動元の要素を入れ替え
       * @param queue
       * @param from
       * @param to
       */
      swap: function(queue,from,to){
        let self = this;
        let jobs = self.jobs[queue];
        let temp = jobs[from];
        jobs.splice(from,1,jobs[to]);
        jobs.splice(to,1,temp)
      },

      /**
       * 変更不可のジョブか
       * @param index
       * @returns boolean
       */
      isNotBeChanged: function(index,queue = this.selectedQueueName){
        let self = this;
        let time = self.jobs[queue][index]['queueDatetime'];
        return self.duplicate[queue].includes(time);
      },

      /**
       * 変更不可の行の背景を薄い灰色に設定
       * @param index
       * @returns {{"background-color": string}}
       */
      backgroundCss: function(index,queue){
        let self = this;
        if(self.isNotBeChanged(index,queue)){
          return {'background-color' : '#EEEEEE'};
        }
      },

      /**
       * 変更不可のジョブを設定
       */
      checkDuplicate: function(){
        let self = this;

        $.each(self.jobs,function(queue,jobs){
          let temp = '';
          // 追加時間が重複したもののみ残す
          self.duplicate[queue] = jobs.filter(element => {
              if(temp === element['queueDatetime']){
                return true;
              }else{
                temp = element['queueDatetime'];
              }
          });

          temp = '';
          // 重複を解消する
          self.duplicate[queue] = self.duplicate[queue].filter(element => {
            if(temp !== element['queueDatetime']){
              temp = element['queueDatetime'];
              return true;
            }
          });

          // 追加時間のみ取り出す
          self.duplicate[queue] = self.duplicate[queue].map(element => {
            return element['queueDatetime'];
          })
        });
      },

      /**
       * 再読み込み
       */
      loadData: function(){
        let self = this;
        self.getData();
      },

      /**
       * 変更ジョブの保存
       * キューの状態取得 -> 最新ジョブを取得し、更新ジョブとの差分を更新ジョブに追加 -> エンキュー
       */
      onSubmit: function() {
        var self = this;

        let isJobExists = self.jobsNum[self.selectedQueueName];
        if(!isJobExists){
          self.message = '保存するジョブが存在しません。';
          return;
        }
        if(!confirm('一度保存した変更は元には戻せません。\n' +
          '本当によろしいですか？')){
          return;
        }
        self.nowLoading = true;

        new Promise((resolve) => {
          // ワーカーが停止しているか確認
          $.ajax({
            type: "POST"
            , url: self.isStopWorkerUrl
            , dataType: "json"
            , data: {
              workerName: self.selectedQueueName
            }
          })
            .always(function(result){
              if(result.status === 'ok'){
                self.workerStatus = false;
              }else{
                self.workerStatus = true;
                self.nowLoading = false;
              }
              resolve();
            });
        })
          .then(() => {
            if(!self.workerStatus){
              // 最新のキュージョブ取得
              $.ajax({
                type: "POST"
                , url: self.queueChangePlacesUrl
                , dataType: "json"
              })
                .done(function(result) {
                  // 更新対象のキューに最新のジョブの差分を追加すると同時に対象でないキューにも最新ジョブを追加する
                  Object.keys(self.jobsNum).forEach(function (queue) {
                    if(result.jobs[queue].slice(self.jobsNum[queue]).length > 0){
                      for(let idx = self.jobsNum[queue];idx < result.jobs[queue].length;idx++){
                        self.jobs[queue].push(result.jobs[queue][idx]);
                      }
                    }
                  });
                })
                .fail(function(stat) {
                  console.error(stat.message);
                  self.message = 'キューの新規取得に失敗しました。';
                  self.messageClass = 'alert alert-danger multiLine'
                });
            }else{
              self.message = 'キューが停止していないため保存ができませんでした。';
              self.messageClass = 'alert alert-warning multiLine';
              self.nowLoading = false;
            }
          })
          .then(() => {
            if(!self.workerStatus){
              $.ajax({
                type: "POST"
                , url: self.saveQueueChangedPlacesUrl
                , dataType: "json"
                , data: {
                  jobs: self.jobs[self.selectedQueueName]
                  , selectedQueue: self.selectedQueueName
                }
              })
                .done(function(result) {
                  self.message = result.message;
                  self.messageClass = 'alert alert-success multiLine';
                  if(result.status !== 'ok'){
                    self.messageClass = 'alert alert-warning multiLine';
                  }
                })
                .fail(function(stat) {
                  console.error(stat.message);
                  self.message = 'キューの保存に失敗しました。';
                  self.messageClass = 'alert alert-danger multiLine'
                })
                .always(function(){
                  self.caption = "キューのジョブ順変更";
                  self.nowLoading = false;
                });
            }
          })
      }
    }
  });

  /**
   * 通知表示テーブル
   */
  var vmNotificationListTable = new Vue({
    el: '#notificationListTable',
    data: {
      notifications: [],
      notificationsSearch: [],
      displayNotifications: [],
      timerId: null,
      listUrl: null,
      listMoreUrl: null,
      notificationUrl: null,
      isLoading: null,
      lastLogId: null,
      firstLogId: null,
      filterFlg: 0,
      searchFlg: 0,
      filterInfoErrorFlg: 0,
      notification_count: 0,
      searchItem: {
        targetDateFrom: $.Plusnao.Date.getDateString($.Plusnao.Date.getYesterday(), false, false) + " 00:00"
      }
    },
    socketio: null,
    ready:async function() {
      // 初期データ取得処理
      var self = this;
      self.listUrl = $(self.$el).data('url');
      self.listMoreUrl = $(self.$el).data('moreUrl');
      self.notificationUrl = $(self.$el).data('notificationUrl');
      self.notificationPath = $(self.$el).data('notificationPath');

      // 初回は実行させたいためawaitで実行
      await self.getLastLogList();
      clearInterval(self.timerId);


      /**
       * デスクトップ通知設定確認
       */
      if (typeof Notification !== 'undefined' && (! Notification.permission || Notification.permission == 'default')) {
        Notification.requestPermission();
      }

      /**
       * 通知サーバ(WebSocket) 接続設定
       */
      var deferred = new $.Deferred();
      deferred.promise();

      try {
        this.socketio = io.connect(self.notificationUrl, { path: self.notificationPath + 'socket.io' });

        this.socketio.on("connect", function() {
          if (Notification.permission == 'granted') {
            vmGlobalMessage.setMessage('通知機能が有効です。', 'alert alert-success');
            vmGlobalMessage.canNotify = true;
          } else {
            vmGlobalMessage.setMessage('通知機能がOFFになっています。', 'alert alert-warning');
            vmGlobalMessage.canNotify = false;
          }
          // 初回のみ
          if (deferred) {
            deferred.resolve();
          }
        });
        this.socketio.on("connect_error", function(name) {
          // 初回のみ
          if (deferred) {
            deferred.reject();
          }
        });
        // 通知受け取り！
        this.socketio.on("publish", function (data) {
          self.onNotificationPublished(data);
        });
        // 切断
        this.socketio.on("disconnect", function () {});

      } catch (e) {
        deferred.reject();
      }

      // 通知サーバ接続を確認後、デスクトップ通知を確認、設定する
      deferred.done(function() {

        // do nothing

      }).fail(function() {
        // vmGlobalMessage.setMessage('通知サーバへ接続できませんでした。', 'alert alert-danger');
        vmGlobalMessage.canNotify = false;
      });

      // 対象日時カレンダー有効化
      $('#dt_filter_from').datetimepicker({
        locale: 'ja',
        format : 'YYYY-MM-DD HH:mm'
      });
      $('#dt_filter_to').datetimepicker({
        locale: 'ja',
        format : 'YYYY-MM-DD HH:mm'
      });


      // 死活管理 (1分おき)
      setInterval(watchAndReconnectNotificationServer.bind(this, this.socketio), 60000);
    },
    computed:{
      /**
       * @return array
       * 通知リスト表示用
       * エラーログ抽出
       */
      notificationsList: function(){
        self = this;
        self.displayNotifications = self.notifications;
        if (self.searchFlg === 1) {
          self.displayNotifications = self.notificationsSearch;
          self.notification_count = self.displayNotifications.length;
        }
        if(self.filterInfoErrorFlg === 1){
            return self.displayNotifications.filter(function(element){ return (element['HAS_INFORMATION'] === 1 || element['ERROR_FLAG'] === -1)});
        }
        if(self.filterFlg === 1){
          return self.displayNotifications.filter(function(element){ return element['ERROR_FLAG'] === -1});
        }
        return self.displayNotifications;
      }
    },

    methods: {
      /**
       * 最新ログ取得処理
       */
      getLastLogList:async function () {
        var self = this;
        if (self.isLoading) {
          return;
        } else {
          self.isLoading = true;
        }

        var data = {
          'last_id': self.lastLogId
        };
        await $.ajax({
          type: "GET"
          , url: self.listUrl
          , dataType: "json"
          , data: data
        }).done(function(data) {
          // ID昇順に並べ換えて、1つずつ追加する
          $(data.sort(function(a, b) {
            var aid, bid;
            aid = Number(a.ID);
            bid = Number(b.ID);
            if (aid == bid) { return 0; }
            return  aid > bid ? 1 : -1;
          })).each(function(i, row) {
            self.notifications.unshift(row);

            if (!self.lastLogId || row.ID > self.lastLogId) {
              self.lastLogId = row.ID;
            }
          });

          // 新規通知情報取得
          if (self.notifications.length > 0) {
            self.firstLogId = self.notifications[self.notifications.length - 1].ID;
            self.timerId = setInterval(() => {
              var data = {
                'last_id': self.lastLogId
              };
              $.ajax({
                type: "GET"
                , url: self.listUrl
                , dataType: "json"
                , data: data
              }).done(function(data) {
                // ID昇順に並べ換えて、1つずつ追加する
                $(data.sort(function(a, b) {
                  var aid, bid;
                  aid = Number(a.ID);
                  bid = Number(b.ID);
                  if (aid == bid) { return 0; }
                  return  aid > bid ? 1 : -1;
                })).each(function(i, row) {
                  self.notifications.unshift(row);

                  if (!self.lastLogId || row.ID > self.lastLogId) {
                    self.lastLogId = row.ID;
                  }
                });

                if (self.notifications.length > 0) {
                  self.firstLogId = self.notifications[self.notifications.length - 1].ID;
                }
              }).always(function() {
                self.isLoading = false;
              });
            }, 3000)
          }
        }).always(function() {
          self.isLoading = false;
        });
      },

      /**
       * もっと読む
       */
      loadLogMore: function () {

        var self = this;
        if (!self.firstLogId) {
          return;
        }
        if (self.isLoading) {
          return;
        } else {
          self.isLoading = true;
        }

        var data = {
          'first_id': self.firstLogId
        };

        $.ajax({
          type: "GET"
          , url: self.listMoreUrl
          , dataType: "json"
          , data: data
        }).done(function(data) {
          $(data).each(function(i, row) {
            self.notifications.push(row);

            if (!self.firstLogId || row.ID < self.firstLogId) {
              self.firstLogId = row.ID;
            }
          });

        }).always(function() {
          self.isLoading = false;
        });

      },

      /**
       * 絞込(リアルタイムOFF)処理
       */
      getLogSearchList: function () {
        var self = this;
        self.searchUrl = $(self.$el).data('notification-search-url');
        var data = {
          'target_date_from': self.searchItem.targetDateFrom,
          'target_date_to': self.searchItem.targetDateTo,
          'pc_name': self.searchItem.pcName,
          'exec_title': self.searchItem.execTitle,
          'log_title': self.searchItem.logTitle,
          'sub': self.searchItem.subMessage
        };
        $.ajax({
          type: "GET"
          , url: self.searchUrl
          , dataType: "json"
          , data: data
        }).done(function(data) {
          if (data.message !== undefined) {
            alert(data.message);
            return;
          }
          self.searchFlg = 1;
          self.notificationsSearch.splice(-self.notificationsSearch.length);
          $(data).each(function(i, row) {
            self.notificationsSearch.push(row);
          });
        })
        .fail(function(stat) {
          self.message = 'データが取得できませんでした。';
          self.$set('runningProcesses', []);
          self.$set('jobs', {});
        })
        .always(function() {
          self.isLoading = false;
        });
      },

      /**
       * 絞込処理 or 絞込解除（トグル）
       */
      toggleSearch: function() {
        if(this.searchFlg > 0){
          this.searchFlg = 0;
        }
        else{
          this.getLogSearchList();
          this.searchFlg = 1;
        }
      },

      /**
       * 絞込クリア処理
       */
      searchClear: function () {
        this.searchFlg = 0;
      },

      /**
       * フィルターの有無のフラグを変更
       */
      chageFilterFlg: function(){
        this.filterFlg = this.filterFlg > 0 ? 0 : 1;
      },

      /**
       * エラー＋INFO抽出フィルターのフラグを変更
       */
      chageErrorInfoFilterFlg: function(){
        this.filterInfoErrorFlg = this.filterInfoErrorFlg > 0 ? 0 : 1;
      },

      /**
       * information 表示ポップアップ
       */
      openInformationModal: function (id) {
        vmNotificationInformation.open(id);
      },

      /// 通知インベントハンドラ
      /**
       * 基本的に、通知ロジックはここで制御する。
       * @param data
       */
      onNotificationPublished: function (data) {
        // 最新のログを取得する
        this.getLastLogList();

        if (data.notify == 1) {
          var notification = {
              message: data.notification_message
            , level: data.notification_level
          };

          this.notify(notification);

          // このタイミングで、キュー一覧も更新してみる
          vmQueueMain.loadData();
        }
      },

      /**
       * デスクトップ通知
       * @param data
       */
      notify: function(data) {
        if (!vmGlobalMessage.canNotify) {
          return;
        }

        var iconImage;
        var message = data.message || '通知がありました。';
        var level =  data.level || 'info';
        switch(level) {
          case 'info':
            iconImage = appImages.icon.info;
            break;
          case 'notice':
            iconImage = appImages.icon.notice;
            break;
          case 'warning':
          case 'error':
            iconImage = appImages.icon.warning;
            break;
        }

        var notice = new Notification('Plusnao Web System通知', {
            tag: 'Plusnao Web System通知'
          , body: message
          , icon: iconImage // Google Chrome only ?
          , iconUrl: iconImage // FireFox ?
        });
      },

      /**
       * 状態の切り替え
       * @param onTarget
       * @param offTarget
       */
      toggleStatus: function(onTarget, offTarget) {
        $(onTarget).addClass('active');
        $(onTarget).removeClass('btn-default');
        $(onTarget).addClass('btn-info');

        $(offTarget).removeClass('active');
        $(offTarget).addClass('btn-default');
        $(offTarget).removeClass('btn-info');
      },

      /**
       * ログ取得再開
       */
      resumeGetNewLog: function() {
        const self = this;
        self.toggleStatus('#resumeLog', '#stopLog');
        self.notifications = [];
        self.lastLogId = null;
        self.isLoading = false;
        self.getLastLogList();
      },

      /**
       * ログ取得停止
       */
      stopGetNewLog: function() {
        const self = this;
        self.toggleStatus('#stopLog', '#resumeLog' );
        clearInterval(self.timerId);
      }
    }
  });

  /**
   * 通知 情報表示ポップアップ
   */
  var vmNotificationInformation = new Vue({
    el: "#modalNotificationInformation",
    data: {
        caption: 'ログ メッセージ'
      , message: ''
      , messageClass: 'alert'
      , url: null

      , nowLoading: true
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        var id = e.relatedTarget.id;
        if (!id) {
          self.message = 'ログ情報を取得できませんでした。';
          self.messageClass = 'alert alert-danger';
          return;
        }

        $.ajax({
          type: "GET"
          , url: self.url
          , dataType: "json"
          , data: { "id": id }
        })
          .done(function(result) {
            if (result) {
              self.message = result.INFORMATION.replace(/\\n/g, "\n");
              // self.message = $.Plusnao.String.dump(result.INFORMATION);

              if (result.ERROR_FLAG == 0) {
                self.messageClass = 'alert alert-info';
              } else {
                self.messageClass = 'alert alert-warning';
              }
            } else {
              self.message = 'ログが取得できませんでした。';
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(function(stat) {
            self.message = 'ログが取得できませんでした。';
            self.messageClass = 'alert alert-danger';
          })
      });
    },

    methods: {
      open: function(id, callbackSuccess) {
        this.callbackSuccess = callbackSuccess;

        self.nowLoading = true;
        $(this.$el).modal('show', { id: id });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
      }
    }
  });

  // ==========================================================
  // キュー画面
  // ==========================================================
  /**
   * キュー画面 メインブロック
   */
  var vmQueueMain = new Vue({
      el: "#queueListMain"
    , data: {
        message: null
      , url: null
      , removeLockUrl: null
      , checkWorkerStatusUrl: null
      , runningProcesses: []

      , jobs: {}
      , selectedWorkerName: null
      , workerData: {}
      , boxContent: null
      , isStop: null

      , nowLoading: false
    }
    , ready: function() {
      var self = this;
      self.url = $(this.$el).data('url');
      self.removeLockUrl = $(this.$el).data('removeLockUrl');
      self.checkWorkerStatusUrl =  $(this.$el).data('checkWorkerStatusUrl');

      self.selectedWorkerName = 'main';
      self.boxContent = 'ワーカーの一時停止';

      $('.nav-tabs-justified').children('li').on('click', function(){
        self.selectedWorkerName = $(this).attr('id');
      });
      // データ取得
      self.loadData();

    }

    , computed: {
      runningProcessesCss: function() {
        return this.runningProcesses.length > 0
             ? 'panel-warning'
             : 'panel-default'
        ;
      }
    }
    , methods: {
      loadData: function() {
        var self = this;
        self.nowLoading = true;
        setInterval(() => {
          $.ajax({
              type: "GET"
            , url: self.url
            , dataType: "json"
            , data: null
          })
            .done(function(result) {
              self.message = result.message;

              if (result.message) {
                self.$set('runningProcesses', []);
                self.$set('jobs', {});
              } else {
                self.$set('runningProcesses', result.runningProcesses);
                self.$set('jobs', result.jobs);
              }
            })
            .fail(function(stat) {
              console.log(stat);
              self.message = 'データが取得できませんでした。';
              self.$set('runningProcesses', []);
              self.$set('jobs', {});
            })
            .always(function() {
              self.nowLoading = false;
            });

            // キュー 一時停止
            $.ajax({
              type: "GET"
              , url: self.checkWorkerStatusUrl
              , dataType: "json"
            })
              .done(function(result) {
                if(!result.error){
                  if(result.output.length > 0){
                    // 更新前に初期化
                    self.workerData = {};
                    result.output.map(function(value){
                      self.workerData[value['stop_worker']] = {'id':value['id'],'remaining_time': value['remaining_time'],'is_running': value['is_running']};
                    });

                    // 選択しているワーカーのデータが存在していて制限時間を過ぎたもの
                    if((!!self.workerData[self.selectedWorkerName] && self.workerData[self.selectedWorkerName]['remaining_time'] <= '00:00:00')){
                      delete self.workerData[self.selectedWorkerName];
                    }

                    // 表示内容(残り時間)とisStop更新
                    $.each(self.workerData,function(index,value){
                      if(self.selectedWorkerName === index) {
                        self.boxContent = '停止中(残り' + value['remaining_time'] + ')';
                        self.isStop = true;
                        return false;
                      }else{
                        self.boxContent = 'ワーカーの一時停止';
                        self.isStop = null;
                      }
                    });
                  }else{
                    self.boxContent = 'ワーカーの一時停止';
                    self.isStop = null;
                  }
                }else{
                  console.error(result.error);
                }
              })
              .fail(function(stat) {
                console.log(stat);
              });
        }, 1000)
      }

      , removeLock: function(id) {
        var self = this;

        if (confirm("この処理のロックを解除してよろしいですか？\n\nこの処理が本当に現在実行中でないことを確認してください。")) {
          self.nowLoading = true;

          var data = { id: id };
          $.ajax({
              type: "POST"
            , url: self.removeLockUrl
            , dataType: "json"
            , data: data
          })
            .done(function(result) {
              if (result.status == 'ok') {
                self.loadData();
                alert(result.message);

              } else {
                self.message = result.message;
              }
            })
            .fail(function(stat) {
              console.log(stat);
              self.message = 'ロック解除処理に失敗しました。';
            })
            .always(function() {
              self.nowLoading = false;
            });
        }
      }
      , openWorkerCancelModal: function(){
        if (vmJobWorkerCancelModal) {
          vmJobWorkerCancelModal.open();
        } else {
          throw new Error('エラーが発生しました。');
        }
      }
      , openWorkerStopModal: function(){
        if (vmJobWorkerStopModal) {
          vmJobWorkerStopModal.open();
        } else {
          throw new Error('エラーが発生しました。');
        }
      }
    }
  });

  /**
   * Job Worker 停止モーダル
   */
  var vmJobWorkerStopModal = new Vue({
    el: "#modalStopJobWorkerConfirm",
    data: {
      caption: 'ワーカー一時停止'
      , message: ''
      , messageClass: 'alert'
      , time: '10'
      , url: null

      , nowLoading: true
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');

      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        self.message = 'ジョブキューを処理するワーカーを一時停止します。（現在実行中の処理は中断されません。）\n';
        self.message += '一時停止する必要がなくなったときは必ずボタンを押して停止をキャンセルしてください。\n';
        self.message += '停止時間を指定してください。（何も指定しなければ10分間停止します。）';

        self.messageClass = 'alert alert-danger multiLine';
        self.nowLoading = false;
      });
    },
    methods: {
      open: function() {
        self.nowLoading = true;
        $(this.$el).modal('show');
        $('.modal-footer button.btn-primary', self.$el).show();
      },

      onSubmit: function() {
        let self = this;

        if(!/^\d{1,3}$/.test(self.time)){
            alert('3桁以内の数で入力してください。');
            return;
        }
        self.time = self.time || 10;
        self.caption = "ワーカー停止中・・・";
        self.nowLoading = true;

        $.ajax({
          type: "GET"
          , url: self.url
          , dataType: "json"
          , data: {
            stopTime: self.time,
            selectedWorkerName: vmQueueMain.selectedWorkerName
          }
        })
          .done(function(result) {
            if (result.error) {
              self.message = '停止に失敗しました。\n';
              self.message += result.error;
              self.messageClass = 'alert alert-danger multiLine';
            } else {
              self.message = result.output;
              self.messageClass = 'alert alert-success';
            }
          })
          .fail(function(stat) {
            self.message = '予期しないエラーが発生しました。';
            self.messageClass = 'alert alert-danger';

          }).always(function(){
          self.caption = 'ワーカー一時停止';
          $('.modal-footer button.btn-primary', self.$el).hide();
          self.nowLoading = false;
          self.time = 10;
        });
      },

      resetDialog: function() {
        this.time = 10;
        this.caption = 'ワーカー一時停止';
        this.$data.message = '';
        this.$data.messageClass = '';
        $('.modal-footer button.btn-primary').show();
      }
    }
  });

  /**
   * Job Worker 一時停止キャンセルモーダル
   */
  var vmJobWorkerCancelModal = new Vue({
    el: "#modalCancelJobWorkerConfirm",
    data: {
      caption : 'ワーカー一時停止キャンセル'
      , message: ''
      , messageClass: 'alert'
      , url: null

      , nowLoading: true
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');
      // イベント登録
      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();

        if(vmQueueMain.workerData[vmQueueMain.selectedWorkerName].is_running){
          self.message = 'このワーカーは実行中の処理が存在するため起動しています。\n';
          self.message += '停止するまでお待ちください。';
          $('.modal-footer button.btn-primary').hide();
        }else{
          self.message = 'ワーカーの一時停止をキャンセルします。\n';
          self.message += 'よろしいですか？';
        }
        self.messageClass = 'alert alert-info multiLine';
        self.nowLoading = false;
      });
    },
    methods: {
      open: function() {
        self.nowLoading = true;
        $(this.$el).modal('show');
        $('.modal-footer button.btn-primary', self.$el).show();
      },

      onSubmit: function() {
        let self = this;

        self.caption = "ワーカー停止キャンセル中・・・";
        self.nowLoading = true;

        $.ajax({
          type: "GET"
          , url: self.url
          , dataType: "json"
          , data: {
            id: vmQueueMain.workerData[vmQueueMain.selectedWorkerName].id,
            workerName : vmQueueMain.selectedWorkerName
          }
        })
          .done(function(result) {
            if (result.error) {
              self.message = 'キャンセルに失敗しました。\n';
              self.message += result.info;
              self.messageClass = 'alert alert-danger multiLine';
            } else {
              self.message = result.info;
              self.messageClass = 'alert alert-success multiLine';
            }
          })
          .fail(function() {
            self.message = '予期しないエラーが発生しました。';
            self.messageClass = 'alert alert-danger';
          }).always(function(){
          self.caption = 'ワーカー一時停止キャンセル';
          $('.modal-footer button.btn-primary', self.$el).hide();
          self.nowLoading = false;
        });
      },

      resetDialog: function() {
        this.caption = 'ワーカー一時停止キャンセル';
        this.$data.message = '';
        this.$data.messageClass = '';
        $('.modal-footer button.btn-primary').show();
      }
    }
  });

  // ==========================================================
  // 機能画面
  // ==========================================================
  /**
   * 機能画面 メインブロック
   */
  var vmFunctionsMain = new Vue({
    el: "#functionsMainBlock",
    data: {
    },
    methods: {
      notifyTest: function() {
        vmNotificationListTable.notify({
            message: 'テスト通知です。'
          , level: 'warning'
        })
      }
    }
  });


  // 機能画面 在庫更新処理 モーダル
  var vmStockListModal = new Vue({
    el: '#modalUpdateStockList',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "import_stock_list"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 機能画面 受注更新処理 モーダル
  var vmOrderListModal = new Vue({
    el: '#modalUpdateOrderList',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null

      , dataStartDate: '2008-08-01'
      , startMonth: 3
      , term: 'month'
      , aggregate: true
    },
    computed: {
      downloadTermString: function() {
        var str = '';
        if (this.startMonth) {
          str = "(" + $.Plusnao.Date.getDateString(this.calculateMonthStartDate()) + " ～ )";
        }
        return str;
      }
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "import_order_list"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        var startDate;
        if (self.term == 'month') {
          startDate = this.calculateMonthStartDate();
        } else if (self.term == 'all') {
          startDate = new Date(this.dataStartDate); // NextEngine 利用開始月
        } else {
          alert('取得期間が選択されていません。');
          return false;
        }

        // Ajaxでキュー追加
        var data = {
            'start-date': $.Plusnao.Date.getDateString(startDate)
          , 'end-date'  : $.Plusnao.Date.getDateString(new Date())
          , 'aggregate' : self.aggregate ? 1 : 0
        };

        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      /// 「月」設定の時の取得開始日を取得
      calculateMonthStartDate: function() {
        var months = this.startMonth || 0;
        var now = new Date();
        return new Date(now.getFullYear(), now.getMonth() - Number(months), 1);
      }
    }
  });

  // 機能画面 受注明細差分更新処理 モーダル
  var vmOrderListIncrementalModal = new Vue({
    el: '#modalUpdateOrderListIncremental',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , alert: ''
      , alertClass: 'alert'
      , noticeHidden: true
      , alertHidden: true
      , queueUrl: null
      , verifyUrl: null
      , checkUrl: null
      , bundleNumUrl: null
      , currentDetailAve: '-'
      , pastDetailAve: '-'

      , mode: 'default' // デフォルト値
      , slipNumbers: ''
    },
    computed: {
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.checkUrl = $(self.$el).data('checkUrl');
      self.detailNumUrl = $(self.$el).data('detailNumUrl');
      // イベント登録
      var data = {
          "queue": "main"
        , "command": "import_order_list_incremental"
      };
      $(self.$el).on('show.bs.modal', function() {
        self.loadData();
      });
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      loadData: function() {
        var self = this;
        $.ajax({
          type: "GET"
          , url: self.detailNumUrl
          , dataType: "json"
          , data: {}
        })
          .done(function(result) {
            if (result.status === 'ok') {
              self.currentDetailAve = result.currentDetailNum;
              self.pastDetailAve = result.pastDetailNum ? result.pastDetailNum : '-';
            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();
          })
          . always(function() {
          });
      }
      , onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {
           mode : self.mode
           , slipNumber : self.slipNumbers.trim().replace(/\r/g,"").replace(/\n/g,",")
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
            self.alertHidden = true;
          });
      },

      onCheck: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {
        };

        $.ajax({
            type: "POST"
          , url: self.$data.checkUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.alert = result.message;
            if(result.warning){
              self.$data.alertClass = 'alert alert-warning';
            } else {
              self.$data.alertClass = 'alert alert-success';
            }
          })
          .fail(function(stat) {
            self.$data.alert = 'チェックに失敗しました';
            self.$data.alertClass = 'alert alert-danger';
          })
          .always(function() {
            self.alertHidden = false;
          });
      },


      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        this.alertHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 機能画面 入出庫データ取込処理 モーダル
  var vmInOutModal = new Vue({
    el: '#modalUpdateInOut',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null

      , checkUseFtpCsv: false
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "import_stock_in_out"
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });


  // 機能画面 閲覧ランキング データ取込処理 モーダル
  var vmViewRankingModal = new Vue({
    el: '#modalUpdateViewRanking',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "import_view_ranking"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  //// 機能画面 楽天レビュー データ取込処理 モーダル
  // 自動取込化により、コメントアウト
  //var vmRakutenReviewModal = new Vue({
  //  el: '#modalUpdateRakutenReview',
  //  data: {
  //    caption: '処理確認'
  //    , message: ''
  //    , messageClass: 'alert'
  //    , notices: []
  //    , noticeClass: 'alert alert-warning'
  //    , noticeHidden: true
  //    , queueUrl: null
  //    , verifyUrl: null
  //  },
  //  ready: function() {
  //    var self = this;
  //    self.queueUrl = $(self.$el).data('url');
  //    self.verifyUrl = $(self.$el).data('verifyUrl');
  //
  //    // イベント登録
  //    var data = {
  //        "queue": "main"
  //      , "command": "import_rakuten_review"
  //    };
  //    $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
  //  },
  //
  //  methods: {
  //    onSubmit: function() {
  //      var self = this;
  //
  //      // Ajaxでキュー追加
  //      var data = {};
  //      $.ajax({
  //        type: "POST"
  //        , url: self.$data.queueUrl
  //        , dataType: "json"
  //        , data: data
  //      })
  //        .done(function(result) {
  //          self.$data.message = result.message;
  //          self.$data.messageClass = 'alert alert-success';
  //
  //          var notification = new Notification("Plusnao Web System 通知", {
  //            tag: 'Plusnao Web System 通知'
  //            , body: result.message
  //            , iconUrl: appImages.icon.info // Firefox
  //            , icon: appImages.icon.info // Chrome
  //          });
  //
  //          /*
  //          // 通知タブに戻す（サンプル）
  //          $(self.$el).modal('hide');
  //          var targetNav = $('.nav-tabs a[href="#tabNotifications"]');
  //          if (targetNav.size() > 0) {
  //            targetNav.tab('show');
  //          }
  //          */
  //
  //        })
  //        .fail(function(stat) {
  //          self.$data.message = '処理を開始できませんでした。';
  //          self.$data.messageClass = 'alert alert-danger';
  //        })
  //        .always(function() {
  //          $('.modal-footer button.btn-primary', self.$el).hide();
  //          self.noticeHidden = true;
  //        });
  //    },
  //
  //    resetDialog: function() {
  //      this.$data.message = '';
  //      this.$data.messageClass = '';
  //      this.notices = [];
  //      this.noticeHidden = true;
  //      $('.modal-footer button.btn-primary', self.$el).hide();
  //    }
  //  }
  //});

  // 機能画面 Amazon在庫情報 データ取込処理 モーダル
  var vmImportAmazonStockModal = new Vue({
    el: '#modalImportAmazonStock',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "import_amazon_stock"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
          type: "POST"
          , url: self.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 機能画面 Wowma ロットナンバーCSV取込 モーダル
  var vmImportWowmaLotNumberCsvModal = new Vue({
    el: '#modalImportWowmaLotNumber',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , verifyUrl: null
      , uploadUrl: null

    },
    computed: {
    },
    ready: function () {
      var self = this;
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.uploadUrl = $(self.$el).data('uploadUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function () {
        self.resetDialog();
        self.nowLoading = true;

        var data = {
          "command": "import_wowma_lot_number_csv"
        };

        $.ajax({
            type: "GET"
          , url: self.verifyUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {

            if (result.valid) {
              self.message = self.message ? result.message.trim() : null;
              self.messageClass = 'alert alert-success';

              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }

              $('.modal-footer button.btn-primary', self.$el).show();

            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }
            }
          })
          .fail(function (stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
          })
          .always(function () {
            self.nowLoading = false;
          });
      });

      // アップロードフォーム
      $('#uploadWowmaLotNumber').fileinput({
          uploadUrl: self.uploadUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false

        , fileActionSettings: {
            showZoom: false
          , showUpload: false
        }
        // , allowedFileTypes: ['csv', 'text']
        , allowedFileExtensions: ['csv']
      })

        .on('filebatchuploadsuccess', function(event, data, previewId, index) {

          if (data.response && data.response.error) {
            alert(data.response.error);
          } else {
            var message = data.response.message ? data.response.message : '処理を完了しました。';
            self.$data.message = message;
            self.$data.messageClass = 'alert alert-success';
          }

          $('#uploadWowmaLotNumber').fileinput('clear');
        })

    },

    methods: {

      resetDialog: function () {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });



  // 機能画面 ロケーション更新処理 モーダル
  var vmRefreshLocationModal = new Vue({
    el: '#modalRefreshLocation',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "refresh_location"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });


  // 機能画面 ロケーション並べ替え処理 モーダル
  var vmMoveWarehouseLocationToLastModal = new Vue({
    el: '#modalSortLocationOrder',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "sort_location_order"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {};
        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });



  // 機能画面 伝票毎利益再集計 モーダル
  var vmAggregateSalesDetailModal = new Vue({
    el: '#modalAggregateSalesDetail',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , setting: []
    },
    computed: {
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');

      $(self.$el).on('show.bs.modal', function() {
        self.loadData();
      });

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "aggregate_sales_detail"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      loadData: function() {
        var self = this;

        $.ajax({
          type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
          , data: {}
        })
          .done(function(result) {

            if (result.status === 'ok') {
              self.setting = result.setting;
              $('.modal-footer button.btn-primary', self.$el).show();
            } else {
              self.message = result.message.length > 0
                ? result.message
                : '設定値を取得できませんでした。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();

            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();
          })
          . always(function() {
          });
      },
      onSubmit: function(onlySave = false) {
        var self = this;
        const type = self.setting.type;
        let months = self.setting.months;

        if ((onlySave || type === 'part') && !months.match(/^0$|^[1-9][0-9]{0,2}$/)) {
          self.$data.message = `指定期間が不正です。半角数値を入力してください。[${months}]`;
          self.$data.messageClass = 'alert alert-danger';
          return;
        }

        // Ajaxでキュー追加
        var data = {
            "type": type
          , "months": months
          , "onlySave": onlySave ? 1 : 0
        };

        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status === 'ok') {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';
            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';
            }

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            if (!onlySave) {
              $('.modal-footer button.btn-primary', self.$el).hide();
              self.noticeHidden = true;
            }
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });


  // 機能画面 NextEngine CSV出力＆アップロード処理 モーダル
  var vmExportCsvNextEngineModal = new Vue({
    el: '#modalExportCsvNextEngine',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , isUploading: false
      , queueUrl: null
      , checkUrl: null
      , verifyUrl: null
      , currentDir: null
      , savetMsgShow: false

      , delDaysFromSalesEndDate: null
      , upload: true
      , doDownload: 1
      , ignorePriceDiff : null
      , createSetProduct : true // 初期値true
      , doUpdateOrderListIncremental : false
    },
    computed: {
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.checkUrl = $(self.$el).data('checkUrl');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      self.currentDir = $(self.$el).data('currentDir');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "export_csv_next_engine"
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {

      onSubmit: function(onlySave = false) {
        var self = this;

        // Ajaxでキュー追加
        var data = {
            'upload' : self.upload ? 1 : 0
          , 'doDownload' : self.doDownload
          , 'delDaysFromSalesEnd' : self.delDaysFromSalesEnd
          , 'ignorePriceDiff' : self.ignorePriceDiff ? 1 : 0
          , 'doUpdateOrderListIncremental' : self.doUpdateOrderListIncremental ? 1 : 0
          , 'createSetProduct' : self.createSetProduct ? 1 : 0
          , 'onlySave': onlySave ? 1 : 0
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              if (onlySave) {
                self.savetMsgShow = true;
                setTimeout(() => { self.savetMsgShow = false }, 1000);

              } else {
                self.$data.message = result.message;
                self.$data.messageClass = 'alert alert-success';

                var notification = new Notification("Plusnao Web System 通知", {
                  tag: 'Plusnao Web System 通知'
                  , body: result.message
                  , iconUrl: appImages.icon.info // Firefox
                  , icon: appImages.icon.info // Chrome
                });
              }
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            if (!onlySave) {
              $('.modal-footer button.btn-primary', self.$el).hide();
              self.noticeHidden = true;
            }
          });
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 機能画面 NextEngineモール商品CSV出力モーダル
  var vmExportMallProductCsvNextEngineModal = new Vue({
    el: '#modalExportMallProductCsvNextEngine',
    data: {
      caption: 'NextEngineモール商品CSV出力'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , downloadUrl: null
      , verifyUrl: null
      , exportUrl: null
      , exportShops: [
        "rakuten",
        "motto",
        "laforest",
        "dolcissimo",
        "gekipla",
        "yahooPlusnao",
        "kawaemon",
        "wowma",
        "ppm"
      ]
      , exportTarget: "onlyDiff"
      , upload: true
    },
    ready: function() {
      var self = this;
      self.downloadUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.exportUrl = $(self.$el).data('exportUrl');

      $(self.$el).on('show.bs.modal', function(e) {
        self.resetDialog();
      });

      const data = {
        queue: "main",
        command: "export_csv_next_engine_mall_product",
      };
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },
    methods: {
      onSubmit: function() {
        const self = this;
        self.notices = [];
        self.noticeHidden = true;
        const shops = self.exportShops;
        const target = self.exportTarget;

        if (shops.length === 0) {
          self.notices.push('出力店舗を選択して下さい');
        }
        if (target.length === 0) {
          self.notices.push('出力対象を選択して下さい');
        }
        if (shops.length === 0 || target.length === 0) {
          self.noticeHidden = false;
          return; // 処理中止
        }

        const data = {
          shops,
          isOnlyDiff: self.exportTarget === "onlyDiff" ? 1 : 0,
          doUpload: self.upload ? 1 : 0,
        };

        $.ajax({
          type: "POST"
          , url: self.exportUrl
          , dataType: "json"
          , data
        })
          .done((result) => {
            if (result.status === 'ok') {
              const message = 'NextEngineモール商品 CSV出力処理をキューに追加しました。';
              self.message = message;
              self.messageClass = 'alert alert-success';
              new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知',
                body: message,
                iconUrl: appImages.icon.info,// Firefox
                icon: appImages.icon.info, // Chrome
              });
            } else {
              self.message = result.message;
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(() => {
            self.message = '処理を開始できませんでした。';
            self.messageClass = 'alert alert-danger';
          })
          .always(() => {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });


  // 機能画面 NextEngine 在庫同期処理 モーダル
  var vmExportCsvNextEngineUpdateStockModal = new Vue({
    el: '#modalExportCsvNextEngineUpdateStock',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null

      , upload: true
      , doDownload : 1
      , lastProcessed: null
      , inventoryCsvFile: {}
    },
    computed: {
        inventoryCsvFileDisabled: function() {
        return this.isExistInventoryCsvFile() ? null: 'disabled';
      }
      , inventoryCsvFileDate: function() {
        return this.isExistInventoryCsvFile() ? ('(' + this.inventoryCsvFile.date + ')') : null;
      }
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();

        self.nowLoading = true;

        var data = {
            "queue": "main"
          , "command": "export_csv_next_engine_update_stock"
        };

        $.ajax({
            type: "GET"
          , url: self.verifyUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {

            if (result.valid) {
              self.message = result.message.trim();
              self.messageClass = 'alert alert-success';

              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }

              self.lastProcessed = result.lastProcessed ? result.lastProcessed : '-';

              if (result.inventoryCsvFile && result.inventoryCsvFile.path) {
                self.$set('inventoryCsvFile', result.inventoryCsvFile);
              }

              $('.modal-footer button.btn-primary', self.$el).show();

            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }
            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
          })
          . always(function() {
            self.nowLoading = false;
          });
      });

    },

    methods: {
      onSubmit: function() {
        var self = this;

        $('.modal-footer button.btn-primary', self.$el).hide();
        self.inventoryCsvFile = {}; // 空にする

        // Ajaxでキュー追加
        var data = {
          'upload' : self.upload ? 1 : 0
          , 'doDownload' : self.doDownload
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            self.noticeHidden = true;
          });
      },

      isExistInventoryCsvFile: function() {
        return this.inventoryCsvFile.path && this.inventoryCsvFile.path.length > 0;
      },

      downloadInventoryCsvFile: function() {
        if (this.isExistInventoryCsvFile()) {
          var $form = $('#downloadCsvFileForm', this.$el);
          $form.submit();
        }
      },

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.lastProcessed = null;
        this.inventoryCsvFile = {};
      }
    }
  });

  // 機能画面 Yahoo CSV出力＆アップロード処理 モーダル
  var vmExportCsvYahooModal = new Vue({
    el: '#modalExportCsvYahoo',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , immediateShippingDate: null
      , updateUrl: null
      , getSettingUrl : null

      , setting: []
      , upload: true // デフォルト値
      , makeDelete: false // デフォルト値
      , deleteDisabled: true // デフォルト値
      , updateStockDisabled: true // デフォルト値

      , exportType: 'stock'

      // vue.js 1.0 以降であれば、チェックボックスには配列をそのままbindできる。0.12の名残
      , exportTarget: {
            "plusnao": true
          , "kawaemon": true
      }
    },
    computed: {
        deleteDisabledAttr: function() { // HTML attr
        return this.deleteDisabled ? 'disabled' : null;
      }
      , deleteDisabledCss: function() {
        return this.deleteDisabled ? 'labelDeleteDisabled' : '';
      }
      , updateStockDisabledAttr: function() { // HTML attr
        return this.updateStockDisabled ? 'disabled' : null;
      }
      , updateStockDisabledCss: function() {
        return this.updateStockDisabled ? 'labelDeleteDisabled' : '';
      }
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');
      this.setting = [];

      self.immediateShippingDate = $(self.$el).find('input[type="date"]').val();

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "export_csv_yahoo"
      };
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        this.setting = [];
        self.loadData();
      });
      var callbacks = {
        "success": function (result) {

          // 即納予定日 更新
          if (result.immediateShippingDate) {
            self.immediateShippingDate = result.immediateShippingDate;
          }

          if (result.yahoo_api_enabled) {
            // API利用可
            self.deleteDisabled = false;
            self.updateStockDisabled = false;
            self.makeDelete = true;

            self.exportType = 'stock'; // デフォルト
          } else {
            // API利用不可
            self.deleteDisabled = true;
            self.updateStockDisabled = true;
            self.makeDelete = false;

            self.exportType = 'all';
          }
        }
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        var exportTarget = [];
        for (var k in self.exportTarget) {
          if (self.exportTarget[k]) {
            exportTarget.push(k);
          }
        }

        // 入力チェック
        if (exportTarget.length == 0) {
          this.notices = ['出力対象を選択して下さい'];
          this.noticeHidden = false;
          return; // 処理中止
        } else {
          this.notices = [];
          this.noticeHidden = true;
        }

        // Ajaxでキュー追加
        var data = {
            'upload'        : self.upload ? 1 : 0
          , 'make_delete'   : self.makeDelete ? 1 : 0
          , 'export_target' : exportTarget
          , 'export_type'   : self.exportType

          , 'immediate_shipping_date': self.immediateShippingDate
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                  tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },
      loadData: function() {
        var self = this;

        $.ajax({
          type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
          , data: {}
        })
            .done(function(result) {

              if (result.status === 'ok') {
                self.setting = result.setting;
                $('.modal-footer button.btn-primary', self.$el).show();
              } else {
                self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
                self.messageClass = 'alert alert-danger';
                $('.modal-footer button.btn-primary', self.$el).hide();

              }
            })
            .fail(function(stat) {
              self.message = 'エラーが発生しました。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();
            })
            . always(function() {
            });
      },
      onUpdate: function () {
        var self = this;

        var data = {
          setting: self.setting
        };
        console.log(self.setting);

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.message = '設定を保存しました。';
                self.messageClass = 'alert alert-success';
              } else {
                self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
                self.messageClass = 'alert alert-danger';
              }

            })
            .fail(function(stat) {
              self.$data.message = '設定が保存できませんでした。';
              self.$data.messageClass = 'alert alert-danger';
            })
            .always(function() {
              $('.modal-footer button.btn-primary', self.$el).show();
              self.noticeHidden = true;
            });
      },

      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.deleteDisabled = true;
      },

      closeModal: function() {
        $(this.$el).modal('hide');
      }
    }
  });

  // 機能画面 YahooおとりよせCSV出力＆アップロード処理 モーダル
  var vmExportCsvYahooOtoriyoseModal = new Vue({
    el: '#modalExportCsvYahooOtoriyose',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , updateUrl: null

      , setting: []
      , upload: true // デフォルト値
      , doCommonProcess: true // デフォルト値
      , deleteDisabled: true // デフォルト値
      , authUrl: null
    },
    computed: {},
    ready: function () {
      var self = this;
      var self = this;
      self.queueUrl = $(self.$el).data('queueUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      self.authUrl = null;
      self.deleteDisabled = true;

      // イベント登録
      var data = {
        "queue": "main"
        , "command": "export_csv_yahoo_otoriyose"
      };
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        this.setting = [];
        self.loadData();
      });
      var callbacks = {
        "success": function (result) {

          if (result.agents['otoriyose']) {
            self.authUrl = result.agents['otoriyose'].authUrl;
            self.deleteDisabled = !(result.agents['otoriyose'].isApiEnabled);
          }
        }
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
    },

    methods: {
      loadData: function() {
        var self = this;

        $.ajax({
          type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
          , data: {}
        })
            .done(function(result) {

              if (result.status === 'ok') {
                self.setting = result.setting;
                $('.modal-footer button.btn-primary', self.$el).show();
              } else {
                self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
                self.messageClass = 'alert alert-danger';
                $('.modal-footer button.btn-primary', self.$el).hide();

              }
            })
            .fail(function(stat) {
              self.message = 'エラーが発生しました。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();
            })
            . always(function() {
            });
      },
      onSubmit: function () {
        var self = this;

        var data = {
          setting: self.setting
        };
        console.log(self.setting);

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.message = '設定を保存しました。';
                self.messageClass = 'alert alert-success';
              } else {
                self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
                self.messageClass = 'alert alert-danger';
              }

            })
            .fail(function(stat) {
              self.$data.message = '設定が保存できませんでした。';
              self.$data.messageClass = 'alert alert-danger';
            })
            .always(function() {
              $('.modal-footer button.btn-primary', self.$el).show();
              self.noticeHidden = true;
            });
      },
      runConfirm: function(setting) {
        var self = this;

        // APIが利用できなくとも出力できるようにする
        //if (self.deleteDisabled) {
        //  alert('APIの認証がされていません。');
        //  return;
        //}

        // Ajaxでキュー追加
        var data = {
          upload: self.upload ? 1 : 0
          , download: self.deleteDisabled ? 0 : 1
          , doCommonProcess: self.doCommonProcess ? 1 : 0
        };

        $.ajax({
          type: "POST"
          , url: self.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.error) {
              self.message = result.message;
              self.messageClass = 'alert alert-danger';

            } else {
              self.message = result.message;
              self.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function (stat) {
            self.message = '処理を開始できませんでした。';
            self.messageClass = 'alert alert-danger';
          })
          .always(function () {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function () {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.deleteDisabled = true;
      },

      closeModal: function () {
        $(this.$el).modal('hide');
      }
    }
  });


  // 機能画面 Amazon CSV出力＆アップロード処理 モーダル
  var vmExportCsvAmazonModal = new Vue({
    el: '#modalExportCsvAmazon',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , immediateShippingDate: null

      , exportTarget: 'stocks' // デフォルト値
      , upload: true // デフォルト値
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      self.immediateShippingDate = $(self.$el).find('input[type="date"]').val();

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "export_csv_amazon"
      };
      var callbacks = {
        "success": function (result) {

          // 即納予定日 更新
          if (result.immediateShippingDate) {
            self.immediateShippingDate = result.immediateShippingDate;
          }
        }
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {
            'upload' : self.upload ? 1 : 0
          , 'export_target': self.exportTarget
          , 'immediate_shipping_date': self.immediateShippingDate
        };

        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      closeModal: function() {
        $(this.$el).modal('hide');
      }
    }
  });


  // 2018/01/29 Amazon.com 販売休止
  //// 機能画面 Amazon.com CSV出力＆アップロード処理 モーダル
  //var vmExportCsvAmazonComModal = new Vue({
  //  el: '#modalExportCsvAmazonCom',
  //  data: {
  //      caption: '処理確認'
  //    , message: ''
  //    , messageClass: 'alert'
  //    , notices: []
  //    , noticeClass: 'alert alert-warning'
  //    , noticeHidden: true
  //    , queueUrl: null
  //    , verifyUrl: null
  //
  //    , exportTarget: 'stocks' // デフォルト値
  //    , upload: true // デフォルト値
  //  },
  //  ready: function() {
  //    var self = this;
  //    self.queueUrl = $(self.$el).data('url');
  //    self.verifyUrl = $(self.$el).data('verifyUrl');
  //
  //    // イベント登録
  //    var data = {
  //        "queue": "main"
  //      , "command": "export_csv_amazon_com"
  //    };
  //    var callbacks = {
  //      "success": function (result) {
  //      }
  //    };
  //
  //    $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
  //  },
  //
  //  methods: {
  //    onSubmit: function() {
  //      var self = this;
  //
  //      // Ajaxでキュー追加
  //      var data = {
  //          'upload' : self.upload ? 1 : 0
  //        , 'export_target': self.exportTarget
  //      };
  //
  //      $.ajax({
  //          type: "POST"
  //        , url: self.$data.queueUrl
  //        , dataType: "json"
  //        , data: data
  //      })
  //        .done(function(result) {
  //          if (result.error) {
  //            self.$data.message = result.message;
  //            self.$data.messageClass = 'alert alert-danger';
  //
  //          } else {
  //            self.$data.message = result.message;
  //            self.$data.messageClass = 'alert alert-success';
  //
  //            var notification = new Notification("Plusnao Web System 通知", {
  //              tag: 'Plusnao Web System 通知'
  //              , body: result.message
  //              , iconUrl: appImages.icon.info // Firefox
  //              , icon: appImages.icon.info // Chrome
  //            });
  //          }
  //        })
  //        .fail(function(stat) {
  //          self.$data.message = '処理を開始できませんでした。';
  //          self.$data.messageClass = 'alert alert-danger';
  //        })
  //        .always(function() {
  //          $('.modal-footer button.btn-primary', self.$el).hide();
  //          self.noticeHidden = true;
  //        });
  //    },
  //
  //    resetDialog: function() {
  //      this.message = '';
  //      this.messageClass = '';
  //      this.notices = [];
  //      this.noticeHidden = true;
  //      $('.modal-footer button.btn-primary', self.$el).hide();
  //    },
  //
  //    closeModal: function() {
  //      $(this.$el).modal('hide');
  //    }
  //  }
  //});


  // 機能画面 SHOPLIST CSV出力＆アップロード処理 モーダル
  var vmExportCsvShoplistModal = new Vue({
    el: '#modalExportCsvShoplist',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null

      , exportTarget: 'stocks' // デフォルト値
      , upload: true // デフォルト値
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
          "queue": "main"
        , "command": "export_csv_shoplist"
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {
            'upload' : self.upload ? 1 : 0
          , 'export_target': self.exportTarget
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      closeModal: function() {
        $(this.$el).modal('hide');
      }
    }
  });

  // 機能画面 楽天 CSV出力＆アップロード処理 モーダル
  const vmExportCsvRakutenModal = new Vue({
    el: '#modalExportCsvRakuten',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , updateUrl: null
      , getSettingUrl: null
      , immediateShippingDate: null

      , setting: []
      , upload: true // デフォルト値
      // vue.js 1.0 以降であれば、チェックボックスには配列をそのままbindできる。0.12の名残
      , exportTarget: {
            "rakutenPlusnao": true
          , "rakutenMotto": true
          , "rakutenLaforest": true
          , "rakutenDolcissimo": true
          , "rakutenGekipla": true
      }
    },
    ready: function() {
      const self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');
      this.setting = [];

      self.immediateShippingDate = $(self.$el).find('input[type="date"]').val();

      // イベント登録
      const data = {
          "queue": "main"
        , "command": "export_csv_rakuten"
      };

      $(self.$el).on('show.bs.modal', () => {
        self.resetDialog();
        this.setting = [];
        self.loadData();
      });

      const callbacks = {
        "success": function (result) {
          // 即納予定日 更新
          if (result.immediateShippingDate) {
            self.immediateShippingDate = result.immediateShippingDate;
          }
        }
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        var exportTarget = [];
        for (var k in self.exportTarget) {
          if (self.exportTarget[k]) {
            exportTarget.push(k);
          }
        }
        // 入力チェック
        if (exportTarget.length == 0) {
          this.notices = ['出力対象を選択して下さい'];
          this.noticeHidden = false;
          return; // 処理中止
        } else {
          this.notices = [];
          this.noticeHidden = true;
        }

        // Ajaxでキュー追加
        const data = {
            'upload' : self.upload ? 1 : 0
          , 'export_target' : exportTarget
          , 'immediate_shipping_date': self.immediateShippingDate
        };

        $.ajax({
            type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                  tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },
      loadData: function() {
        const self = this;

        $.ajax({
          type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
          , data: {
          }
        })
          .done(function(result) {
            if (result.status === 'ok') {
              self.setting = result.setting;
              $('.modal-footer button.btn-primary', self.$el).show();
            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();

            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();
          })
      },
      onUpdate: function () {
        const self = this;

        const data = {
          setting: self.setting
        };

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status === 'ok') {
              self.message = '設定を保存しました。';
              self.messageClass = 'alert alert-success';
            } else {
              self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
              self.messageClass = 'alert alert-danger';
            }

          })
          .fail(function(stat) {
            self.$data.message = '設定が保存できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).show();
            self.noticeHidden = true;
          });
      },
      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      closeModal: function() {
        $(this.$el).modal('hide');
      }
    }
  });


  // 機能画面 楽天 在庫更新CSV出力＆アップロード処理 モーダル
  var vmExportCsvRakutenUpdateStockModal = new Vue({
    el: '#modalExportCsvRakutenUpdateStock',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null

      , upload: true // デフォルト値
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
        "queue": "main"
        , "command": "export_csv_rakuten_update_stock"
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data));
    },

    methods: {
      onSubmit: function() {
        var self = this;

        // Ajaxでキュー追加
        var data = {
          'upload' : self.upload ? 1 : 0
        };

        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      closeModal: function() {
        $(this.$el).modal('hide');
      }
    }
  });

  // 機能画面 楽天 RPP除外設定モーダル
  var vmExportCsvRakutenRppExcludeModal = new Vue({
    el: '#modalExportCsvRakutenRppExclude',
    data: {
      caption: '楽天RPP除外設定'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null

      , targetDataList: ""
      , upload: true // デフォルト値
    },
    ready: function() {
      var self = this;
      self.exportUrl = $(self.$el).data('exportUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getListUrl = $(self.$el).data('getListUrl');
      self.verifyUrl = $(self.$el).data('verifyUrl');

      // イベント登録
      var data = {
        "queue": "main"
        , "command": "export_csv_rakuten_rpp_exclude"
      };
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        this.targetDataList = [];
      });

      var callbacks = {
        "success": function (result) {
          self.loadData();
        }
      };

      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
    },

    methods: {
      loadData: function() {
        var self = this;

        $.ajax({
          type: "GET"
          , url: self.getListUrl
          , dataType: "json"
          , data: {}
        })
        .done(function(result) {
          if (result.status === 'ok') {
            self.targetDataList = result.data;
            self.message = result.message;
            self.messageClass = 'alert alert-success';
            $('.modal-footer button.btn-primary', self.$el).show();
            $('.modal-footer button.btn-warning', self.$el).show();
          } else {
            self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();

          }
        })
        .fail(function(stat) {
          self.message = 'エラーが発生しました。';
          self.messageClass = 'alert alert-danger';
          $('.modal-footer button.btn-primary', self.$el).hide();
        })
        . always(function() {
        });
      },
      /** 保存のみ */
      onSubmit: function() {
        var self = this;
        var data = {
          'target' : self.targetDataList
        };

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status === 'ng') {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';
            }
          })
          .fail(function(stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          });
      },

      // 保存して即時反映
      runConfirm: function() {
        var self = this;
        var data = {
            'target' : self.targetDataList
          };

        $.ajax({
          type: "POST"
          , url: self.exportUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.status === 'ng') {
              self.message = result.message;
              self.messageClass = 'alert alert-danger';

            } else {
              self.message = result.message;
              self.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function (stat) {
            self.message = '処理を開始できませんでした。';
            self.messageClass = 'alert alert-danger';
          })
          .always(function () {
            $('.modal-footer button.btn-primary', self.$el).hide();
            $('.modal-footer button.btn-warning', self.$el).hide();
            self.noticeHidden = true;
          });
      },

      resetDialog: function() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();
        $('.modal-footer button.btn-warning', self.$el).hide();
      },

      closeModal: function() {
        $(this.$el).modal('hide');
      }
    }
  });

  const vmExportCsvRakutenGoldModal = new Vue({
    el: '#modalExportCsvRakutenGold',
    data: {
      caption: '楽天GOLD CSV出力',
      message: '',
       messageClass: 'alert',
      notices: [],
      noticeClass: 'alert alert-warning',
      noticeHidden: true,
      verifyUrl: null,
      getSettingUrl: null,
      updateSettingUrl: null,
      exportUrl: null,
      exportShop: ["rakuten", "motto", "laforest", "dolcissimo", "gekipla"],
      exportCsv: ["category", "ranking", "petitprice"],
      upload: true,
      setting: {},
    },
    ready() {
      const self = this;
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');
      self.updateSettingUrl = $(self.$el).data('updateSettingUrl');
      self.exportUrl = $(self.$el).data('exportUrl');

      $(self.$el).on('show.bs.modal', () => {
        self.resetDialog();
        this.setting = [];
        self.loadData();
      });

      const data = {
        queue: "main",
        command: "export_csv_rakuten_gold",
      };
      const callbacks = {success: (result) => {self.loadData()}};
      $(self.$el).on('show.bs.modal', generateVerifyCallback(this, self.verifyUrl, data, callbacks));
    },
    methods: {
      resetDialog() {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-warning', self.$el).hide();
        $('.modal-footer p.text-right', self.$el).hide();
      },
      loadData() {
        const self = this;
        $.ajax({
          type: "GET",
          url: self.getSettingUrl,
          dataType: "json",
        })
          .done((result) => {
            if (result.status === 'ok') {
              self.setting = result.setting;
              $('.modal-footer button.btn-warning', self.$el).show();
              $('.modal-footer p.text-right', self.$el).show();
            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(() => {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
          })
      },
      onUpdate() {
        const self = this;
        const data = {
          setting: self.setting
        };
        $.ajax({
          type: "POST",
          url: self.updateSettingUrl,
          dataType: "json",
          data,
        })
          .done((result) => {
            if (result.status === 'ok') {
              self.message = '設定を保存しました。';
              self.messageClass = 'alert alert-success';
            } else {
              self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
              self.messageClass = 'alert alert-danger';
            }

          })
          .fail(() => {
            self.$data.message = '設定が保存できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(() => {
            $('.modal-footer button.btn-warning', self.$el).show();
            $('.modal-footer p.text-right', self.$el).show();
            self.noticeHidden = true;
          });
      },
      onSubmit() {
        const self = this;
        self.notices = [];
        self.noticeHidden = true;
        const shop = self.exportShop;
        const csv = self.exportCsv;

        if (shop.length === 0) {
          self.notices.push('出力店舗を選択して下さい');
        }
        if (csv.length === 0) {
          self.notices.push('出力CSVを選択して下さい');
        }
        if (shop.length === 0 || csv.length === 0) {
          self.noticeHidden = false;
          return; // 処理中止
        }

        const data = {
          shop,
          csv,
          upload: self.upload ? 1 : 0,
          setting: self.setting,
        };

        $.ajax({
          type: "POST"
          , url: self.exportUrl
          , dataType: "json"
          , data
        })
          .done((result) => {
            if (result.status === 'ok') {
              const message = '楽天GOLD CSV出力処理をキューに追加しました。';
              self.message = message;
              self.messageClass = 'alert alert-success';
              new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知',
                body: message,
                iconUrl: appImages.icon.info,// Firefox
                icon: appImages.icon.info, // Chrome
              });
            } else {
              self.message = result.message;
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(() => {
            self.message = '処理を開始できませんでした。';
            self.messageClass = 'alert alert-danger';
          })
          .always(() => {
            $('.modal-footer button.btn-warning', self.$el).hide();
            $('.modal-footer p.text-right', self.$el).hide();
            self.noticeHidden = true;
          });
      }
    }
  });

  // 機能画面 Amazon FBA出荷用CSV出力 モーダル
  var vmExportCsvAmazonFbaOrderModal = new Vue({
    el: '#modalExportCsvAmazonFbaOrder',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , downloadUrl: null

      , csvFiles: []
    },
    computed: {
      isExistCsvFile: function () {
        return this.csvFiles.length > 0;
      }
    },
    ready: function () {
      var self = this;
      self.queueUrl = $(self.$el).data('url');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.downloadUrl = $(self.$el).data('downloadUrl');
      self.uploadUrl = $(self.$el).data('uploadUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function () {
        self.resetDialog();

        self.nowLoading = true;

        var data = {
          "command": "export_csv_amazon_fba_order"
        };

        $.ajax({
            type: "GET"
          , url: self.verifyUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {

            if (result.valid) {
              self.message = result.message.trim();
              self.messageClass = 'alert alert-success';

              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }

              if (result.csvFiles.length > 0) {
                self.$set('csvFiles', result.csvFiles);
              }

              $('.modal-footer button.btn-primary', self.$el).show();

            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }
            }
          })
          .fail(function (stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
          })
          .always(function () {
            self.nowLoading = false;
          });
      });

      // アップロードフォーム
      $('#amazonFbaOrderCsvUpload').fileinput({
          uploadUrl: self.uploadUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false

        , fileActionSettings: {
          showZoom: false
          , showUpload: false
        }
        // , allowedFileTypes: ['csv', 'text']
        , allowedFileExtensions: ['csv']
      })

        .on('filebatchuploadsuccess', function(event, data, previewId, index) {

          if (data.response && data.response.error) {
            alert(data.response.error);
          } else {

            var message = data.response.message ? data.response.message : 'Amazon FBA出荷用CSV出力処理をキューに追加しました。';
            self.$data.message = message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });

            //
            //$(self.$el).modal().hide(function() {
            //  $(self.$el).modal().show(function() {
            //
            //    var message = data.response.message ? data.response.message : 'Amazon FBA出荷用CSV出力処理をキューに追加しました。';
            //    self.$data.message = message;
            //    self.$data.messageClass = 'alert alert-success';
            //
            //    var notification = new Notification("Plusnao Web System 通知", {
            //        tag: 'Plusnao Web System 通知'
            //      , body: message
            //      , iconUrl: appImages.icon.info // Firefox
            //      , icon: appImages.icon.info // Chrome
            //    });
            //
            //  });
            //});

          }

          $('#amazonFbaOrderCsvUpload').fileinput('clear');
        })

    },

    methods: {
      //onSubmit: function () {
      //  var self = this;
      //
      //  $('.modal-footer button.btn-primary', self.$el).hide();
      //
      //  // Ajaxでキュー追加
      //  var data = {};
      //
      //  $.ajax({
      //    type: "POST"
      //    , url: self.$data.queueUrl
      //    , dataType: "json"
      //    , data: data
      //  })
      //    .done(function (result) {
      //      if (result.error) {
      //        self.$data.message = result.message;
      //        self.$data.messageClass = 'alert alert-danger';
      //
      //      } else {
      //        self.$data.message = result.message;
      //        self.$data.messageClass = 'alert alert-success';
      //
      //        var notification = new Notification("Plusnao Web System 通知", {
      //          tag: 'Plusnao Web System 通知'
      //          , body: result.message
      //          , iconUrl: appImages.icon.info // Firefox
      //          , icon: appImages.icon.info // Chrome
      //        });
      //      }
      //    })
      //    .fail(function (stat) {
      //      self.$data.message = '処理を開始できませんでした。';
      //      self.$data.messageClass = 'alert alert-danger';
      //    })
      //    .always(function () {
      //      self.noticeHidden = true;
      //    });
      //},

      getDownloadUrl: function (file) {
        return this.downloadUrl + "?name=" + file.name
      },

      resetDialog: function () {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.csvFiles = [];
      }
    }
  });

  // 機能画面 Amazon S&L出荷用CSV出力 モーダル
  var vmExportCsvAmazonSnlOrderModal = new Vue({
    el: '#modalExportCsvAmazonSnlOrder',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true

      , verifyUrl: null
      , downloadUrl: null
      , uploadStockUrl: null

      , csvFiles: []
    },
    computed: {
      isExistCsvFile: function () {
        return this.csvFiles.length > 0;
      }
    },
    ready: function () {
      var self = this;
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.downloadUrl = $(self.$el).data('downloadUrl');
      self.uploadStockUrl = $(self.$el).data('uploadStockUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', self.getInitFunction());

      // アップロードフォーム
      $('#amazonSnlOrderCsvUpload').fileinput({
          uploadUrl: self.uploadStockUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false

        , fileActionSettings: {
          showZoom: false
          , showUpload: false
        }
        // , allowedFileTypes: ['csv', 'text']
        , allowedFileExtensions: ['csv', 'txt']
      })

        .on('filebatchuploadsuccess', function(event, data, previewId, index) {

          if (data.response && data.response.error) {
            alert(data.response.error);

          } else {
            // var message = data.response.message ? data.response.message : 'Amazon S&L出荷用CSV出力処理をキューに追加しました。';
            var message = data.response.message ? data.response.message : 'Amazon S&L出荷用CSVを作成しました。';

            (self.getInitFunction())({ message: message, messageClass: 'alert alert-success' });
          }

          $('#amazonSnlOrderCsvUpload').fileinput('clear');
        })

    },

    methods: {
      getDownloadUrl: function (dirName, fileName) {
        return this.downloadUrl + "?dir=" + dirName + "&name=" + fileName
      },

      resetDialog: function () {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.csvFiles = [];
      }

      , getInitFunction: function() {
        var self = this;

        return function(options) {
          self.resetDialog();

          self.nowLoading = true;

          var data = {
            "command": "export_csv_amazon_snl_order"
          };

          $.ajax({
              type: "GET"
            , url: self.verifyUrl
            , dataType: "json"
            , data: data
          })
            .done(function (result) {

              if (result.valid) {
                if (result.message && result.message.length > 0) {
                  self.message = result.message.trim();
                  self.messageClass = 'alert alert-success';
                }

                if (result.notices.length > 0) {
                  self.notices = result.notices;
                  self.noticeHidden = false;
                }

                if (result.csvFiles.length > 0) {
                  self.$set('csvFiles', result.csvFiles);
                }

                $('.modal-footer button.btn-primary', self.$el).show();

                if (options && options.message) {
                  self.message = options.message;
                  self.messageClass = options.messageClass;
                }


              } else {
                self.message = result.message.length > 0 ? result.message : '初期処理に失敗しました。';
                self.messageClass = 'alert alert-danger';
                if (result.notices.length > 0) {
                  self.notices = result.notices;
                  self.noticeHidden = false;
                }
              }
            })
            .fail(function (stat) {
              self.message = 'エラーが発生しました。';
              self.messageClass = 'alert alert-danger';
            })
            .always(function () {
              self.nowLoading = false;
            });
        }
      }
    }
  });

  // 機能画面 SHOPLIST スピード便出荷用CSV出力 モーダル
  var vmExportCsvShoplistSpeedBinOrderModal = new Vue({
    el: '#modalExportCsvShoplistSpeedBinOrder',
    data: {
        caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , downloadUrl: null
      , uploadUrl: null

      , keepStock: null
      , dataList: []
      , shippingDate: null
    },
    computed: {
      isExistCsvFile: function () {
        return this.csvFiles.length > 0;
      }
    },
    ready: function () {
      var self = this;
      self.queueUrl = $(self.$el).data('queueUrl');
      self.verifyUrl = $(self.$el).data('verifyUrl');
      self.downloadUrl = $(self.$el).data('downloadUrl');
      self.uploadUrl = $(self.$el).data('uploadUrl');

      $('#shoplistSpeedBinShippingDate').datepicker({
        language: 'ja'
        , format: 'yyyy-mm-dd'
      });

      // イベント登録
      $(self.$el).on('show.bs.modal', function () {
        self.resetDialog();

        self.nowLoading = true;

        var data = {
          "command": "aggregate_shoplist_speedbin_delivery"
        };

        $.ajax({
            type: "GET"
          , url: self.verifyUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {

            if (result.valid) {
              if (result.message && result.message.length > 0) {
                self.message = result.message.trim();
                self.messageClass = 'alert alert-success';
              }

              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }

              if (result.dataList.length > 0) {
                self.$set('dataList', result.dataList);
              }
              self.keepStock = result.keepStock

              $('.modal-footer button.btn-primary', self.$el).show();

            } else {
              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              if (result.notices.length > 0) {
                self.notices = result.notices;
                self.noticeHidden = false;
              }
            }
          })
          .fail(function (stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
          })
          .always(function () {
            self.nowLoading = false;
          });
      });

    },

    methods: {
      onSubmit: function () {
        var self = this;

        $('.modal-footer button.btn-primary', self.$el).hide();

        // Ajaxでキュー追加
        var data = {
          'keepStock' : self.keepStock
        };

        $.ajax({
          type: "POST"
          , url: self.$data.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function (result) {
            if (result.error) {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-danger';

            } else {
              self.$data.message = result.message;
              self.$data.messageClass = 'alert alert-success';

              var notification = new Notification("Plusnao Web System 通知", {
                tag: 'Plusnao Web System 通知'
                , body: result.message
                , iconUrl: appImages.icon.info // Firefox
                , icon: appImages.icon.info // Chrome
              });
            }
          })
          .fail(function (stat) {
            self.$data.message = '処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function () {
            self.noticeHidden = true;
          });
      },

      getDownloadUrl: function (id, type, shippingDate) {
        return this.downloadUrl + "?id=" + id + "&type=" + type + "&shippingDate=" + shippingDate
      },
      
      uploadAcceptCsv: function (id) {
        let self = this;
        let $input = $(self.$el).find('#shoplistSpeedbinAcceptCsv');
        let files = $input.get(0).files;
        
        if (!files.length) {
          self.notices = ['アップロードするファイルが選択されていません。'];
          self.noticeHidden = false;
          return;
        }
        
        let file = files[0];
        if (!file.name.match(/\.csv$/)) {
          this.notices = ['ファイルの拡張子が .csv ではありません。'];
          this.noticeHidden = false;
          return;
        }

        let formData = new FormData();
        formData.append($input.attr('name'), file);
        formData.append('id', id);
        $.ajax({
          type: 'POST',
          timeout: 30000,
          url: self.uploadUrl,
          dataType: 'json',
          processData: false,
          contentType: false,
          data: formData
        }).done(function(result) {
          if (result.status == 'ok') {
            self.$data.message = 'データを更新しました';
            self.$data.messageClass = 'alert alert-success';
          } else {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-danger';
          }
        }).fail(function (stat) {
          self.$data.message = '処理を開始できませんでした。';
          self.$data.messageClass = 'alert alert-danger';
        })
        .always(function () {
          self.noticeHidden = true;
        });
        
      },

      resetDialog: function () {
        this.$data.message = '';
        this.$data.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('.modal-footer button.btn-primary', self.$el).hide();

        this.csvFiles = [];
      }
    }
  });


  // 機能画面 おとりよせ購買データ取り込み モーダル
  const vmImportCsvOtoriyosePurchaseData = new Vue({
    el: '#modalImportCsvOtoriyosePurchaseData',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true

      , uploadUrl: ''
    },
    ready: function () {
      const self = this;
      self.uploadUrl = $(self.$el).data('uploadUrl');

      $(self.$el).on('show.bs.modal', function () {
        self.resetDialog();
      });

      // アップロードフォーム
      $('#uploadOtoriyosePurchaseCsv').fileinput({
          uploadUrl: self.uploadUrl
        , language: 'ja'
        , showPreview: true
        , uploadAsync: false
        // この方法でしか動的な変更ができない
        , uploadExtraData: function () {
          return { forceUpload: $('#forceUpload').val() }
        }

        , fileActionSettings: {
            showZoom: false
          , showUpload: false
        }
        , allowedFileExtensions: ['csv']
      })
        .on('fileremoved', function () {
          $('#forceUpload').val(false);
        })
        .on('filecleared', function() {
          $('#forceUpload').val(false);
        })
        .on('filebatchuploaderror', function(event, data) {
          if (data.response && data.response.forcible) {
            if (confirm(data.response.error + data.response.message)) {
              $('#forceUpload').val(true);
              self.message = 'ファイルの内容で上書きします。再度アップロードボタンを押してください。';
              self.messageClass = 'alert alert-warning';
            }
          }
        })
        .on('filebatchuploadsuccess', function(event, data, previewID, index) {
          $('#forceUpload').val(false);
          self.message = data.response.message ? data.response.message : '処理を完了しました。';
          self.messageClass = 'alert alert-success';

          $('#uploadOtoriyosePurchaseCsv').fileinput('clear');
        })
    },
    methods: {
      resetDialog: function () {
        this.message = '';
        this.messageClass = '';
        this.notices = [];
        this.noticeHidden = true;
        $('#forceUpload').val(false);
        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  })


  var vmExportDeleteImagesCsvPPMModal = new Vue({
    el: '#modalExportDeleteImagesCsvPPM',
    data: {
      caption: '処理確認'
      , message: ''
      , messageClass: 'alert alert-info'

      , nowLoading: true

      , isDisabled: true
      , isNotDownload: true
      , updateUrl: ''
    },
    ready: function(){
      const self = this;

      self.updateUrl = $(self.$el).data('updateUrl');

      self.message = 'ポンパレモールの未販売商品画像削除CSVをダウンロードします。ダウンロード終了後、CSVをポンパレモールにアップロードしてください。';
      const now = new Date();
      const nowhour = now.getHours();
      if(nowhour === 23 || nowhour === 0){
        $('#ppm-del-img-csv').hide();
        self.message = '23時・0時はCSVファイル更新のためダウンロードできません。';
        self.messageClass = 'alert alert-success';
      }
      self.nowLoading = false;
    },
    methods:{
      toggleStatus: function(){
        const self = this;
        self.message = 'CSVをポンパレモールにアップロード終了後、「アップロード済み」を押してください。';
        self.isNotDownload = false;

        // 連打防止のため数秒後に非活性解除
        setTimeout(function(){
          self.isDisabled = false;
        }, 5000);
      },
      updateImageStatus: function(){
        const self = this;
        self.nowLoading = true;

        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          })
          .done(function(result) {
            if (result.status == 'ng') {
              self.messageClass = 'alert alert-warning';
              self.message = result.message;
            } else {
              self.message = 'テーブルの更新を終了しました。';
              self.messageClass = 'alert alert-success';
            }
          })
          .always(function() {
            $('#update-status').hide();
            self.nowLoading = false;
          });
      }
    }
  });



  // 仕入先アドレス 検索モーダル
  var vmSearchVendorAddress = new Vue({
    el: '#modalSearchVendorAddress',
    data: {
        caption: '仕入先アドレス検索'
      , message: ''
      , messageClass: 'alert'
      , nowLoading: false

      , url: null
      , searchVendorAddress: ''
      , addresses: []
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');

      // open時処理登録
      $(self.$el).on('show.bs.modal', self.resetDialog);
    },

    methods: {
      search: function() {
        var self = this;
        self.nowLoading = true;

        // Ajaxでキュー追加
        var data = {
          'address': this.searchVendorAddress
        };
        $.ajax({
            type: "POST"
          , url: self.$data.url
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;

            if (result.status == 'ng') {
              self.$data.messageClass = 'alert alert-warning';
            } else {
              self.$data.messageClass = 'alert alert-success';
            }

            self.$data.addresses = result.results;
          })
          .fail(function(stat) {
            self.$data.message = '検索に失敗しました。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;

            self.nowLoading = false;
          });
      }

      , keyPress: function($event) {
        if ($event.which === 13) {
          return this.search();
        } else {
          return false;
        }
      }

      , resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        $('.modal-footer button.btn-primary', self.$el).hide();
        this.nowLoading = false;

        this.searchVendorAddress = null;
        this.addresses = [];
      }
    }
  });

  // 楽天納期管理番号 モーダル
  var vmRakutenNokiKanri = new Vue({
    el: '#modalRakutenNokiKanri',
    data: {
        caption: '楽天納期管理番号'
      , message: ''
      , messageClass: 'alert'
      , nowLoading: false

      , url: null
      , numbers: []
      , lastUpdated: null
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');

      // open時処理登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();
        self.loadData();
      });
    },

    methods: {
      loadData: function() {
        var self = this;
        self.nowLoading = true;

        // Ajaxでキュー追加
        var data = {
        };
        $.ajax({
            type: "GET"
          , url: self.$data.url
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.message = result.message;

            if (result.status == 'ng') {
              self.messageClass = 'alert alert-warning';
            } else {
              self.messageClass = 'alert alert-success';
            }

            self.numbers = result.results;
            self.lastUpdated = result.lastUpdated;

          })
          .fail(function(stat) {
            self.message = '取得に失敗しました。';
            self.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;

            self.nowLoading = false;
          });
      }

      , resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        $('.modal-footer button.btn-primary', self.$el).hide();
        this.nowLoading = false;

        this.numbers = [];
      }

      , selectAll: function(event) {
        event.target.select();
      }
    }
  });


  // 依頼先（仕入先） 注残一覧モーダル
  var vmVendorOrderList = new Vue({
    el: '#modalVendorOrderList',
    data: {
        caption: '依頼先別 注残一覧画面'
      , message: ''
      , messageClass: 'alert'
      , nowLoading: false

      , searchVendorKeyword: null
      , vendors: []
    },
    ready: function() {
      var self = this;
      self.url = $(self.$el).data('url');

      // open時処理登録
      $(self.$el).on('show.bs.modal', function() {

        self.resetDialog();
        self.nowLoading = true;

        $.ajax({
            type: "GET"
          , url: self.url
          , dataType: "json"
          , data: {}
        })
          .done(function(result) {

            if (result.status == 'ok') {

              self.vendors = result.results;

            } else {
              self.message = result.message.length > 0 ? result.message : '依頼先一覧の取得に失敗しました。';
              self.messageClass = 'alert alert-danger';
            }
          })
          .fail(function(stat) {
            self.message = '依頼先一覧の取得ができませんでした。';
            self.messageClass = 'alert alert-danger';
          })
          . always(function() {

            self.nowLoading = false;
          });
      });
    },

    methods: {

      resetDialog: function() {
        this.$data.message = '';
        this.$data.messageClass = '';
        $('.modal-footer button.btn-primary', self.$el).hide();
        this.nowLoading = false;

        this.searchVendorAddress = null;
        this.vendors = [];
      }
    }
  });


  // 外部倉庫在庫取得設定 モーダル
  var vmExternalWarehouseStockFetchSettingModal = new Vue({
    el: '#modalExternalWarehouseStockFetchSetting',
    data: {
        caption: '外部倉庫在庫取得設定'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , updateUrl: null

      , settings: []
      , mode: 'list'
      , runProcessSetting: null
    },
    computed: {
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('queueUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();

        this.settings = [];
        self.loadData();
      });
    },

    methods: {
      loadData: function() {
        var self = this;

        $.ajax({
            type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
          , data: {}
        })
          .done(function(result) {

            if (result.status === 'ok') {
              self.settings = result.settings;
              $('.modal-footer button.btn-primary', self.$el).show();

            } else {

              self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();

            }
          })
          .fail(function(stat) {
            self.message = 'エラーが発生しました。';
            self.messageClass = 'alert alert-danger';
            $('.modal-footer button.btn-primary', self.$el).hide();
          })
          . always(function() {
          });
      },


      onSubmit: function() {
        var self = this;

        var data = {
          settings: self.settings
        };

        self.resetDialog();

        $.ajax({
            type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            if (result.status === 'ok') {
              self.message = '設定を保存し、cron設定を再生成しました。';
              self.messageClass = 'alert alert-success';
              self.settings = result.settings;

            } else {
              self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
              self.messageClass = 'alert alert-danger';
            }

          })
          .fail(function(stat) {
            self.$data.message = '設定が保存できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).show();
            self.noticeHidden = true;
          });
      },

      toggleIsActive: function(setting) {
        setting.active = Number(setting.active) === 0 ? -1 : 0;
      },

      runConfirm: function(setting) {
        var self = this;

        console.log(setting);
        self.runProcessSetting = setting;
        self.mode = 'confirm';
        self.message = setting.name + 'の在庫取得処理をキューに追加してよろしいですか？';
        self.messageClass = 'alert alert-warning';

        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      runProcess: function(confirm) {
        var self = this;

        self.resetDialog();

        if (!self.runProcessSetting || confirm !== 'yes') {
          self.mode = 'list';
          self.runProcessSetting = null;

          $('.modal-footer button.btn-primary', self.$el).show();
          return;
        }

        // TODO キュー追加処理
        // Ajaxでキュー追加
        var data = {
          setting: self.runProcessSetting
        };
        $.ajax({
          type: "POST"
          , url: self.queueUrl
          , dataType: "json"
          , data: data
        })
          .done(function(result) {
            self.$data.message = result.message;
            self.$data.messageClass = 'alert alert-success';

            var notification = new Notification("Plusnao Web System 通知", {
              tag: 'Plusnao Web System 通知'
              , body: result.message
              , iconUrl: appImages.icon.info // Firefox
              , icon: appImages.icon.info // Chrome
            });
            self.message = self.runProcessSetting.name + 'の在庫取得処理をキューに追加しました。';
            self.messageClass = 'alert alert-success';
          })
          .fail(function(stat) {
            self.$data.message = self.runProcessSetting.name + 'の在庫取得処理を開始できませんでした。';
            self.$data.messageClass = 'alert alert-danger';
          })
          .always(function() {
            $('.modal-footer button.btn-primary', self.$el).hide();
            self.noticeHidden = true;
          });

        self.mode = 'list';

        $('.modal-footer button.btn-primary', self.$el).show();
      },

      resetDialog: function() {
        var self = this;

        this.message = '時(0 ～ 23)はコンマ区切りで複数設定できます';
        this.messageClass = 'alert alert-info';

        this.notices = [];
        this.noticeHidden = true;

        this.mode = 'list';

        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });


  ///**
  // * Yahoo代理店一覧モーダル
  // */
  //var vmModalYahooAgentList = new Vue({
  //  el: '#modalYahooAgentList',
  //  data: {
  //      caption: 'Yahoo代理店一覧画面'
  //    , message: ''
  //    , messageClass: 'alert'
  //    , nowLoading: false
  //
  //    , agents: []
  //  },
  //  ready: function() {
  //    var self = this;
  //    self.url = $(self.$el).data('url');
  //
  //    // open時処理登録
  //    $(self.$el).on('show.bs.modal', function() {
  //
  //      self.resetDialog();
  //      self.nowLoading = true;
  //
  //      $.ajax({
  //          type: "GET"
  //        , url: self.url
  //        , dataType: "json"
  //        , data: {}
  //      })
  //        .done(function(result) {
  //
  //          if (result.status == 'ok') {
  //
  //            self.agents = result.results;
  //
  //          } else {
  //            self.message = result.message.length > 0 ? result.message : 'Yahoo代理店一覧の取得に失敗しました。';
  //            self.messageClass = 'alert alert-danger';
  //          }
  //        })
  //        .fail(function(stat) {
  //          self.message = 'Yahoo代理店一覧の取得ができませんでした。';
  //          self.messageClass = 'alert alert-danger';
  //        })
  //        . always(function() {
  //
  //          self.nowLoading = false;
  //        });
  //    });
  //  },
  //
  //  methods: {
  //
  //    resetDialog: function() {
  //      this.$data.message = '';
  //      this.$data.messageClass = '';
  //      $('.modal-footer button.btn-primary', self.$el).hide();
  //      this.nowLoading = false;
  //
  //      this.searchVendorAddress = null;
  //      this.vendors = [];
  //    }
  //  }
  //});


  // 倉庫間箱移動設定 モーダル
  var vmExternalWarehouseBoxMoveSettingModal = new Vue({
    el: '#modalExternalWarehouseBoxMoveSetting',
    data: {
      caption: '倉庫間箱移動設定'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , queueUrl: null
      , verifyUrl: null
      , updateUrl: null

      , setting: []
      , mode: 'list'
      , runProcessSetting: null
    },
    computed: {
    },
    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('queueUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.resetDialog();

        this.setting = [];
        self.loadData();
      });
    },

    methods: {
      loadData: function() {
        var self = this;

        $.ajax({
          type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
          , data: {}
        })
            .done(function(result) {

              if (result.status === 'ok') {
                self.setting = result.setting[0];
                $('.modal-footer button.btn-primary', self.$el).show();

              } else {

                self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
                self.messageClass = 'alert alert-danger';
                $('.modal-footer button.btn-primary', self.$el).hide();

              }
            })
            .fail(function(stat) {
              self.message = 'エラーが発生しました。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();
            })
            . always(function() {
            });
      },


      onSubmit: function() {
        var self = this;

        var data = {
          setting: self.setting
        };
        console.log(self.setting);
        // self.resetDialog();

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.message = '設定を保存し、cron設定を再生成しました。';
                self.messageClass = 'alert alert-success';
                self.setting = result.setting[0];

              } else {
                self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
                self.messageClass = 'alert alert-danger';
              }

            })
            .fail(function(stat) {
              self.$data.message = '設定が保存できませんでした。';
              self.$data.messageClass = 'alert alert-danger';
            })
            .always(function() {
              $('.modal-footer button.btn-primary', self.$el).show();
              self.noticeHidden = true;
            });
      },

      toggleIsActive: function(setting) {
        setting.active = Number(setting.active) === 0 ? -1 : 0;
      },

      runConfirm: function(setting) {
        var self = this;

        console.log(setting);
        self.runProcessSetting = setting;
        self.mode = 'confirm';
        self.message = '倉庫間箱移動処理をキューに追加してよろしいですか？';
        self.messageClass = 'alert alert-warning';

        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      runProcess: function(confirm) {
        var self = this;

        self.resetDialog();

        if (!self.runProcessSetting || confirm !== 'yes') {
          self.mode = 'list';
          self.runProcessSetting = null;

          $('.modal-footer button.btn-primary', self.$el).show();
          return;
        }

        // TODO キュー追加処理
        // Ajaxでキュー追加
        var data = {
          setting: self.runProcessSetting
        };
        $.ajax({
          type: "POST"
          , url: self.queueUrl
          , dataType: "json"
          , data: data
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.$data.message = result.message;
                self.$data.messageClass = 'alert alert-success';

                var notification = new Notification("Plusnao Web System 通知", {
                  tag: 'Plusnao Web System 通知'
                  , body: result.message
                  , iconUrl: appImages.icon.info // Firefox
                  , icon: appImages.icon.info // Chrome
                });
                self.message = '倉庫間箱移動処理をキューに追加しました。';
                self.messageClass = 'alert alert-success';

              } else {
                self.message = result.message.length > 0 ? result.message : '倉庫間箱移動処理を開始できませんでした。';
                self.messageClass = 'alert alert-danger';
              }

            })
            .fail(function(stat) {
              self.$data.message = '倉庫間箱移動処理を開始できませんでした。';
              self.$data.messageClass = 'alert alert-danger';
            })
            .always(function() {
              $('.modal-footer button.btn-primary', self.$el).hide();
              self.noticeHidden = true;
            });

        self.mode = 'list';

        $('.modal-footer button.btn-primary', self.$el).show();
      },

      resetDialog: function() {
        var self = this;

        this.message = '時(0 ～ 23)はコンマ区切りで複数設定できます';
        this.messageClass = 'alert alert-info';

        this.notices = [];
        this.noticeHidden = true;

        this.mode = 'list';

        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });

  // 受注明細取込設定 モーダル
  var vmImportOrderListSettingModal = new Vue({
    el: '#modalImportOrderListSetting',
    data: {
      caption: '受注明細取込設定'
      , message: ''
      , messageClass: 'alert'
      , notices: []
      , noticeClass: 'alert alert-warning'
      , noticeHidden: true
      , selectedTabName: 'sunday'

      , queueUrl: null
      , verifyUrl: null
      , updateUrl: null

      , setting: []

      // データ表示用
      , dataHours: null
      , dataMinutes: null
      , dataImportOrderListMonths: null
      , dataLimitTimeHour: null
      , dataLimitTimeMinute: null

      , mode: 'list'
      , runProcessSetting: null
    },

    ready: function() {
      var self = this;
      self.queueUrl = $(self.$el).data('queueUrl');
      self.updateUrl = $(self.$el).data('updateUrl');
      self.getSettingUrl = $(self.$el).data('getSettingUrl');

      // イベント登録
      $(self.$el).on('show.bs.modal', function() {
        self.setting = [];

        self.resetDialog();
        self.loadData();
      })

      $('#dayOfTheWeek').children('li').on('click', function(){
        // 切り替え前にsettingにデータを上書き
        self.overwriteData();
        self.selectedTabName = $(this).attr('id');
      });
    },

    watch: {
      // 選択するたびに切り替え
      selectedTabName: function(){this.setData();}
    },

    computed: {
      isNotActive : function(){
        let self = this;
        return self.setting[self.selectedTabName].active === 0;
      },
    },

    methods: {
      // データ取得
      loadData: function() {
        var self = this;

        $.ajax({
          type: "GET"
          , url: self.getSettingUrl
          , dataType: "json"
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.setting = result.setting;
                $('.modal-footer button.btn-primary', self.$el).show();
              } else {

                self.message = result.message.length > 0 ? result.message : '処理を開始できませんでした。';
                self.messageClass = 'alert alert-danger';
                $('.modal-footer button.btn-primary', self.$el).hide();

              }
            })
            .fail(function(stat) {
              self.message = 'エラーが発生しました。';
              self.messageClass = 'alert alert-danger';
              $('.modal-footer button.btn-primary', self.$el).hide();
            })
            .always(function() {
              self.setData();
            });
      },

      // データセット
      setData: function(){
        let self = this;
        self.dataHours = self.setting[self.selectedTabName].hours;
        self.dataMinutes = self.setting[self.selectedTabName].minutes;
        self.dataImportOrderListMonths = self.setting[self.selectedTabName].import_order_list_months;
        self.dataLimitTimeHour = self.setting[self.selectedTabName].limit_time_hour;
        self.dataLimitTimeMinute = self.setting[self.selectedTabName].limit_time_minute;
      },
      // データ上書き
      overwriteData: function(){
        let self = this;
        Vue.set(self.setting[self.selectedTabName],'hours',self.dataHours);
        Vue.set(self.setting[self.selectedTabName],'minutes',self.dataMinutes);
        Vue.set(self.setting[self.selectedTabName],'import_order_list_months',self.dataImportOrderListMonths);
        Vue.set(self.setting[self.selectedTabName],'limit_time_hour',self.dataLimitTimeHour);
        Vue.set(self.setting[self.selectedTabName],'limit_time_minute',self.dataLimitTimeMinute);
      },

      onSubmit: function() {
        var self = this;

        self.overwriteData();

        var data = {
          setting: self.setting
        };

        $.ajax({
          type: "POST"
          , url: self.updateUrl
          , dataType: "json"
          , data: data
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.message = '設定を保存し、cron設定を再生成しました。';
                self.messageClass = 'alert alert-success';
                self.setting = result.setting;

              } else {
                self.message = result.message.length > 0 ? result.message : '設定の保存に失敗しました。';
                self.messageClass = 'alert alert-danger';
              }

            })
            .fail(function(stat) {
              self.$data.message = '設定が保存できませんでした。';
              self.$data.messageClass = 'alert alert-danger';
            })
            .always(function() {
              $('.modal-footer button.btn-primary', self.$el).show();
              self.noticeHidden = true;
            });
      },

      // ON/OFF切り替え
      toggleIsActive: function() {
        let self = this;
        Vue.set(self.setting[self.selectedTabName],'active',self.setting[self.selectedTabName].active === 0 ? -1 : 0);
      },

      runConfirm: function(setting) {
        var self = this;

        self.runProcessSetting = setting;

        self.mode = 'confirm';
        self.message = '受注明細取込をキューに追加してよろしいですか？\n\n[取込期間] ' + setting.import_order_list_months + 'ヶ月分 ＋ 今月分';
        self.messageClass = 'alert alert-warning';

        $('.modal-footer button.btn-primary', self.$el).hide();
      },

      runProcess: function(confirm) {
        var self = this;

        self.resetDialog();

        if (!self.runProcessSetting || confirm !== 'yes') {
          self.mode = 'list';
          self.runProcessSetting = null;

          $('.modal-footer button.btn-primary', self.$el).show();
          return;
        }

        // Ajaxでキュー追加
        var data = {
          setting: self.runProcessSetting
        };

        $.ajax({
          type: "POST"
          , url: self.queueUrl
          , dataType: "json"
          , data: data
        })
            .done(function(result) {
              if (result.status === 'ok') {
                self.$data.message = result.message;
                self.$data.messageClass = 'alert alert-success';

                var notification = new Notification("Plusnao Web System 通知", {
                  tag: 'Plusnao Web System 通知'
                  , body: result.message
                  , iconUrl: appImages.icon.info // Firefox
                  , icon: appImages.icon.info // Chrome
                });
                self.message = '受注明細取込をキューに追加しました。';
                self.messageClass = 'alert alert-success';

              } else {
                self.message = result.message.length > 0 ? result.message : '受注明細取込を開始できませんでした。';
                self.messageClass = 'alert alert-danger';
              }

            })
            .fail(function(stat) {
              self.$data.message = '受注明細取込を開始できませんでした。';
              self.$data.messageClass = 'alert alert-danger';
            })
            .always(function() {
              $('.modal-footer button.btn-primary', self.$el).hide();
              self.noticeHidden = true;
            });

        self.mode = 'list';

        $('.modal-footer button.btn-primary', self.$el).show();
      },

      resetDialog: function() {
        var self = this;

        this.message = '時(0 ～ 23)はコンマ区切りで複数設定できます';
        this.messageClass = 'alert alert-info';

        this.notices = [];
        this.noticeHidden = true;

        this.mode = 'list';

        $('.modal-footer button.btn-primary', self.$el).hide();
      }
    }
  });
});
