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
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function removeSectionHierarchy($plan_year_id, $section_id, $section_label, array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->removeSectionHierarchy($plan_year_id, $section_id);
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
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function copySectionHierarchy($plan_year_id, $section_id, $section_label, $plan_year_id_to_copy, array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->copySectionHierarchy($plan_year_id, $section_id, $plan_year_id_to_copy);
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
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function removeSectionMeta($plan_year_id, $section_id, $section_label, array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->removeSectionMeta($plan_year_id, $section_id);
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
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function updateSectionMeta($plan_year_id, $section_id, $section_label, $plan_year_id_to_copy = '', array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->updateSectionMeta($plan_year_id, $section_id, $plan_year_id_to_copy);
    $context['message'] = 'Updating section meta data with ' . $section_label . ' copied from ' . $plan_year_id_to_copy ? $plan_year_id_to_copy : 'none' . ' to plan year ' . $plan_year_id;
  }

  /**
   * Remove all content tagged with this plan year section.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $section_label
   *   The section label.
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function removeSectionContent($plan_year_id, $section_id, $section_label, array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->removeSectionContent($plan_year_id, $section_id);
    $context['message'] = 'Removing section hierarchy of ' . $section_label . ' from plan year ' . $plan_year_id;
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
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function removeSection($plan_year_id, $section_id, $section_label, array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->removeSection($plan_year_id, $section_id);
    $context['message'] = 'Removing section of ' . $section_label . ' from plan year ' . $plan_year_id;
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
   * @param array $context
   *   Keys of message and results to communicate with batch.
   */
  public static function addSection($plan_year_id, $section_id, $section_label, array &$context = []) {
    /** @var \Drupal\sp_create\UpdatePlanYearService $update_plan_year */
    $update_plan_year = \Drupal::service('sp_create.update_plan_year');
    $update_plan_year->addSection($plan_year_id, $section_id);
    $context['message'] = 'Adding section of ' . $section_label . ' to plan year ' . $plan_year_id;
  }

  /**
   * Finish callback for batch.
   *
   * @param bool $success
   *   True if no exceptions thrown.
   * @param array $results
   *   The $context['results'] array passed in from all operations.
   * @param array $operations
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
