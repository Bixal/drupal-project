<?php

/**
 * @file
 * Preprocess function for page.
 */

/**
 * Implements hook_preprocess_page().
 */
function uswds_wioa_preprocess_page(&$variables) {
  // Show the official U.S. Government banner?
  if (theme_get_setting('uswds_government_banner')) {
    $variables['government_banner'] = [
      '#theme' => 'government_banner',
      '#image_base' => $variables['active_theme_path'] . '/images',
    ];
  }
}
