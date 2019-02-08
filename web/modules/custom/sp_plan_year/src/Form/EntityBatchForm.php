<?php

namespace Drupal\sp_plan_year\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\sp_create\UpdatePlanYearBatch;
use Drupal\sp_retrieve\CustomEntitiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityBatchForm.
 */
abstract class EntityBatchForm extends EntityForm {

  /**
   * The custom entities retrieval service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * PlanYearEntityWizardForm constructor.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   */
  public function __construct(CustomEntitiesService $custom_entities_retrieval) {
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sp_retrieve.custom_entities')
    );
  }

  /**
   * Retrieve the label of a section.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return string
   *   A section label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSectionLabel($section_id) {
    return $this->customEntitiesRetrieval->getLabel('section', $section_id);
  }

  /**
   * Retrieve the label of a group.
   *
   * @param string $group_id
   *   A group ID.
   *
   * @return string
   *   A section label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getGroupLabel($group_id) {
    return $this->customEntitiesRetrieval->getLabel('group', $group_id);
  }

  /**
   * Add or update section and plan year copied to the plan year.
   *
   * @param string $section_id
   *   A section ID.
   * @param string $plan_year_id_to_copy
   *   A plan year ID to copy from.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchUpdateSectionMeta($section_id, $plan_year_id_to_copy = '') {
    return [
      [UpdatePlanYearBatch::class, 'updateSectionMeta'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
        $plan_year_id_to_copy,
      ],
    ];
  }

  /**
   * Remove section content tagged with terms from this vocab.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchRemoveSectionContent($section_id) {
    return [
      [UpdatePlanYearBatch::class, 'removeSectionContent'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
      ],
    ];
  }

  /**
   * Remove terms from section vocabulary.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchRemoveSectionHierarchy($section_id) {
    return [
      [UpdatePlanYearBatch::class, 'removeSectionHierarchy'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
      ],
    ];
  }

  /**
   * Copy hierarchy from a different plan year if exists.
   *
   * @param string $section_id
   *   A section ID.
   * @param string $plan_year_id_to_copy
   *   A plan year ID to copy from.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchCopySectionHierarchy($section_id, $plan_year_id_to_copy) {
    return [
      [UpdatePlanYearBatch::class, 'copySectionHierarchy'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
        $plan_year_id_to_copy,
      ],
    ];
  }

  /**
   * Create a section vocabulary.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchAddSection($section_id) {
    return [
      [UpdatePlanYearBatch::class, 'addSection'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
      ],
    ];
  }

  /**
   * Remove section vocabulary.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchRemoveSection($section_id) {
    return [
      [UpdatePlanYearBatch::class, 'removeSection'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
      ],
    ];
  }

  /**
   * Remove section and plan year copied from plan year.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchRemoveSectionMeta($section_id) {
    return [
      [UpdatePlanYearBatch::class, 'removeSectionMeta'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
      ],
    ];
  }

  /**
   * Add State Plans Year for the entire plan year if it is missing it.
   *
   * @return array
   *   A batch operation.
   */
  protected function batchAddStatePlansYear() {
    return [
      [UpdatePlanYearBatch::class, 'addStatePlansYear'],
      [
        $this->entity->id(),
      ],
    ];
  }

  /**
   * Add State Plan Year for the group if it is missing it.
   *
   * @param string $group_id
   *   A group ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchAddStatePlanYear($group_id) {
    return [
      [UpdatePlanYearBatch::class, 'addStatePlanYear'],
      [
        $this->entity->id(),
        $group_id,
        $this->getGroupLabel($group_id),
      ],
    ];
  }

  /**
   * Remove the State Plan Year Section node the group if it has it.
   *
   * @param string $section_id
   *   A section ID.
   * @param string $group_id
   *   A group ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchRemoveStatePlanYearSection($section_id, $group_id) {
    return [
      [UpdatePlanYearBatch::class, 'removeStatePlanYearSection'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
        $group_id,
        $this->getGroupLabel($group_id),
      ],
    ];
  }

  /**
   * Add the State Plan Year Section node to the group if it is missing it.
   *
   * @param string $section_id
   *   A section ID.
   * @param string $group_id
   *   A group ID.
   *
   * @return array
   *   A batch operation.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function batchAddStatePlanYearSection($section_id, $group_id) {
    return [
      [UpdatePlanYearBatch::class, 'addStatePlanYearSection'],
      [
        $this->entity->id(),
        $section_id,
        $this->getSectionLabel($section_id),
        $group_id,
        $this->getGroupLabel($group_id),
      ],
    ];
  }

}
