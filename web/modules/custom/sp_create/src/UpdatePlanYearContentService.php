<?php

namespace Drupal\sp_create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\sp_expire\ContentService;
use Drupal\sp_retrieve\NodeService;
use Drupal\sp_retrieve\TaxonomyService;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Drupal\sp_retrieve\CustomEntitiesService;
use Drupal\Core\Database\Connection as Database;
use Drupal\Core\Session\AccountProxy;

/**
 * Class UpdatePlanYearContentService.
 *
 * Provides methods for altering a plans content. This is basically all
 * the information that is stored in the database.
 */
class UpdatePlanYearContentService {

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
   * The entity clone service.
   *
   * @var \Drupal\sp_create\CloneService
   */
  protected $clone;

  /**
   * The custom entities retrieval service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * The taxonomy retrieval service.
   *
   * @var \Drupal\sp_retrieve\TaxonomyService
   */
  protected $taxonomyService;

  /**
   * The node retrieval service.
   *
   * @var \Drupal\sp_retrieve\nodeService
   */
  protected $nodeService;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new CreateStatePlanService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\sp_create\CloneService $clone
   *   The entity clone service.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   * @param \Drupal\sp_retrieve\TaxonomyService $taxonomy_service
   *   The taxonomy retrieval service.
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node retrieval service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, CloneService $clone, CustomEntitiesService $custom_entities_retrieval, TaxonomyService $taxonomy_service, NodeService $node_service, Database $database, AccountProxy $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->clone = $clone;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->taxonomyService = $taxonomy_service;
    $this->nodeService = $node_service;
    $this->database = $database;
    $this->currentUser = $current_user;
  }

  /**
   * Drop all term hierarchy from this section.
   *
   * The section meta data should be updated by this point and the given section
   * may not be found on the plan year entity. In addition, the section content
   * should be removed as well (removeSectionContent is already called).
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSectionHierarchy($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      // Retrieve only the top level terms of the section, as children will be
      // automatically removed.
      foreach ($this->taxonomyService->getVocabularyTids($section_vocabulary_id, TRUE) as $tid) {
        $term = $term_storage->load($tid);
        // Just in-case a child was retrieved and it's already removed, check
        // that the term could be loaded.
        if (NULL !== $term) {
          $term->delete();
        }
      }
    }
  }

  /**
   * Copy all term hierarchy from a different plan year to this section.
   *
   * The section meta data should be updated by this point with the new plan
   * year to copy.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $plan_year_id_to_copy
   *   The plan year to copy hierarchy from to this section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function copySectionHierarchy($plan_year_id, $section_id, $plan_year_id_to_copy) {
    $plan_year_vid = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $plan_year_to_copy_section_vid = PlanYearInfo::createSectionVocabularyId($plan_year_id_to_copy, $section_id);
    $source_mapping = [];
    // Clone each term in the copied year, keeping track of parent so it can
    // be set later.
    foreach ($this->taxonomyService->getVocabularyTids($plan_year_to_copy_section_vid) as $tid) {
      /** @var \Drupal\taxonomy\Entity\Term $source_term */
      $source_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
      /** @var \Drupal\taxonomy\Entity\Term $cloned_term */
      $cloned_term = $this->clone->cloneEntity('taxonomy_term', $plan_year_to_copy_section_vid, $tid);
      $source_mapping[] = [
        'source_tid' => $source_term->id(),
        'source_tid_parent' => $source_term->get('parent')->first()->getValue()['target_id'],
        'cloned_tid' => $cloned_term->id(),
      ];
    }
    // Now that all terms have been saved, they need to have the hierarchy set
    // again and saved to the new vocabulary.
    foreach ($source_mapping as $item) {
      $cloned_term = $this->entityTypeManager->getStorage('taxonomy_term')
        ->load($item['cloned_tid']);
      // This term had a parent in the source vocab but we haven't set it on
      // the current plan year term yet.
      if (!empty($item['source_tid_parent'])) {
        $cloned_tid_parent = $this->getParentTid($source_mapping, $item['source_tid_parent']);
        if (FALSE === $cloned_tid_parent) {
          continue;
        }
        $cloned_term->set('parent', $cloned_tid_parent);
      }
      // Remove - Cloned from the label of the term.
      $cloned_term->set('name', str_replace(' - Cloned', '', $cloned_term->label()));
      // Move the cloned term to the new section year.
      $cloned_term->set('vid', $plan_year_vid);
      $cloned_term->save();
    }
  }

  /**
   * Retrieve the cloned Tid of the given source Tid parent.
   *
   * @param array $source_mapping
   *   An array keyed by source_tid, source_tid_parent, and cloned_tid.
   * @param int $source_tid_parent
   *   The Tid of a term parent from the cloned vocab.
   *
   * @return int|bool
   *   Returns a term ID if the cloned term ID can be found.
   */
  protected function getParentTid(array $source_mapping, $source_tid_parent) {
    foreach ($source_mapping as $item) {
      if ($item['source_tid'] === $source_tid_parent) {
        return $item['cloned_tid'];
      }
    }
    return FALSE;
  }

  /**
   * Remove state plan year answers tagged with terms from this vocab.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function removeStatePlanYearAnswersBySection($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL === $section_vocabulary) {
      throw new \Exception(sprintf('Unable to delete state plan year answers in section %s if the vocabulary (%s) is already deleted', $section_id, $section_vocabulary_id));
    }
    // Remove all section content that is tagged with this section vocabulary.
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    foreach ($query->condition('type', PlanYearInfo::getSpyaNodeBundles(), 'in')
      ->condition('field_plan_year', $plan_year_id)
      ->condition('field_section', $section_id)
      ->accessCheck(FALSE)
      ->execute() as $nid) {
      $node_storage->load($nid)->delete();
    }
  }

  /**
   * Remove all state plan year section nodes in this plan year and section ID.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function removeStatePlanYearSection($plan_year_id, $section_id) {
    $section_vocabulary_id = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->taxonomyService->getVocabulary($section_vocabulary_id);
    if (NULL === $section_vocabulary) {
      throw new \Exception(sprintf('Unable to delete state plan year section (%s) if the vocabulary (%s) is already deleted', $section_id, $section_vocabulary_id));
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    foreach ($this->nodeService->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id) as $state_plan_year_section_nid) {
      $node_storage->load($state_plan_year_section_nid)->delete();
    }
  }

  /**
   * Copy a state plan year answer value from one year to another.
   *
   * Make sure to run MixedEntityService::canCopyStatePlanYearAnswer() before
   * calling this method.
   *
   * @param string $from_state_plan_year_answer_nid
   *   A state plan year answer node ID to copy the value from.
   * @param string $to_state_plan_year_answer_nid
   *   A state plan year answer node ID to copy the value to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function copyStatePlanYearAnswer($from_state_plan_year_answer_nid, $to_state_plan_year_answer_nid) {
    /** @var \Drupal\node\Entity\Node $from */
    $from = $this->customEntitiesRetrieval->single('node', $from_state_plan_year_answer_nid);
    /** @var \Drupal\node\Entity\Node $to */
    $to = $this->customEntitiesRetrieval->single('node', $to_state_plan_year_answer_nid);
    $plan_year_id_from = PlanYearInfo::getPlanYearIdFromEntity($from);
    $plan_year_id_to = PlanYearInfo::getPlanYearIdFromEntity($to);
    $from_value_field = PlanYearInfo::getStatePlanYearAnswerValueField($from);
    $to_value_field = PlanYearInfo::getStatePlanYearAnswerValueField($to);
    // Copy the value from the from year to the to year and save.
    $to_value_field->setValue($from_value_field->getValue());
    $this->nodeSave($to, FALSE, sprintf('Answer copied from plan year %s to %s.', $plan_year_id_from, $plan_year_id_to));
  }

  /**
   * Create a state plans year node that references the state plan year given.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   *
   * @return \Drupal\node\Entity\Node
   *   Returns the state plans year node created or that already existed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createStatePlansYear($plan_year_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $state_plans_year_nid = $this->nodeService->getStatePlansYearByPlanYear($plan_year_id);
    /** @var \Drupal\node\Entity\Node $state_plans_year */
    if (empty($state_plans_year_nid)) {
      $state_plans_year = $node_storage->create([
        'type' => PlanYearInfo::SPZY_BUNDLE,
        'field_plan_year' => [['target_id' => $plan_year_id]],
      ]);
      $this->nodeSave($state_plans_year);
      return $state_plans_year;
    }
    $state_plans_year = $node_storage->load($state_plans_year_nid);
    return $state_plans_year;
  }

  /**
   * Add a state plan year node for the given plan year and group.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $group_id
   *   A group ID.
   *
   * @return \Drupal\node\Entity\Node
   *   Returns the state plan year node created or that already existed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addStatePlanYear($plan_year_id, $group_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $state_plan_year_nid = $this->nodeService->getStatePlanYearByPlanYearAndGroupId($plan_year_id, $group_id);
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    if (!empty($state_plan_year_nid)) {
      $state_plan_year = $node_storage->load($state_plan_year_nid);
      return $state_plan_year;
    }
    $state_plans_year_nid = $this->nodeService->getStatePlansYearByPlanYear($plan_year_id);
    // Ensure that the state plans node was created already, it should have
    // been when the plan year was created, but that might have been imported
    // with config.
    if (empty($state_plans_year_nid)) {
      throw new \Exception('The state plans year must exist before creating a state plan year.');
    }
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $node_storage->create([
      'type' => PlanYearInfo::SPY_BUNDLE,
      'field_state_plans_year' => [['target_id' => $state_plans_year_nid]],
    ]);
    $this->nodeSave($state_plan_year);
    /** @var \Drupal\group\Entity\Group $group */
    $group = $this->entityTypeManager->getStorage('group')->load($group_id);
    /** @var \Drupal\group\Entity\Group $state_group_entity */
    $group->addContent($state_plan_year, 'group_node:' . $state_plan_year->getType());
    return $state_plan_year;
  }

  /**
   * Save a node and enforce a revision message.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to be saved.
   * @param bool $new
   *   Whether the node is new or not.
   * @param string $revisionMessage
   *   A message to log in the revision.
   *
   * @return int
   *   SAVED_NEW or SAVED_UPDATED.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function nodeSave(Node $node, $new = TRUE, $revisionMessage = 'Content created by automated process.') {
    if (TRUE === $new) {
      // These node types start in 'New, not available' so that admins can turn
      // on plans for editorial when they are ready.
      if (in_array($node->getType(), array_merge(
          PlanYearInfo::getSpyaNodeBundles(), [
            PlanYearInfo::SPY_BUNDLE,
            PlanYearInfo::SPYS_BUNDLE,
          ]
      ))) {
        $node->set('moderation_state', ContentService::MODERATION_STATE_NEW);
      }
    }
    // Owner should ALWAYS be the automated user. Grab the revision user as the
    // currently logged in user if available. It won't be in CLI. If the current
    // user is the user 1, don't allow it to be the revision user.
    $owner_user = $revision_user = $this->nodeService->getAutomatedNodeOwner();
    if ($this->currentUser->id() != '1' && !$this->currentUser->isAnonymous()) {
      $revision_user = User::load($this->currentUser->id());
    }
    $node->setOwner($owner_user);
    $node->setRevisionUser($revision_user);
    $node->enforceIsNew($new);
    $node->setRevisionLogMessage($revisionMessage);
    $node->setRevisionTranslationAffected(TRUE);
    return $node->save();
  }

  /**
   * Add a state plan year section for the given plan year, group, and section.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $group_id
   *   A group ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return \Drupal\node\Entity\Node
   *   Returns the state plan year section node created or that already existed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function addStatePlanYearSection($plan_year_id, $group_id, $section_id) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    // If this state plan year section already exists, just quit.
    $state_plan_year_section_nid = $this->nodeService->getStatePlanYearSectionByPlanYearGroupAndSection($plan_year_id, $group_id, $section_id);
    /** @var \Drupal\node\Entity\Node $state_plan_year_section */
    if (!empty($state_plan_year_section_nid)) {
      $state_plan_year_section = $node_storage->load($state_plan_year_section_nid);
      return $state_plan_year_section;
    }
    // There is trouble if the state plan year this section belongs to is not
    // created yet.
    $state_plan_year_nid = $this->nodeService->getStatePlanYearByPlanYearAndGroupId($plan_year_id, $group_id);
    if (empty($state_plan_year_nid)) {
      throw new \Exception('The state plan year for plan year ' . $plan_year_id . ' and group ' . $group_id . ' does not exist.');
    }
    /** @var \Drupal\node\Entity\Node $state_plan_year_section */
    $state_plan_year_section = $node_storage->create([
      'type' => PlanYearInfo::SPYS_BUNDLE,
      'field_section' => [['target_id' => $section_id]],
      'field_state_plan_year' => [['target_id' => $state_plan_year_nid]],
    ]);
    $this->nodeSave($state_plan_year_section);
    /** @var \Drupal\group\Entity\Group $state_group_entity */
    $state_group_entity = $this->entityTypeManager->getStorage('group')->load($group_id);
    $state_group_entity->addContent($state_plan_year_section, 'group_node:' . $state_plan_year_section->getType());
    return $state_plan_year_section;
  }

  /**
   * Remove state plan year section for the given plan year, group, and section.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $group_id
   *   A group ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeStatePlanYearSectionGroup($plan_year_id, $group_id, $section_id) {
    // Be care that section ID is set, otherwise all state plan year sections
    // for this plan year and group will be removed.
    if (NULL === $section_id) {
      return;
    }
    $state_plan_year_section_nid = $this->nodeService->getStatePlanYearSectionByPlanYearGroupAndSection($plan_year_id, $group_id, $section_id);
    if (empty($state_plan_year_section_nid)) {
      return;
    }
    $this->entityTypeManager->getStorage('node')->load($state_plan_year_section_nid)->delete();
  }

  /**
   * Remove a state plan year answer node.
   *
   * @param string $state_plan_year_answer_nid
   *   A state plan year answer node ID.
   *
   * @return string
   *   The state plan year answer node title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeStatePlanYearAnswer($state_plan_year_answer_nid) {
    /** @var \Drupal\node\Entity\Node $state_plan_year_answer */
    $state_plan_year_answer = $this->entityTypeManager->getStorage('node')->load($state_plan_year_answer_nid);
    if (NULL === $state_plan_year_answer) {
      return 'Answer could not be found';
    }
    $title = $state_plan_year_answer->getTitle();
    $state_plan_year_answer->delete();
    return $title;
  }

  /**
   * Add state plan year answer node.
   *
   * @param string $node_bundle
   *   The node type.
   * @param string $field_unique_id_reference
   *   The UUID that uniquely identifies a term field between years.
   * @param string $plan_year_id
   *   The plan year ID that this answer belongs to.
   * @param string $section_id
   *   The section ID that this answer belongs to.
   * @param string $section_year_term_tid
   *   The term that this answer is based on.
   * @param string $state_plan_year_section_nid
   *   The state plan year section NID that this answer belongs to.
   *
   * @return \Drupal\node\Entity\Node
   *   Either the created state plan year answer or the already existing.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function addStatePlanYearAnswer($node_bundle, $field_unique_id_reference, $plan_year_id, $section_id, $section_year_term_tid, $state_plan_year_section_nid) {
    $state_plan_year_answer_nid = $this->nodeService->getStatePlanYearAnswer($node_bundle, $field_unique_id_reference, $plan_year_id, $section_id, $section_year_term_tid, $state_plan_year_section_nid);
    $node_storage = $this->entityTypeManager->getStorage('node');
    /** @var \Drupal\node\Entity\Node $state_plan_year_answer */
    if (!empty($state_plan_year_answer_nid)) {
      $state_plan_year_answer = $node_storage->load($state_plan_year_answer_nid);
      return $state_plan_year_answer;
    }
    /** @var \Drupal\node\Entity\Node $state_plan_year_section */
    $state_plan_year_section = $node_storage->load($state_plan_year_section_nid);
    if (NULL === $state_plan_year_section) {
      throw new \Exception('The state plan year answer to be created had an invalid state plan year section saved to it.');
    }
    $group_id = $this->customEntitiesRetrieval->getGroupId($state_plan_year_section);
    /** @var \Drupal\node\Entity\Node $state_plan_year_answer */
    $state_plan_year_answer = $node_storage->create([
      'type' => $node_bundle,
      'field_field_unique_id_reference' => $field_unique_id_reference,
      'field_plan_year' => [['target_id' => $plan_year_id]],
      'field_section' => [['target_id' => $section_id]],
      'field_section_year_term' => [['target_id' => $section_year_term_tid]],
      'field_state_plan_year_section' => [['target_id' => $state_plan_year_section_nid]],
    ]);
    $this->nodeSave($state_plan_year_answer);
    /** @var \Drupal\group\Entity\Group $state_group_entity */
    $state_group_entity = $this->entityTypeManager->getStorage('group')->load($group_id);
    $state_group_entity->addContent($state_plan_year_answer, 'group_node:' . $state_plan_year_answer->getType());
    return $state_plan_year_answer;
  }

  /**
   * Update state plan year section moderation status when answers are changed.
   *
   * @param string $state_plan_year_section_nid
   *   A state plan section node ID.
   * @param string $revision_log_message
   *   A revision log message to enter that describes what started this call.
   * @param string $default_moderation_state
   *   The moderation state to set if there are no answer nodes in the section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateStatePlanYearSectionModerationStateBasedOnAnswers($state_plan_year_section_nid, $revision_log_message, $default_moderation_state) {
    // Retrieve the moderation state of all answers in this section.
    $answers_moderation_states = $this->nodeService->getStatePlanYearAnswersModerationStateByStatePlanYearSectionNid($state_plan_year_section_nid);
    if (empty($answers_moderation_states)) {
      $state_plan_year_section_new_moderation_state = $default_moderation_state;
    }
    else {
      // Don't care how many of each are a status only if the status is
      // set.
      $answers_moderation_states = array_unique($answers_moderation_states);
      $state_plan_year_section_new_moderation_state = NULL;
      // At least a single answer in draft.
      if (in_array(ContentService::MODERATION_STATE_DRAFT, $answers_moderation_states)) {
        $state_plan_year_section_new_moderation_state = ContentService::MODERATION_STATE_DRAFT;
      }
      // At least a single answer is disallowed.
      elseif (in_array(ContentService::MODERATION_STATE_DISALLOW, $answers_moderation_states)) {
        $state_plan_year_section_new_moderation_state = ContentService::MODERATION_STATE_DISALLOW;
      }
      // At least a single answer is hidden.
      elseif (in_array(ContentService::MODERATION_STATE_HIDDEN, $answers_moderation_states)) {
        $state_plan_year_section_new_moderation_state = ContentService::MODERATION_STATE_HIDDEN;
      }
    }
    $this->updateNodeModerationState($state_plan_year_section_nid, $revision_log_message, $state_plan_year_section_new_moderation_state);
  }

  /**
   * Update a node's moderation state.
   *
   * @param string $nid
   *   A node ID.
   * @param string $revision_log_message
   *   A revision log message to enter that describes what started this call.
   * @param string $new_moderation_state
   *   The new moderation state.
   *
   * @return bool
   *   True if the current moderation state was different the new.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateNodeModerationState($nid, $revision_log_message, $new_moderation_state) {
    // Only update if there is a new moderation state and it
    // is different from the current.
    $node = $this->nodeService->load($nid);
    if (!empty($node) && !empty($new_moderation_state) && $new_moderation_state !== $node->get('moderation_state')) {
      $node->set('moderation_state', $new_moderation_state);
      $this->nodeSave($node, FALSE, $revision_log_message);
      return TRUE;
    }
    return FALSE;
  }

}