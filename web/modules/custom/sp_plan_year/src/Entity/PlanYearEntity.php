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
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sp_plan_year\PlanYearEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sp_plan_year\Form\PlanYearEntityForm",
 *       "edit" = "Drupal\sp_plan_year\Form\PlanYearEntityForm",
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

}
