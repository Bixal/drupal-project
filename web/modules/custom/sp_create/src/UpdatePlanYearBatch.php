<?php

namespace Drupal\sp_create;

/**
 * Class UpdatePlanYearBatch.
 */
class UpdatePlanYearBatch {

  /**
   * Drop all term hierarchy from this section.
   *
   * The section meta data should be updated by this point and the given section
   * may not be found on the plan year entity.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeSectionHierarchy($plan_year_id, $section_id, $section_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->removeSectionHierarchy($plan_year_id, $section_id);
    $context['message'] = 'Removing hierarchy of ' . $section_label . ' from plan year ' . $plan_year_id;
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
   * @param string $section_label
   *   The section label.
   * @param string $plan_year_id_to_copy
   *   The plan year to copy hierarchy from to this section.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public static function copySectionHierarchy($plan_year_id, $section_id, $section_label, $plan_year_id_to_copy, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->copySectionHierarchy($plan_year_id, $section_id, $plan_year_id_to_copy);
    $context['message'] = 'Copying hierarchy of ' . $section_label . ' from ' . $plan_year_id_to_copy . ' to plan year ' . $plan_year_id;
  }

  /**
   * Remove a section from a plan year meta data.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeSectionMeta($plan_year_id, $section_id, $section_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearConfigService $update_plan_year_config */
    $update_plan_year_config = \Drupal::service('sp_create.update_plan_year_config');
    $update_plan_year_config->removeSectionMeta($plan_year_id, $section_id);
    $context['message'] = 'Removing section meta data of ' . $section_label . ' from plan year ' . $plan_year_id;
  }

  /**
   * Update or add a new section and the plan year it's copied from.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param string $plan_year_id_to_copy
   *   The plan year to copy hierarchy from to this section.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateSectionMeta($plan_year_id, $section_id, $section_label, $plan_year_id_to_copy = '', &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearConfigService $update_plan_year_config */
    $update_plan_year_config = \Drupal::service('sp_create.update_plan_year_config');
    $update_plan_year_config->updateSectionMeta($plan_year_id, $section_id, $plan_year_id_to_copy);
    $context['message'] = 'Updating section meta data with ' . $section_label . ' copied from ' . $plan_year_id_to_copy ? $plan_year_id_to_copy : 'none' . ' to plan year ' . $plan_year_id;
  }

  /**
   * Remove this plan year section vocabulary.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeSection($plan_year_id, $section_id, $section_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearConfigService $update_plan_year_config */
    $update_plan_year_config = \Drupal::service('sp_create.update_plan_year_config');
    $update_plan_year_config->removeSection($plan_year_id, $section_id);
    $context['message'] = 'Removing section ' . $section_label . ' from plan year ' . $plan_year_id;
  }

  /**
   * Add this plan year section vocabulary.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function addSection($plan_year_id, $section_id, $section_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearConfigService $update_plan_year_config */
    $update_plan_year_config = \Drupal::service('sp_create.update_plan_year_config');
    $update_plan_year_config->addSection($plan_year_id, $section_id);
    $context['message'] = 'Adding section ' . $section_label . ' to plan year ' . $plan_year_id;
  }

  /**
   * Add the state plans year node.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addStatePlansYear($plan_year_id, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->createStatePlansYear($plan_year_id);
    $context['message'] = 'Creating state plans year ' . $plan_year_id;
  }

  /**
   * Add the state plan year node to the given group.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $group_id
   *   A group ID.
   * @param string $group_label
   *   The group label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addStatePlanYear($plan_year_id, $group_id, $group_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->addStatePlanYear($plan_year_id, $group_id);
    $context['message'] = 'Creating state plan year ' . $plan_year_id . ' for ' . $group_label;
  }

  /**
   * Remove a state plan year section node from a group.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeStatePlanYearSection($plan_year_id, $section_id, $section_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->removeStatePlanYearSection($plan_year_id, $section_id);
    $context['message'] = 'Removing state plan year section for ' . $plan_year_id . ' in section ' . $section_label;
  }

  /**
   * Remove a state plan year section node from a group.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param string $group_id
   *   A group ID.
   * @param string $group_label
   *   The group label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeStatePlanYearSectionGroup($plan_year_id, $section_id, $section_label, $group_id, $group_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->removeStatePlanYearSectionGroup($plan_year_id, $group_id, $section_id);
    $context['message'] = 'Removing state plan year section for ' . $plan_year_id . ' section ' . $section_label . ' for ' . $group_label;
  }

  /**
   * Remove state plan year answers tagged with terms from this vocab.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeStatePlanYearAnswersBySection($plan_year_id, $section_id, $section_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->removeStatePlanYearAnswersBySection($plan_year_id, $section_id);
    $context['message'] = 'Removing state plan year answers for ' . $plan_year_id . ' in section ' . $section_label;
  }

  /**
   * Add a state plan year section node to a group.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param string $group_id
   *   A group ID.
   * @param string $group_label
   *   The group label.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addStatePlanYearSection($plan_year_id, $section_id, $section_label, $group_id, $group_label, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $update_plan_year_content->addStatePlanYearSection($plan_year_id, $group_id, $section_id);
    $context['message'] = 'Adding state plan year section for ' . $plan_year_id . ' section ' . $section_label . ' for ' . $group_label;
  }

  /**
   * Remove a state plan year answer node.
   *
   * @param string $state_plan_year_answer_nid
   *   A state plan year answer node ID.
   * @param array|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function removeStatePlanYearAnswer($state_plan_year_answer_nid, &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    $title = $update_plan_year_content->removeStatePlanYearAnswer($state_plan_year_answer_nid);
    $context['message'] = 'Removing state plan year answer ' . $title;
  }

  /**
   * Create a state plan year answer node.
   *
   * @param string $node_bundle
   *   The node type.
   * @param string $field_unique_id_reference
   *   The UUID that uniquely identifies a term field between years.
   * @param string $plan_year_id
   *   The plan year ID that this content belongs to.
   * @param string $section_id
   *   The section ID that this content belongs to.
   * @param string $section_year_term_tid
   *   The term that this piece of content is based on.
   * @param string $state_plan_year_section_nid
   *   The state plan year section NID that this piece of content belongs to.
   * @param array|\DrushBatchContext|\DrushBatchContext $context
   *   Keys of message and results to communicate with batch.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function addStatePlanYearAnswer($node_bundle, $field_unique_id_reference, $plan_year_id, $section_id, $section_year_term_tid, $state_plan_year_section_nid, &$context = []) {
    /* @var \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content */
    $update_plan_year_content = \Drupal::service('sp_create.update_plan_year_content');
    /* @var \Drupal\node\Entity\Node */
    $state_plan_answer = $update_plan_year_content->addStatePlanYearAnswer($node_bundle, $field_unique_id_reference, $plan_year_id, $section_id, $section_year_term_tid, $state_plan_year_section_nid);
    $context['message'] = 'Adding state plan year answer item ' . $state_plan_answer->getTitle();
  }

  /**
   * Finish callback for batch.
   *
   * @param bool $success
   *   True if no exceptions thrown.
   * @param array $results
   *   The $context['results'] array passed in from all operations.
   * @param array|\DrushBatchContext $operations
   *   An array of function calls (not used in this function).
   */
  public static function finished($success, array $results, array $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->translate('Plan year updated successfully.');
    }
    else {
      $message = t('Finished with an error.');
    }
    /** @var \Drupal\Core\Messenger\MessengerInterface $messenger */
    $messenger = \Drupal::service('messenger');
    $messenger->addMessage($message);
  }

}
