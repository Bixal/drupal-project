<?php

namespace Drupal\sp_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_uuid' field type.
 *
 * @FieldType(
 *   id = "field_uuid",
 *   label = @Translation("UUID"),
 *   module = "sp_field",
 *   description = @Translation("Automatically create a UUID."),
 *   default_widget = "field_uuid_default_widget",
 *   default_formatter = "string"
 * )
 */
class UuidItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      // Columns contains the values that the field will store.
      'columns' => array(
        // List the values that the field will save. This
        // field will only save a single value, 'value'.
        'value' => array(
          'type' => 'varchar',
          'length' => 36,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['value'] = DataDefinition::create('string')
      ->setLabel('UUID')
      ->setDescription('A Universally unique identifier that will be automaticaly create.');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
