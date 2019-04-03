/**
 * @file
 * Dynamic select options for download plan form.
 */

(function($, Drupal, drupalSettings) {
  /**
   * Add sticky headers to display plan lists.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.displayPlan = {
    attach(context, settings) {
      var $form;
      var $state;
      var $year;
      var $button;
      var selectedState;
      var selectedYear;
      var selectedStatePlans;

      var getSelectedStatePlans = function(stateID) {
        if (!stateID) {
          console.log('No valid State selected.');
          return;
        }
        return settings.sp_display.download_plan_form.state_plans[stateID]
          .plans;
      };

      // Enable years based on selected State or Territory.
      var toggleYearOptions = function(yearsSelect, selectedPlan) {
        for (var key in selectedPlan) {
          var $options = yearsSelect.find(`option[value="${key}"]`);

          yearsSelect.val('');

          if (selectedPlan.hasOwnProperty(key) && selectedPlan[key] !== '') {
            $options.prop('disabled', false);
          } else {
            $options.prop('disabled', true);
          }
        }
      };

      // Enable year select if a State or Territory selected.
      var toggleYearSelect = function(yearsSelect, selectedPlan) {
        if (selectedPlan) {
          yearsSelect.prop('disabled', false);
          toggleYearOptions(yearsSelect, selectedPlan);
        } else {
          yearsSelect.prop('disabled', true);
          yearsSelect.val('');
        }
      };

      var toggleDownload = function(e) {
        $form = $(e.currentTarget);
        $state = $form.find('[name=states]');
        $year = $form.find('[name=years]');
        $button = $form.find('[name=download]');
        selectedState = $state.val();
        selectedYear = $year.val();
        selectedStatePlans = getSelectedStatePlans(selectedState);

        if ($(e.target).is('.download-plan-form__states')) {
          toggleYearSelect($year, selectedStatePlans);
        }

        if (selectedState.length && selectedYear.length) {
          $button.prop('disabled', false);
        } else {
          $button.prop('disabled', true);
        }
      };

      var getStatePlan = function(e) {
        e.preventDefault();
        window.location = `${selectedStatePlans[selectedYear]}`;
      };

      $('.download-plan-form', context)
        .find('[name=states]')
        .select2();

      $('.download-plan-form', context).on('change', toggleDownload);
      $('.download-plan-form', context).on('submit', getStatePlan);
    }
  };
})(jQuery, Drupal, drupalSettings);
