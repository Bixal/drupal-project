<?php

namespace Drupal\sp_section\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sp_section\Entity\SectionEntity;

/**
 * Class SectionEntityForm.
 */
class SectionEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\sp_section\Entity\SectionEntity $section */
    $section = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $section->label(),
      '#description' => $this->t("Label for the Section."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'value',
      '#value' => $section->isNew() ? SectionEntity::getRandomId() : $section->id(),
      '#disabled' => !$section->isNew(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure that ID is unique.
    if ($this->entity->isNew() && NULL !== SectionEntity::load($form_state->getValue('id'))) {
      $form_state->setErrorByName('id', $this->t('The entity ID must be unique.'));
    }
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
