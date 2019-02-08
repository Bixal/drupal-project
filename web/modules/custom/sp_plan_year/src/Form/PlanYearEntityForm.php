<?php

namespace Drupal\sp_plan_year\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\sp_create\UpdatePlanYearContentService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PlanYearEntityForm.
 */
class PlanYearEntityForm extends EntityForm {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The plan year content service.
   *
   * @var \Drupal\sp_create\UpdatePlanYearContentService
   */
  protected $planYearContentService;

  /**
   * SystemBrandingOffCanvasForm constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\sp_create\UpdatePlanYearContentService $plan_year_content_service
   *   The plan year content service.
   */
  public function __construct(AccountInterface $current_user, UpdatePlanYearContentService $plan_year_content_service) {
    $this->currentUser = $current_user;
    $this->planYearContentService = $plan_year_content_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('sp_create.update_plan_year_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 4,
      '#default_value' => $plan_year->label(),
      '#description' => $this->t("This is the 4 digit year of the plan."),
      '#required' => TRUE,
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $plan_year->id(),
      '#machine_name' => [
        'exists' => '\Drupal\sp_plan_year\Entity\PlanYearEntity::load',
      ],
      '#disabled' => !$plan_year->isNew(),
    ];

    if ($this->currentUser->hasPermission('administer site configuration')) {
      $this->messenger()->addWarning($this->t('You are logged in with an account that has the Administer Site Configuration permission and have access to the Sections and Copy from plan year section fields. These are not available to normal users and should only be set using the wizard.'));
      $form['sections'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => 'section',
        '#title' => $this->t('Sections'),
        '#description' => $this->t('Choose 1 or more sections to add to this plan year. Separate with commas.'),
        '#default_value' => $plan_year->getSections(),
        '#tags' => TRUE,
      ];

      $form['copy_from_plan_year_section'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Copy from plan year section'),
        '#default_value' => $plan_year->getCopyFromPlanYearSection(),
        '#description' => $this->t('A comma separated section ID to plan year ID list.'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->entity;
    $status = $plan_year->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Plan Year.', [
          '%label' => $plan_year->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Plan Year.', [
          '%label' => $plan_year->label(),
        ]));
    }
    $form_state->setRedirectUrl($plan_year->toUrl('wizard'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $label = $form_state->getValue('label');
    $id = $form_state->getValue('id');
    if (!ctype_digit($label) || $label < 1900 || $label > 2200) {
      $form_state->setErrorByName('label', $this->t('Must be a valid four digit year.'));
    }
    if ($id !== $label && (!ctype_digit($id) || $id < 1900 || $id > 2200)) {
      $form_state->setErrorByName('id', $this->t('Must be a valid four digit year.'));
    }
    elseif ($id !== $label) {
      $form_state->setErrorByName('id', $this->t('Label and machine-readable name must be the same.'));
    }
  }

}
