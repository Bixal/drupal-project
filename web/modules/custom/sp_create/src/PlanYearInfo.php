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
  const SECTION_BASE_VOCABULARY_NAME = 'section_base';

  /**
   * The length of the random string created for section ID.
   *
   * @var int
   */
  const SECTION_ID_LENGTH = 19;

  /**
   * The state plan year content type bundle.
   *
   * @var string
   */
  const SPY_BUNDLE = 'state_plan_year';

  /**
   * The state plans year content type bundle.
   *
   * @var string
   */
  const SPZY_BUNDLE = 'state_plans_year';

  /**
   * The state plan year section content type bundle.
   *
   * @var string
   */
  const SPYS_BUNDLE = 'state_plan_year_section';

  /**
   * The state plan year content bool content type bundle.
   *
   * @var string
   */
  const SPYC_BOOL_BUNDLE = 'bool_sp_content';

  /**
   * Yes or no state plan content.
   *
   * @var string
   */
  const SPYC_BOOL_EB = 'node-' . self::SPYC_BOOL_BUNDLE;


  /**
   * The state plan year content text content type bundle.
   *
   * @var string
   */
  const SPYC_TEXT_BUNDLE = 'text_sp_content';

  /**
   * Text state plan content.
   *
   * @var string
   */
  const SPYC_TEXT_EB = 'node-' . self::SPYC_TEXT_BUNDLE;

  /**
   * Get ID and labels for state plan content type bundles.
   *
   * @return array
   *   An array keyed by the entity type bundle with the label as the value.
   */
  public static function getSpycEntityTypeBundles() {
    return [self::SPYC_BOOL_EB => 'Node - Yes/No', self::SPYC_TEXT_EB => 'Node - Text'];
  }

  /**
   * Get just the bundles from the entity type bundles.
   *
   * @param string $entity_type
   *   An entity type.
   *
   * @return array
   *   Get an array of bundles.
   */
  public static function getSpycEntityBundles($entity_type) {
    $return = [];
    foreach (self::getSpycEntityTypeBundles() as $entity_type_bundle => $label) {
      $etb_array = explode('-', $entity_type_bundle);
      if ($etb_array[0] !== $entity_type) {
        continue;
      }
      $return[] = $etb_array[1];
    }
    return $return;
  }

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
    if ('section' === $prefix && 4 === strlen($plan_year_id) && self::SECTION_ID_LENGTH === strlen($section_id)) {
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
        case self::SPYS_BUNDLE:
          if (empty($entity->field_state_plan_year)) {
            break;
          }
          $plan_year_id = $entity->field_state_plan_year->entity->field_state_plans_year->entity->field_plan_year->entity->id();
          break;

        case self::SPY_BUNDLE:
          if (empty($entity->field_state_plans_year)) {
            break;
          }
          $plan_year_id = $entity->field_state_plans_year->entity->field_plan_year->entity->id();
          break;

        case self::SPZY_BUNDLE:
        case self::SPYC_TEXT_BUNDLE:
        case self::SPYC_BOOL_BUNDLE:
          if (empty($entity->field_plan_year)) {
            break;
          }
          $plan_year_id = $entity->field_plan_year->entity->id();
          break;

      }
    }
    return $plan_year_id;
  }

}
