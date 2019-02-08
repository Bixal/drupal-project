<?php

namespace Drupal\sp_create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sp_retrieve\NodeService;
use Drupal\sp_retrieve\TaxonomyService;
use Psr\Log\LoggerInterface;
use Drupal\sp_retrieve\CustomEntitiesService;
use Drupal\Core\Database\Connection as Database;

/**
 * Class UpdatePlanYearContentService.
 *
 * Provides methods for altering a plans content. This is basically all
 * the information that is stored in the database.
 */
class UpdatePlanYearContentService {

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
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, CloneService $clone, CustomEntitiesService $custom_entities_retrieval, TaxonomyService $taxonomy_service, NodeService $node_service, Database $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->clone = $clone;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->taxonomyService = $taxonomy_service;
    $this->nodeService = $node_service;
    $this->database = $database;
  }

  /**
   * Drop all term hierarchy from this section.
   *
   * The section meta data should be updated by this point and the given section
   * may not be found on the plan year entity. In addition, the section content
   * should be removed as well (removeSectionContent is already called).
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
  public function removeSectionHierarchy($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      // Retrieve only the top level terms of the section, as children will be
      // automatically removed.
      foreach ($this->taxonomyService->getVocabularyTids($section_vocabulary_id, TRUE) as $tid) {
        $term = $term_storage->load($tid);
        // Just in-case a child was retrieved and it's already removed, check
        // that the term could be loaded.
        if (NULL !== $term) {
          $term->delete();
        }
      }
    }
  }

  /**
   * Copy all term hierarchy from a different plan year to this section.
   *
   * The section meta data should be updated by this point with the new plan
   * year to copy.
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
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function copySectionHierarchy($plan_year_id, $section_id, $plan_year_id_to_copy) {
    $plan_year_vid = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $plan_year_to_copy_section_vid = PlanYearInfo::createSectionVocabularyId($plan_year_id_to_copy, $section_id);
    $source_mapping = [];
    // Clone each term in the copied year, keeping track of parent so it can
    // be set later.
    foreach ($this->taxonomyService->getVocabularyTids($plan_year_to_copy_section_vid) as $tid) {
      /** @var \Drupal\taxonomy\Entity\Term $source_term */
      $source_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
      /** @var \Drupal\taxonomy\Entity\Term $cloned_term */
      $cloned_term = $this->clone->cloneEntity('taxonomy_term', $plan_year_to_copy_section_vid, $tid);
      $source_mapping[] = [
        'source_tid' => $source_term->id(),
        'source_tid_parent' => $source_term->get('parent')->first()->getValue()['target_id'],
        'cloned_tid' => $cloned_term->id(),
      ];
    }
    // Now that all terms have been saved, they need to have the hierarchy set
    // again and saved to the new vocabulary.
    foreach ($source_mapping as $item) {
      $cloned_term = $this->entityTypeManager->getStorage('taxonomy_term')
        ->load($item['cloned_tid']);
      // This term had a parent in the source vocab but we haven't set it on
      // the current plan year term yet.
      if (!empty($item['source_tid_parent'])) {
        $cloned_tid_parent = $this->getParentTid($source_mapping, $item['source_tid_parent']);
        if (FALSE === $cloned_tid_parent) {
          continue;
        }
        $cloned_term->set('parent', $cloned_tid_parent);
      }
      // Remove - Cloned from the label of the term.
      $cloned_term->set('name', str_replace(' - Cloned', '', $cloned_term->label()));
      // Move the cloned term to the new section year.
      $cloned_term->set('vid', $plan_year_vid);
      $cloned_term->save();
    }
  }

  /**
   * Retrieve the cloned Tid of the given source Tid parent.
   *
   * @param array $source_mapping
   *   An array keyed by source_tid, source_tid_parent, and cloned_tid.
   * @param int $source_tid_parent
   *   The Tid of a term parent from the cloned vocab.
   *
   * @return int|bool
   *   Returns a term ID if the cloned term ID can be found.
   */
  protected function getParentTid(array $source_mapping, $source_tid_parent) {
    foreach ($source_mapping as $item) {
      if ($item['source_tid'] === $source_tid_parent) {
        return $item['cloned_tid'];
      }
    }
    return FALSE;
  }

  /**
   * Remove all content and tagged with this plan year section.
   *
   * This will remove the plan year section nodes as well.
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
  public function removeSectionContent($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      // Remove all section content that is tagged with this section vocabulary.
      $node_storage = $this->entityTypeManager->getStorage('node');
      foreach ($this->taxonomyService->getVocabularyTids($section_vocabulary_id) as $tid) {
        // Get all content tagged with this term.
        $node_query = $this->database->select('taxonomy_index', 'ti');
        $node_query->fields('ti', ['nid']);
        $node_query->condition('ti.tid', $tid);
        foreach ($node_query->execute() as $node_record) {
          $node_storage->load($node_record->nid)->delete();
        }
      }
      // Remove all state plan year section nodes in this plan year and section
      // id.
      foreach ($this->nodeService->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id) as $state_plan_year_section_nid) {
        $node_storage->load($state_plan_year_section_nid)->delete();
      }
    }

  }

  /**
   * Create a state plans year node that references the state plan year given.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   *
   * @return \Drupal\node\Entity\Node
   *   Returns the state plans year node created or that already existed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createStatePlansYear($plan_year_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $state_plans_year_nid = $this->nodeService->getStatePlansYearByPlanYear($plan_year_id);
    /** @var \Drupal\node\Entity\Node $state_plans_year */
    if (empty($state_plans_year_nid)) {
      $state_plans_year = $node_storage->create([
        'type' => 'state_plans_year',
        'field_plan_year' => [['target_id' => $plan_year_id]],
      ]);
      $state_plans_year->save();
      return $state_plans_year;
    }
    $state_plans_year = $node_storage->load($state_plans_year_nid);
    return $state_plans_year;
  }

  /**
   * Add a state plan year node for the given plan year and group.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $group_id
   *   A group ID.
   *
   * @return \Drupal\node\Entity\Node
   *   Returns the state plan year node created or that already existed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addStatePlanYear($plan_year_id, $group_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $state_plan_year_nid = $this->nodeService->getStatePlanYearByPlanYearAndGroupId($plan_year_id, $group_id);
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    if (!empty($state_plan_year_nid)) {
      $state_plan_year = $node_storage->load($state_plan_year_nid);
      return $state_plan_year;
    }
    $state_plans_year_nid = $this->nodeService->getStatePlansYearByPlanYear($plan_year_id);
    // Ensure that the state plans node was created already, it should have
    // been when the plan year was created, but that might have been imported
    // with config.
    if (empty($state_plans_year_nid)) {
      throw new \Exception('The state plans year must exist before creating a state plan year.');
    }
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $node_storage->create([
      'type' => 'state_plan_year',
      'field_state_plans_year' => [['target_id' => $state_plans_year_nid]],
    ]);
    $state_plan_year->save();
    /** @var \Drupal\group\Entity\Group $group */
    $group = $this->entityTypeManager->getStorage('group')->load($group_id);
    /** @var \Drupal\group\Entity\Group $state_group_entity */
    $group->addContent($state_plan_year, 'group_node:' . $state_plan_year->getType());
    return $state_plan_year;
  }

  /**
   * Add a state plan year section for the given plan year, group, and section.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $group_id
   *   A group ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return \Drupal\node\Entity\Node
   *   Returns the state plan year section node created or that already existed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function addStatePlanYearSection($plan_year_id, $group_id, $section_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    // If this state plan year section already exists, just quit.
    $state_plan_year_section_nid = $this->nodeService->getStatePlanYearSectionByPlanYearGroupAndSection($plan_year_id, $group_id, $section_id);
    /** @var \Drupal\node\Entity\Node $state_plan_year_section */
    if (!empty($state_plan_year_section_nid)) {
      $state_plan_year_section = $node_storage->load($state_plan_year_section_nid);
      return $state_plan_year_section;
    }
    // There is trouble if the state plan year this section belongs to is not
    // created yet.
    $state_plan_year_nid = $this->nodeService->getStatePlanYearByPlanYearAndGroupId($plan_year_id, $group_id);
    if (empty($state_plan_year_nid)) {
      throw new \Exception('The state plan year for plan year ' . $plan_year_id . ' and group ' . $group_id . ' does not exist.');
    }
    /** @var \Drupal\node\Entity\Node $state_plan_year_section */
    $state_plan_year_section = $node_storage->create([
      'type' => 'state_plan_year_section',
      'field_section' => [['target_id' => $section_id]],
      'field_state_plan_year' => [['target_id' => $state_plan_year_nid]],
    ]);
    $state_plan_year_section->save();
    /** @var \Drupal\group\Entity\Group $state_group_entity */
    $state_group_entity = $this->entityTypeManager->getStorage('group')->load($group_id);
    $state_group_entity->addContent($state_plan_year_section, 'group_node:' . $state_plan_year_section->getType());
    return $state_plan_year_section;
  }

  /**
   * Remove state plan year section for the given plan year, group, and section.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $group_id
   *   A group ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeStatePlanYearSection($plan_year_id, $group_id, $section_id) {
    // Be care that section ID is set, otherwise all state plan year sections
    // for this plan year and group will be removed.
    if (NULL === $section_id) {
      return;
    }
    $state_plan_year_section_nid = $this->nodeService->getStatePlanYearSectionByPlanYearGroupAndSection($plan_year_id, $group_id, $section_id);
    if (empty($state_plan_year_section_nid)) {
      return;
    }
    $this->entityTypeManager->getStorage('node')->load($state_plan_year_section_nid)->delete();
  }

}
