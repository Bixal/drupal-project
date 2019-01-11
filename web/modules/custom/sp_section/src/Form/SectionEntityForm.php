<?php

namespace Drupal\sp_section\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SectionEntityForm.
 */
class SectionEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $section = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 19,
      '#default_value' => $section->label(),
      '#description' => $this->t("Label for the Section."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $section->id(),
      '#machine_name' => [
        'exists' => '\Drupal\sp_section\Entity\SectionEntity::load',
      ],
      '#disabled' => !$section->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $section = $this->entity;
    $status = $section->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created the %label Section.', [
          '%label' => $section->label(),
        ]));
        break;

      default:
        $this->messenger()->addStatus($this->t('Saved the %label Section.', [
          '%label' => $section->label(),
        ]));
    }
    $form_state->setRedirectUrl($section->toUrl('collection'));
  }

}
