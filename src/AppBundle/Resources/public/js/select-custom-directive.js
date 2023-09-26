Vue.directive('select', {
  inserted: function(el, binding) {
    $(el).select2({
      width: 'auto',
      theme: "bootstrap"
    }).on('select2:select', function() {
      el.dispatchEvent(new Event('change'));
    });
  }
});