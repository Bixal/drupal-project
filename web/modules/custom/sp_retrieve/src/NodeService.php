<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupContent;
use Drupal\sp_expire\ContentService;

/**
 * Class CustomEntitiesService.
 */
class NodeService {

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
   * Constructs a new TaxonomyService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\sp_expire\ContentService $moderated_content
   *   Retrieves moderated content.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Retrieve custom entities.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContentService $moderated_content, CustomEntitiesService $custom_entities_retrieval) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderatedContent = $moderated_content;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
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
      ->condition('type', 'state_plan_year_section')
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
      ->condition('type', 'state_plan_year_section')
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
      ->condition('type', 'state_plan_year')
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
      ->condition('type', 'state_plans_year')
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
      /** @var \Drupal\group\Entity\GroupContent $group_content */
      $group_content = GroupContent::loadByEntity($node_storage->load($state_plan_year_nid));
      $group_content = current($group_content);
      $group_ids[] = $group_content->getGroup()->id();
    }

    return array_diff($all_group_ids, $group_ids);
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
  public function getGroupsMissingStatePlanYearsAndStatePlanYearSections($plan_year_id = NULL) {
    $return = [];
    $all_group_ids = $this->customEntitiesRetrieval->all('group', 'ids');
    $group_ids_with_plans = [];
    $group_ids_with_sections = [];
    if (empty($all_group_ids)) {
      return $return;
    }
    if (NULL === $plan_year_id) {
      $plan_years = $this->customEntitiesRetrieval->all('plan_year', 'ids');
    }
    else {
      $plan_years[] = $plan_year_id;
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var string $plan_year_id */
    foreach ($plan_years as $plan_year_id) {
      $return[$plan_year_id] = [];
      $state_plans_year_nid = $this->getStatePlansYearByPlanYear($plan_year_id);
      if (empty($state_plans_year_nid)) {
        $return[$plan_year_id]['plan_year_without_state_plans_year'] = 1;
      }
      else {
        $return[$plan_year_id]['plan_year_without_state_plans_year'] = 0;
      }
      $group_ids_with_plans[$plan_year_id] = [];
      $group_ids_with_sections[$plan_year_id] = [];
      $return[$plan_year_id]['group_ids_without_sections'] = [];
      $state_plan_year_nids = $this->getStatePlanYearsByPlansYear($plan_year_id);
      foreach ($state_plan_year_nids as $state_plan_year_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_year */
        $state_plan_year = $node_storage->load($state_plan_year_nid);
        /** @var \Drupal\group\Entity\GroupContent $group_content */
        $group_content = GroupContent::loadByEntity($state_plan_year);
        $group_content = current($group_content);
        $group_ids_with_plans[$plan_year_id][] = $group_content->getGroup()->id();
      }
      $return[$plan_year_id]['group_ids_without_plans'] = array_diff($all_group_ids, $group_ids_with_plans[$plan_year_id]);
      // If the number of groups without plans is not the same as the number of
      // groups, then there are some groups, then there might be groups missing
      // sections.
      if (count($return[$plan_year_id]['group_ids_without_plans']) !== count($all_group_ids)) {
        /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
        $plan_year = $this->customEntitiesRetrieval->single('plan_year', $plan_year_id);
        foreach ($plan_year->getSections() as $section) {
          $group_ids_with_sections[$plan_year_id][$section->id()] = [];
          $state_plan_year_section_nids = $this->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section->id());
          // There is at least one group missing this section.
          foreach ($state_plan_year_section_nids as $state_plan_year_section_nid) {
            /** @var \Drupal\node\Entity\Node $state_plan_year_section */
            $state_plan_year_section = $node_storage->load($state_plan_year_section_nid);
            /** @var \Drupal\group\Entity\GroupContent $group_content */
            $group_content = GroupContent::loadByEntity($state_plan_year_section);
            $group_content = current($group_content);
            $group_ids_with_sections[$plan_year_id][$section->id()][] = $group_content->getGroup()->id();
          }
          $return[$plan_year_id]['group_ids_without_sections'][$section->id()] = array_diff($all_group_ids, $group_ids_with_sections[$plan_year_id][$section->id()]);
          // Remove all groups that are missing entire plans, there is no need
          // to create a single section if the whole plan needs to be created.
          $return[$plan_year_id]['group_ids_without_sections'][$section->id()] = array_diff($return[$plan_year_id]['group_ids_without_sections'][$section->id()], $return[$plan_year_id]['group_ids_without_plans']);
        }
      }
    }

    if (NULL !== $plan_year_id) {
      return current($return);
    }
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
      /** @var \Drupal\group\Entity\GroupContent $group_content */
      $group_content = GroupContent::loadByEntity($state_plan_year);
      $group_content = current($group_content);
      $group_moderation_states[$group_content->getGroup()->id()] = $moderation_state;
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
    $all_group_ids = $this->customEntitiesRetrieval->all('group', 'ids');
    // Find what plans are not created yet.
    $groups_without_plans = $this->getGroupsMissingPlanYear($plan_year_id);
    // Get the groups that do have plans and see if they have missing section.
    $groups_with_plans = array_diff($all_group_ids, $groups_without_plans);
    // If all groups are missing plans, no need to check missing sections.
    if (count($groups_without_plans) === count($all_group_ids)) {
      return [];
    }
    // Get all groups state plan year section node IDs for this plan year
    // and section.
    $state_plan_year_section_nids = $this->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id);
    $node_storage = $this->entityTypeManager->getStorage('node');
    $groups_with_section_ids = [];
    foreach ($state_plan_year_section_nids as $state_plan_year_section_nid) {
      /** @var \Drupal\group\Entity\GroupContent $group_content */
      $group_content = GroupContent::loadByEntity($node_storage->load($state_plan_year_section_nid));
      $group_content = current($group_content);
      $groups_with_section_ids[] = $group_content->getGroup()->id();
    }
    // Out of all the groups that have plans, get ones that are missing
    // the given section.
    return array_diff($groups_with_plans, $groups_with_section_ids);
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
      ->condition('type', 'state_plan_year')
      ->condition('field_state_plans_year', $state_plans_year_nid)
      ->condition('nid', $groups_state_plan_years, 'in')
      ->range(0, 1)
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
      ->condition('type', 'state_plan_year_section')
      ->condition('field_state_plan_year', $state_plan_year_nid);
    if (NULL !== $section_id) {
      $query->condition('field_section', $section_id);
      return current($query->range(0, 1)
        ->execute());
    }
    return $query->execute();
  }

}
