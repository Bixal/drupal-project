<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;

/**
 * Class PlanYearTriggers.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearTriggers {

  use StringTranslationTrait;

  /**
   * Return of \Drupal\sp_retrieve\TaxonomyService::getPlanYearTriggers().
   *
   * These are all the triggers in a plan year.
   *
   * @var array
   */
  protected $triggers;

  /**
   * Use the default state for the term access option, regardless of answers.
   *
   * @var string
   */
  protected $init = FALSE;

  /**
   * Errors that have occurred.
   *
   * @var array
   *   An array of error strings.
   */
  protected $errors = [];

  /**
   * PlanYearTriggers constructor.
   *
   * @param array $triggers
   *   Return of \Drupal\sp_retrieve\TaxonomyService::getPlanYearTriggers().
   * @param bool $init
   *   If the state plan year is going from new to draft.
   */
  public function __construct(array $triggers, $init = FALSE) {
    $this->triggers = $triggers;
    $this->init = $init;
    if (FALSE === $this->validate()) {
      return;
    }
  }

  /**
   * Errors that might have occurred when the object is instantiated.
   *
   * @return array
   *   An array of string errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Tests the triggers to make sure they are sound.
   *
   * @return bool
   *   True if validation passes.
   */
  protected function validate() {
    if (empty($this->triggers)) {
      return TRUE;
    }
    $errors = [];
    foreach ($this->triggers as $trigger) {
      $term_link = Link::createFromRoute(
        $this->t('"Access Term Field ID"'),
        'entity.taxonomy_term.edit_form',
        ['taxonomy_term' => $trigger['validation']['tid']]
      )->toString();
      if (!empty($trigger['validation']['target_section_missing'])) {
        $errors[] = $this->t(
          'The section referenced in @link @field_uuid no longer exists.',
          [
            '@link' => $term_link,
            '@field_uuid' => $trigger['validation']['target_term_field_uuid'],
          ]
        );
      }
      if (!empty($trigger['validation']['condition_source_missing'])) {
        $errors[] = $this->t(
          'The condition reference for access in @link @field_uuid no longer exists.',
          [
            '@link' => $term_link,
            '@field_uuid' => $trigger['validation']['target_term_field_uuid'],
          ]
        );
      }
      if (!empty($trigger['validation']['condition_source_not_a_yes_no'])) {
        $errors[] = $this->t(
          'The condition reference for access in @link @field_uuid does not reference a @yes_or_no_term.',
          [
            '@link' => $term_link,
            '@field_uuid' => $trigger['validation']['target_term_field_uuid'],
            '@yes_or_no_term' => Link::createFromRoute(
              'yes / no question',
              'entity.taxonomy_term.edit_form',
              ['taxonomy_term' => $trigger['validation']['condition_source_not_a_yes_no']]
            )->toString(),
          ]
        );
      }
      if (!empty($trigger['validation']['target_and_condition_same'])) {
        $errors[] = sprintf('The condition reference in %s at "Access Term Field ID" %s can not have the same "Access Term Field ID" as the target.', $term_link, $trigger['validation']['target_term_field_uuid']);
      }
    }
    if (!empty($errors)) {
      $this->errors = array_merge($this->errors, $errors);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieve an object that will test if a question has been triggered.
   *
   * @param \Drupal\node\Entity\Node $answer_new
   *   The node that holds the answer to a question.
   * @param \Drupal\node\Entity\Node|null $answer_old
   *   The $answer_new's previous revision before the change.
   *
   * @return \Drupal\sp_retrieve\PlanYearAnswerTriggers
   *   A PlanYearAnswerTriggers object.
   */
  public function answer(Node $answer_new, Node $answer_old = NULL) {
    $answer_triggers = [];
    if ($answer_new->hasField('field_field_unique_id_reference') && $answer_new->hasField('field_yes_or_no')) {
      $field_unique_id_reference = $answer_new->get('field_field_unique_id_reference')->getString();
      $answer_triggers = $this->getAnswerTriggers($field_unique_id_reference);
    }
    return new PlanYearAnswerTriggers($answer_triggers, $this->init, $answer_new, $answer_old);
  }

  /**
   * Retrieve the triggers where current question is the condition.
   *
   * @param string $field_unique_id_reference
   *   The unique identifier for an answer between all states and years.
   *
   * @return array
   *   Triggers for only the given question..
   */
  protected function getAnswerTriggers($field_unique_id_reference) {
    $answer_triggers = [];
    foreach ($this->triggers as $trigger) {
      if ($trigger['condition']['term_field_uuid'] === $field_unique_id_reference) {
        $answer_triggers[] = $trigger;
      }
    }
    return $answer_triggers;
  }

}
