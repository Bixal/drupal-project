<?php

/**
 * @file
 * Preprocess function for this hook.
 */

/**
 * Implements hook_preprocess_page().
 */
function uswds_wioa_preprocess_page(&$variables) {
  // Check to see if we need to turn on edge-to-edge mode.
  if (_uswds_wioa_edge_to_edge_mode()) {
    // If edge-to-edge mode is on, then the content area will not be wrapped in
    // "usa-grid", and the sidebars will be disabled.
    $variables['edge_to_edge'] = TRUE;
    $variables['content_class'] = 'usa-width-full';
    $variables['page']['sidebar_first'] = [];
    $variables['page']['sidebar_second'] = [];
  }
}
