<?php

namespace Drupal\sp_expire;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\sp_create\DebugLogger;

/**
 * Class ExpireStatePlanService.
 */
class ExpireStatePlanService {

  /**
   * The SP Expire content service.
   *
   * @var \Drupal\sp_expire\ContentService
   */
  protected $contentService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ExpireStatePlanService object.
   *
   * @param \Drupal\sp_expire\ContentService $contentService
   *   SP Expire content service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ContentService $contentService, EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    $this->contentService = $contentService;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Expire moderated state plan content that has not been updated in 90 days.
   *
   * @param string|null $daysAgo
   *   The number of days ago to check expiring content, pass null for 90 days.
   * @param null|\Drupal\Console\Core\Style\DrupalStyle $io
   *   Optional object to allow for console output.
   * @param bool $debugInfo
   *   Turns on or off extra debug info in watchdog.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Exception
   */
  public function expireModeratedContent($daysAgo = NULL, $io = NULL, $debugInfo = FALSE) {
    // Run time logger that needs the IO object to allow printing to the
    // console.
    $logger = new DebugLogger($this->logger, $debugInfo, $io);
    // Default to expiring content that was last updated 90 or more days ago.
    if (NULL === $daysAgo) {
      $daysAgo = 90;
    }
    // Retrieve all the latest revisions of nodes in the editorial workflow that
    // are not published or in draft state.
    $contentEntityTypeId = 'node';
    $contentEntityStorage = $this->entityTypeManager->getStorage($contentEntityTypeId);
    // Don't retrieve published, draft, or already expired nodes.
    $moderationStates = [
      $this->contentService::MODERATION_STATE_PUBLISHED => '<>',
      'draft' => '<>',
      'expired' => '<>',
    ];
    $workflowId = 'editorial';
    $states = [];
    foreach ($moderationStates as $moderationState => $moderationStateOperator) {
      $states[] = "($moderationState operator $moderationStateOperator)";
    }
    $logger->debug('Retrieving moderated state entities latest revisions with states of: @states type of: @type and workflow ID of: @workflow',
      [
        '@states' => implode(', ', $states),
        '@type' => $contentEntityTypeId ? $contentEntityTypeId : 'ALL',
        '@workflow' => $workflowId ? $workflowId : 'ALL',
      ]
    );
    $content_moderated_state_ids = $this->contentService->getContentModeratedStateEntity($this->contentService::MODERATION_STATE_REVISION_LATEST, $moderationStates, $contentEntityTypeId, $workflowId);
    if (!empty($content_moderated_state_ids)) {
      $logger->debug(
        'Found moderated state entities of (key: revision_id value: id): @content_moderated_state_ids',
        ['@content_moderated_state_ids' => print_r($content_moderated_state_ids, 1)]
      );
      // Retrieve the corresponding entity IDs (node IDs, instead of moderated
      // state entity IDs).
      $nids = $this->contentService->getContentEntity($content_moderated_state_ids);
      $logger->debug('Corresponding nodes (key: revision_id value: nid): @nids', ['@nids' => print_r($nids, 1)]);
      $logger->debug('Checking that the nodes were last modified more than @days ago.', ['@days' => $daysAgo]);
      // Check that that the latest revision of this node was changed more than
      // 90 days ago.
      $timestamp = strtotime(sprintf('%d days ago', $daysAgo));
      if (-1 === $timestamp) {
        $message = 'Invalid value given for days ago, it did not give a valid timestamp with strtotime().';
        $logger->critical($message, []);
        return;
      }

      $query = $contentEntityStorage->getQuery();
      $query
        // Allows unpublished revisions to be returned.
        ->accessCheck(FALSE)
        ->latestRevision()
        ->condition('nid', array_values($nids), 'in')
        ->condition('changed', $timestamp, '<=');

      // This should be in the exact same for as $nids above except missing
      // items were changed less than 90 days ago and we'll not be touched.
      $nids_changed = $query->execute();
      foreach ($nids_changed as $vid => $nid) {
        if (!isset($nids[$vid])) {
          $message = 'Retrieved the wrong vid (%d) for node %d.';
          $context = ['@vid' => $vid, '@nid' => $nid];
          $logger->critical($message, $context);
          return;
        }
      }
      // Only use the nids in the correct status that were also updated at the
      // correct time.
      $nids = $nids_changed;

      if (!empty($nids)) {
        $logger->debug(
          'Of those nodes, the following were updated more than @days days ago (key: revision_id value: nid): @nids',
          ['@days' => $daysAgo, '@nids' => print_r($nids, 1)]
        );
        foreach ($nids as $vid => $nid) {
          // Load each node by it's version ID load() gets the current version
          // only.
          /** @var \Drupal\node\Entity\Node $node */
          $node = $contentEntityStorage->loadRevision($vid);
          $time = time();
          // Always make sure a new revision is created.
          $node->setNewRevision(TRUE);
          // Optional, set a log message for this revision.
          $node->setRevisionLogMessage(sprintf('Content expired, not updated in %d days.', $daysAgo));
          // Optional, set a new time for the revision log message. This will
          // default to the last revision log time.
          $node->setRevisionCreationTime($time);
          // Optional, set a new time for the updated time. This will default
          // to the last revision changed time.
          $node->setChangedTime($time);
          // This is REQUIRED and I'm not sure why. If this is this flag is not
          // set, the revision will not show in the revisions tab.
          $node->setRevisionTranslationAffected(TRUE);
          // Set a new moderation state. This does not need to follow the normal
          // workflow, it can go from transitions that do not exist. However,
          // it's probably good to set up a new transition that only 'admins'
          // have access to so that we can create a workflow transition message
          // for this change.
          $node->set('moderation_state', 'expired');
          $node->save();
          $logger->debug('The node of ID @nid was expired because it was updated more than @days days ago.', [
            '@nid' => $node->id(),
            '@days' => $daysAgo,
          ]);
        }
      }
      else {
        $logger->debug('Of those nodes, none were updated more than @days days ago.', ['@days' => $daysAgo]);
      }
    }
    else {
      $logger->debug('No nodes were updated more than @days days ago.', ['@days' => $daysAgo]);
    }
  }

}