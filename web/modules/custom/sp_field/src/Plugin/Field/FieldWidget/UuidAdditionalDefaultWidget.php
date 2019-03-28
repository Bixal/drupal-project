<?php

namespace Drupal\sp_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_uuid_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_uuid_additional_default_widget",
 *   module = "sp_field",
 *   label = @Translation("UUID (Additional)"),
 *   field_types = {
 *     "field_uuid_additional"
 *   }
 * )
 */
class UuidAdditionalDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, UuidInterface $uuid) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('uuid'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $value = isset($items[$delta]->value) ? $items[$delta]->value : '';
    $entity = $items->getEntity();
    // Don't show the UUID until after the save.
    if (TRUE === $entity->isNew()) {
      $value = $this->uuid->generate();
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
