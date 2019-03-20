<?php

/**
 * @file
 * Contains theme functionality for sp_display module.
 */

use Drupal\node\Entity\Node;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_retrieve\PlanYearDisplay;

/**
 * Common preprocess functionality between plan year and section.
 *
 * @param string $content_type
 *   The content type that $variables['node'] should be.
 * @param array $variables
 *   The preprocess variables.
 *
 * @return bool|\Drupal\sp_retrieve\PlanYearDisplay
 *   False on error else the PlanYearDisplay object.
 */
function sp_display_display_plan_common($content_type, array &$variables) {
  $variables['content'] = 'Could not load a plan year.';
  $node = $variables['node'];
  if (!($node instanceof Node) || $node->getType() !== $content_type) {
    return FALSE;
  }
  $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($node);
  if (FALSE === $plan_year_id) {
    return FALSE;
  }
  /** @var \Drupal\sp_retrieve\TaxonomyService $node_service */
  $taxonomy_service = \Drupal::getContainer()->get('sp_retrieve.taxonomy');
  $plan_year_display_info = $taxonomy_service->getPlanYearDisplayInfo($plan_year_id);
  if (empty($plan_year_display_info)) {
    return FALSE;
  }
  $plan_year_display = new PlanYearDisplay($plan_year_display_info);
  if ($errors = $plan_year_display->getErrors()) {
    foreach ($errors as $error) {
      \Drupal::messenger()->addError($error);
    }
    return FALSE;
  }
  $variables['#attached']['library'][] = 'sp_display/display.plan';
  return $plan_year_display;
}

/**
 * Prepares variables the plan year view template.
 *
 * Default template: sp-display-plan-year-view.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - node: The plan year node.
 */
function template_preprocess_sp_display_plan_year_view(array &$variables) {
  $plan_year_display = sp_display_display_plan_common(PlanYearInfo::SPY_BUNDLE, $variables);
  if (FALSE === $plan_year_display) {
    return;
  }
  $variables['content'] = $plan_year_display->getPlanYearItemList();
}

/**
 * Prepares variables the plan year section view template.
 *
 * Default template: sp-display-plan-year-section-view.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - node: The plan year section node.
 */
function template_preprocess_sp_display_plan_year_section_view(array &$variables) {
  $plan_year_display = sp_display_display_plan_common(PlanYearInfo::SPYS_BUNDLE, $variables);
  if (FALSE === $plan_year_display) {
    return;
  }
  $variables['content'] = $plan_year_display->getPlanYearSectionItemList(
    $variables['node']->get('field_section')->getString()
  );
}
