/**
 * 作業ログ登録 JS
 */

const draggable = window["vuedraggable"];
const operationLog = new Vue({
  el: "#operationLog",
  components: {
    draggable: draggable,
  },
  data: {
    findTaskUrl: null,
    addTaskUrl: null,
    registerUrl: null,
    showSettings: false,
    newTaskName: "",
    sortedFlg: false,
    newTaskListIds: [],
    registerItem: {
      taskId: null,
      note: null,
      targetType: null,
      targetValues: [],
    },
    voucherNumberStr: "",
    taskList: [],
    taskNameList: [],
    messageState: {},
  },

  mounted: function () {
    const self = this;
    // URL取得
    self.findTaskUrl = $(self.$el).data("findTaskUrl");
    self.addTaskUrl = $(self.$el).data("addTaskUrl");
    self.deleteTaskUrl = $(self.$el).data("deleteTaskUrl");
    self.sortTaskUrl = $(self.$el).data("sortTaskUrl");
    self.registerUrl = $(self.$el).data("registerUrl");

    self.messageState = new PartsGlobalMessageState();

    self.findTaskList();
  },

  computed: {
    dispTaskList: function() {
      return this.taskList;
    },
  },

  methods: {
    // タスクリスト取得
    findTaskList: function () {
      const self = this;
      self.messageState.clear();
      $.ajax({
        type: "GET",
        url: self.findTaskUrl,
        dataType: "json",
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.taskList = result.list.map((task) => {
              task.attribute = "task" + task.id;
              return task;
            });
            self.taskNameList = result.list.map((task) => {
              return task.name;
            });
          } else {
            const message = result.message ? result.message : "タスクリスト取得中にエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
        })
        .always(function() {
          self.scrollTop();
        });
    },

    // 設定画面 表示/非表示
    toggleSettings: function () {
      this.showSettings = !this.showSettings;
    },

    // タスク追加
    addTask: function () {
      const self = this;
      // 前後の空白文字は除去。
      const addTaskName = self.newTaskName.trim();
      self.messageState.clear();
      if (self.newTaskName === "") {
        self.messageState.setMessage("タスク名を入力してください。", "alert alert-danger");
        self.scrollTop();
        return;
      }
      if (self.taskNameList.indexOf(addTaskName) !== -1) {
        self.messageState.setMessage("既に同じ名前のタスクが登録されています。", "alert alert-danger");
        self.scrollTop();
        return;
      }
      $.ajax({
        type: "GET",
        url: self.addTaskUrl,
        dataType: "json",
        data: {
          taskName: addTaskName,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.newTaskName = "";
            self.findTaskList();
          } else {
            const message = result.message ? result.message : "タスク追加中にエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
        })
        .always(function () {
          self.scrollTop();
        });
    },

    // タスク削除
    deleteTask: function (id) {
      const self = this;
      self.messageState.clear();
      $.ajax({
        type: "GET",
        url: self.deleteTaskUrl,
        dataType: "json",
        data: {
          id: id,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.findTaskList();
          } else {
            const message = result.message ? result.message : "タスク削除中にエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
        })
        .always(function() {
          self.scrollTop();
        });
    },

    // 並び替え実行時
    onSort: function () {
      this.sortedFlg = true;
    },

    // 並び替えを保存
    saveSort: function () {
      const self = this;
      self.messageState.clear();
      const newTaskList = document.querySelectorAll(".tasks");
      newTaskList.forEach((task) => {
        self.newTaskListIds.push(task.getAttribute("data-task-id"));
      });
      self.taskList = [];
      $.ajax({
        type: "GET",
        url: self.sortTaskUrl,
        dataType: "json",
        data: {
          ids: self.newTaskListIds,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.sortedFlg = false;
            self.findTaskList();
          } else {
            const message = result.message ? result.message : "タスク並び替え中にエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました。", "alert alert-danger");
        })
        .always(function() {
          self.newTaskListIds = [];
          self.scrollTop();
        });
    },

    // 作業ログ登録
    register: function () {
      const self = this;
      self.messageState.clear();
      if (self.registerItem.taskId === null) {
        self.messageState.setMessage("タスクは必ず選択してください。", "alert alert-danger");
        self.scrollTop();
        return;
      }
      if (self.voucherNumberStr !== "") {
        /* 前後の空白文字は除去。空白文字のみの行(改行しただけ等)は、DBに登録しない。
            リスト全てが空白文字のみでなければ、業務対象種別を「1.伝票番号」として扱う｡ */
        self.registerItem.targetValues = self.voucherNumberStr.split("\n").map((v) => v.trim()).filter((v) => v);
        self.registerItem.targetType = self.registerItem.targetValues.length ? 1 : null;
      }
      $.ajax({
        type: "POST",
        url: self.registerUrl,
        dataType: "json",
        data: {
          form: self.registerItem,
        },
      })
        .done(function (result) {
          if (result.status === "ok") {
            self.messageState.setMessage("登録しました。", "alert alert-success");
            self.registerItem.taskId = null;
            self.registerItem.note = null;
            self.registerItem.targetType = null;
            self.registerItem.targetValue = [];
            self.voucherNumberStr = "";
          } else {
            const message = result.message ? result.message : "作業ログ登録中にエラーが発生しました";
            self.messageState.setMessage(message, "alert alert-danger");
          }
        })
        .fail(function () {
          self.messageState.setMessage("エラーが発生しました。",  "alert alert-danger");
        })
        .always(function() {
          self.scrollTop();
        });
    },

    // ページのトップへスクロール
    scrollTop: function(){
      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    }
  },
});
