<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_expire\ContentService;
use Drupal\sp_plan_year\Entity\PlanYearEntity;

/**
 * Class CustomEntitiesService.
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
   * Retrieves content by moderation state and workflow.
   *
   * @var \Drupal\sp_expire\ContentService
   */
  protected $moderatedContent;

  /**
   * Retrieve custom entities.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * Retrieve taxonomy service.
   *
   * @var \Drupal\sp_retrieve\TaxonomyService
   */
  protected $taxonomyService;

  /**
   * SP Retrieve cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new TaxonomyService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\sp_expire\ContentService $moderated_content
   *   Retrieves moderated content.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Retrieve custom entities.
   * @param \Drupal\sp_retrieve\TaxonomyService $taxonomy_service
   *   The taxonomy service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   SP Retrieve cache bin.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentService $moderated_content, CustomEntitiesService $custom_entities_retrieval, TaxonomyService $taxonomy_service, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderatedContent = $moderated_content;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->taxonomyService = $taxonomy_service;
    $this->cache = $cache;
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
   * @return int|null
   *   All state plan year section node IDs for the given state
   *   plan year.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearSectionsByStatePlanYearAndSection($state_plan_year_nid) {
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
    $all_group_ids = $this->customEntitiesRetrieval->all('group', 'ids');
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
    $all_group_ids = $this->customEntitiesRetrieval->all('group', 'ids');
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
    $all_group_ids = $this->customEntitiesRetrieval->all('group', 'ids');
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
   * Find all pieces of state plan answers that are referenced by section terms.
   *
   * If a term is updated and the type of state plan answer is changed or
   * removed, this will be used to find that "orphan" content that needs to
   * go away.
   *
   * @return array
   *   An array state plan year answer node IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getOrphansStatePlanYearAnswers() {
    $cid = __METHOD__;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $orphans = [];
    foreach (PlanYearInfo::getSpyaNodeBundles() as $node_bundle) {
      foreach ($node_storage->getQuery()
        ->condition('type', $node_bundle)
        ->accessCheck(FALSE)
        ->execute() as $state_plan_year_answer_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_year_answer */
        $state_plan_year_answer = $node_storage->load($state_plan_year_answer_nid);
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $section_year_term_field */
        $section_year_term_field = $state_plan_year_answer->get('field_section_year_term')->get(0);
        // If there is no term referenced, this piece of content is in a bad
        // state.
        if (empty($section_year_term_field)) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $section_year_term_tid = $section_year_term_field->getValue()['target_id'];
        /** @var \Drupal\taxonomy\Entity\Term $section_year_term */
        $section_year_term = $term_storage->load($section_year_term_tid);
        // Make sure the referenced term still exists.
        if (NULL === $section_year_term) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $state_plan_year_content_info = $this->taxonomyService->getStatePlanYearContentInfoFromSectionYearTerm($section_year_term);
        // Ensure that the node type matches between the node and the term.
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $plan_year_field */
        $plan_year_field = $state_plan_year_answer->get('field_plan_year');
        // If there is no plan year referenced, this piece of content is in a
        // bad state.
        if ($plan_year_field->isEmpty()) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $plan_year_id = $plan_year_field->getString();
        // The plan year from the node and term don't line up.
        if ($state_plan_year_content_info['plan_year_id'] !== $plan_year_id) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $section_field */
        $section_field = $state_plan_year_answer->get('field_section');
        // If there is no section referenced, this piece of content is in a
        // bad state.
        if ($section_field->isEmpty()) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $section_id = $section_field->getString();
        // The section from the node and term don't line up.
        if ($state_plan_year_content_info['section_id'] !== $section_id) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        /** @var \Drupal\Core\Field\FieldItemList $field_unique_id_reference_field */
        $field_unique_id_reference_field = $state_plan_year_answer->get('field_field_unique_id_reference');
        // If there is no field unique ID referenced, this piece of content is
        // in a bad state.
        if ($field_unique_id_reference_field->isEmpty()) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $field_unique_id_reference = $field_unique_id_reference_field->getString();
        // The section from the node and term don't line up.
        if (empty($state_plan_year_content_info['content'][$field_unique_id_reference])) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        // Odds are that this referenced term changed from a 'node' to be shown
        // to states to a 'section' placeholder.
        if (!empty($state_plan_year_content_info['content'][$field_unique_id_reference]['section'])) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        // The node type to be created if the term does not match
        // the node type created.
        if ($node_bundle !== $state_plan_year_content_info['content'][$field_unique_id_reference]['node_bundle']) {
          $orphans[] = $state_plan_year_answer_nid;
        }
      }
    };
    $this->cache->set($cid, $orphans, Cache::PERMANENT, $this->getMissingContentCacheTags());
    return $orphans;
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
   * Get all groups that are missing plan year answers in the given plan year.
   *
   * Plan year content is ALL nodes (including answers) while answers is just
   * the plan year answer nodes.
   *
   * This cache is based off of all 'missing' content and is only effected
   * if new terms are added or removed, not the 'copy answers' cache that is
   * effected whenever a term is updated.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array fields for missing answers that can be used to create them.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMissingPlanYearAnswers($plan_year_id) {
    $cid = __METHOD__ . $plan_year_id;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $return = [];
    // This array will be keyed by section ID and created as needed.
    $state_plan_year_section_nids = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, $plan_year_id);
    foreach ($plan_year->getSections() as $section) {
      // Get all the terms in this section vocabulary.
      $plan_year_vid = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section->id());
      foreach ($this->taxonomyService->getVocabularyTids($plan_year_vid) as $tid) {
        /** @var \Drupal\taxonomy\Entity\Term $section_year_term */
        $section_year_term = $this->customEntitiesRetrieval->single('taxonomy_term', $tid);
        $state_plan_year_content_info = $this->taxonomyService->getStatePlanYearContentInfoFromSectionYearTerm($section_year_term);
        $section_id = $state_plan_year_content_info['section_id'];
        // The 'content' key holds which nodes need to be created.
        foreach ($state_plan_year_content_info['content'] as $content_info) {
          // If this term field is not referencing a bundle to be created, skip
          // it.
          if (empty($content_info['node_bundle'])) {
            continue;
          }
          // Wait till the last minute to get all the state plan section node
          // IDs that need to be checked.
          if (!isset($state_plan_year_section_nids[$section_id])) {
            $state_plan_year_section_nids[$section_id] = $this->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id);
          }
          // Check that this referenced answer already exists for each
          // groups state plan year section node.
          foreach ($state_plan_year_section_nids[$section_id] as $state_plan_year_section_nid) {
            if (empty($this->getStatePlanYearAnswer(
              $content_info['node_bundle'],
              $content_info['term_field_uuid'],
              $plan_year_id,
              $section_id,
              $tid,
              $state_plan_year_section_nid
            ))) {
              $return[] = [
                'node_bundle' => $content_info['node_bundle'],
                'field_unique_id_reference' => $content_info['term_field_uuid'],
                'plan_year' => $plan_year_id,
                'section' => $section_id,
                'section_year_term' => $tid,
                'state_plan_year_section' => $state_plan_year_section_nid,
              ];
            }
          }
        }
      }
    }
    $this->cache->set($cid, $return, Cache::PERMANENT, $this->getMissingContentCacheTags($plan_year_id));
    return $return;
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
   * Can a state plan year answer value be copied from one year to another?
   *
   * This will not copy if:
   *   * The given nodes don't exist.
   *   * The plan years of both nodes match.
   *   * The field that determines that the answers are the 'same' just from
   *      different years does not match.
   *   * Any of the given nodes are orphans and are to be deleted.
   *   * There is no value to copy from.
   *   * There is already a value saved to.
   *
   * @param string $from_state_plan_year_answer_nid
   *   A state plan year answer node ID to copy the value from.
   * @param string $to_state_plan_year_answer_nid
   *   A state plan year answer node ID to copy the value to.
   *
   * @return bool
   *   True if this answer can be copied.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function canCopyStatePlanYearAnswer($from_state_plan_year_answer_nid, $to_state_plan_year_answer_nid) {
    /** @var \Drupal\node\Entity\Node $from */
    $from = $this->customEntitiesRetrieval->single('node', $from_state_plan_year_answer_nid);
    if (NULL === $from) {
      throw new \Exception(sprintf('The "from" state plan year answer node did not exist (%d)', $from_state_plan_year_answer_nid));
    }
    /** @var \Drupal\node\Entity\Node $to */
    $to = $this->customEntitiesRetrieval->single('node', $to_state_plan_year_answer_nid);
    if (NULL === $to) {
      throw new \Exception(sprintf('The "to" state plan year answer node did not exist (%d)', $to_state_plan_year_answer_nid));
    }
    $plan_year_id_from = PlanYearInfo::getPlanYearIdFromEntity($from);
    $plan_year_id_to = PlanYearInfo::getPlanYearIdFromEntity($to);
    if ($plan_year_id_from === $plan_year_id_to) {
      throw new \Exception(sprintf('The "to" (%d) and "from" (%d) plan year IDs cannot copy from the same year (%s).', $to->id(), $from->id(), PlanYearInfo::getPlanYearIdFromEntity($to)));
    }
    if ($from->get('field_field_unique_id_reference')->getString() !== $to->get('field_field_unique_id_reference')->getString()) {
      throw new \Exception(sprintf('The "to" (%d) and "from" (%d) state plan year answer nodes are not descended from the same term in a different year.', $to->id(), $from->id()));
    }
    $orphans = $this->getOrphansStatePlanYearAnswers();
    // If either the from or to are orphans (to be deleted) do not copy their
    // value.
    if (in_array($to->id(), $orphans, TRUE)) {
      return FALSE;
    }
    if (in_array($from->id(), $orphans, TRUE)) {
      return FALSE;
    }
    $from_value_field = PlanYearInfo::getStatePlanYearAnswerValueField($from);
    // There is nothing to copy from, no answer was given.
    if ($from_value_field->isEmpty()) {
      return FALSE;
    }
    $to_value_field = PlanYearInfo::getStatePlanYearAnswerValueField($to);
    // The state already answered this question, do not overwrite.
    if (!$to_value_field->isEmpty()) {
      return FALSE;
    }
    return TRUE;
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
   * Retrieve count of the total state plan year answers to copy and a message.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   *
   * @return array
   *   'Count' will be greater than 0 if there is plan year answer to copy and
   *   'message' will hold the corresponding supported or unsupported message.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearAnswersWithCopiableAnswersByStatePlanYearSummary($state_plan_year_nid) {
    $return = [
      'count' => 0,
      'message' => '',
    ];
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $this->entityTypeManager->getStorage('node')->load($state_plan_year_nid);
    if (PlanYearInfo::SPY_BUNDLE !== $state_plan_year->getType()) {
      $return['message'] = $this->t('%title is not a a state plan year, %type are not supported.', [
        '%title' => $state_plan_year->getTitle(),
        '%type' => $state_plan_year->getType(),
      ]);
      return $return;
    }
    $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($state_plan_year);
    if (!empty($this->getOrphansStatePlanYearAnswers()) || !empty($this->getMissingPlanYearAnswers($plan_year_id))) {
      $return['message'] = Link::createFromRoute($this->t('%title must update content before copying answers.', ['%title' => $state_plan_year->getTitle()]), 'entity.plan_year.content', [PlanYearEntity::ENTITY => $plan_year_id])
        ->toString();
    }
    $copiable_answers_by_section = $this->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYear($state_plan_year->id());
    if (empty($copiable_answers_by_section)) {
      $return['message'] = $this->t('%title has no eligible answers to copy from a previous plan year.', ['%title' => $state_plan_year->getTitle()]);
      return $return;
    }
    $copiable_answers_total = 0;
    foreach (array_column($copiable_answers_by_section, 'state_plan_year_answers') as $state_plan_year_answers) {
      $copiable_answers_total += count($state_plan_year_answers);
    };
    $return['message'] = $this->formatPlural($copiable_answers_total, '%title can copy %count answer from a previous plan year.', '%title can copy %count answers from a previous plan year.', ['%count' => $copiable_answers_total, '%title' => $state_plan_year->getTitle()]);
    $return['count'] = $copiable_answers_total;
    return $return;
  }

  /**
   * Retrieve all state plan year answers that can be copied in this plan year.
   *
   * Since this is using a state plan year node ID, this will retrieve only
   * a single groups content.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   *
   * @return array
   *   An array keyed by section ID with sub-arrays with keys of 'to' and 'from'
   *   that hold the node ID of the content to copy from and to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getStatePlanYearAnswersWithCopiableAnswersByStatePlanYear($state_plan_year_nid) {
    $cid = __METHOD__ . $state_plan_year_nid;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $return = [];
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $this->customEntitiesRetrieval->single('node', $state_plan_year_nid);
    if (NULL === $state_plan_year) {
      throw new \Exception(sprintf('Unable to load state plan year %s', $state_plan_year_nid));
    }
    $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($state_plan_year);
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single('plan_year', $plan_year_id);
    // If this plan year is not copying from any years, no need to check.
    $copy_from_plan_year_section = array_filter($plan_year->getCopyFromPlanYearSectionArray());
    if (empty($copy_from_plan_year_section)) {
      return $return;
    }
    // Use the state plan year node to determine what group this is.
    $group_id = $this->customEntitiesRetrieval->getGroupId($state_plan_year);
    foreach ($copy_from_plan_year_section as $section_id => $plan_year_id_from) {
      $state_plan_year_section_nid = $this->getStatePlanYearSectionByStatePlanYearAndSection($state_plan_year_nid, $section_id);
      if (empty($state_plan_year_section_nid)) {
        continue;
      }
      // All state plan year answers for a this state in the section.
      $state_plan_year_answers_to_nids = $this->getStatePlanYearAnswersByStatePlanYearSection($state_plan_year_section_nid);
      if (empty($state_plan_year_answers_to_nids)) {
        continue;
      }
      // Get the state plan year that is being copied from.
      $state_plan_year_from_nid = $this->getStatePlanYearByPlanYearAndGroupId($plan_year_id_from, $group_id);
      if (empty($state_plan_year_from_nid)) {
        continue;
      }
      // Get the state plan year section that the state plan year answer to be
      // copied from belongs to.
      $state_plan_year_section_from_nid = $this->getStatePlanYearSectionByStatePlanYearAndSection($state_plan_year_from_nid, $section_id);
      foreach ($state_plan_year_answers_to_nids as $state_plan_year_answer_to_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_year_answer_to */
        $state_plan_year_answer_to = $this->customEntitiesRetrieval->single('node', $state_plan_year_answer_to_nid);
        // The to and from should share this same unique ID.
        $shared_field_field_unique_id_reference = $state_plan_year_answer_to->get('field_field_unique_id_reference')->getString();
        // Retrieve the state plan answer that will be copied from.
        $state_plan_year_answer_from_nid = $this->getStatePlanYearAnswerByStatePlanYearSectionAndFieldUniqueId($state_plan_year_section_from_nid, $shared_field_field_unique_id_reference);
        // Make sure that the from piece of state plan year content can be
        // found.
        if (empty($state_plan_year_answer_from_nid)) {
          continue;
        }
        // Now that all the information needed is found, determine if copying
        // is possible.
        if (TRUE !== $this->canCopyStatePlanYearAnswer($state_plan_year_answer_from_nid, $state_plan_year_answer_to_nid)) {
          continue;
        }
        if (empty($return[$section_id])) {
          $return[$section_id] = [
            'state_plan_year_section_from' => $state_plan_year_section_from_nid,
            'state_plan_year_answers' => [],
          ];
        }
        $return[$section_id]['state_plan_year_answers'][] = ['from' => $state_plan_year_answer_from_nid, 'to' => $state_plan_year_answer_to_nid];
      }
    }
    $this->cache->set($cid, $return, Cache::PERMANENT, $this->getCopyAnswersCacheTags($state_plan_year_nid));
    return $return;
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

}
