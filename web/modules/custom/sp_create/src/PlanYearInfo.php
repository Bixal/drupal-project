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
   * The state plan year answer bool required content type bundle.
   *
   * @var string
   */
  const SPYA_BOOL_BUNDLE_REQUIRED = 'bool_sp_answer_required';

  /**
   * The state plan year answer bool optional content type bundle.
   *
   * @var string
   */
  const SPYA_BOOL_BUNDLE_OPTIONAL = 'bool_sp_answer_optional';

  /**
   * The state plan year answer text required content type bundle.
   *
   * @var string
   */
  const SPYA_TEXT_BUNDLE_REQUIRED = 'text_sp_answer_required';

  /**
   * The state plan year answer text optional content type bundle.
   *
   * @var string
   */
  const SPYA_TEXT_BUNDLE_OPTIONAL = 'text_sp_answer_optional';

  /**
   * Get node bundles for state plan answer nodes.
   *
   * @return array
   *   An array keyed by the entity type bundle with the label as the value.
   */
  public static function getSpyaNodeBundles() {
    return [
      self::SPYA_BOOL_BUNDLE_OPTIONAL,
      self::SPYA_BOOL_BUNDLE_REQUIRED,
      self::SPYA_TEXT_BUNDLE_OPTIONAL,
      self::SPYA_TEXT_BUNDLE_REQUIRED,
    ];
  }

  /**
   * Get node bundles and labels for state plan answer nodes.
   *
   * @return array
   *   An array keyed by the entity type bundle with the label as the value.
   */
  public static function getSpyaLabels() {
    return [
      self::SPYA_BOOL_BUNDLE_OPTIONAL => 'Yes/No (Optional)',
      self::SPYA_BOOL_BUNDLE_REQUIRED => 'Yes/No (Required)',
      self::SPYA_TEXT_BUNDLE_OPTIONAL => 'Text (Optional)',
      self::SPYA_TEXT_BUNDLE_REQUIRED => 'Text (Required)',
    ];
  }

  /**
   * Get all node types that make up state plan year nodes.
   *
   * @return array
   *   An array of node types.
   */
  public static function getSpyNodeBundles() {
    return array_merge(
      PlanYearInfo::getSpyaNodeBundles(),
      [
        PlanYearInfo::SPY_BUNDLE,
        PlanYearInfo::SPYS_BUNDLE,
        PlanYearInfo::SPZY_BUNDLE,
      ]
    );
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
    $array = explode('_', $vid);
    // Ensure there are 3 portions separated by '_'.
    if (count($array) !== 3) {
      return FALSE;
    }
    list($prefix, $plan_year_id, $section_id) = $array;
    if ('section' === $prefix && 4 === strlen($plan_year_id) && self::SECTION_ID_LENGTH === strlen($section_id)) {
      return ['plan_year_id' => $plan_year_id, 'section_id' => $section_id];
    }
    return FALSE;
  }

  /**
   * Get the plan year ID that the entity belongs to.
   *
   * Recursion is used in this method to follow the chain of references.
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
          $field = $entity->get('field_state_plan_year');
          if ($field->isEmpty()) {
            break;
          }
          return self::getPlanYearIdFromEntity($field->entity);

        case self::SPY_BUNDLE:
          $field = $entity->get('field_state_plans_year');
          if ($field->isEmpty()) {
            break;
          }
          return self::getPlanYearIdFromEntity($field->entity);

      }
      if (in_array($entity->bundle(), array_merge(self::getSpyaNodeBundles(), [self::SPZY_BUNDLE]))) {
        $field = $entity->get('field_plan_year');
        if (!$field->isEmpty()) {
          $plan_year_id = $field->entity->id();
        }
      }
    }
    return $plan_year_id;
  }

  /**
   * Get the state plan year node ID that the entity belongs to.
   *
   * Recursion is used in this method to follow the chain of references.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A node.
   *
   * @return bool|string
   *   False if no state plan year node ID can be found, otherwise the node ID.
   */
  public static function getStatePlanYearNidFromEntity(EntityInterface $entity) {
    $state_plan_year_nid = FALSE;
    if ($entity instanceof Node) {
      switch ($entity->bundle()) {
        case self::SPYS_BUNDLE:
          $field = $entity->get('field_state_plan_year');
          if ($field->isEmpty()) {
            break;
          }
          return self::getStatePlanYearNidFromEntity($field->entity);

        case self::SPY_BUNDLE:
          $state_plan_year_nid = $entity->id();
          break;

      }
      if (in_array($entity->bundle(), self::getSpyaNodeBundles())) {
        $field = $entity->get('field_state_plan_year_section');
        if (!$field->isEmpty()) {
          return self::getStatePlanYearNidFromEntity($field->entity);
        }
      }
    }
    return $state_plan_year_nid;
  }

  /**
   * Retrieve the field that holds an answer by the state.
   *
   * @param \Drupal\node\Entity\Node $entity
   *   A single state plan year answer.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field that holds the answer by the state.
   *
   * @throws \Exception
   */
  public static function getStatePlanYearAnswerValueField(Node $entity) {
    switch ($entity->bundle()) {
      case self::SPYA_TEXT_BUNDLE_OPTIONAL:
      case self::SPYA_TEXT_BUNDLE_REQUIRED:
        return $entity->get('body');

      case self::SPYA_BOOL_BUNDLE_OPTIONAL:
      case self::SPYA_BOOL_BUNDLE_REQUIRED:
        return $entity->get('field_yes_or_no');
    }
    throw new \Exception(sprintf('The entity %s of bundle %s was not a valid state plan content node.', $entity->id(), $entity->bundle()));
  }

}
