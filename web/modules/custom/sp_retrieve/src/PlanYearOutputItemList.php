<?php

namespace Drupal\sp_retrieve;

use Drupal\sp_create\PlanYearInfo;

/**
 * Class PlanYearItemListAbstract.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearOutputItemList extends PlanYearOutputAbstract {

  /**
   * Whether to include other sections that are referenced from the current.
   *
   * @var bool
   */
  protected $insertReferences;

  /**
   * Return the entire plan as a large nested list.
   *
   * @return array
   *   A render array.
   */
  public function getItemList() {
    $this->insertReferences = TRUE;
    return $this->itemList(
      $this->getItems($this->planYearDisplay->getRootSectionId(), NULL),
      ['display-plan-year', 'depth-gradient']
    );
  }

  /**
   * Create a render array for an item list.
   *
   * @param array $items
   *   An array of self::item().
   * @param array $wrapper_classes
   *   An array of additional classes to add to the wrapper.
   *
   * @return array
   *   A render array.
   */
  protected function itemList(array $items, array $wrapper_classes = []) {
    $return = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => array_merge([
          'display-plan-year',
        ], $wrapper_classes),
      ],
      '#attributes' => [
        'class' => [
          'wrapper__items',
        ],
      ],
      '#items' => $items,
    ];

    return $return;
  }

  /**
   * Create an item for an item list.
   *
   * @param string $content
   *   The body of the list item.
   * @param array $children
   *   An array of self::item() that are children of this term.
   * @param array $classes
   *   An array of classes to apply to the list item.
   *
   * @return array
   *   An array that belongs as an item in an item list or child of another.
   */
  protected function item($content, array $children = [], array $classes = []) {
    return [
      '#wrapper_attributes' => [
        'class' => $classes,
      ],
      '#markup' => $content,
      'children' => $children,
    ];
  }

  /**
   * Retrieve an entire section or a sub-section of a section as self::item()'s.
   *
   * @param string $section_id
   *   The section ID.
   * @param array|null $section_info
   *   Optional sub-section of the section info.
   *
   * @return array
   *   An array of self::item().
   */
  protected function getItems($section_id, array $section_info = NULL) {
    if (NULL === $section_info) {
      $section_info = $this->planYearDisplay->getPlanYearDisplayInfo($section_id);
    }
    $items = [];
    foreach ($section_info as $section_term) {
      $items = array_merge($items, $this->getSectionTermAsItem($section_term));
    }
    return $items;
  }

  /**
   * Turn individual section terms into a self::item().
   *
   * Note that it is possible that multiple self::item() are returned from
   * this method. This will happen when referencing another section. All top
   * level terms of that section will be placed at the same level.
   *
   * @param array $section_term
   *   A single section term.
   *
   * @return array
   *   An array of self::item().
   */
  protected function getSectionTermAsItem(array $section_term) {
    $items = [];
    $title = $section_term['name'];
    if (!empty($section_term['section_references'])) {
      // If on a reference term and references are not being inserted, do not
      // show any item at all. Perhaps this will.
      // @TODO: This should probably be displayed but be a link to the
      // referenced section(s).
      if (FALSE == $this->insertReferences) {
        return $items;
      }
      foreach ($section_term['section_references'] as $section_id) {
        $items = array_merge($items, $this->getItems($section_id, NULL));
      }
    }
    else {
      $description = '';
      if (!empty($section_term['description']['value'])) {
        $element = [
          '#type' => 'processed_text',
          '#text' => $section_term['description']['value'],
          '#format' => $section_term['description']['format'],
        ];
        $description = '<div class="description">' . render($element) . '</div>';
      }
      if (!empty($section_term['questions'])) {
        $questions = [];
        foreach ($section_term['questions'] as $term_field_uuid => $question) {
          $extra_text = '';
          if (!empty($question['extra_text'])) {
            $element = [
              '#type' => 'processed_text',
              '#text' => $question['extra_text']['value'],
              '#format' => $question['extra_text']['format'],
            ];
            $extra_text = render($element);
          }
          $questions[] = $this->item($extra_text . '<p>' . PlanYearInfo::getSpyaLabels()[$question['node_bundle']] . '( <strong>' . $term_field_uuid . '</strong> )</p>');
        }
        $questions = render($this->itemList($questions, ['questions']));
      }
      if (!empty($section_term['hide_name'])) {
        $title = '';
      }
      elseif (!empty($section_term['hierarchical_outline'])) {
        $title = $section_term['hierarchical_outline'] . '. ' . $title;
      }
      if (!empty($title)) {
        $title = '<span class="item-title">' . $title . '</span>';
      }
      $parent_content = $title . $description . $questions;
      $children_items = [];
      if (!empty($section_term['children'])) {
        $children_items = $this->getItems($section_term['section_id'], $section_term['children']);
      }
      $items[] = $this->item($parent_content, $children_items, ['depth-' . $section_term['depth']]);
    }
    return $items;
  }

}
