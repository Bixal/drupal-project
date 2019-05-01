<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sp_plan_year\Entity\PlanYearEntity;
use Drupal\group\Entity\GroupContent;

/**
 * Class CustomEntitiesService.
 */
class CustomEntitiesService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a new CreateStatePlanService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * Retrieve all entity IDs or entities of a given type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entities_or_ids
   *   Either entities or ids.
   * @param string $bundle
   *   An optional bundle if the entity supports it.
   * @param string $bundle_key
   *   The bundle key that is the field name.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|int[]
   *   An array of entity IDs or entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @TODO: Move this to a new service that is more generic.
   */
  public function all($entity_type, $entities_or_ids = 'entities', $bundle = 'all', $bundle_key = 'type') {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $storage->getQuery()
      ->accessCheck(FALSE);
    if ($bundle !== 'all') {
      $query->condition($bundle_key, $bundle);
    }
    $entity_ids = $query
      ->execute();
    if (empty($entity_ids)) {
      return [];
    }
    if ($entities_or_ids === 'ids') {
      return $entity_ids;
    }
    return $storage->loadMultiple($entity_ids);
  }

  /**
   * Retrieve all entity labels of the given entity type.
   *
   * @param string $entity_type
   *   An entity type.
   * @param string $bundle
   *   An optional bundle if the entity supports it.
   * @param string $bundle_key
   *   The bundle key that is the field name.
   *
   * @return array
   *   An array of entity labels keyed by entity ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @TODO: Move this to a new service that is more generic.
   */
  public function labels($entity_type, $bundle = 'all', $bundle_key = 'type') {
    $key = md5(__CLASS__ . __METHOD__ . $entity_type);
    $labels = &drupal_static($key);
    if (NULL !== $labels) {
      return $labels;
    }
    $labels = [];
    $entities = $this->all($entity_type, 'entities', $bundle, $bundle_key);
    foreach ($entities as $entity) {
      $labels[$entity->id()] = $entity->label();
    }
    return $labels;
  }

  /**
   * Retrieve the label of a custom entity.
   *
   * @param string $entity_type
   *   An entity type.
   * @param string $entity_id
   *   An entity ID.
   *
   * @return string
   *   A section label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @TODO: Move this to a new service that is more generic.
   */
  public function getLabel($entity_type, $entity_id) {
    $all_labels = $this->labels($entity_type);
    if (!empty($all_labels[$entity_id])) {
      return $all_labels[$entity_id];
    }
    return 'Missing Label';
  }

  /**
   * Load a single entity.
   *
   * @param string $entity_type
   *   An entity type.
   * @param string $entity_id
   *   An entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @TODO: Move this to a new service that is more generic.
   */
  public function single($entity_type, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    return $storage->load($entity_id);
  }

  /**
   * Load a single entity by UUID.
   *
   * @param string $entity_type
   *   An entity type.
   * @param string $uuid
   *   An entity UUID.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function uuid($entity_type, $uuid) {
    return $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
  }

  /**
   * Retrieve all section IDs and labels in every plan year.
   *
   * @return array
   *   An array keyed by plan year IDs and values of an array of section labels
   *   keyed by section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function allSectionsByPlanYear() {
    $return = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    foreach ($this->all(PlanYearEntity::ENTITY, 'entities') as $plan_year) {
      $sections = $this->allSectionsInPlanYear($plan_year);
      if (!empty($sections)) {
        $return[$plan_year->id()] = $sections;
      }
    }
    return $return;
  }

  /**
   * Retrieve an array of sections in a plan year keyed by section ID.
   *
   * @param \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year
   *   A plan year entity.
   *
   * @return array
   *   An array of sections in a plan year keyed by section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function allSectionsInPlanYear(PlanYearEntity $plan_year) {
    $return = [];
    foreach ($plan_year->getSections() as $section) {
      $return[$section->id()] = $section->label();
    }
    return $return;
  }

  /**
   * Retrieve all plan year IDs and labels used in each section.
   *
   * @param string $hide_plan_year
   *   A plan year ID to not include.
   *
   * @return array
   *   An array keyed by section IDs and values of an array of plan year labels
   *   keyed by plan year ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function allPlanYearsBySection($hide_plan_year) {
    $return = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    foreach ($this->all(PlanYearEntity::ENTITY, 'entities') as $plan_year) {
      if ($plan_year->id() === $hide_plan_year) {
        continue;
      }
      foreach ($plan_year->getSections() as $section) {
        $return[$section->id()][$plan_year->id()] = $plan_year->label();
      }
    }
    return $return;
  }

  /**
   * Retrieve all the plan years that each plan year is copying from.
   *
   * @return array
   *   An array keyed by plan year with a value of all plan years it is copying
   *   from.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function allPlanYearCopyFrom() {
    $return = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    foreach ($this->all(PlanYearEntity::ENTITY, 'entities') as $plan_year) {
      $plan_year[$plan_year->id()] = [];
      foreach ($plan_year->getCopyFromPlanYearSectionArray() as $copy_from_plan_year_id) {
        // Since the sections can be copying from the same year, don't add the
        // same year twice.
        if (!in_array($copy_from_plan_year_id, $return[$plan_year->id()])) {
          $return[$plan_year->id()][] = $copy_from_plan_year_id;
        }
      }
    }
    return $return;
  }

  /**
   * Retrieve all the plan years that each plan year is copying to.
   *
   * This is all plan years that each plan year is a source to as opposed to the
   * above which is all plan years that a plan year is a source from.
   *
   * @return array
   *   An array keyed by plan year with a value of all plan years it is copying
   *   to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function allPlanYearCopyTo() {
    $return = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    foreach ($this->all(PlanYearEntity::ENTITY, 'entities') as $plan_year) {
      foreach ($plan_year->getCopyFromPlanYearSectionArray() as $copy_from_plan_year_id) {
        // A plan year can be being copied to by the same year multiple times.
        if (!in_array($plan_year->id(), $return[$copy_from_plan_year_id])) {
          $return[$copy_from_plan_year_id][] = $plan_year->id();
        }
      }
    }
    return $return;
  }

  /**
   * Retrieve the group that the $group_content entity belongs to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group_content
   *   An entity that belongs to a group.
   *
   * @return \Drupal\group\Entity\GroupInterface|bool
   *   False if the entity does not belong to a group otherwise the group.
   */
  public function getGroup(ContentEntityInterface $group_content) {
    $group_id = &drupal_static(__FUNCTION__ . $group_content->uuid());
    if (NULL !== $group_id) {
      return $group_id;
    }
    $group_contents = GroupContent::loadByEntity($group_content);
    if (empty($group_contents)) {
      return FALSE;
    }
    $group_content = current($group_contents);
    return $group_content->getGroup();
  }

  /**
   * Retrieve the group ID that the $group_content entity belongs to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $group_content
   *   An entity that belongs to a group.
   *
   * @return string|bool
   *   False if the entity does not belong to a group otherwise the group ID.
   */
  public function getGroupId(ContentEntityInterface $group_content) {
    $group = $this->getGroup($group_content);
    return FALSE === $group ? FALSE : $group->id();
  }

  /**
   * Retrieve all state groups.
   *
   * @param string $entities_or_ids
   *   Either entities or ids.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|int[]
   *   An array of all state groups.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllStates($entities_or_ids = 'entities') {
    return $this->all('group', $entities_or_ids, 'state', 'type');
  }

  /**
   * Retrieve all state group's labels.
   *
   * @return array
   *   An array of group ID to group label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllStateLabels() {
    return $this->labels('group', 'state', 'type');
  }

}
