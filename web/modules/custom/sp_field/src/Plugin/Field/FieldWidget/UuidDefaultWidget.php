<?php

namespace Drupal\sp_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_uuid_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_uuid_default_widget",
 *   module = "sp_field",
 *   label = @Translation("UUID"),
 *   field_types = {
 *     "field_uuid"
 *   }
 * )
 */
class UuidDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $uuid_service = \Drupal::service('uuid');

    // Don't show the UUID until after the save.
    if (strlen($value)) {
      $element += [
        '#type' => 'item',
        '#markup' => $value,
      ];
    }
    else {
      $value = $uuid_service->generate();
    }
    $element['value'] = [
      '#type' => 'hidden',
      '#maxlength' => 36,
      '#size' => 36,
      '#value' => $value,
    ];
    return $element;
  }

}
