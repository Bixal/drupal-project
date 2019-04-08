<?php

namespace Drupal\sp_retrieve;

use Drupal\node\Entity\Node;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_expire\ContentService;

/**
 * Class PlanYearAnswerTriggers.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearAnswerTriggers {

  /**
   * An answer that needs to move from the initial moderation state.
   */
  const TRIGGERED = 'triggered';

  /**
   * An answer that needs to be moved to the initial moderation state.
   */
  const INITIAL = 'initial';

  /**
   * The triggers where current question is the condition.
   *
   * @var array
   */
  protected $triggers;

  /**
   * The node that holds the answer to a question.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $answerNew;

  /**
   * The $answer_new's previous revision before the change.
   *
   * @var \Drupal\node\Entity\Node|null
   */
  protected $answerOld;

  /**
   * Use the default state for the term access option, regardless of answers.
   *
   * Init is required because there is no way to tell that the plan year
   * changing from new to draft should have been the cause of the triggers
   * being applied instead of the answers going from new to draft / hidden /
   * disallow.
   *
   * @var string
   */
  protected $init;

  /**
   * PlanYearAnswerTriggers constructor.
   *
   * @param array $triggers
   *   The triggers where current question is the condition.
   * @param bool $init
   *   If the state plan year is going from new to draft.
   * @param \Drupal\node\Entity\Node $answer_new
   *   The node that holds the answer to a question.
   * @param \Drupal\node\Entity\Node|null $answer_old
   *   The $answer_new's previous revision before the change.
   */
  public function __construct(array $triggers, $init, Node $answer_new, Node $answer_old = NULL) {
    $this->triggers = $triggers;
    $this->answerNew = $answer_new;
    $this->answerOld = $answer_old;
    $this->init = $init;
  }

  /**
   * Retrieve an array of triggered conditions and their targets.
   *
   * Based on the current answer, are new conditions triggered?
   *
   * @return array
   *   An array with sub arrays with keys of target and new_moderation_state.
   */
  public function getTriggered() {
    $triggered = [];
    if (empty($this->triggers)) {
      return $triggered;
    }
    $new_answer = $this->answerNew->get('field_yes_or_no')->getString();
    $old_answer = $this->answerOld !== NULL ? $this->answerOld->get('field_yes_or_no')->getString() : '';
    $new_state = $this->answerNew->get('moderation_state')->getString();
    $old_state = $this->answerOld !== NULL ? $this->answerOld->get('moderation_state')->getString() : NULL;
    foreach ($this->triggers as $trigger) {
      // If this is set, that means it was triggered to go to a new state.
      $new_moderation_state = NULL;
      // Whether the new value matches the condition.
      $matches_condition = $trigger['condition']['value'] === $new_answer;
      // Whether the value has changed.
      // If the answer did NOT change, that means the current item was changed
      // by another trigger. The only way an answer changes is if someone
      // explicitly updates an answer.
      $changed = $old_answer !== $new_answer;
      // Whether the new value has changed and matches the condition.
      $matches_condition_and_changed = $matches_condition && $changed;
      // Whether the new value has changed  but the condition does not match.
      $does_not_match_condition_and_changed = !$matches_condition && $changed;

      // The current item got hidden or disallowed by another trigger, not by
      // being explicitly turned on by a first level trigger. This means that
      // all the triggered answers / sections should be put in the initial
      // state. For example, if the question for whether they want to enable
      // a combined section was disallowed by someone clicking no on whether
      // they want a combined plan or not, the answers in that combined section
      // should be hidden.
      $triggered_disabled_states_and_not_changed = (
        $old_state !== $new_state &&
        in_array($new_state, [
          ContentService::MODERATION_STATE_HIDDEN,
          ContentService::MODERATION_STATE_DISALLOW,
        ]) &&
        !$changed
      );

      // If the current item got is being sent to draft by another trigger and
      // and the condition matches, then trigger its targets to be their non
      // initialized state. For example, if someone turns on a combined section,
      // disables combined plans altogether, then turns combined plans back on.
      // In that situation, the combined section should be turned back on since
      // they answers 'yes' to it before.
      $triggered_draft_and_matches_condition_and_not_changed = (
        $old_state !== $new_state &&
        $new_state == ContentService::MODERATION_STATE_DRAFT &&
        $matches_condition &&
        !$changed
      );

      // There are 3 times that you want the trigger to fire for the "initial"
      // state. The initial state is different for all condition options.
      // For example: the "shown" access option is initialized to be hidden
      // and shown when the condition value matches (the yes or no).
      // 1: When the state plan moderation state changes from "new" to
      // ---"started". This is accomplished by passing true to the init value
      // ---of the constructor.
      // 2: When the yes / no value has changed and the condition to trigger
      // ---has not been met. This will always happen based on an explicit yes
      // ---or no answer by an editor.
      // 3: When an editor sets a yes / no and one of those triggers (2:)
      // ---changes the moderation state of another yes / no to either hidden
      // ---or disallowed. Also note that the triggered entity will not have
      // ---changed it's value (yes / no).
      // There are 2 times that you want the trigger to fire for the
      // "triggered" state. The "triggered" state is when the condition is
      // met or has already been met and needs to be applied again.
      // 1: When the yes / no value has changed and the condition to trigger
      // ---has been met. This will always happen based on an explicit yes
      // ---or no answer by an editor. This is similar to 2 above.
      // 2: When an editor sets a yes / no and one of those triggers (2:)
      // ---changes the moderation state of another yes / no to draft. In
      // ---addition, the condition to trigger the target must be met. Also
      // ---note that the triggered entity will not have changed it's value
      // ---(yes / no). This state is required in order to keep second level+
      // ---triggers with values that match the condition already to not start
      // ---at their initial value if they are triggered again after being
      // ---disabled (hidden or disallowed).
      // Based on our conditionals, determine if this is "initial", "triggered",
      // or not triggered.
      $initialOrTriggered = NULL;
      if ($this->init || $does_not_match_condition_and_changed || $triggered_disabled_states_and_not_changed) {
        $initialOrTriggered = self::INITIAL;
      }
      elseif ($matches_condition_and_changed || $triggered_draft_and_matches_condition_and_not_changed) {
        $initialOrTriggered = self::TRIGGERED;
      }

      switch ($trigger['condition']['option']) {
        case PlanYearInfo::ANSWER_ACCESS_SHOWN:
          if ($initialOrTriggered === self::INITIAL) {
            $new_moderation_state = ContentService::MODERATION_STATE_HIDDEN;
          }
          elseif ($initialOrTriggered === self::TRIGGERED) {
            $new_moderation_state = ContentService::MODERATION_STATE_DRAFT;
          }
          break;

        case PlanYearInfo::ANSWER_ACCESS_HIDE:
          if ($initialOrTriggered === self::INITIAL) {
            $new_moderation_state = ContentService::MODERATION_STATE_DRAFT;
          }
          elseif ($initialOrTriggered === self::TRIGGERED) {
            $new_moderation_state = ContentService::MODERATION_STATE_HIDDEN;
          }
          break;

        case PlanYearInfo::ANSWER_ACCESS_DISALLOW:
          if ($initialOrTriggered === self::INITIAL) {
            $new_moderation_state = ContentService::MODERATION_STATE_DRAFT;
          }
          elseif ($initialOrTriggered === self::TRIGGERED) {
            $new_moderation_state = ContentService::MODERATION_STATE_DISALLOW;
          }
          break;

        case PlanYearInfo::ANSWER_ACCESS_ALLOW:
          if ($initialOrTriggered === self::INITIAL) {
            $new_moderation_state = ContentService::MODERATION_STATE_DISALLOW;
          }
          elseif ($initialOrTriggered === self::TRIGGERED) {
            $new_moderation_state = ContentService::MODERATION_STATE_DRAFT;
          }
          break;

      }
      // If the new moderation state is set, that means the target has been
      // triggered.
      if (NULL !== $new_moderation_state) {
        $triggered[] = [
          'target' => $trigger['target'],
          'new_moderation_state' => $new_moderation_state,
        ];
      }
    }
    return $triggered;
  }

}
