<?php

namespace Drupal\sp_create;

use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class PlanYearInfo.
 *
 * Provides information about plans that doesn't require service access.
 */
class PlanYearInfo {

  /**
   * The vocabulary ID of the section base to copy from.
   *
   * @var string
   */
  public static $sectionBaseVocabularyName = 'section_base';

  /**
   * The length of the random string created for section ID.
   *
   * @var int
   */
  public static $sectionIdLength = 19;

  /**
   * Create the string to be used for a section vocabulary ID.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return string
   *   A 32 character string section vocabulary ID.
   */
  public static function createSectionVocabularyId($plan_year_id, $section_id) {
    return "section_{$plan_year_id}_{$section_id}";
  }

  /**
   * Convert a section vocabulary ID to the plan year and section it represents.
   *
   * @param string $vid
   *   A section vocabulary ID.
   *
   * @return array|bool
   *   An array keyed by plan_year_id and section_id or false if not a valid
   *   string.
   */
  public static function getPlanYearIdAndSectionIdFromVid($vid) {
    list($prefix, $plan_year_id, $section_id) = explode('_', $vid);
    if ('section' === $prefix && 4 === strlen($plan_year_id) && self::$sectionIdLength === strlen($section_id)) {
      return ['plan_year_id' => $plan_year_id, 'section_id' => $section_id];
    }
    return FALSE;
  }

  /**
   * Get the plan year ID that the entity belongs to.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A term or node.
   *
   * @return bool|string
   *   False if no plan year ID can be found, otherwise the plan year ID.
   */
  public static function getPlanYearIdFromEntity(EntityInterface $entity) {
    $plan_year_id = FALSE;
    if ($entity instanceof Term) {
      $plan_year_info = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($entity->getVocabularyId());
      if (FALSE !== $plan_year_info) {
        $plan_year_id = $plan_year_info['plan_year_id'];
      }
    }
    elseif ($entity instanceof Node) {
      switch ($entity->bundle()) {
        case 'state_plan_year_section':
          if (!empty($entity->field_state_plan_year)) {
            break;
          }
          $plan_year_id = $entity->field_state_plan_year->entity->field_state_plans_year->entity->field_plan_year->entity->id();
          break;

        case 'state_plan_year':
          if (!empty($entity->field_state_plans_year)) {
            break;
          }
          $plan_year_id = $entity->field_state_plans_year->entity->field_plan_year->entity->id();
          break;

        case 'state_plans_year':
        case 'text_sp_content':
        case 'bool_sp_content':
          if (!empty($entity->field_plan_year)) {
            break;
          }
          $plan_year_id = $entity->field_plan_year->entity->id();
          break;

      }
    }
    return $plan_year_id;
  }

}
