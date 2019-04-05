<?php

use Drupal\Component\Utility\Html;

/**
 * Implements hook_preprocess_field().
 */
function uswds_wioa_preprocess_field(&$variables) {
  $field_name = $variables['field_name'];

  switch ($field_name) {
    case 'field_home_callout_link':
    case 'field_home_image_banner_link';
      $variables['items'][0]['content']['#options']['attributes']['class'][] = 'usa-button ' . Html::cleanCssIdentifier('usa-button-' . $field_name);
      break;
  }
}
