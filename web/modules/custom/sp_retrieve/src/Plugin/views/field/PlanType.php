<?php

namespace Drupal\sp_retrieve\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler shows if a state plan is a combined plan or unified plan.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("plan_type")
 *
 * @see https://www.webomelette.com/creating-custom-views-field-drupal-8
 *
 * @all_plans
 */
class PlanType extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $output['plan_type'] = [
      '#theme' => 'sp_display_plan_type_badge',
      '#node' => $values->_entity,
    ];
    return $output;
  }

}
