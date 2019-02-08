<?php

namespace Drupal\sp_plan_year\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sp_create\UpdatePlanYearBatch;
use Drupal\sp_retrieve\CustomEntitiesService;
use Drupal\sp_retrieve\NodeService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PlanYearEntityContentForm.
 */
class PlanYearEntityContentForm extends EntityBatchForm {

  /**
   * The node retrieval service.
   *
   * @var \Drupal\sp_retrieve\NodeService
   */
  protected $nodeRetrieval;

  /**
   * PlanYearEntityWizardForm constructor.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   * @param \Drupal\sp_retrieve\NodeService $node_retrieval
   *   The node retrieval service.
   */
  public function __construct(CustomEntitiesService $custom_entities_retrieval, NodeService $node_retrieval) {
    parent::__construct($custom_entities_retrieval);
    $this->nodeRetrieval = $node_retrieval;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sp_retrieve.custom_entities'),
      $container->get('sp_retrieve.node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $current_plan_year */
    $current_plan_year = $this->entity;
    // Find what plans are not created yet.
    $all_group_ids = $this->customEntitiesRetrieval->all('group', 'ids');
    if (empty($all_group_ids)) {
      $this->messenger()->addError($this->t('Please create the states and territories before creating the plan content.'));
      return $form;
    }
    $group_content_message = [];
    $missing_content = $this->nodeRetrieval->getGroupsMissingStatePlanYearsAndStatePlanYearSections($current_plan_year->id());
    if (!empty($missing_content['plan_year_without_state_plans_year'])) {
      $group_content_message[] = $this->t('This plan year is missing the state plans year node.');
    }
    if (!empty($missing_content['missing_state_plans_year'])) {
      $group_content_message[] = $this->t('The state plans year node is missing.');
    }
    if (!empty($missing_content['group_ids_without_plans'])) {
      $group_content_message[] = $this->t('There are %cnt_wo out of %cnt_w groups without plans.', [
        '%cnt_wo' => count($missing_content['group_ids_without_plans']),
        '%cnt_w' => count($all_group_ids),
      ]);
    }
    // As long as all groups are not missing a plan, get what sections might
    // be missing.
    if (!empty($missing_content['group_ids_without_sections'])) {
      foreach ($missing_content['group_ids_without_sections'] as $section_id => $groups_missing_section) {
        if (!empty($groups_missing_section)) {
          $group_content_message[] = $this->t('There are %cnt_wo out of %cnt_w groups without the section %section.', [
            '%cnt_wo' => count($groups_missing_section),
            '%cnt_w' => count($all_group_ids),
            '%section' => $this->getSectionLabel($section_id),
          ]);
        }
      }
    }
    if (!empty($group_content_message)) {
      $group_content_message = implode('</li><li>', $group_content_message);
    }
    $form['create_plans_and_sections'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create plans and sections'),
      '#description' => $group_content_message ? '<ul><li>' . $group_content_message . '</li></ul>' : $this->t('All plans and sections have been created.'),
      '#disabled' => !strlen($group_content_message),
    ];
    // @TODO: Make sure that all these groups don't have an in-progress plan year if any sections are being copied from any plan year.
    // Allow them to copy answers from previous years if they copied hierarchy.
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This usually has the submit and delete button.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#submit' => ['::submitForm'],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   *
   * This usually prepares the entity by applying changes to it. However, this
   * form is not going to set it directly.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $current_plan_year */
    $current_plan_year = $this->entity;
    if ($form_state->getValue('create_plans_and_sections')) {
      $batch = [
        'title' => $this->t('Adding missing content to plan year %label', ['%label' => $this->entity->label()]),
        'operations' => [],
        'finished' => [UpdatePlanYearBatch::class, 'finished'],
      ];
      $missing_content = $this->nodeRetrieval->getGroupsMissingStatePlanYearsAndStatePlanYearSections($current_plan_year->id());
      // Every plan year must have a state plans year already.
      if (!empty($missing_content['plan_year_without_state_plans_year'])) {
        $batch['operations'][] = $this->batchAddStatePlansYear();
      }
      // At least one group doesn't have a plan.
      if (!empty($missing_content['group_ids_without_plans'])) {
        foreach ($missing_content['group_ids_without_plans'] as $group_id_without_plan) {
          $batch['operations'][] = $this->batchAddStatePlanYear($group_id_without_plan);
          foreach ($current_plan_year->getSections() as $section) {
            $batch['operations'][] = $this->batchAddStatePlanYearSection($section->id(), $group_id_without_plan);
          }
        }
      }
      // As long as all groups are not missing a plan, get what sections might
      // be missing.
      if (!empty($missing_content['group_ids_without_sections'])) {
        foreach ($missing_content['group_ids_without_sections'] as $section_id => $groups_missing_section) {
          foreach ($groups_missing_section as $group_id) {
            $batch['operations'][] = $this->batchAddStatePlanYearSection($section_id, $group_id);
          }
        }
      }
    }

    if (empty($batch['operations'])) {
      $this->messenger()->addError($this->t('No operations were set.'));
    }
    else {
      batch_set($batch);
      // Clear out the current saved stepped data and reload the first step.
      $this->messenger()->addMessage($this->t('The plan year %label has had content added successfully.', [
        '%label' => $this->entity->label(),
      ]));
      // Send them back to the listing page.
      $form_state->setRedirectUrl($this->entity->toUrl('content'));
    }

  }

}
