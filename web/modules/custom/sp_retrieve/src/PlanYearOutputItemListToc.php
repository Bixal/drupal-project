<?php

namespace Drupal\sp_retrieve;

/**
 * Class PlanYearOutputItemListToc.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearOutputItemListToc extends PlanYearOutputItemList {

  protected $planName;

  /**
   * Set the plan name to be used as the parent item in the list.
   *
   * @param string $plan_name
   *   The parent item in the list.
   */
  public function setPlanName($plan_name) {
    $this->planName = $plan_name;
  }

  /**
   * Return the table of contents as a nested list.
   *
   * @return array
   *   A render array.
   *
   * @throws \Exception
   */
  public function getItemList() {
    if (empty($this->planName)) {
      throw new \Exception('Plan name must be passed before retrieving the TOC.');
    }
    return $this->itemList(
      [
        $this->item($this->planName, $this->getItems($this->planYearDisplay->getRootSectionId(), NULL), ['depth-0']),
      ],
      ['plan-year-toc']
    );
  }

  /**
   * Turn individual section terms into a self::item().
   *
   * For use in TOC display.
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
      foreach ($section_term['section_references'] as $section_id) {
        $items = array_merge($items, $this->getItems($section_id, NULL));
      }
    }
    else {
      // Return an empty item is this section term is not in TOC.
      if (empty($section_term['shown_on_toc'])) {
        return $items;
      }
      if (!empty($section_term['hierarchical_outline'])) {
        $title = $section_term['hierarchical_outline'] . '. ' . $title;
      }
      $parent_content = '<span class="item-title">' . $title . '</span>';
      $children_items = [];
      if (!empty($section_term['children'])) {
        $children_items = $this->getItems($section_term['section_id'], $section_term['children']);
      }
      $items[] = $this->item($parent_content, $children_items, ['depth-' . ($section_term['depth'] + 1)]);
    }
    return $items;
  }

}
