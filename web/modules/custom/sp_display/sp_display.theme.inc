<?php

/**
 * @file
 * Contains theme functionality for sp_display module.
 */

use Drupal\node\Entity\Node;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_retrieve\PlanYearDisplay;
use Drupal\sp_retrieve\PlanYearOutputItemList;
use Drupal\sp_retrieve\PlanYearOutputItemListToc;
use Drupal\sp_retrieve\PlanYearOutputItemListSection;

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
  /** @var \Drupal\sp_retrieve\PlanYearDisplay $plan_year_display */
  $plan_year_display = &drupal_static(__FUNCTION__ . '-' . $plan_year_id);
  if (NULL !== $plan_year_display) {
    return $plan_year_display;
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
  $output = new PlanYearOutputItemList($plan_year_display);
  $variables['content'] = $output->getItemList();
}

/**
 * Prepares variables the plan year toc template.
 *
 * Default template: sp-display-plan-year-toc.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - node: The plan year node.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 */
function template_preprocess_sp_display_plan_year_toc(array &$variables) {
  $plan_year_display = sp_display_display_plan_common(PlanYearInfo::SPY_BUNDLE, $variables);
  if (FALSE === $plan_year_display) {
    return;
  }
  /** @var \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_service */
  $custom_entities_service = \Drupal::getContainer()->get('sp_retrieve.custom_entities');
  /** @var \Drupal\group\Entity\Group $group */
  // @TODO: This should be a new 'State Full Name' field instead of group label.
  $group = $custom_entities_service->single('group', $custom_entities_service->getGroupId($variables['node']));
  $plan_year_name = $custom_entities_service->getLabel('plan_year', PlanYearInfo::getPlanYearIdFromEntity($variables['node']));
  $output = new PlanYearOutputItemListToc($plan_year_display);
  $output->setPlanName(t('WIOA State Plan for @state_name FY-@year', ['@state_name' => $group->label(), '@year' => $plan_year_name]));
  $variables['content'] = $output->getItemList();
}

/**
 * Prepares variables the plan year section view template.
 *
 * Default template: sp-display-plan-year-section-view.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - node: The plan year section node.
 *
 * @throws \Exception
 */
function template_preprocess_sp_display_plan_year_section_view(array &$variables) {
  $plan_year_display = sp_display_display_plan_common(PlanYearInfo::SPYS_BUNDLE, $variables);
  if (FALSE === $plan_year_display) {
    return;
  }
  $output = new PlanYearOutputItemListSection($plan_year_display);
  $output->setSectionId($variables['node']->get('field_section')->getString());
  $variables['content'] = $output->getItemList();
}
