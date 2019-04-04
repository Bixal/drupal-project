<?php

namespace Drupal\sp_retrieve;

use Drupal\node\Entity\Node;

/**
 * Class PlanYearTriggers.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearTriggers {

  /**
   * The return of \Drupal\sp_retrieve\TaxonomyService::getPlanYearTriggers().
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
   * PlanYearTriggers constructor.
   *
   * @param array $triggers
   *   The return of \Drupal\sp_retrieve\TaxonomyService::getPlanYearTriggers().
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
   * Tests the triggers to make sure they are sound.
   *
   * @todo
   */
  public function validate() {
    // Make sure a target is is only referenced one.
    // Make sure if the target is a section that the section vocab exists.
    // Make sure that the UUID in the condition that references a yes / no
    // question is actually a yes or no piece of content, not itself, and
    // exists.
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
