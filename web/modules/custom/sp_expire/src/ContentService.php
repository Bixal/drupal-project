<?php

namespace Drupal\sp_expire;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class ContentService.
 */
class ContentService {

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
  public  const MODERATION_STATE_REVISION_CURRENT = 'current';

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
   * @param string $moderationState
   *   Usually this is 'published' self::MODERATION_STATE_PUBLISHED
   * @param string $moderationStateOperator
   *   An operator like in a query condition: =, <>, etc.
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
  public function getContentModeratedStateEntity($latestOrCurrentRevision = '', $moderationStates = [], $contentEntityTypeId = '', $workflowId = '') {
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery();
    if ($latestOrCurrentRevision === self::MODERATION_STATE_REVISION_CURRENT) {
      $query->currentRevision();
    } elseif ($latestOrCurrentRevision === self::MODERATION_STATE_REVISION_LATEST) {
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
  public function getContentEntity($content_moderated_state_result) {
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
   * @param string $contentEntityTypeId
   * @param string $workflowId
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function latestRevision($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_LATEST, [], $contentEntityTypeId, $workflowId);
  }

  public function latestRevisionPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_LATEST, [self::MODERATION_STATE_PUBLISHED => '=' ], $contentEntityTypeId, $workflowId);
  }

  public function latestRevisionUnPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_LATEST, [self::MODERATION_STATE_PUBLISHED => '<>' ], $contentEntityTypeId, $workflowId);
  }

  public function currentRevision($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_CURRENT, [], $contentEntityTypeId, $workflowId);
  }

  public function currentRevisionPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_CURRENT, [self::MODERATION_STATE_PUBLISHED => '=' ], $contentEntityTypeId, $workflowId);
  }

  public function currentRevisionUnPublished($contentEntityTypeId = '', $workflowId = '') {
    return $this->getContentModeratedStateEntity(self::MODERATION_STATE_REVISION_CURRENT, [self::MODERATION_STATE_PUBLISHED => '<>' ], $contentEntityTypeId, $workflowId);
  }

  public function latestRevisions() {
    $result = $this->latestRevisionPublished();
    $this->getContentEntity($result);
    dpm($result,'latest version is published');
    $result = $this->latestRevisionUnPublished();
    $this->getContentEntity($result);
    dpm($result,'latest version is unpublished');
    $result = $this->currentRevisionPublished();
    $this->getContentEntity($result);
    dpm($result,'current version is published');
    $result = $this->currentRevisionUnPublished();
    $this->getContentEntity($result);
    dpm($result,'current version is published');
    return;
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery()
      ->latestRevision()
      //->condition('content_entity_type_id', $this->entityTypeId)
      //->condition('moderation_state', self::MODERATION_STATE_PUBLISHED, '<>')
      ->sort('id', 'DESC');

    $result = $query->execute();
    //dpm($result,'latest versions');
    //dpm($this->entityTypeManager->getStorage('content_moderation_state')->load($result[63]));
//return;
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery()
      ->latestRevision()
      //->condition('content_entity_type_id', $this->entityTypeId)
      ->condition('moderation_state', self::MODERATION_STATE_PUBLISHED, '<>')
      ->sort('id', 'DESC');

    $result = $query->execute();
    dpm($result,'latest version is not published');
    $this->latestRevisionPublished();





    return;
    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getAggregateQuery()
      ->aggregate('content_entity_id', 'MAX')
      ->groupBy('content_entity_revision_id')
      //->condition('content_entity_type_id', $this->entityTypeId)
      //->condition('moderation_state', self::MODERATION_STATE_PUBLISHED, '<>')
      ->sort('content_entity_revision_id', 'DESC');

    $result = $query->execute();
    dpm($result,'res');
    return;
    //$result ? array_column($result, 'content_entity_revision_id') : [];
    $content_entity_revision_id = $result ? array_column($result, 'content_entity_revision_id') : [];
    dpm($content_entity_revision_id, 'ceri');

    $query = $this->entityTypeManager->getStorage('content_moderation_state')->getQuery()
      //->condition('content_entity_type_id', $this->entityTypeId)
      ->condition('moderation_state', self::MODERATION_STATE_PUBLISHED, '<>')
      ->condition('content_entity_revision_id', $content_entity_revision_id, 'in')
      ->sort('content_entity_revision_id', 'DESC');

    $result = $query->execute();
    dpm($result,'res2');
    //$result ? array_column($result, 'content_entity_revision_id') : [];
    $content_entity_revision_id_unpub = $result ? array_column($result, 'content_entity_id_max') : [];
    dpm($content_entity_revision_id_unpub, 'ceri2');
return;
    // Only add the pager if a limit is specified.
    /*if ($this->limit) {
      $query->pager($this->limit);
    }*/

    $result = $query->execute();
    dpm($result,'res');
    return $result ? array_column($result, 'content_entity_revision_id') : [];
  }
}
