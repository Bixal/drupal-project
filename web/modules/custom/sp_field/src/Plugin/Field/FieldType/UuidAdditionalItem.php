<?php

namespace Drupal\sp_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'field_uuid' field type.
 *
 * @FieldType(
 *   id = "field_uuid_additional",
 *   label = @Translation("UUID (Additional)"),
 *   module = "sp_field",
 *   description = @Translation("Automatically create a UUID."),
 *   default_widget = "field_uuid_additional_default_widget",
 *   default_formatter = "string"
 * )
 */
class UuidAdditionalItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        // List the values that the field will save. This
        // field will only save a single value, 'value'.
        'value' => [
          'type' => 'varchar',
          'length' => 36,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string')
      ->setLabel('UUID')
      ->setDescription('A Universally unique identifier that will be automatically create.');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Default to one field item with a generated UUID.
    $uuid = \Drupal::service('uuid');
    $this->setValue(['value' => $uuid->generate()], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = \Drupal::service('uuid')->generate();
    return $values;
  }

}
