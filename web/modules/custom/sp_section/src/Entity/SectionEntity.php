<?php

namespace Drupal\sp_section\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Component\Utility\Random;
use Drupal\sp_create\PlanYearInfo;

/**
 * Defines the Section entity.
 *
 * @ConfigEntityType(
 *   id = "section",
 *   label = @Translation("Section"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\sp_section\SectionEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\sp_section\Form\SectionEntityForm",
 *       "edit" = "Drupal\sp_section\Form\SectionEntityForm",
 *       "delete" = "Drupal\sp_section\Form\SectionEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\sp_section\SectionEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "section",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/section/{section}",
 *     "add-form" = "/admin/structure/section/add",
 *     "edit-form" = "/admin/structure/section/{section}/edit",
 *     "delete-form" = "/admin/structure/section/{section}/delete",
 *     "collection" = "/admin/structure/section"
 *   }
 * )
 */
class SectionEntity extends ConfigEntityBase implements SectionEntityInterface {

  /**
   * The Section ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Section label.
   *
   * @var string
   */
  protected $label;

  /**
   * Retrieve a new ID if it is new, otherwise the current.
   *
   * @return string
   *   The ID.
   */
  public static function getRandomId() {
    $random = new Random();
    return strtolower($random->name(PlanYearInfo::$sectionIdLength));
  }

}
