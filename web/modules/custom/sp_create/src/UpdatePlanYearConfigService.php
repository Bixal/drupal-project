<?php

namespace Drupal\sp_create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sp_retrieve\NodeService;
use Drupal\sp_retrieve\TaxonomyService;
use Psr\Log\LoggerInterface;
use Drupal\sp_retrieve\CustomEntitiesService;

/**
 * Class UpdatePlanYearConfigService.
 *
 * Provides methods for altering a plans configuration. This is basically all
 * the information that is stored in code.
 */
class UpdatePlanYearConfigService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity clone service.
   *
   * @var \Drupal\sp_create\CloneService
   */
  protected $clone;

  /**
   * The custom entities retrieval service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * The taxonomy retrieval service.
   *
   * @var \Drupal\sp_retrieve\TaxonomyService
   */
  protected $taxonomyService;

  /**
   * The node retrieval service.
   *
   * @var \Drupal\sp_retrieve\nodeService
   */
  protected $nodeService;

  /**
   * The update plan year content service.
   *
   * @var \Drupal\sp_create\UpdatePlanYearContentService
   */
  protected $updatePlanYearContentService;

  /**
   * Constructs a new CreateStatePlanService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\sp_create\CloneService $clone
   *   The entity clone service.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   * @param \Drupal\sp_retrieve\TaxonomyService $taxonomy_service
   *   The taxonomy retrieval service.
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node retrieval service.
   * @param \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content_service
   *   The update plan year service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, CloneService $clone, CustomEntitiesService $custom_entities_retrieval, TaxonomyService $taxonomy_service, NodeService $node_service, UpdatePlanYearContentService $update_plan_year_content_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->clone = $clone;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->taxonomyService = $taxonomy_service;
    $this->nodeService = $node_service;
    $this->updatePlanYearContentService = $update_plan_year_content_service;
  }

  /**
   * Remove all information having to do with the given plan year.
   *
   * Content is removed in the reverse order of references.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeStatePlan($plan_year_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single('plan_year', $plan_year_id);
    // Remove all section based information.
    /** @var \Drupal\node\Entity\Node $state_plans_year */
    foreach ($plan_year->getSections() as $section) {
      // Remove state plan year content nodes for this section.
      $this->updatePlanYearContentService->removeStatePlanYearContentBySection($plan_year_id, $section->id());
      // Remove all section nodes in this section.
      $this->updatePlanYearContentService->removeStatePlanYearSection($plan_year_id, $section->id());
      // Remove all terms in the plan year section vocabulary.
      $this->updatePlanYearContentService->removeSectionHierarchy($plan_year_id, $section->id());
      // Remove the plan year section vocabulary.
      $this->removeSection($plan_year_id, $section->id());
    }
    // Get all state plan year nodes for this year and remove them.
    $state_plan_year_nids = $this->nodeService->getStatePlanYearsByPlansYear($plan_year_id);
    if (!empty($state_plan_year_nids)) {
      foreach ($state_plan_year_nids as $state_plan_year_nid) {
        $node_storage->load($state_plan_year_nid)->delete();
      }
    }
    // Get the state plans year node for this plan year and remove it.
    // This content might not be created yet.
    $state_plans_year_nid = $this->nodeService->getStatePlansYearByPlanYear($plan_year_id);
    if (!empty($state_plan_year_nid)) {
      $state_plans_year = $node_storage->load($state_plans_year_nid);
      $state_plans_year->delete();
    }
  }

  /**
   * Remove a section from a plan year meta data.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSectionMeta($plan_year_id, $section_id) {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->entityTypeManager->getStorage('plan_year')->load($plan_year_id);
    $plan_year->removeSection($section_id);
    $plan_year->save();
  }

  /**
   * Update or add a new section and the plan year it's copied from.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $plan_year_id_to_copy
   *   The plan year to copy hierarchy from to this section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateSectionMeta($plan_year_id, $section_id, $plan_year_id_to_copy = '') {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->entityTypeManager->getStorage('plan_year')->load($plan_year_id);
    $plan_year->addSection($section_id, $plan_year_id_to_copy);
    $plan_year->save();
  }

  /**
   * Remove this plan year section vocabulary.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSection($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      $section_vocabulary->delete();
    }
  }

  /**
   * Add this plan year section vocabulary.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addSection($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL === $section_vocabulary) {
      $new_label = $this->customEntitiesRetrieval->getLabel('plan_year', $plan_year_id) . ' - ' . $this->customEntitiesRetrieval->getLabel('section', $section_id);
      $this->clone->cloneBundle(
        'taxonomy_term',
        PlanYearInfo::$sectionBaseVocabularyName,
        $section_vocabulary_id,
        $new_label
      );
    }
  }

}
