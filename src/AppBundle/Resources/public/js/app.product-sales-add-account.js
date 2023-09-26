/**
 * 管理画面　商品　商品売上担当者追加
 */
/** メイン画面 */
const addAccount = new Vue({
  el: '#addAccount',
  data: {
    confirmUrl: null,
    registerUrl: null,
    listUrl: null,
    users: [],
    teams: [],
    tasks: [],
    settingFrom: {
      selectUser: null,
      selectTeam: null,
      selectTask: null,
      workAmount: '1.0',
      detail: null,
      applyStartDate: $.Plusnao.Date.getDateString(new Date(), false, false),
      applyEndDate: null,
      singleProduct: null,
      multiProduct: null,
    },
    bulkInsertFlg: false,
    isMultiProductRegisterAfterConfirm: false,
    list: [],
    messageState: {},
  },
  mounted: function () {
    const self = this;
    // URL取得
    this.confirmUrl = $(this.$el).data('confirmUrl');
    this.registerUrl = $(this.$el).data('registerUrl');
    this.listUrl = $(this.$el).data('listUrl');

    // 検索用データ取得
    self.users = USERS_DATA;
    self.teams = TEAMS_DATA;
    self.tasks = TASKS_DATA;

    // datepicker設定
    $('.datepicker').datepicker({
      language: 'ja',
      format: 'yyyy-mm-dd',
      autoclose: true
    }).on('change', function (e) {
      self.$set(self.settingFrom, e.target.name, e.target.value);
    });

    this.messageState = new PartsGlobalMessageState();

    // codeパラメータが有る場合、そのコードで検索
    const code = (new URL(document.location)).searchParams.get('code');
    if (code != undefined) {
      self.settingFrom.singleProduct = code;
      self.settingFrom.multiProduct = code;
      self.confirm();
    }
  },
  computed: {
    singleConfirmDisabled: function () {
      if (!this.settingFrom.selectTask) {
        return true;
      }
      return this.isMultiProductRegister();
    },
    multiConfirmDisabled: function () {
      if (!this.settingFrom.selectTask) {
        return true;
      }
      return !this.isMultiProductRegister();
    },
  },
  watch: {
    "settingFrom.workAmount": function () {
      this.list = this.calculationWorkAmount(this.list);
    },
    bulkInsertFlg: {
      handler: function handler(value) {
        if (!value) {
          this.settingFrom.singleProduct = this.settingFrom.singleProduct?.split('\n')[0];
        }
      }
    }
  },
  methods: {
    // タスクが複数商品登録かどうか true: 複数商品、false: 1商品(もしくはタスクを選択されていない)
    isMultiProductRegister: function () {
      if (!this.settingFrom.selectTask) {
        return false;
      }
      const self = this;
      const target = this.tasks.find(task => task.id === self.settingFrom.selectTask);
      return target.multiProductRegisterFlg;
    },
    // 対象商品の確認
    confirm: function () {
      const self = this;
      this.messageState.clear();
      this.isMultiProductRegisterAfterConfirm = this.isMultiProductRegister();
      const target = this.isMultiProductRegisterAfterConfirm ?
        this.settingFrom.multiProduct :
        this.settingFrom.singleProduct;
      const products = target ? target.split('\n') : [];

      // 入力チェック
      if (products.length === 0) {
        self.messageState.setMessage("商品を入力してください。", 'alert alert-danger');
        return;
      }

      // ajax
      $.ajax({
        type: "POST",
        url: self.confirmUrl,
        dataType: "json",
        data: {
          products: products
        }
      }).done(function (result) {
        if (result.status == 'ok') {
          self.list = self.calculationWorkAmount(result.list);
        } else {
          const message = result.message ? result.message : '対象商品の確認でエラーが発生しました。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      }).fail(function () {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      });
    },
    // 登録
    register: function () {
      const self = this;
      this.messageState.clear();

      // 入力チェック(ボタンを非活性などにするとどこが必須かわからない可能性があるので、入力チェックをする)
      let errors = [];
      if (!self.settingFrom.selectUser) {
        errors.push("担当者は必須です");
      }
      if (!self.settingFrom.selectTeam) {
        errors.push("担当チームは必須です");
      }
      if (!self.settingFrom.selectTask) {
        errors.push("タスク種別は必須です");
      }
      if (!self.settingFrom.workAmount) {
        errors.push("仕事量は必須です");
      }
      if (!self.settingFrom.applyStartDate) {
        errors.push("適用開始日は必須です");
      }
      if (errors.length > 0) {
        self.messageState.setMessage(errors.join("\n"), 'alert alert-danger');
        return;
      }

      const form = {
        list: self.list,
        userId: self.settingFrom.selectUser,
        teamId: self.settingFrom.selectTeam,
        taskId: self.settingFrom.selectTask,
        detail: self.settingFrom.detail,
        applyStartDate: self.settingFrom.applyStartDate,
        applyEndDate: self.settingFrom.applyEndDate
      };

      // ajax
      $.ajax({
        type: "POST",
        url: self.registerUrl,
        dateType: "json",
        data: {
          form: form
        }
      }).done(function (result) {
        if (result.status == 'ok') {
          alert('商品売上担当者を登録しました。');
          const code = self.list[0].daihyoSyohinCode
          location.href = self.listUrl + "?code=" + code;
        } else {
          const message = result.message ? result.message : '対象商品の確認でエラーが発生しました。';
          self.messageState.setMessage(message, 'alert alert-danger');
        }
      }).fail(function () {
        self.messageState.setMessage('エラーが発生しました。', 'alert alert-danger');
      });
    },
    // リストの仕事量計算　計算し、リストで返す。
    calculationWorkAmount: function (array) {
      const self = this;
      if (array.length === 0) {
        return array;
      }
      let workAmount = 0;
      if (self.isMultiProductRegisterAfterConfirm) {
        workAmount = (Math.floor(self.settingFrom.workAmount * 100 / array.length) / 100)
          .toLocaleString(undefined, { minimumFractionDigits: 1, maximumFractionDigits: 2 });
      } else {
        workAmount = self.settingFrom.workAmount;
      }
      return array.map(item => {
        item.workAmount = self.settingFrom.workAmount === "" ? "" : workAmount;
        return item;
      });
    },
  },
});