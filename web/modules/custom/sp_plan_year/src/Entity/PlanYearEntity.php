<?php

namespace Drupal\sp_plan_year\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Plan Year entity.
 *
 * @ConfigEntityType(
 *   id = "plan_year",
 *   label = @Translation("Plan Year"),
 *   handlers = {
 *     "access" = "Drupal\sp_plan_year\PlanYearAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sp_plan_year\PlanYearEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sp_plan_year\Form\PlanYearEntityForm",
 *       "edit" = "Drupal\sp_plan_year\Form\PlanYearEntityForm",
 *       "wizard" = "Drupal\sp_plan_year\Form\PlanYearEntityWizardForm",
 *       "delete" = "Drupal\sp_plan_year\Form\PlanYearEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\sp_plan_year\PlanYearEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "plan_year",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/plan_year/{plan_year}",
 *     "add-form" = "/admin/structure/plan_year/add",
 *     "edit-form" = "/admin/structure/plan_year/{plan_year}/edit",
 *     "wizard-form" = "/admin/structure/plan_year/{plan_year}/wizard",
 *     "delete-form" = "/admin/structure/plan_year/{plan_year}/delete",
 *     "collection" = "/admin/structure/plan_year"
 *   }
 * )
 */
class PlanYearEntity extends ConfigEntityBase implements PlanYearEntityInterface {

  /**
   * The Plan Year ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Plan Year label.
   *
   * @var string
   */
  protected $label;

  /**
   * An array of section entity IDs.
   *
   * @var array
   */
  protected $sections;

  /**
   * A comma separated section ID to plan year ID list.
   *
   * @var string
   */
  protected $copy_from_plan_year_section;

  /**
   * Retrieve stored section entity IDs and convert to entities.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   An array of section entities keyed by entity ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSections() {
    if (!empty($this->sections)) {
      $sections = [];
      foreach ($this->sections as $section) {
        $sections[] = $section['target_id'];
      }
      return $this->entityTypeManager()
        ->getStorage('section')
        ->loadMultiple($sections);
    }
    return [];
  }

  /**
   * Retrieve the stored plan year section combos to copy from.
   *
   * @return string
   *   A comma separated section ID to plan year ID list.
   */
  public function getCopyFromPlanYearSection() {
    return $this->copy_from_plan_year_section;
  }

  /**
   * Convert the string value to an array of section IDs to plan year IDs.
   *
   * @return array
   *   An array of section IDs to plan year IDs.
   */
  public function getCopyFromPlanYearSectionArray() {
    $return = [];
    foreach (explode(',', $this->getCopyFromPlanYearSection()) as $item) {
      if (empty($item)) {
        continue;
      }
      list($section_id, $plan_year_id) = explode('=', $item);
      $return[$section_id] = $plan_year_id;
    }
    return $return;
  }

  /**
   * Remove a section from the plan year meta data.
   *
   * @param string $section_id
   *   The section ID to remove.
   */
  public function removeSection($section_id) {
    $this->sections = array_values($this->sections);
    $key = FALSE;
    if (!empty($this->sections)) {
      $section_ids = array_column($this->sections, 'target_id');
      $key = array_search($section_id, $section_ids);
    }
    if (FALSE !== $key) {
      unset($this->sections[$key]);
    }
    $copy_from_plan_year_section_array = $this->getCopyFromPlanYearSectionArray();
    // Remove this section from the plan year IDs to copy.
    if (!empty($copy_from_plan_year_section_array[$section_id])) {
      unset($copy_from_plan_year_section_array[$section_id]);
    }
    $this->copy_from_plan_year_section = $this->copyFromPlanYearSectionArrayToString($copy_from_plan_year_section_array);
  }

  /**
   * Add a section to the plan year meta data.
   *
   * @param string $section_id
   *   The section ID to add.
   * @param string $plan_year_id_to_copy
   *   Optional plan year ID that will copy the hierarchy from that year.
   */
  public function addSection($section_id, $plan_year_id_to_copy = '') {
    $key = FALSE;
    if (!empty($this->sections)) {
      $section_ids = array_column($this->sections, 'target_id');
      $key = array_search($section_id, $section_ids);
    }
    if (FALSE === $key) {
      $this->sections[] = ['target_id' => $section_id];
    }
    $copy_from_plan_year_section_array = $this->getCopyFromPlanYearSectionArray();
    // This section was copying from a previous plan year, remove it.
    if (!empty($copy_from_plan_year_section_array[$section_id])) {
      unset($copy_from_plan_year_section_array[$section_id]);
    }
    // Add the new plan year ID to copy from if one is set.
    if (!empty($plan_year_id_to_copy)) {
      $copy_from_plan_year_section_array[$section_id] = $plan_year_id_to_copy;
    }
    $this->copy_from_plan_year_section = $this->copyFromPlanYearSectionArrayToString($copy_from_plan_year_section_array);
  }

  /**
   * Helper function to create a string for use in copy_from_plan_year_section.
   *
   * @param array $copy_from_plan_year_section_array
   *   The value returned from getCopyFromPlanYearSectionArray() modifed.
   *
   * @return string
   *   The string version, ready to be saved to copy_from_plan_year_section.
   */
  protected function copyFromPlanYearSectionArrayToString(array $copy_from_plan_year_section_array) {
    $key_value_pairs = [];
    foreach ($copy_from_plan_year_section_array as $section_id => $plan_year_id) {
      $key_value_pairs[] = "$section_id=$plan_year_id";
    }
    return implode(',', $key_value_pairs);
  }

}
