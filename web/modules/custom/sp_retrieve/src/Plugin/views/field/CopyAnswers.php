<?php

namespace Drupal\sp_retrieve\Plugin\views\field;

use Drupal\sp_retrieve\CustomEntitiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sp_retrieve\MixedEntityService;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\content_moderation\ModerationInformationInterface;

/**
 * Field handler shows if a state plan has any copiable state plan year content.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("copy_answers")
 *
 * @see https://www.webomelette.com/creating-custom-views-field-drupal-8
 */
class CopyAnswers extends FieldPluginBase {

  /**
   * The mixed entity retrieval service.
   *
   * @var \Drupal\sp_retrieve\MixedEntityService
   */
  protected $mixedService;

  /**
   * Retrieve custom entities.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * The moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Constructs a TimeInterval plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\sp_retrieve\MixedEntityService $mixed_service
   *   The mixed entity retrieval service.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Retrieve custom entities.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MixedEntityService $mixed_service, CustomEntitiesService $custom_entities_retrieval, ModerationInformationInterface $moderation_info) {
    $this->mixedService = $mixed_service;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->moderationInfo = $moderation_info;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('sp_retrieve.mixed'),
      $container->get('sp_retrieve.custom_entities'),
      $container->get('content_moderation.moderation_information')
    );
  }

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
    /** @var \Drupal\node\Entity\Node $node */
    $state_plan_year = $values->_entity;
    $summary = $this->mixedService->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYearSummary($state_plan_year->id());
    if (empty($summary['count'])) {
      return $summary['message'];
    }
    $output['copy'] = [
      '#type' => 'table',
      '#header' => [
        'Section',
        'From Year',
        'Section Status',
        'Num to Copy',
      ],
    ];

    foreach ($this->mixedService->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYear($state_plan_year->id()) as $section_id => $copiable_answers_section) {
      $state_plan_year_section = $this->customEntitiesRetrieval->single('node', $copiable_answers_section['state_plan_year_section_from']);
      $plan_year_label = $state_plan_year_section->get('field_state_plan_year')->entity->get('field_state_plans_year')->entity->get('field_plan_year')->entity->label();
      $section_label = $state_plan_year_section->get('field_section')->entity->label();
      $moderation_state_id = $state_plan_year_section->get('moderation_state')->getString();
      /** @var \Drupal\workflows\Entity\Workflow $workflow */
      $workflow = $this->moderationInfo->getWorkflowForEntity($state_plan_year_section);
      /** @var \Drupal\content_moderation\ContentModerationState $moderation_state */
      $moderation_state = $workflow->getTypePlugin()->getState($moderation_state_id);
      $moderation_state_label = $moderation_state->label();
      $output['copy'][$section_id]['section'] = ['#markup' => $section_label];
      $output['copy'][$section_id]['from_year'] = ['#markup' => $plan_year_label];
      $output['copy'][$section_id]['section_status'] = ['#markup' => $moderation_state_label];
      $output['copy'][$section_id]['num_to_copy'] = ['#markup' => count($copiable_answers_section['state_plan_year_answers'])];
    }
    return $output;
  }

}
