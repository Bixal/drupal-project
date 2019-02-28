<?php

namespace Drupal\sp_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Uuid\Uuid;
use Drupal\sp_create\PlanYearInfo;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Component\Uuid\Php;
use Drupal\sp_retrieve\CustomEntitiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sp_plan_year\Entity\PlanYearEntity;

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
class SectionEntryDefaultWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * Custom entities service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesService;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, Php $uuid, CustomEntitiesService $custom_entities_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->uuid = $uuid;
    $this->customEntitiesService = $custom_entities_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('uuid'), $container->get('sp_retrieve.custom_entities'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $plan_year_info = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($items->getEntity()->bundle());
    if (FALSE === $plan_year_info) {
      throw new \Exception('This field can only be added to state plan year section term.');
    }
    $element['#element_validate'] = [
      [static::class, 'validateAll'],
    ];
    $node_bundle = isset($items[$delta]->node_bundle) ? $items[$delta]->node_bundle : '';
    $section = isset($items[$delta]->section) ? $items[$delta]->section : '';
    $extra_text = isset($items[$delta]->extra_text) ? $items[$delta]->extra_text : '';
    $term_field_uuid = isset($items[$delta]->term_field_uuid) ? $items[$delta]->term_field_uuid : '';
    $access_option = isset($items[$delta]->access_option) ? $items[$delta]->access_option : '';
    $access_term_field_uuid = isset($items[$delta]->access_term_field_uuid) ? $items[$delta]->access_term_field_uuid : '';
    $access_value = isset($items[$delta]->access_value) ? $items[$delta]->access_value : '';
    /** @var \Drupal\sp_field\Plugin\Field\FieldType\SectionEntryItem $item */
    $item = $items[$delta];
    $props = $item->getProperties();
    $element['node_bundle'] = [
      '#title' => $props['node_bundle']->getDataDefinition()->getLabel(),
      '#type' => 'select',
      '#options' => PlanYearInfo::getSpyaNodeBundles(),
      '#empty_option' => $this->t('- Choose an answer type -'),
      '#default_value' => $node_bundle,
      '#description' => $props['node_bundle']->getDataDefinition()
        ->getDescription(),
    ];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesService->single(PlanYearEntity::ENTITY, $plan_year_info['plan_year_id']);
    $sectionOptions = [];
    foreach ($plan_year->getSections() as $selected_section) {
      $sectionOptions[$selected_section->id()] = $selected_section->label();
    }
    // @TODO: This needs to get sections in the current plan year by context.
    // @TODO: What happens if they remove a section they added?
    $element['section'] = [
      '#title' => $props['section']->getDataDefinition()->getLabel(),
      '#type' => 'select',
      '#options' => $sectionOptions,
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
      $term_field_uuid = $this->uuid->generate();
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
    $node_bundle = strlen($element['node_bundle']['#value']);
    $section = strlen($element['section']['#value']);
    $extra_text = strlen($element['extra_text']['#value']);
    $access_option = strlen($element['access_option']['#value']);
    $access_term_field_uuid = strlen($element['access_term_field_uuid']['#value']);
    $access_value = strlen($element['access_value']['#value']);

    // Can't choose both section & and a content entity..
    if ($node_bundle && $section) {
      $form_state->setError($element['node_bundle'], t("You may not choose both @etb and @section at the same time.", [
        '@etb' => $element['node_bundle']['#title'],
        '@section' => $element['section']['#title'],
      ]));
      $form_state->setError($element['section']);
    }

    // If they entered any other portion of the form but not section or content
    // entity.
    if (!$node_bundle && !$section && ($node_bundle || $extra_text || $access_option || $access_term_field_uuid || $access_value)) {
      $form_state->setError($element['node_bundle'], t("You must select either @etb and @section as well.", [
        '@etb' => $element['node_bundle']['#title'],
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
