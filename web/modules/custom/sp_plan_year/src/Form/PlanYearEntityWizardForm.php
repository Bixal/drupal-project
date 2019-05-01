<?php

namespace Drupal\sp_plan_year\Form;

use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\sp_create\UpdatePlanYearBatch;
use Drupal\sp_retrieve\CustomEntitiesService;
use Drupal\sp_retrieve\NodeService;
use Drupal\sp_section\Entity\SectionEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\sp_plan_year\Entity\PlanYearEntity;

/**
 * Class PlanYearEntityForm.
 */
class PlanYearEntityWizardForm extends EntityBatchForm {

  /**
   * The node retrieval service.
   *
   * @var \Drupal\sp_retrieve\NodeService
   */
  protected $nodeService;

  /**
   * The shared temporary store instance.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $store;

  /**
   * The entity builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Denotes that the plan year copied from a section has not changed.
   *
   * @var string
   */
  public static $noChange = 'noChange';

  /**
   * PlanYearEntityWizardForm constructor.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node retrieval service.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   Service used to create a shared temporary store.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity builder service.
   */
  public function __construct(CustomEntitiesService $custom_entities_retrieval, NodeService $node_service, SharedTempStoreFactory $temp_store_factory, EntityFormBuilderInterface $entity_form_builder) {
    parent::__construct($custom_entities_retrieval);
    $this->nodeService = $node_service;
    $this->store = $temp_store_factory->get('plan_year_wizard');
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sp_retrieve.custom_entities'),
      $container->get('sp_retrieve.node'),
      $container->get('tempstore.shared'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Sets the current step to the given step name.
   *
   * @param string $step
   *   A step name.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setCurrentStep($step) {
    $step_passed = $this->store->get('step_passed');
    $step_passed[$step] = 0;
    $this->store->set('step_current', $step);
    $this->store->set('step_passed', $step_passed);
  }

  /**
   * Show that a step has been passed.
   *
   * @param string $step
   *   A step name.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setStepPassed($step) {
    $this->store->set('step_current', '');
    $step_passed = $this->store->get('step_passed');
    $step_passed[$step] = 1;
    $this->store->set('step_passed', $step_passed);
  }

  /**
   * Determine if a step has been passed yet.
   *
   * @param string $step
   *   A step name.
   *
   * @return int
   *   1 for true 0 for false.
   */
  protected function isStepPassed($step) {
    $step_passed = $this->store->get('step_passed');
    if (isset($step_passed[$step])) {
      return $step_passed[$step];
    }
    return 0;
  }

  /**
   * Retrieves the current steps information.
   *
   * @return array
   *   An array of step information.
   */
  protected function getStepCurrent() {
    $step_current = $this->store->get('step_current');
    return [
      'value' => $this->getStepValue($step_current),
      'name' => $step_current,
      'passed' => $this->isStepPassed($step_current),
    ];
  }

  /**
   * Retrieve the current value stored for the given step.
   *
   * @param string $step
   *   A step name.
   *
   * @return mixed|null
   *   Steps usually store data as arrays. If the step is has no value yet null.
   */
  protected function getStepValue($step) {
    $step_values = $this->store->get('step_values');
    if (isset($step_values[$step])) {
      return $step_values[$step];
    }
    return NULL;
  }

  /**
   * Set the given steps value.
   *
   * @param string $step
   *   A step name.
   * @param mixed $value
   *   A value to store for a step.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setStepValue($step, $value) {
    $step_values = $this->store->get('step_values');
    $step_values[$step] = $value;
    $this->store->set('step_values', $step_values);
  }

  /**
   * Return the value of all steps.
   *
   * @return array
   *   An array keyed by the step names.
   */
  protected function getStepsValue() {
    return $this->store->get('step_values') ? $this->store->get('step_values') : [];
  }

  /**
   * Set which plan year is currently stored in the shared temp store.
   *
   * Since shared data is shared between all plan years, this can be used to
   * determine if the stored data is from a different plan year.
   *
   * @param string $plan_year_id
   *   A plan year entity ID.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function setModifyingEntity($plan_year_id) {
    $this->store->set('modifying_entity', $plan_year_id);
  }

  /**
   * Retrieve the current modifying entity ID.
   *
   * @return string
   *   A plan year entity ID.
   */
  protected function getModifyingEntity() {
    return $this->store->get('modifying_entity') ? $this->store->get('modifying_entity') : '';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $current_plan_year */
    $current_plan_year = $this->entity;
    // Determine if they can access the current plan year wizard.
    $form_state->set('disable_actions', FALSE);
    if (!empty($this->getModifyingEntity()) && $current_plan_year->id() !== $this->getModifyingEntity()) {
      // This is the entity that they are already working with.
      $modifying_entity = $this->entityTypeManager->getStorage(PlanYearEntity::ENTITY)->load($this->getModifyingEntity());
      $this->messenger()->addError(
        $this->t(
          'There is already a plan year wizard form in progress, please %wizard_url before continuing.',
          [
            '%wizard_url' => Link::fromTextAndUrl(
              $this->t('complete it or cancel the wizard'),
              $modifying_entity->toUrl('wizard')
            )->toString(),
          ]
        )
      );
      $form_state->set('disable_actions', TRUE);
      return $form;
    }
    // It's possible, depending on the section to disable the submit / next
    // button, initialize to false.
    $form_state->set('disable_next', FALSE);

    // Get variables that are shared by the different steps.
    $all_plan_year_labels = $this->customEntitiesRetrieval->labels(PlanYearEntity::ENTITY);
    // Don't include the current plan year in any label.
    unset($all_plan_year_labels[$current_plan_year->id()]);
    $all_section_labels = $this->customEntitiesRetrieval->labels(SectionEntity::ENTITY);
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity[] $current_sections */
    $current_sections = $current_plan_year->getSections();
    $current_plan_year_section = $current_plan_year->getCopyFromPlanYearSectionArray();
    if (!empty($current_sections)) {
      $this->messenger()->addWarning($this->t('You are modifying a plan year that has been run through the wizard already and has sections.'));
    }

    //
    // Step: Allow them to choose a year to set the sections from.
    // They should only see this step if:
    // - They have not set any sections yet.
    // - There are previous plan years.
    if (empty($current_sections) && !empty($all_plan_year_labels) && !$this->isStepPassed('choose_plan_year_previous')) {
      $current_step_info = $this->getStepCurrent();
      $this->setCurrentStep('choose_plan_year_previous');
      $form['plan_year_previous'] = [
        '#type' => 'select',
        '#title' => $this->t('Previous Plan Years'),
        '#default_value' => isset($current_step_info['value']['plan_year_previous']) ? $current_step_info['value']['plan_year_previous'] : '',
        '#empty_option' => $this->t('- Optional -'),
        '#options' => $all_plan_year_labels,
        '#description' => $this->t('Use the sections in this plan year in the current plan year.'),
      ];
      // Only give option to copy the hierarchy if they chose a year.
      $form['copy_hierarchy'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Copy hierarchy from each section'),
        '#default_value' => isset($current_step_info['value']['copy_hierarchy']) ? $current_step_info['value']['copy_hierarchy'] : '',
        '#description' => $this->t('The hierarchy is empty when creating a new plan year for each section. Hierarchy are the questions and text of the plan, not the answers that the states give. If you would like to copy the hierarchy from the plan year selected above, check this.'),
        '#states' => [
          'invisible' => [
            'select[name="plan_year_previous"]' => ['value' => ''],
          ],
        ],
      ];
    }

    //
    // Step: Allow them to choose which sections should be in the plan year.
    //
    // They should only see this tep if:
    // - Always!
    elseif (!$this->isStepPassed('modify_sections')) {
      $this->setCurrentStep('modify_sections');
      $form['copy_sections'] = [
        '#type' => 'table',
        '#caption' => $this->t('Choose sections that you would like to use in this plan year. You can optionally copy the hierarchy from a section in a given year.'),
        '#header' => [
          'Section',
          'Copy hierarchy from a different plan year (Optional)',
          'Current saved value of hierarchy copy plan year',
        ],
        '#sticky' => TRUE,
        '#empty' => $this->t('You must create at least a single section to continue.'),
      ];
      // The sections and years to copy from.
      $selected_sections = [];
      // Reload the values from this step.
      $modify_sections = $this->getStepValue('modify_sections');
      if (!empty($modify_sections['selected_sections'])) {
        $selected_sections = $modify_sections['selected_sections'];
      }
      // Editing a plan year that already has sections assigned.
      elseif (!empty($current_sections)) {
        foreach ($current_sections as $section) {
          $selected_sections[$section->id()] = self::$noChange;
        }
      }
      else {
        // If they set a previous plan year, go ahead and set up the form with
        // those defaults.
        $choose_plan_year_previous = $this->getStepValue('choose_plan_year_previous');
        if (!empty($choose_plan_year_previous['plan_year_previous'])) {
          /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $previous_plan_year */
          $previous_plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, $choose_plan_year_previous['plan_year_previous']);
          foreach ($previous_plan_year->getSections() as $section) {
            $selected_sections[$section->id()] = !empty($choose_plan_year_previous['copy_hierarchy']) ? $previous_plan_year->id() : '';
          }
        }
      }
      // Get all plan year IDs that every section is has been used in (Except
      // the current plan year, it doesn't make sense to copy a section from
      // itself).
      $all_plan_years_by_section = $this->customEntitiesRetrieval->allPlanYearsBySection($current_plan_year->id());
      foreach ($all_section_labels as $section_id => $section_label) {
        $form['copy_sections'][$section_id]['copy'] = [
          '#type' => 'checkbox',
          '#title' => $section_label,
          '#default_value' => isset($selected_sections[$section_id]),
        ];
        if (!empty($all_plan_years_by_section[$section_id])) {
          $options = [];
          if (!empty($current_sections)) {
            $options[self::$noChange] = $this->t('- Keep plan year the same as saved -');
          }
          $options += $all_plan_years_by_section[$section_id];
          // If this section was selected and they chose to copy from a plan
          // year it was used in. $selected_sections is section ID to plan year.
          $default_value = '';
          if (isset($selected_sections[$section_id]) && in_array($selected_sections[$section_id], $all_plan_years_by_section[$section_id])) {
            $default_value = $selected_sections[$section_id];
          }
          // If the wizard saved value was not a plan year, then if they have
          // sections already and the selected section is not to not copy
          // then make it 'nochange'.
          elseif (!empty($current_sections) && !empty($selected_sections[$section_id])) {
            $default_value = self::$noChange;
          }
          $form['copy_sections'][$section_id]['plan_year_to_copy'] = [
            '#type' => 'select',
            '#title' => $this->t('Copy hierarchy from plan year'),
            '#default_value' => $default_value,
            '#empty_option' => $this->t("- Do not copy the hierarchy -"),
            '#options' => $options,
            '#title_display' => 'invisible',
          ];
        }
        // This section is only in this plan year, cannot copy from another
        // year.
        elseif (!empty($current_sections[$section_id])) {
          $form['copy_sections'][$section_id]['plan_year_to_copy'] = [
            '#type' => 'select',
            '#title' => $this->t('Copy hierarchy from plan year'),
            '#default_value' => self::$noChange,
            '#options' => [self::$noChange => $this->t('- Only used in this plan year -')],
            '#title_display' => 'invisible',
            '#disabled' => TRUE,
          ];
        }
        else {
          $form['copy_sections'][$section_id]['plan_year_to_copy'] = [
            '#markup' => $this->t('Not used in any plan year yet.'),
          ];
        }
        // They have not gone through the wizard and chose any sections yet.
        if (empty($current_sections)) {
          $message = $this->t('Not saved to plan year.');
        }
        else {
          if (empty($current_sections[$section_id])) {
            $message = $this->t('This section is not saved to this plan year yet.');
          }
          elseif (!empty($current_plan_year_section[$section_id])) {
            $message = $this->t(
              'Saved plan year hierarchy to copy is %plan_year',
              ['%plan_year' => $all_plan_year_labels[$current_plan_year_section[$section_id]]]
            );
          }
          else {
            $message = $this->t('Currently not copying a plan year hierarchy');
          }
        }
        $form['copy_sections'][$section_id]['saved_plan_year_to_copy'] = [
          '#markup' => $message,
        ];
      }

      $form['add_section'] = [
        '#type' => 'details',
        '#title' => $this->t('Create a new section'),
        '#open' => TRUE,
        '#description' => $this->t('This is a new section that will be available going forward, not just to this plan year. If you are creating a new plan year, make sure to copy sections from the above form instead of re-creating it here. It could get extremely confusing having multiple sections with the same name.'),
      ];
      $form['add_section']['section_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Section Name'),
        '#maxlength' => 255,
      ];
      $form['add_section']['add_section_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add a new section'),
        '#validate' => ['::validateAddSection'],
      ];

    }
    //
    // Step: Allow them to confirm the changes specified.
    //
    // They should only see this tep if:
    // - they made selections step modify_sections.
    elseif (!$this->isStepPassed('confirm_section')) {
      $this->setCurrentStep('confirm_section');
      // Ask them if they want to create these sections.
      // After finish, forward them on to "creation wizard", where they can
      // create the content referenced by the hierarchy (from past year or not).
      // TODO: Get past sections chosen and compare, warning them about the
      // selections.
      $form['confirm_sections'] = [
        '#type' => 'table',
        '#caption' => $this->t('Confirm the section updates to be made to this plan year'),
        '#header' => [
          'Section',
          'Action',
          'Notes',
        ],
        '#sticky' => TRUE,
        '#empty' => $this->t('The choices made would not result in any changes. Cancel the wizard to continue or return to the previous step to make a change.'),
      ];
      $changes = $this->getChanges();
      if (!empty($changes)) {
        foreach ($changes['added_sections'] as $change) {
          $section_id = $change['section_id'];
          $form['confirm_sections'][$section_id]['section'] = [
            '#type' => 'value',
            '#value' => $this->getSectionLabel($section_id),
          ];
          $form['confirm_sections'][$section_id]['section'] = [
            '#type' => 'markup',
            '#markup' => $this->getSectionLabel($section_id),
          ];
          $form['confirm_sections'][$section_id]['action'] = [
            '#type' => 'markup',
            '#markup' => $this->t('New section added'),
          ];
          $message = $this->t('This change will not affect any current plan data.');
          if (!empty($change['plan_year_id_to_copy'])) {
            $message .= ' ' . $this->t('The section hierarchy from %plan_year for this section will be copied to the current plan year.', ['%plan_year' => $change['plan_year_id_to_copy']]);
          }
          $form['confirm_sections'][$section_id]['notes'] = [
            '#type' => 'markup',
            '#markup' => $message,
          ];
        }
        foreach ($changes['removed_section_ids'] as $section_id) {
          $form['confirm_sections'][$section_id]['section'] = [
            '#type' => 'markup',
            '#markup' => $this->getSectionLabel($section_id),
          ];
          $form['confirm_sections'][$section_id]['action'] = [
            '#type' => 'markup',
            '#markup' => $this->t('Current section removed'),
          ];
          $form['confirm_sections'][$section_id]['notes'] = [
            '#type' => 'markup',
            '#markup' => $this->t('This section will be removed from the current plan year. All hierarchy will be removed and content entered by the states will be deleted.'),
          ];
        }
        foreach ($changes['copied_plan_year_changes'] as $section_id => $new_plan_year_id_to_copy) {
          $form['confirm_sections'][$section_id]['section'] = [
            '#type' => 'markup',
            '#markup' => $this->getSectionLabel($section_id),
          ];
          $form['confirm_sections'][$section_id]['action'] = [
            '#type' => 'markup',
            '#markup' => $this->t('Copied plan year changed'),
          ];
          if ($new_plan_year_id_to_copy === $this->getCurrentSectionCopyPlanYear($section_id)) {
            $message = $this->t('<b>Are you sure you meant to do this?</b> If you meant to keep the copied plan year as is, select "Keep plan year the same as saved" in the previous step. If you continue, the entire hierarchy and content for this section will be removed and the hierarchy replaced with the same plan year (%plan_year).', ['%plan_year' => $new_plan_year_id_to_copy]);
          }
          elseif (empty($new_plan_year_id_to_copy)) {
            $message = $this->t('This section will remain for the current plan year but the hierarchy and content entered by the states will be removed.');
          }
          // A new plan year was given to copy the hierarchy from.
          else {
            $message = $this->t('This section will remain for the current plan year but the hierarchy will be replaced by plan year %plan_year and content entered by the states will be removed.', ['%plan_year' => $new_plan_year_id_to_copy]);
          }
          $form['confirm_sections'][$section_id]['notes'] = [
            '#type' => 'markup',
            '#markup' => $message,
          ];
        }
      }
      else {
        $form_state->set('disable_next', TRUE);
      }
    }

    return $form;
  }

  /**
   * The plan year a section assigned to the current plan year is copied from.
   *
   * This comes from a field saved to the entity, not step values.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return string
   *   A plan year ID or empty if not copied from a plan year.
   */
  protected function getCurrentSectionCopyPlanYear($section_id) {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $current_plan_year */
    $current_plan_year = $this->entity;
    $current_plan_year_section = $current_plan_year->getCopyFromPlanYearSectionArray();
    $old_plan_year_id_to_copy = '';
    if (!empty($current_plan_year_section[$section_id])) {
      $old_plan_year_id_to_copy = $current_plan_year_section[$section_id];
    }
    return $old_plan_year_id_to_copy;
  }

  /**
   * Retrieve the changes to the current plan year.
   *
   * Compare the values saved to the entity to the step values.
   *
   * @return array
   *   An array keyed by added_section, removed_section_ids, and
   *   copied_plan_year_changes or an empty array if no changes found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getChanges() {
    $modify_sections = $this->getStepValue('modify_sections');
    if (empty($modify_sections)) {
      return [];
    }
    $selected_section_ids = array_keys($modify_sections['selected_sections']);
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $current_plan_year */
    $current_plan_year = $this->entity;
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity[] $current_sections */
    $current_sections = $current_plan_year->getSections();
    $current_section_ids = array_keys($current_sections);
    $removed_section_ids = array_diff($current_section_ids, $selected_section_ids);
    $added_section_ids = array_diff($selected_section_ids, $current_section_ids);
    $copied_plan_year_changes = [];
    // Determine what copied plan years are changing for each section. In
    // order to be 'changed' they would have to select the section on the
    // previous step.
    foreach ($modify_sections['selected_sections'] as $section_id => $new_plan_year_id_to_copy) {
      // Don't pick up new sections as modified.
      if (in_array($section_id, $added_section_ids)) {
        continue;
      }
      // If they chose not to change the plan year saved, skip.
      if (self::$noChange === $new_plan_year_id_to_copy) {
        continue;
      }
      // If this was not new or not changed, it might be a plan year ID or empty
      // to signify emptying the hierarchy.
      $copied_plan_year_changes[$section_id] = $new_plan_year_id_to_copy;
    }
    // Only continue if there were any changes, otherwise just return an empty
    // array.
    if (!empty($added_section_ids) || !empty($removed_section_ids) || !empty($copied_plan_year_changes)) {
      // Added sections need the plan year ID to copy the hierarchy from, if
      // given.
      $added_sections = [];
      foreach ($added_section_ids as $section_id) {
        $added_sections[] = [
          'section_id' => $section_id,
          'plan_year_id_to_copy' => $modify_sections['selected_sections'][$section_id],
        ];
      }
      return [
        'added_sections' => $added_sections,
        'removed_section_ids' => $removed_section_ids,
        'copied_plan_year_changes' => $copied_plan_year_changes,
      ];
    }
    return [];
  }

  /**
   * Retrieve the name of the last submitted step.
   *
   * @return string
   *   The last submitted step.
   */
  protected function getPreviousStepName() {
    $steps_value = $this->getStepsValue();
    $current_step = $this->getStepCurrent();

    // Remove the current step from the step values.
    if (isset($steps_value[$current_step['name']])) {
      unset($steps_value[$current_step['name']]);
    }
    // Get the names of each step. The last step is the previous.
    $step_names = array_keys($steps_value);
    if (!empty($step_names)) {
      return end($step_names);
    }
    // No previous steps.
    return '';
  }

  /**
   * {@inheritdoc}
   *
   * This usually has the submit and delete button.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = [];
    if ($form_state->get('disable_actions')) {
      return $actions;
    }
    if (FALSE === $form_state->get('disable_next')) {
      $actions['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Next'),
        '#submit' => ['::submitForm'],
      ];
    }
    if ($this->getPreviousStepName()) {
      $actions['previous'] = [
        '#type' => 'submit',
        '#value' => $this->t('Previous'),
        '#submit' => ['::submitFormPrevious'],
      ];
    }
    if (!empty($this->getModifyingEntity())) {
      $actions['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#submit' => ['::submitFormCancel'],
      ];
    }
    return $actions;
  }

  /**
   * Validate creating a new section.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function validateAddSection(array &$form, FormStateInterface $form_state) {
    if (strlen(trim($form_state->getValue('section_label'))) === 0) {
      $form_state->setErrorByName('section_label', $this->t('A section name is required.'));
    }
    else {
      // Create a new section.
      $entity = SectionEntity::create(['id' => SectionEntity::getRandomId(), 'label' => trim($form_state->getValue('section_label'))]);
      $entity->save();
      // Keep track of the section ID created so that it will be selected.
      $step_name = 'modify_sections';
      $step_value = $this->getStepValue($step_name);
      $step_value['new_section'] = $entity->id();
      $this->setStepValue($step_name, $step_value);
    }
  }

  /**
   * Return to the last step.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitFormPrevious(array &$form, FormStateInterface $form_state) {
    $this->setCurrentStep($this->getPreviousStepName());
  }

  /**
   * Cancel the wizard.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitFormCancel(array &$form, FormStateInterface $form_state) {
    $this->clearStepData();
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

  /**
   * Clear all temporary data stored by the steps.
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  protected function clearStepData() {
    // Delete the warning about editing a plan with sections.
    $this->messenger()->deleteAll();
    // Every key we set needs to be deleted.
    $this->store->delete('step_current');
    $this->store->delete('step_passed');
    $this->store->delete('step_values');
    $this->store->delete('modifying_entity');
  }

  /**
   * {@inheritdoc}
   *
   * This usually prepares the entity by applying changes to it. However, this
   * form is not going to set it directly.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // If they submit any step, lock the wizard to this entity.
    $this->setModifyingEntity($this->entity->id());
    $current_step_info = $this->getStepCurrent();
    switch ($current_step_info['name']) {
      case 'choose_plan_year_previous':
        $this->setStepValue($current_step_info['name'], [
          'plan_year_previous' => $form_state->getValue('plan_year_previous'),
          'copy_hierarchy' => $form_state->getValue('copy_hierarchy'),
        ]);
        $this->setStepPassed($current_step_info['name']);
        break;

      case 'modify_sections':
        $step_value = $this->getStepValue($current_step_info['name']);
        $selected_sections = [];
        // Take the newly created section and add it as selected.
        if (!empty($step_value['new_section'])) {
          // New sections never have a year, hence setting equal to ''.
          $selected_sections[$step_value['new_section']] = '';
        }
        $copy_sections = $form_state->getValue('copy_sections', []);
        if (!empty($copy_sections)) {
          foreach ($copy_sections as $section_id => $options) {
            if ($options['copy']) {
              $selected_sections[$section_id] = !empty($options['plan_year_to_copy']) ? $options['plan_year_to_copy'] : '';
            }
          }
        }
        $this->setStepValue($current_step_info['name'], [
          'selected_sections' => $selected_sections,
        ]);
        // If they were not adding a new section, send to next form.
        if ($this->getTriggerElement($form_state) !== 'add_section_submit') {
          $this->setStepPassed($current_step_info['name']);
        }
        break;

      case 'confirm_section':
        $batch = [
          'title' => $this->t('Updating plan year %label', ['%label' => $this->entity->label()]),
          'operations' => [],
          'finished' => [UpdatePlanYearBatch::class, 'finished'],
        ];
        // Create state plans year if missing.
        if (empty($this->nodeService->getStatePlansYearByPlanYear($this->entity->id()))) {
          $batch['operations'][] = $this->batchAddStatePlansYear();
        }
        $all_group_ids = $this->customEntitiesRetrieval->getAllStates('ids');
        // Retrieve all groups without state plan year nodes created yet.
        foreach ($this->nodeService->getGroupsMissingPlanYear($this->entity->id()) as $group_id) {
          $batch['operations'][] = $this->batchAddStatePlanYear($group_id);
        }
        foreach ($this->getChanges() as $action => $change) {
          switch ($action) {
            // Removing sections already saved to this plan year.
            case 'removed_section_ids':
              foreach ($change as $section_id) {
                // Remove state plan year content tagged with terms from this
                // vocab.
                $batch['operations'][] = $this->batchRemoveStatePlanYearAnswersBySection($section_id);
                // Remove the state plan year section node.
                $batch['operations'][] = $this->batchRemoveStatePlanYearSection($section_id);
                // Remove terms from section vocabulary.
                $batch['operations'][] = $this->batchRemoveSectionHierarchy($section_id);
                // Remove section and plan year copied from plan year.
                $batch['operations'][] = $this->batchRemoveSectionMeta($section_id);
                // Remove section vocabulary.
                $batch['operations'][] = $this->batchRemoveSection($section_id);
              }
              break;

            // Adding brand new sections.
            case 'added_sections':
              foreach ($change as $item) {
                $plan_year_id_to_copy = !empty($item['plan_year_id_to_copy']) ? $item['plan_year_id_to_copy'] : '';
                // Add section and plan year copied to the plan year.
                $batch['operations'][] = $this->batchUpdateSectionMeta($item['section_id'], $plan_year_id_to_copy);
                // Create a section vocabulary.
                $batch['operations'][] = $this->batchAddSection($item['section_id']);
                // Add all State Plan Year Section nodes.
                foreach ($all_group_ids as $group_id) {
                  $batch['operations'][] = $this->batchAddStatePlanYearSection($item['section_id'], $group_id);
                }
                // Copy hierarchy from a different plan year if exists.
                if ($plan_year_id_to_copy) {
                  $batch['operations'][] = $this->batchCopySectionHierarchy($item['section_id'], $plan_year_id_to_copy);
                }
              }
              break;

            // Sections already exists, changing what plan year to copy from.
            case 'copied_plan_year_changes':
              foreach ($change as $section_id => $new_plan_year_id_to_copy) {
                // Update plan year copied on the plan year for this section.
                $batch['operations'][] = $this->batchUpdateSectionMeta($section_id, $new_plan_year_id_to_copy);
                // Remove state plan year content tagged with terms from this
                // vocab. Be sure to leave the state plan year section nodes
                // because it's just the terms and content changing, not the
                // sections themselves.
                $batch['operations'][] = $this->batchRemoveStatePlanYearAnswersBySection($section_id);
                // Remove terms from section vocabulary.
                $batch['operations'][] = $this->batchRemoveSectionHierarchy($section_id);
                // Create missing plan year sections if needed.
                foreach ($this->nodeService->getGroupsMissingPlanYearSection($this->entity->id(), $section_id) as $group_id) {
                  $batch['operations'][] = $this->batchAddStatePlanYearSection($section_id, $group_id);
                }
                // Copy hierarchy from a different plan year if exists.
                if ($new_plan_year_id_to_copy) {
                  $batch['operations'][] = $this->batchCopySectionHierarchy($section_id, $new_plan_year_id_to_copy);
                }
              }
              break;
          }
        }

        if (empty($batch['operations'])) {
          $this->messenger()->addError($this->t('No operations were set.'));
        }
        else {
          batch_set($batch);
          // Clear out the current saved stepped data and reload the first step.
          $this->clearStepData();
          $this->messenger()->addMessage($this->t('The plan year %label has been updated successfully.', [
            '%label' => $this->entity->label(),
          ]));
          // Send them back to the listing page.
          $form_state->setRedirectUrl($this->entity->toUrl('content'));
        }
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Don't validate the cancel or previous submission.
    if (in_array($this->getTriggerElement($form_state), ['cancel', 'previous'])) {
      return;
    }
    $current_step_info = $this->getStepCurrent();
    // Ensure that they chose at least one section from the 'modify_sections'
    // step.
    if ($current_step_info['name'] === 'modify_sections' && $this->getTriggerElement($form_state) !== 'add_section_submit') {
      $at_least_one_section_selected = FALSE;
      foreach ($form_state->getValue('copy_sections', []) as $options) {
        if ($options['copy']) {
          $at_least_one_section_selected = TRUE;
          break;
        }
      }
      if (FALSE === $at_least_one_section_selected) {
        $form_state->setErrorByName('copy_sections', $this->t('At least a single section must be used in a plan year.'));
      }
    }
  }

  /**
   * Retrieve what form element submitted the page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return string
   *   The name of the form element.
   */
  protected function getTriggerElement(FormStateInterface $form_state) {
    return $form_state->getTriggeringElement()['#parents'][0];
  }

}
