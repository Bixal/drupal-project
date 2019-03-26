<?php

namespace Drupal\sp_retrieve;

/**
 * Class PlanYearItemListAbstract.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearOutputItemListSection extends PlanYearOutputItemList {

  protected $sectionId;

  /**
   * Set the section that should be displayed.
   *
   * @param string $section_id
   *   The section ID that will be returned in the list.
   */
  public function setSectionId($section_id) {
    $this->sectionId = $section_id;
  }

  /**
   * Return a single plan section as a nested list.
   *
   * References to other sections are not displayed.
   *
   * @return array
   *   A render array.
   *
   * @throws \Exception
   */
  public function getItemList() {
    if (empty($this->sectionId)) {
      throw new \Exception('The section ID to be returned before retrieving the list.');
    }
    $this->insertReferences = FALSE;
    return $this->itemList(
      $this->getItems($this->sectionId, NULL),
      ['plan-year-section', 'depth-gradient']
    );
  }

}
