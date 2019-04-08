<?php

/**
 * @file
 * Utility code related to edge to edge mode.
 */

/**
 * Determine whether edge-to-edge mode is on.
 *
 * @ret bool
 *   TRUE if edge-to-edge mode is on.
 */
function _uswds_wioa_edge_to_edge_mode() {
  if (theme_get_setting('uswds_paragraphs_edge_to_edge')) {
    throw new Exception('Do not enable the edge to edge setting in the theme.');
  }
  $result = &drupal_static(__FUNCTION__);
  if (!isset($result)) {
    $result = FALSE;
    // If on the homepage, edge to edge is on.
    if (Drupal::service('path.matcher')->isFrontPage()) {
      return TRUE;
    }
  }
  return $result;
}
