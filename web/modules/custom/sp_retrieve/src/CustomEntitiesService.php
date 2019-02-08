<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Constructs a new CreateStatePlanService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Retrieve all entity IDs or entities of a given type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entities_or_ids
   *   Either entities or ids.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|int
   *   An array of entity IDs or entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @TODO: Move this to a new service that is more generic.
   */
  public function all($entity_type, $entities_or_ids = 'entities') {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entity_ids = $storage->getQuery()
      ->accessCheck(FALSE)
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
   *
   * @return array
   *   An array of entity labels keyed by entity ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @TODO: Move this to a new service that is more generic.
   */
  public function labels($entity_type) {
    $key = md5(__CLASS__ . __METHOD__ . $entity_type);
    $labels = &drupal_static($key);
    if (NULL !== $labels) {
      return $labels;
    }
    $labels = [];
    $entities = $this->all($entity_type, 'entities');
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
    foreach ($this->all('plan_year', 'entities') as $plan_year) {
      foreach ($plan_year->getSections() as $section) {
        $return[$plan_year->id()][$section->id()] = $section->label();
      }
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
    foreach ($this->all('plan_year', 'entities') as $plan_year) {
      if ($plan_year->id() === $hide_plan_year) {
        continue;
      }
      foreach ($plan_year->getSections() as $section) {
        $return[$section->id()][$plan_year->id()] = $plan_year->label();
      }
    }
    return $return;
  }

}
