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

/**
 * Prepares variables the download plan form template.
 *
 * Default template: sp-display-download-plan-form.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - orientation: Horizontal or vertical.
 *   - node: The homepage node.
 *
 * @throws \Exception
 */
function template_preprocess_sp_display_download_plan_form(array &$variables) {
  $content = _sp_dispay_fake_content();
  $variables['#attached'] = [
    'library' => ['sp_display/download.plan.form'],
    'drupalSettings' => [
      'sp_display' => ['download_plan_form' => $content],
    ],
  ];
  $variables['state_options'] = '<option value="" selected>' . t('Choose a state') . '</option>';
  if (!empty($content['state_plans'])) {
    foreach ($content['state_plans'] as $group_id => $state) {
      $variables['state_options'] .= '<option value="' . $group_id . '">' . $state['name'] . '</option>';
    }
  }
  $variables['years_options'] = '<option value="" selected>' . t('Choose a year') . '</option>';
  if (!empty($content['all_plan_years'])) {
    foreach ($content['all_plan_years'] as $plan_year_id => $plan_year_label) {
      $variables['years_options'] .= '<option value="' . $plan_year_id . '">' . $plan_year_label . '</option>';
    }
  }
}

/**
 * Temp function to return fake content for display form.
 *
 * @return array
 *   An array for drupal settings.
 */
function _sp_dispay_fake_content() {
  return [
    // These are all plan years that have been created. These are the options
    // for 'years' for all states. The key is the value of the option and the
    // value is the display of the option. When a user clicks a state, enable
    // the 'years' option if the 'state_plans' group ID has a plan with this
    // plan year ID.
    'all_plan_years' => [
      '2016' => 'FY 2016-2017',
      '2018' => 'FY 2018-2019',
      '2020' => 'FY 2020-2021',
    ],
    // Keyed by the states group ID, this will be the value of the state option.
    // Use this value to determine what plans that state has to populate years.
    'state_plans' => [
      '1' => [
        // Only used as the display for the state option.
        'name' => 'Washington',
        // There will always be a key here for each of the above
        // 'all_plan_years'. The key corresponds to the key in the
        // 'all_plan_years' and the value will be populated if this state has
        // a plan for that year.
        'plans' => ['2016' => '', '2018' => '', '2020' => ''],
      ],
      '3' => [
        'name' => 'Virginia',
        'plans' => ['2016' => '/virginia/2016.pdf', '2018' => '', '2020' => ''],
      ],
      '7' => [
        'name' => 'Illinois',
        'plans' => [
          '2016' => '/illinois/2016.pdf',
          '2018' => '/illinois/2018.pdf',
          '2020' => '',
        ],
      ],
      '20' => [
        'name' => 'Iowa',
        'plans' => [
          '2016' => '/iowa/2016.pdf',
          '2018' => '/iowa/2018.pdf',
          '2020' => '/iowa/2020.pdf',
        ],
      ],
      '21' => [
        'name' => 'Florida',
        'plans' => [
          '2016' => '/florida/2016.pdf',
          '2018' => '',
          '2020' => '/florida/2020.pdf',
        ],
      ],
      '27' => [
        'name' => 'Georgia',
        'plans' => [
          '2016' => '',
          '2018' => '',
          '2020' => '/georgia/2020.pdf',
        ],
      ],
      '33' => [
        'name' => 'Tennessee',
        'plans' => [
          '2016' => '',
          '2018' => '/tennessee/2018.pdf',
          '2020' => '',
        ],
      ],
    ],
  ];
}
