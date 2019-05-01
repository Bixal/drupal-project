<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_expire\ContentService;
use Drupal\sp_plan_year\Entity\PlanYearEntity;
use Exception;

/**
 * Class NodeService.
 */
class NodeService {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Retrieve custom entities.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * SP Retrieve cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new NodeService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Retrieve custom entities.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   SP Retrieve cache bin.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CustomEntitiesService $custom_entities_retrieval, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->cache = $cache;
  }

  /**
   * Return the node storage query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The node storage query.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getQuery() {
    return $this->entityTypeManager->getStorage('node')->getQuery();
  }

  /**
   * Retrieve all state plan year section NIDs for all groups.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   An array of state plan year section node IDs in the given plan year
   *   and section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id) {
    $state_plan_year_section_nids = [];
    $state_plans_year_id = $this->getStatePlansYearByPlanYear($plan_year_id);
    if (FALSE !== $state_plans_year_id) {
      foreach ($this->getStatePlanYearsByStatePlansYear($state_plans_year_id) as $state_plan_year_nid) {
        $state_plan_year_section_nid = $this->getStatePlanYearSectionByStatePlanYearAndSection($state_plan_year_nid, $section_id);
        if (!empty($state_plan_year_section_nid)) {
          $state_plan_year_section_nids[] = $state_plan_year_section_nid;
        }
      }
    }
    return $state_plan_year_section_nids;
  }

  /**
   * Return the state plan year section node ID for state plan year and section.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return int|null
   *   The corresponding state plan year section node ID for the given state
   *   plan year and section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearSectionByStatePlanYearAndSection($state_plan_year_nid, $section_id) {
    $state_plan_year_section_nid = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', PlanYearInfo::SPYS_BUNDLE)
      ->condition('field_section', $section_id)
      ->condition('field_state_plan_year', $state_plan_year_nid)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return current($state_plan_year_section_nid);
  }

  /**
   * Retrieve all state plan year section NIDs for a single group.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   *
   * @return array|null
   *   All state plan year section node IDs for the given state
   *   plan year.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearSectionsByStatePlanYear($state_plan_year_nid) {
    return $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', PlanYearInfo::SPYS_BUNDLE)
      ->condition('field_state_plan_year', $state_plan_year_nid)
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * Retrieve all state plan year NIDs of every group for a state plans year.
   *
   * @param string $state_plans_year_nid
   *   A state plan year NID.
   *
   * @return array
   *   An array of state plan year node IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearsByStatePlansYear($state_plans_year_nid) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', PlanYearInfo::SPY_BUNDLE)
      ->condition('field_state_plans_year', $state_plans_year_nid)
      ->accessCheck(FALSE);
    return $query->execute();
  }

  /**
   * Return the corresponding state plans year node ID for the given plan year.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return int|null
   *   A state plans year node ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlansYearByPlanYear($plan_year_id) {
    $state_plans_year_nid = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', PlanYearInfo::SPZY_BUNDLE)
      ->condition('field_plan_year', $plan_year_id)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return current($state_plans_year_nid);
  }

  /**
   * Return all state plan year node IDs for all group in the given plan year.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array of state plan year node IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearsByPlansYear($plan_year_id) {
    $state_plan_year_nids = [];
    $state_plans_year_id = $this->getStatePlansYearByPlanYear($plan_year_id);
    if (!empty($state_plans_year_id)) {
      $state_plan_year_nids = $this->getStatePlanYearsByStatePlansYear($state_plans_year_id);
    }
    return $state_plan_year_nids;
  }

  /**
   * Get all groups that do not have a plan for the given year.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array of group IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupsMissingPlanYear($plan_year_id) {
    $all_group_ids = $this->customEntitiesRetrieval->getAllStates('ids');
    $group_ids = [];
    $state_plan_year_nids = $this->getStatePlanYearsByPlansYear($plan_year_id);
    // If all groups have a state plan year, return.
    if (count($state_plan_year_nids) === count($all_group_ids)) {
      return [];
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    foreach ($state_plan_year_nids as $state_plan_year_nid) {
      /** @var \Drupal\node\Entity\Node $state_plan_year */
      $state_plan_year = $node_storage->load($state_plan_year_nid);
      $group_ids[] = $this->customEntitiesRetrieval->getGroupId($state_plan_year);
    }

    return array_diff($all_group_ids, $group_ids);
  }

  /**
   * Create cache tags for 'missing' content retrieval.
   *
   * Missing content is determined at a plan year level.
   *
   * @param string|null $plan_year_id
   *   A plan year ID for a single or null for all.
   *
   * @return array
   *   The cache tags.
   */
  public function getMissingContentCacheTags($plan_year_id = NULL) {
    if (NULL !== $plan_year_id) {
      return ['missing_content_' . $plan_year_id];
    }
    else {
      // This is missing content cache that isn't specific to any year.
      return ['missing_content_all'];
    }
  }

  /**
   * Create cache tags for all 'missing' content retrieval.
   *
   * @return array
   *   The cache tags.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMissingContentAllCacheTags() {
    $plan_year_ids = $this->customEntitiesRetrieval->all(PlanYearEntity::ENTITY, 'ids');
    // Get the 'all'.
    $cache_tags = $this->getMissingContentCacheTags();
    foreach ($plan_year_ids as $plan_year_id) {
      $cache_tags = array_merge($cache_tags, $this->getMissingContentCacheTags($plan_year_id));
    }
    return array_filter($cache_tags);
  }

  /**
   * Create cache tags for copying answers retrieval.
   *
   * Copying answers cache is determined at a single state plan year level.
   *
   * @param string|null $state_plan_year_nid
   *   A state plan node ID for a single or null for all.
   *
   * @return array
   *   The cache tags.
   */
  public function getCopyAnswersCacheTags($state_plan_year_nid) {
    return ['copy_answers_' . $state_plan_year_nid];
  }

  /**
   * Create cache tags for all copying answers retrieval.
   *
   * @return array
   *   The cache tags.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCopyAnswersAllCacheTags() {
    $cache_tags = [];
    foreach ($this->customEntitiesRetrieval->all(PlanYearEntity::ENTITY, 'ids') as $plan_year_id) {
      foreach ($this->getStatePlanYearsByPlansYear($plan_year_id) as $state_plan_year_nid) {
        $cache_tags = array_merge($cache_tags, $this->getCopyAnswersCacheTags($state_plan_year_nid));
      }
    }
    return array_filter($cache_tags);
  }

  /**
   * Get all groups that do not have a plan for the given year.
   *
   * @param string|null $plan_year_id
   *   Null if all plan years or a single plan year ID.
   *
   * @return array
   *   Arrays of missing plan year and section group IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupsMissingStatePlanYearsAndStatePlanYearSections($plan_year_id) {
    $cid = __METHOD__ . $plan_year_id;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $return = [];
    $all_group_ids = $this->customEntitiesRetrieval->getAllStates('ids');
    $group_ids_with_plans = [];
    $group_ids_with_sections = [];
    if (empty($all_group_ids)) {
      return $return;
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, $plan_year_id);
    $plan_year_sections = $plan_year->getSections();
    // This value will be true if there is any missing plan years, plan year,
    // or plan year section.
    $return['at_least_one_missing'] = FALSE;
    // This plan has no section entities assigned to it yet, those need to be
    // created first before 'missing' content is created for it.
    if (!empty($plan_year_sections)) {
      $state_plans_year_nid = $this->getStatePlansYearByPlanYear($plan_year_id);
      if (empty($state_plans_year_nid)) {
        $return['plan_year_without_state_plans_year'] = 1;
        $return['at_least_one_missing'] = TRUE;
      }
      else {
        $return['plan_year_without_state_plans_year'] = 0;
      }
      $group_ids_with_plans[$plan_year_id] = [];
      $group_ids_with_sections[$plan_year_id] = [];
      $return['group_ids_without_sections'] = [];
      $state_plan_year_nids = $this->getStatePlanYearsByPlansYear($plan_year_id);
      foreach ($state_plan_year_nids as $state_plan_year_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_year */
        $state_plan_year = $node_storage->load($state_plan_year_nid);
        $group_ids_with_plans[$plan_year_id][] = $this->customEntitiesRetrieval->getGroupId($state_plan_year);
      }
      $return['group_ids_without_plans'] = array_diff($all_group_ids, $group_ids_with_plans[$plan_year_id]);
      if (!empty($return['group_ids_without_plans'])) {
        $return['at_least_one_missing'] = TRUE;
      }
      // If the number of groups without plans is not the same as the number of
      // groups, then there are some groups, then there might be groups missing
      // sections.
      if (count($return['group_ids_without_plans']) !== count($all_group_ids)) {
        foreach ($plan_year_sections as $section) {
          $group_ids_with_sections[$plan_year_id][$section->id()] = [];
          $state_plan_year_section_nids = $this->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section->id());
          // There is at least one group missing this section.
          foreach ($state_plan_year_section_nids as $state_plan_year_section_nid) {
            /** @var \Drupal\node\Entity\Node $state_plan_year_section */
            $state_plan_year_section = $node_storage->load($state_plan_year_section_nid);
            $group_ids_with_sections[$plan_year_id][$section->id()][] = $this->customEntitiesRetrieval->getGroupId($state_plan_year_section);
          }
          $return['group_ids_without_sections'][$section->id()] = array_diff($all_group_ids, $group_ids_with_sections[$plan_year_id][$section->id()]);
          // Remove all groups that are missing entire plans, there is no need
          // to create a single section if the whole plan needs to be created.
          $return['group_ids_without_sections'][$section->id()] = array_diff($return['group_ids_without_sections'][$section->id()], $return['group_ids_without_plans']);
          if (!empty($return['group_ids_without_sections'][$section->id()])) {
            $return['at_least_one_missing'] = TRUE;
          }
        }
      }
    }

    $this->cache->set($cid, $return, Cache::PERMANENT, $this->getMissingContentCacheTags($plan_year_id));
    return $return;
  }

  /**
   * Get all groups that have a non-published plan for the given year.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array of moderation states keyed by group ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupsWithInprogressPlanYear($plan_year_id) {
    $group_moderation_states = [];
    $node_storage = $this->entityTypeManager->getStorage('node');
    foreach ($this->getStatePlanYearsByPlansYear($plan_year_id) as $state_plan_year_nid) {
      /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $node_storage */
      $latest_revision_id = $node_storage->getLatestRevisionId($state_plan_year_nid);
      /** @var \Drupal\node\Entity\Node $state_plan_year */
      $state_plan_year = $node_storage->loadRevision($latest_revision_id);
      $moderation_state = $state_plan_year->get('moderation_state')->value;
      if ($moderation_state !== ContentService::MODERATION_STATE_PUBLISHED) {
        continue;
      }
      $gid = $this->customEntitiesRetrieval->getGroupId($state_plan_year);
      $group_moderation_states[$gid] = $moderation_state;
    }
    return $group_moderation_states;
  }

  /**
   * Get all groups that are missing the given section in the given plan year.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   An array of group IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getGroupsMissingPlanYearSection($plan_year_id, $section_id) {
    $cid = __METHOD__ . $plan_year_id;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $return = [];
    $all_group_ids = $this->customEntitiesRetrieval->getAllStates('ids');
    // Find what plans are not created yet.
    $groups_without_plans = $this->getGroupsMissingPlanYear($plan_year_id);
    // Get the groups that do have plans and see if they have missing section.
    $groups_with_plans = array_diff($all_group_ids, $groups_without_plans);
    // If all groups are missing plans, no need to check missing sections.
    if (count($groups_without_plans) !== count($all_group_ids)) {
      // Get all groups state plan year section node IDs for this plan year
      // and section.
      $state_plan_year_section_nids = $this->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id);
      $node_storage = $this->entityTypeManager->getStorage('node');
      $groups_with_section_ids = [];
      foreach ($state_plan_year_section_nids as $state_plan_year_section_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_plan_year_section */
        $state_plan_plan_year_section = $node_storage->load($state_plan_year_section_nid);
        $groups_with_section_ids[] = $this->customEntitiesRetrieval->getGroupId($state_plan_plan_year_section);
      }
      // Out of all the groups that have plans, get ones that are missing
      // the given section.
      $return = array_diff($groups_with_plans, $groups_with_section_ids);
    }
    $this->cache->set($cid, $return, Cache::PERMANENT, $this->getMissingContentCacheTags($plan_year_id));
    return $return;
  }

  /**
   * Retrieve a single state plan year answer.
   *
   * @param string $node_type
   *   The node type.
   * @param string $field_unique_id_reference
   *   The UUID that uniquiely identifies a term field between years.
   * @param string $plan_year_id
   *   The plan year ID that this answer belongs to.
   * @param string $section_id
   *   The section ID that this answer belongs to.
   * @param string $section_year_term_tid
   *   The term that this piece of answer is based on.
   * @param string $state_plan_year_section_nid
   *   The state plan year section NID that this piece of answer belongs to.
   *
   * @return string
   *   A node ID or empty string if no matching state plan year answer is
   *   found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearAnswer($node_type, $field_unique_id_reference, $plan_year_id, $section_id, $section_year_term_tid, $state_plan_year_section_nid) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    return current($query->condition('type', $node_type)
      ->condition('field_field_unique_id_reference', $field_unique_id_reference)
      ->condition('field_plan_year', $plan_year_id)
      ->condition('field_section', $section_id)
      ->condition('field_section_year_term', $section_year_term_tid)
      ->condition('field_state_plan_year_section', $state_plan_year_section_nid)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute());
  }

  /**
   * Get all state plan year answers tagged with a section term and unique ID.
   *
   * This will get many states content.
   *
   * @param string $section_tid
   *   A term ID in a section vocabulary.
   * @param string $field_unique_id
   *   The Field Unique ID value of a content reference.
   *
   * @return array
   *   An array of state plan year answer node IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getStatePlanYearAnswersBySectionTidAndFieldUniqueId($section_tid, $field_unique_id) {
    $section_term = $this->customEntitiesRetrieval->single('taxonomy_term', $section_tid);
    if (NULL === $section_term) {
      throw new \Exception(sprintf('There is no term with TID %s', $section_tid));
    }
    $section_vocabulary_id = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($section_term->bundle());
    if (FALSE === $section_vocabulary_id) {
      throw new \Exception(sprintf('The give term %s exists but is not a section vocabulary term.', $section_term->label()));
    }
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    // Get all nodes that are tagged with this section term and unique field
    // ID.
    return $query->condition('field_field_unique_id_reference', $field_unique_id)
      ->condition('type', PlanYearInfo::getSpyaNodeBundles(), 'in')
      ->condition('field_section_year_term', $section_tid)
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * Is this a combined plan?
   *
   * @param string $state_plan_year_nid
   *   A state plan node ID.
   *
   * @return bool
   *   True if combined.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function isCombinedPlan($state_plan_year_nid) {
    $state_plan_year_answer_nid = $this->getStatePlanYearAnswerByStatePlanYearAndFieldUniqueId($state_plan_year_nid, PlanYearInfo::COMBINED_QUESTION_FIELD_UNIQUE_ID);
    if (empty($state_plan_year_answer_nid)) {
      return FALSE;
    }
    $state_plan_year_answer = $this->load($state_plan_year_answer_nid);
    return PlanYearInfo::getStatePlanYearAnswerValueField($state_plan_year_answer)->getString() === PlanYearInfo::SPYA_BOOL_YES;
  }

  /**
   * Get a single state plan year answer by state plan year and unique field ID.
   *
   * This will get a single state's content.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   * @param string $field_unique_id
   *   The Field Unique ID value of a content reference.
   *
   * @return string|null
   *   A state plan year answer node ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getStatePlanYearAnswerByStatePlanYearAndFieldUniqueId($state_plan_year_nid, $field_unique_id) {
    $state_plan_year = $this->load($state_plan_year_nid);
    if (NULL === $state_plan_year || $state_plan_year->getType() !== PlanYearInfo::SPY_BUNDLE) {
      throw new Exception(sprintf('There is no state plan year node with node ID %s', $state_plan_year_nid));
    }
    $state_plan_year_section_nids = $this->getStatePlanYearSectionsByStatePlanYear($state_plan_year_nid);
    if (empty($state_plan_year_section_nids)) {
      throw new Exception(sprintf('There are no state plan year sections in state plan year %s', $state_plan_year_nid));
    }
    // Since field unique ID is unique per all state plan year sections
    // (group content), this will only return a single row without knowing
    // which section it actually belongs to.
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    return current($query->condition('field_field_unique_id_reference', $field_unique_id)
      ->condition('type', PlanYearInfo::getSpyaNodeBundles(), 'in')
      ->condition('field_state_plan_year_section', $state_plan_year_section_nids, 'in')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute());
  }

  /**
   * Get a state plan year answer assigned to a section and unique ID.
   *
   * This will get a single state's plan year answer.
   *
   * @param string $state_plan_year_section_nid
   *   A state plan year section node ID.
   * @param string $field_unique_id
   *   The Field Unique ID value of a answer reference.
   *
   * @return int|bool
   *   A state plan year answer node ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getStatePlanYearAnswerByStatePlanYearSectionAndFieldUniqueId($state_plan_year_section_nid, $field_unique_id) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    return current($query->condition('field_field_unique_id_reference', $field_unique_id)
      ->condition('type', PlanYearInfo::getSpyaNodeBundles(), 'in')
      ->condition('field_state_plan_year_section', $state_plan_year_section_nid)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute());
  }

  /**
   * Get all state plan year answers in the given state plan year section.
   *
   * Since state plan year section is group specific, this will be all the
   * groups answers in section in that year.
   *
   * @param string $state_plan_year_section_nid
   *   A state plan year answer node ID.
   *
   * @return array
   *   An array of group IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getStatePlanYearAnswersByStatePlanYearSection($state_plan_year_section_nid) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    return $query
      ->condition('type', PlanYearInfo::getSpyaNodeBundles(), 'in')
      ->condition('field_state_plan_year_section', $state_plan_year_section_nid)
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * Retrieve the state plan year NID for a group in the given plan year.
   *
   * @param string $plan_year_id
   *   State plan year ID.
   * @param string $group_id
   *   A single GID.
   *
   * @return int|null
   *   A single state plan year NID if found otherwise NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearByPlanYearAndGroupId($plan_year_id, $group_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $group_storage = $this->entityTypeManager->getStorage('group');
    $state_plans_year_nid = $this->getStatePlansYearByPlanYear($plan_year_id);
    /** @var \Drupal\group\Entity\Group $group */
    $group = $group_storage->load($group_id);
    $groups_state_plan_years = [];
    /** @var \Drupal\group\Entity\GroupContent $group_content */
    // We have to take the extra step of retrieving all the state plan year
    // that belong to this group in order to narrow the node retrieval to
    // only this group since there is a node of this type created for every
    // group and year.
    foreach ($group->getContent('group_node:state_plan_year') as $group_content) {
      $groups_state_plan_years[] = $group_content->getEntity()->id();
    }
    // There are no state plan year created in this group yet.
    if (empty($groups_state_plan_years)) {
      return NULL;
    }
    return current($node_storage->getQuery()
      ->condition('type', PlanYearInfo::SPY_BUNDLE)
      ->condition('field_state_plans_year', $state_plans_year_nid)
      ->condition('nid', $groups_state_plan_years, 'in')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute());
  }

  /**
   * Retrieve the state plan year section(s) of a group for the plan year.
   *
   * @param string $plan_year_id
   *   State plan year ID.
   * @param string $group_id
   *   A single GID.
   * @param string $section_id
   *   If section ID is given, only a single NID will be returned. If NULL,
   *   then all state plan year section NIDs will be returned.
   *
   * @return array|int|null
   *   An array if state plan year section NIDs if $section_id is NULL,
   *   otherwise, a single NID or NULL if none found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearSectionByPlanYearGroupAndSection($plan_year_id, $group_id, $section_id = NULL) {
    // This state plan year NID is specific to the group.
    $state_plan_year_nid = $this->getStatePlanYearByPlanYearAndGroupId($plan_year_id, $group_id);
    if (empty($state_plan_year_nid)) {
      if ($section_id !== NULL) {
        return [];
      }
      return NULL;
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery()
      ->condition('type', PlanYearInfo::SPYS_BUNDLE)
      ->condition('field_state_plan_year', $state_plan_year_nid)
      ->accessCheck(FALSE);
    if (NULL !== $section_id) {
      $query->condition('field_section', $section_id);
      return current($query->range(0, 1)
        ->execute());
    }
    return $query->execute();
  }

  /**
   * Retrieve the state plan year node IDs that the given year is a source of.
   *
   * A state plan year can have a single source to copy from but can be the
   * source itself of multiple other state plan years.
   * For example, if 2000 is copying from 1996 and a piece of state plan content
   * in 1996 gets edited, we have to know all the state plan years that 1996
   * could be the "source of". The plan year 2000 has the reference to 1996
   * but the opposite needs to be known as well since the value of the from
   * and to need to be known.
   *
   * This function is cached based on 'missing content' ie, the plan year not
   * the state plan year node ID. It's contents can only change if a state plans
   * sections are updated, not if state plan content is updated.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   *
   * @return array
   *   An array of state plan year node IDs that this state plan year is a
   *   source of.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearsCopyingTo($state_plan_year_nid) {
    $state_plan_year_copy_to = [];
    $plan_year_copy_to = $this->customEntitiesRetrieval->allPlanYearCopyTo();
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $this->customEntitiesRetrieval->single('node', $state_plan_year_nid);
    $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($state_plan_year);
    $cid = __METHOD__ . $plan_year_id;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    if (!empty($plan_year_copy_to[$plan_year_id])) {
      $group_id = $this->customEntitiesRetrieval->getGroupId($state_plan_year);
      foreach ($plan_year_copy_to[$plan_year_id] as $plan_year_id_to) {
        $state_plan_year_copy_to[] = $this->getStatePlanYearByPlanYearAndGroupId($plan_year_id_to, $group_id);
      }
    }
    $this->cache->set($cid, $state_plan_year_copy_to, Cache::PERMANENT, $this->getMissingContentCacheTags($plan_year_id));
    return $state_plan_year_copy_to;
  }

  /**
   * Get the user that should be the creator for automatically created nodes.
   *
   * @return \Drupal\user\UserInterface
   *   The user.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function getAutomatedNodeOwner() {
    // Owner should ALWAYS be admin. Grab the revision user as the currently
    // logged in user if available. It won't be in CLI.
    /** @var \Drupal\user\UserInterface $owner_user */
    $owner_user = $this->customEntitiesRetrieval->uuid('user', PlanYearInfo::UUID_USER_AUTOMATED);
    if (NULL === $owner_user) {
      throw new \Exception('The automated user has not been created yet. Please import user content before using this feature.');
    }
    return $owner_user;
  }

  /**
   * Retrieve the homepage NID.
   *
   * @return string|bool
   *   False is no homepage exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getHomepageNid() {
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    return current($query->condition('type', 'homepage')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute());
  }

  /**
   * Load a node.
   *
   * @param string $nid
   *   The node ID.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The node or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load($nid) {
    return $this->entityTypeManager->getStorage('node')->load($nid);
  }

  /**
   * Retrieve all the moderation states of all answers in a section.
   *
   * @param string $state_plan_year_section_nid
   *   A plan year section node ID.
   *
   * @return array
   *   An array of moderation states.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearAnswersModerationStateByStatePlanYearSectionNid($state_plan_year_section_nid) {
    $moderation_states = [];
    foreach ($this->getStatePlanYearAnswersByStatePlanYearSection($state_plan_year_section_nid) as $state_plan_year_answer_nid) {
      $state_plan_year_answer = $this->load($state_plan_year_answer_nid);
      $moderation_states[] = $state_plan_year_answer->get('moderation_state')->getString();
    }
    return $moderation_states;
  }

  /**
   * Retrieve all the moderation states of all sections in a plan year.
   *
   * @param string $state_plan_year_nid
   *   A plan year node ID.
   *
   * @return array
   *   An array of moderation states.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearSectionsModerationStateByStatePlanYearNid($state_plan_year_nid) {
    $moderation_states = [];
    foreach ($this->getStatePlanYearSectionsByStatePlanYear($state_plan_year_nid) as $state_plan_year_section_nid) {
      $state_plan_year_section = $this->load($state_plan_year_section_nid);
      $moderation_states[] = $state_plan_year_section->get('moderation_state')->getString();
    }
    return $moderation_states;
  }

}
