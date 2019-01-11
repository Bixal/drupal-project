<?php

namespace Drupal\sp_plan_year\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PlanYearEntityForm.
 */
class PlanYearEntityForm extends EntityForm {

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
      '#description' => $this->t("Label for the Plan Year."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $plan_year->id(),
      '#machine_name' => [
        'exists' => '\Drupal\sp_plan_year\Entity\PlanYearEntity::load',
      ],
      '#disabled' => !$plan_year->isNew(),
    ];

    $form['sections'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'section',
      '#title' => $this->t('Sections'),
      '#description' => $this->t('Choose 1 or more sections to add to this plan year. Separate with commas.'),
      '#default_value' => $plan_year->getSections(),
      '#tags' => TRUE,
    ];

    /* You will need additional form elements for your custom properties. */

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
    $form_state->setRedirectUrl($plan_year->toUrl('collection'));
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
