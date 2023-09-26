/**
 * 管理画面　設定　チーム一覧　JS
 */
/** メイン画面 */
var teams = new Vue({
  el: '#teamListTable',
  data: {
    teams: null,
  },
  mounted: function () {
    const self = this;
    this.$nextTick(function () {
      self.teams = TEAMS_DATA;
    });
  },
  methods: {
    openModal: function(id) {
      const self = this;
      if ($.Plusnao.Repository.teamEdit) {
        const target = self.teams.find(team => team.id === id) || {};
        $.Plusnao.Repository.teamEdit.open({...target}, function(){
          window.location.reload();
        });
      } else {
        throw new Error('編集フォームの読み込みができませんでした。');
      }
    },
  },
});