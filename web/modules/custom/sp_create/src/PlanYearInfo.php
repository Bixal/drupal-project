<?php

namespace Drupal\sp_create;

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

}
