<?php

namespace Drupal\sp_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Uuid\Uuid;
use Drupal\sp_field\Sections;

/**
 * Plugin implementation of the 'field_section_entry_default_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_section_entry_default_widget",
 *   module = "sp_field",
 *   label = @Translation("Section Entry"),
 *   field_types = {
 *     "field_section_entry"
 *   }
 * )
 */
class SectionEntryDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $sectionsObject = new Sections();
    $element['#element_validate'] = [
      [static::class, 'validateAll'],
    ];
    $entity_type_bundle = isset($items[$delta]->entity_type_bundle) ? $items[$delta]->entity_type_bundle : '';
    $section = isset($items[$delta]->section) ? $items[$delta]->section : '';
    $extra_text = isset($items[$delta]->extra_text) ? $items[$delta]->extra_text : '';
    $term_field_uuid = isset($items[$delta]->term_field_uuid) ? $items[$delta]->term_field_uuid : '';
    $access_option = isset($items[$delta]->access_option) ? $items[$delta]->access_option : '';
    $access_term_field_uuid = isset($items[$delta]->access_term_field_uuid) ? $items[$delta]->access_term_field_uuid : '';
    $access_value = isset($items[$delta]->access_value) ? $items[$delta]->access_value : '';
    /* @var \Drupal\Component\Uuid\Php $uuid_service */
    $uuid_service = \Drupal::service('uuid');
    /** @var \Drupal\sp_field\Plugin\Field\FieldType\SectionEntryItem $item */
    $item = $items[$delta];
    $props = $item->getProperties();
    $element['entity_type_bundle'] = [
      '#title' => $props['entity_type_bundle']->getDataDefinition()->getLabel(),
      '#type' => 'select',
      '#options' => [
        // These options should come from a class and be hardcoded. Each value
        // is a entity type - entity bundle pair that provides input for states.
        // Per the new discussion, i would recommend that these section specific
        // nodes so that permissions can be given per section. E.g.,
        // node-yes_no_tanf. So, entered here without prefix, then determine
        // who this vocab belongs to and add suffix.
        'node-yes_no' => $this->t('Node - Yes/No'),
        'node-text' => $this->t('Node - Text'),
      ],
      '#empty_option' => $this->t('- Choose an entity -'),
      '#default_value' => $entity_type_bundle,
      '#description' => $props['entity_type_bundle']->getDataDefinition()
        ->getDescription(),
    ];

    $element['section'] = [
      '#title' => $props['section']->getDataDefinition()->getLabel(),
      '#type' => 'select',
      '#options' => $sectionsObject->getSections(),
      '#empty_option' => $this->t('- Choose a section -'),
      '#default_value' => $section,
      '#description' => $props['section']->getDataDefinition()
        ->getDescription(),
    ];

    $element['extra_text'] = [
      '#title' => $props['extra_text']->getDataDefinition()->getLabel(),
      '#type' => 'textarea',
      '#default_value' => $extra_text,
      '#description' => $props['extra_text']->getDataDefinition()
        ->getDescription(),
      '#rows' => 3,
      '#format' => 'plain_text',
    ];

    // Don't show the UUID until after the save.
    if (strlen($term_field_uuid)) {
      $element['term_field_uuid_display'] = [
        '#title' => $props['term_field_uuid']->getDataDefinition()->getLabel(),
        '#type' => 'item',
        '#default_value' => '',
        '#description' => $props['term_field_uuid']->getDataDefinition()
          ->getDescription(),
        '#markup' => $term_field_uuid,
      ];
    }
    else {
      $term_field_uuid = $uuid_service->generate();
    }
    $element['term_field_uuid'] = [
      '#type' => 'hidden',
      '#maxlength' => 36,
      '#size' => 36,
      '#value' => $term_field_uuid,
    ];
    // This section will be the same for term and term field fields. Bring it
    // in from a common file.
    $element['access'] = [
      '#type' => 'item',
      '#title' => $this->t('Access'),
      '#description' => $this->t('Change the access to this entry depending on previous values entered by the state.'),
      '#open' => FALSE,
      '#element_validate' => [
        [static::class, 'validateAccess'],
      ],
      '#prefix' => '<hr />',
    ];
    $element['access_option'] = [
      '#title' => $props['access_option']->getDataDefinition()->getLabel(),
      '#type' => 'select',
      '#options' => [
        'hide' => $this->t('Hide: The content will not be shown for entry'),
        'disallow' => $this->t('Disallow: The content will be shown but disabled and the defaults entered'),
      ],
      '#empty_option' => $this->t('- Choose an option -'),
      '#default_value' => $access_option,
      '#description' => $props['access_option']->getDataDefinition()
        ->getDescription(),
    ];
    $element['access_term_field_uuid'] = [
      '#title' => $props['access_term_field_uuid']->getDataDefinition()
        ->getLabel(),
      '#type' => 'textfield',
      '#default_value' => $access_term_field_uuid,
      '#element_validate' => [
        [static::class, 'validateUuid'],
      ],
      '#description' => $props['access_term_field_uuid']->getDataDefinition()
        ->getDescription(),
      '#maxlength' => 36,
      '#size' => 36,
    ];
    $element['access_value'] = [
      '#title' => $props['access_value']->getDataDefinition()->getLabel(),
      '#type' => 'textarea',
      '#default_value' => $access_value,
      '#description' => $props['access_value']->getDataDefinition()
        ->getDescription(),
      '#rows' => 3,
      '#format' => 'plain_text',
    ];
    return $element;
  }

  /**
   * Validate a UUID.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateUuid(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen(trim($value)) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    if (!Uuid::isValid($value)) {
      $form_state->setError($element, t("Invalid UUID given."));
    }
  }

  /**
   * Validate the the form as a whole.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateAll(array $element, FormStateInterface $form_state) {
    $entity_type_bundle = strlen($element['entity_type_bundle']['#value']);
    $section = strlen($element['section']['#value']);
    $extra_text = strlen($element['extra_text']['#value']);
    $access_option = strlen($element['access_option']['#value']);
    $access_term_field_uuid = strlen($element['access_term_field_uuid']['#value']);
    $access_value = strlen($element['access_value']['#value']);

    // Can't choose both section & and a content entity..
    if ($entity_type_bundle && $section) {
      $form_state->setError($element['entity_type_bundle'], t("You may not choose both @etb and @section at the same time.", [
        '@etb' => $element['entity_type_bundle']['#title'],
        '@section' => $element['section']['#title'],
      ]));
      $form_state->setError($element['section']);
    }

    // If they entered any other portion of the form but not section or content
    // entity.
    if (!$entity_type_bundle && !$section && ($entity_type_bundle || $extra_text || $access_option || $access_term_field_uuid || $access_value)) {
      $form_state->setError($element['entity_type_bundle'], t("You must select either @etb and @section as well.", [
        '@etb' => $element['entity_type_bundle']['#title'],
        '@section' => $element['section']['#title'],
      ]));
      $form_state->setError($element['section']);
    }

    // If they chose an access option, require the other access options.
    if ($access_option) {
      if (!$access_term_field_uuid) {
        $form_state->setError($element['access_term_field_uuid'], t("@title is required.", ['@title' => $element['access_term_field_uuid']['#title']]));
      }
      if (!$access_value) {
        $form_state->setError($element['access_value'], t("@title is required.", ['@title' => $element['access_value']['#title']]));
      }
    }
    else {
      if ($access_term_field_uuid) {
        $form_state->setError($element['access_option'], t("You must choose an @option_title first if @title is set.", [
          '@option_title' => $element['access_option']['#title'],
          '@title' => $element['access_term_field_uuid']['#title'],
        ]));
      }
      if ($access_value) {
        $form_state->setError($element['access_option'], t("You must choose an @option_title first if @title is set.", [
          '@option_title' => $element['access_option']['#title'],
          '@title' => $element['access_value']['#title'],
        ]));
      }
    }
  }

}
