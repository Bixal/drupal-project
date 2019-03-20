/**
 * @file
 * Add sticky headers to display plan lists.
 */

(function ($, Drupal, drupalSettings) {

    /**
     * Add sticky headers to display plan lists.
     *
     * @type {Drupal~behavior}
     */
    Drupal.behaviors.displayPlan = {
        attach(context, settings) {
            // Check if the admin tool bar is present and account for the
            // offset.
            var $adminToolBar = $('#toolbar-item-administration-tray');
            if ($adminToolBar.length) {
                if ($adminToolBar.hasClass('toolbar-tray-vertical')) {
                    Drupal.displayPlan.stickyBitsSettings.stickyBitStickyOffset = 40;
                }
                if ($adminToolBar.hasClass('toolbar-tray-horizontal')) {
                    Drupal.displayPlan.stickyBitsSettings.stickyBitStickyOffset = 80;
                }
            }
            // Apply sticky bits again if the settings have changed. This will
            // happen if admin menu finally gets initialized.
            if (JSON.stringify(Drupal.displayPlan.stickyBitsSettings) !== JSON.stringify(Drupal.displayPlan.stickyBitsSettingsNew)) {
                Drupal.displayPlan.stickyBitsSettingsNew = Drupal.displayPlan.stickyBitsSettings;

                $('div.display-plan.item-list li').each(function () {
                    $(this).stickybits(Drupal.displayPlan.stickyBitsSettings);
                })
            }
        },
    };

    /**
     * The settings to pass to stickbits.
     *
     * @type {{stickyBitsSettingsNew: {}, stickyBitsSettings: {}}}
     */
    Drupal.displayPlan = {
        stickyBitsSettings: {},
        stickyBitsSettingsNew: {}
    };


})(jQuery, Drupal, drupalSettings);
