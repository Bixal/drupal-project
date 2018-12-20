<?php

namespace Drupal\sp_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_section_entry' field type.
 *
 * @FieldType(
 *   id = "field_section_entry",
 *   label = @Translation("Section Entry"),
 *   module = "sp_field",
 *   description = @Translation("References the type of content that will be created and reference this term."),
 *   default_widget = "field_section_entry_default_widget",
 *   default_formatter = "string"
 * )
 */
class SectionEntryItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      // Columns contains the values that the field will store.
      'columns' => [
        // List the values that the field will save.
        'entity_type_bundle' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'section' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'extra_text' => [
          'type' => 'text',
          'size' => 'normal',
          'not null' => FALSE,
        ],
        'term_field_uuid' => [
          'type' => 'varchar',
          'length' => 36,
          'not null' => FALSE,
        ],
        'access_option' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'access_term_field_uuid' => [
          'type' => 'varchar',
          'length' => 36,
          'not null' => FALSE,
        ],
        'access_value' => [
          'type' => 'text',
          'size' => 'normal',
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
    $properties['entity_type_bundle'] = DataDefinition::create('string')
      ->setLabel('Content')
      ->setDescription('The type of content that will be created for the state to fill out');
    $properties['section'] = DataDefinition::create('string')
      ->setLabel('Section')
      ->setDescription('The section that will be displayed to the user');
    $properties['extra_text'] = DataDefinition::create('string')
      ->setLabel('Extra Text')
      ->setDescription('Helpful text to place next to the input content or section (like a question)');
    $properties['term_field_uuid'] = DataDefinition::create('string')
      ->setLabel('Field Unique ID')
      ->setDescription('A unique ID for this field. Use this field in other term\'s access sections to change access based on the value of the referenced content.');
    $properties['access_option'] = DataDefinition::create('string')
      ->setLabel('Option')
      ->setDescription('Will cause this field to displayed differently to the state');
    $properties['access_term_field_uuid'] = DataDefinition::create('string')
      ->setLabel('Access Term Field ID')
      ->setDescription('The Field Unique ID from another term that holds the value below');
    $properties['access_value'] = DataDefinition::create('string')
      ->setLabel('Access Value')
      ->setDescription('The value entered by the state in the referenced Field Unique ID content entity');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $entity_type_bundle = $this->get('entity_type_bundle')->getValue();
    $section = $this->get('section')->getValue();
    return ($entity_type_bundle === NULL || $entity_type_bundle === '') && ($section === NULL || $section === '');
  }

}
