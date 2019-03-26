/**
 * @file
 * Dynamic select options for download plan form.
 */

(function ($, Drupal, drupalSettings) {

    /**
     * Add sticky headers to display plan lists.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.displayPlan = {
        attach(context, settings) {
            $('.download-plan-form', context).once().each(function () {
                var $downloadPlanForm = $(this);
                var $orientation = $downloadPlanForm.hasClass('horizontal') ? 'horizontal' : 'vertical';
                // Horizontal or vertical.
                console.log($orientation);
                // The object to use for lookups.
                console.log(settings.sp_display);
                var $statesSelect = $downloadPlanForm.find('[name=states]');
                var $yearsSelect = $downloadPlanForm.find('[name=years]');
                var $downloadButton = $downloadPlanForm.find('button[name=download]');
                $statesSelect.on('change', function () {
                    var selectedStateID = $('option:selected', $(this)).val();
                    var selectedStatePlans = settings.sp_display.download_plan_form.state_plans[selectedStateID].plans;
                    for (var key in selectedStatePlans) {
                        if (selectedStatePlans.hasOwnProperty(key)) {
                            console.log('This has a value if this year for this state has a URL: ' + key + " -> " + selectedStatePlans[key]);
                            console.log('This is the year select option: ' + $yearsSelect.find('option[value=' + key + ']').val())
                        }
                    }
                })

            });

        },
    };

})(jQuery, Drupal, drupalSettings);
