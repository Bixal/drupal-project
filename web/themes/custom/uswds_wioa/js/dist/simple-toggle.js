"use strict";

(function ($) {
  Drupal.behaviors.simpleToggle = {
    attach: function attach(context) {
      var $trigger = $('.js-simple-toggle', context);
      var activeClass = 'is-active';

      var toggleElements = function toggleElements(e) {
        e.preventDefault();
        var $trigger = $(e.currentTarget);
        var target = $trigger.attr('aria-controls');
        $trigger.toggleClass(activeClass);
        $("#" + target).toggleClass(activeClass);
      };

      $trigger.on('click', toggleElements);
    }
  };
})(jQuery);
//# sourceMappingURL=data:application/json;charset=utf8;base64,eyJ2ZXJzaW9uIjozLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiIiwic291cmNlcyI6WyJzaW1wbGUtdG9nZ2xlLmpzIl0sInNvdXJjZXNDb250ZW50IjpbIlwidXNlIHN0cmljdFwiO1xuXG4oZnVuY3Rpb24gKCQpIHtcbiAgRHJ1cGFsLmJlaGF2aW9ycy5zaW1wbGVUb2dnbGUgPSB7XG4gICAgYXR0YWNoOiBmdW5jdGlvbiBhdHRhY2goY29udGV4dCkge1xuICAgICAgdmFyICR0cmlnZ2VyID0gJCgnLmpzLXNpbXBsZS10b2dnbGUnLCBjb250ZXh0KTtcbiAgICAgIHZhciBhY3RpdmVDbGFzcyA9ICdpcy1hY3RpdmUnO1xuXG4gICAgICB2YXIgdG9nZ2xlRWxlbWVudHMgPSBmdW5jdGlvbiB0b2dnbGVFbGVtZW50cyhlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgdmFyICR0cmlnZ2VyID0gJChlLmN1cnJlbnRUYXJnZXQpO1xuICAgICAgICB2YXIgdGFyZ2V0ID0gJHRyaWdnZXIuYXR0cignYXJpYS1jb250cm9scycpO1xuICAgICAgICAkdHJpZ2dlci50b2dnbGVDbGFzcyhhY3RpdmVDbGFzcyk7XG4gICAgICAgICQoXCIjXCIgKyB0YXJnZXQpLnRvZ2dsZUNsYXNzKGFjdGl2ZUNsYXNzKTtcbiAgICAgIH07XG5cbiAgICAgICR0cmlnZ2VyLm9uKCdjbGljaycsIHRvZ2dsZUVsZW1lbnRzKTtcbiAgICB9XG4gIH07XG59KShqUXVlcnkpOyJdLCJmaWxlIjoic2ltcGxlLXRvZ2dsZS5qcyJ9
