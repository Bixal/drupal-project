(function($) {
  Drupal.behaviors.simpleToggle = {
    attach(context) {
      const $trigger = $('.js-simple-toggle', context);
      const activeClass = 'is-active';

      const toggleElements = e => {
        e.preventDefault();

        let $trigger = $(e.currentTarget);
        let target = $trigger.attr('aria-controls');

        $trigger.toggleClass(activeClass);
        $(`#${target}`).toggleClass(activeClass);
      };

      $trigger.on('click', toggleElements);
    }
  };
})(jQuery);
