<?php

namespace Drupal\sp_expire;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ContentService.
 */
class ContentService {

  /**
   * Workflow ID that is used for state plans year nodes.
   */
  public const WORKFLOW_ID_STATE_PLANS_YEAR = 'state_plans_year';

  /**
   * Workflow ID that is used for state plan year nodes.
   */
  public const WORKFLOW_ID_STATE_PLAN_YEAR = 'state_plan_year';

  /**
   * Workflow ID that is used for state plan year section nodes.
   */
  public const WORKFLOW_ID_STATE_PLAN_YEAR_SECTION = 'state_plan_year_section';

  /**
   * Workflow ID that is used for state plan answer nodes.
   *
   * This holds multiple types: yes / no and text input (required and optional).
   */
  public const WORKFLOW_ID_STATE_PLAN_ANSWER = 'state_plan_answer';

  /**
   * The workflow state for published.
   *
   * @var string
   */
  public const MODERATION_STATE_PUBLISHED = 'published';

  /**
   * Retrieve only the latest revisions.
   *
   * @var string
   */
  public const MODERATION_STATE_REVISION_LATEST = 'latest';

  /**
   * Retrieve only the current revisions.
   *
   * @var string
   */
  public const MODERATION_STATE_REVISION_CURRENT = 'current';

  /**
   * The workflow state for a brand new piece of content.
   *
   * Applies for workflows: state_plan_year, state_plan_year_section, or
   * state_plan_answer.
   *
   * @var string
   */
  public const MODERATION_STATE_NEW = 'new_not_available';

  /**
   * The workflow state for a piece of content ready for editorial.
   *
   * Applies for workflows: state_plans_year, state_plan_year,
   * state_plan_year_section, or state_plan_answer.
   *
   * @var string
   */
  public const MODERATION_STATE_DRAFT = 'draft';

  /**
   * The workflow state for an answer that should be hidden.
   *
   * Answers in this state are not shown anywhere.
   *
   * Applies for workflows: state_plan_answer, state_plan_year_section.
   *
   * @var string
   */
  public const MODERATION_STATE_HIDDEN = 'hidden';

  /**
   * The workflow state for an answer that should be disallowed for entry.
   *
   * Answers in this state are shown but not allowed to have values entered.
   *
   * Applies for workflows: state_plan_answer, state_plan_year_section.
   *
   * @var string
   */
  public const MODERATION_STATE_DISALLOW = 'disallow';

  /**
   * The workflow state for an answer that was hidden while being published.
   *
   * Answers in this state are not shown anywhere. This needs its own status
   * because a plan could be re-opened and this answer triggered to be shown
   * while the 'published' version still remaining 'hidden'.
   *
   * Applies for workflows: state_plan_answer, state_plan_year_section.
   *
   * @var string
   */
  public const MODERATION_STATE_HIDDEN_PUBLISHED = 'hidden_published';

  /**
   * The workflow state for an answer that was disallowed while being published.
   *
   * Answers in this state are shown but not allowed to have values entered.
   * These will show the default value on an answer.
   *
   * Applies for workflows: state_plan_answer, state_plan_year_section.
   *
   * @var string
   */
  public const MODERATION_STATE_DISALLOW_PUBLISHED = 'disallow_published';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContentService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Retrieve an array of content_moderation_state entities.
   *
   * @param string $latestOrCurrentRevision
   *   A constant from this class of self::MODERATION_STATE_REVISION_*.
   * @param array $moderationStates
   *   An array keyed by a moderation state ID with values of the operator.
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getContentModeratedStateEntity($latestOrCurrentRevision = '', array $moderationStates = [], $contentEntityTypeId = '', $workflowId = '') {
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery();
    if ($latestOrCurrentRevision === self::MODERATION_STATE_REVISION_CURRENT) {
      $query->currentRevision();
    }
    elseif ($latestOrCurrentRevision === self::MODERATION_STATE_REVISION_LATEST) {
      $query->latestRevision();
    }
    if (!empty($moderationStates)) {
      foreach ($moderationStates as $moderationState => $moderationStateOperator) {
        if ($moderationState && $moderationStateOperator) {
          $query->condition('moderation_state', $moderationState, $moderationStateOperator);
        }
      }
    }
    if ($workflowId) {
      $query->condition('workflow', $workflowId);
    }
    if ($contentEntityTypeId) {
      $query->condition('content_entity_type_id', $contentEntityTypeId);
    }
    return $query->sort('id', 'DESC')
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * Retrieve the entities that correspond to the moderated state entities.
   *
   * @param array $content_moderated_state_result
   *   The return of self::getContentModeratedStateEntity().
   *
   * @return array
   *   An array keyed by content_entity_revision_id with a value of
   *   content_entity_id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getContentEntity(array $content_moderated_state_result) {
    $ids = [];
    foreach ($content_moderated_state_result as $revision_id => $id) {
      /** @var \Drupal\content_moderation\Entity\ContentModerationState $content_moderated_state_entity */
      $content_moderated_state_entity = $this->entityTypeManager->getStorage('content_moderation_state')->loadRevision($revision_id);
      /** @var \Drupal\Core\Field\FieldItemList $field */
      $field = $content_moderated_state_entity->get('content_entity_revision_id');
      $content_entity_revision_id = $field->get(0)->getValue()['value'];
      $field = $content_moderated_state_entity->get('content_entity_id');
      $content_entity_id = $field->get(0)->getValue()['value'];
      $ids[$content_entity_revision_id] = $content_entity_id;
    }

    return $ids;
  }

  /**
   * Retrieve all the latest revisions of nodes.
   *
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function latestRevision($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_LATEST, [], $contentEntityTypeId, $workflowId);
  }

  /**
   * Retrieve all the latest revisions of nodes that are published.
   *
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function latestRevisionPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_LATEST, [self::MODERATION_STATE_PUBLISHED => '='], $contentEntityTypeId, $workflowId);
  }

  /**
   * Retrieve all the latest revisions of nodes that are unpublished.
   *
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function latestRevisionUnPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_LATEST, [self::MODERATION_STATE_PUBLISHED => '<>'], $contentEntityTypeId, $workflowId);
  }

  /**
   * Retrieve all the current revisions of nodes.
   *
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function currentRevision($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_CURRENT, [], $contentEntityTypeId, $workflowId);
  }

  /**
   * Retrieve all the current revisions of nodes that are published.
   *
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function currentRevisionPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_CURRENT, [self::MODERATION_STATE_PUBLISHED => '='], $contentEntityTypeId, $workflowId);
  }

  /**
   * Retrieve all the current revisions of nodes that are unpublished.
   *
   * @param string $contentEntityTypeId
   *   This is the entity type of the moderated content (node, user, etc).
   * @param string $workflowId
   *   The default workflow ID is 'editorial', nothing to get all.
   *
   * @return array
   *   An array keyed by revision_id with a value of id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function currentRevisionUnPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_CURRENT, [self::MODERATION_STATE_PUBLISHED => '<>'], $contentEntityTypeId, $workflowId);
  }

}
