<?php

namespace Drupal\sp_retrieve;

/**
 * Class PlanYearDisplay.
 *
 * @package Drupal\sp_retrieve
 */
class PlanYearDisplay {

  /**
   * A type of hierarchical outline.
   *
   * A, B, C, etc. When Z is reached, AA is the next, etc.
   *
   * @var string
   */
  const ENGLISH_ALPHABET_UC = 'eauc';

  /**
   * A type of hierarchical outline.
   *
   * 1, 2, 3, etc.
   *
   * @var string
   */
  const ARABIC_NUMERALS = 'an';

  /**
   * A type of hierarchical outline.
   *
   * I, II, III, etc.
   *
   * @var string
   */
  const ROMAN_NUMERALS_UC = 'rnuc';

  /**
   * This is the order that the hierarchy will be applied to children levels.
   */
  const HIERARCHICAL_OUTLINE = [
    self::ROMAN_NUMERALS_UC,
    self::ENGLISH_ALPHABET_UC,
    self::ARABIC_NUMERALS,
    self::ENGLISH_ALPHABET_UC,
    self::ROMAN_NUMERALS_UC,
  ];

  /**
   * This is the data that is passed in and modified for display.
   *
   * @var array
   */
  protected $planYearDisplayInfo;

  /**
   * All section references in this plan year.
   *
   * @var array
   *   Form: [source_section_id => [tid => [field_uuid => target_section_id]].
   */
  protected $sectionReferences = [];

  /**
   * All section IDs in this plan.
   *
   * @var array
   *   Section IDs.
   */
  protected $sectionIds = [];

  /**
   * The section ID that references all other sections.
   *
   * @var string|null
   */
  protected $rootSectionId = NULL;

  /**
   * Errors that have occurred.
   *
   * @var array
   *   An array of error strings.
   */
  protected $errors = [];

  /**
   * PlanYearDisplay constructor.
   *
   * Massages the plan year display info for display. After creating a new
   * instance of this class, don't forget to call getErrors() to validate
   * that the plan year info can be displayed.
   *
   * @param array $plan_year_display_info
   *   This is the results of
   *   \Drupal\sp_retrieve\NodeService::getPlanYearDisplayInfo().
   */
  public function __construct(array $plan_year_display_info) {
    $this->planYearDisplayInfo = $plan_year_display_info;
    foreach ($this->planYearDisplayInfo as $section_id => $section_info) {
      $this->sectionIds[] = $section_id;
      $this->setSectionReferences($section_info);
    }
    if (FALSE === $this->validateSectionReferences()) {
      return;
    }

    $this->setRootSectionId();
    $this->applyHierarchicalOutline();
  }

  /**
   * Errors that might have occurred when the object is instantiated.
   *
   * @return array
   *   An array of string errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Debug the titles of the entire plan by printing out using dpm().
   */
  public function getHeadings() {
    foreach ($this->planYearDisplayInfo as $section_info) {
      $this->getSectionHeadings($section_info);
    }
  }

  /**
   * Return the entire plan as a large nested list.
   *
   * @return array
   *   A render array.
   */
  public function getPlanYearItemList() {
    return $this->itemList(
      $this->getPlanYearSectionItems($this->rootSectionId, NULL, TRUE)
    );
  }

  /**
   * Return a single plan section as a nested list.
   *
   * References to other sections are not displayed.
   *
   * @param string $section_id
   *   A section ID.
   *
   * @return array
   *   A render array.
   */
  public function getPlanYearSectionItemList($section_id) {
    return $this->itemList(
      $this->getPlanYearSectionItems($section_id, NULL, FALSE)
    );
  }

  /**
   * Create a render array for an item list.
   *
   * @param array $items
   *   An array of self::item().
   *
   * @return array
   *   A render array.
   */
  protected function itemList(array $items) {
    $return = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => [
          'display-plan',
        ],
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
   * @param bool $insert_references
   *   Whether to include other sections that are referenced from the current.
   *
   * @return array
   *   An array of self::item().
   */
  protected function getPlanYearSectionItems($section_id, array $section_info = NULL, $insert_references = FALSE) {
    if (NULL === $section_info) {
      $section_info = $this->planYearDisplayInfo[$section_id];
    }
    $items = [];
    foreach ($section_info as $section_term) {
      $items = array_merge($items, $this->getSectionTermItem($section_term, $insert_references));
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
   * @param bool $insert_references
   *   Whether to include other sections that are referenced from the current.
   *
   * @return array
   *   An array of self::item().
   */
  protected function getSectionTermItem(array $section_term, $insert_references) {
    $items = [];
    $title = $section_term['name'];
    if (!empty($section_term['section_references'])) {
      // If on a reference term and references are not being inserted, do not
      // show any item at all. Perhaps this will.
      // @TODO: This should probably be displayed but be a link to the
      // referenced section(s).
      if (FALSE == $insert_references) {
        return $items;
      }
      foreach ($section_term['section_references'] as $section_id) {
        $items = array_merge($items, $this->getPlanYearSectionItems($section_id, NULL, TRUE));
      }
    }
    else {
      // @TODO: This shouldn't be skipped, right now it's just the titles but
      // what this should do is keep the link / display the value without
      // showing the title.
      if (!empty($section_term['hide_name'])) {
        return $items;
      }
      if (!empty($section_term['hierarchical_outline'])) {
        $title = $section_term['hierarchical_outline'] . '. ' . $title;
      }
      $parent_content = '<span class="item-title">' . $title . '</span>';
      $children_items = [];
      if (!empty($section_term['children'])) {
        $children_items = $this->getPlanYearSectionItems($section_term['section_id'], $section_term['children'], $insert_references);
      }
      $items[] = $this->item($parent_content, $children_items, ['depth-' . $section_term['depth']]);
    }
    return $items;
  }

  /**
   * Print out the name's of all terms in a section.
   *
   * @param array $section_info
   *   An entire section or sub-section.
   */
  protected function getSectionHeadings(array $section_info) {
    foreach ($section_info as $section_term) {
      if (!empty($section_term['section_references'])) {
        continue;
      }
      if (!empty($section_term['name'])) {
        \Drupal::messenger()->addStatus(str_pad($section_term['hierarchical_outline'], 3, '.') . ' ' . $section_term['name']);
      }
      if (!empty($section_term['children'])) {
        $this->getSectionHeadings($section_term['children']);
      }
    }
  }

  /**
   * Add the self::sectionReferences for a sub-section or entire section.
   *
   * @param array $section_info
   *   An entire section or sub-section.
   */
  protected function setSectionReferences(array $section_info) {
    foreach ($section_info as $section_term) {
      if (!empty($section_term['section_references'])) {
        $this->sectionReferences[$section_term['section_id']][$section_term['tid']] = $section_term['section_references'];
      }
      if (!empty($section_term['children'])) {
        $this->setSectionReferences($section_term['children']);
      }
    }
  }

  /**
   * Alter the self::planYearDisplayInfo by adding hierachial outline numbering.
   *
   * This will also apply the numbering to referenced sections as well.
   *
   * @param string|null $section_id
   *   This should be null to find the root section then find all referenced
   *   sections until all are applied correctly.
   * @param array|null $section_info
   *   This is the current group of siblings being worked on and edited.
   * @param string|null $current_hierarchical_outline_index
   *   This is the index of self::HIERARCHICAL_OUTLINE, it changes when going
   *   down to children.
   * @param int $depth
   *   This is how many level of nestings the current group of siblings has.
   *
   * @return array
   *   The updated section info.
   */
  protected function applyHierarchicalOutline($section_id = NULL, array $section_info = NULL, $current_hierarchical_outline_index = NULL, $depth = 0) {
    // Default to the root section ID.
    if (NULL === $section_id) {
      $section_id = $this->rootSectionId;
    }
    // If starting a new section, get the entire display hierarchy for it.
    if (NULL === $section_info) {
      $section_info = &$this->planYearDisplayInfo[$section_id];
    }
    // Start this at 0 to start the first level. Otherwise it can be passed
    // explicitly to a referenced section to apply the hierarchy outline
    // correctly.
    if (NULL === $current_hierarchical_outline_index) {
      $current_hierarchical_outline_index = 0;
    }
    // This is the "vertical" / "sibling" heading number.
    $current_heading_number = 1;
    foreach ($section_info as &$section_term) {
      $section_term['depth'] = $depth;
      // $section_term['name'] = str_repeat('__', $section_term['depth'] * 2) .
      // $section_term['name'];.
      $section_term['hierarchical_outline'] = '';
      // If this term is a reference to one or more sections, set the outline
      // on those sections as well. Don't even check for children if this
      // is a section reference, that makes no sense. Maybe even throw an
      // error for that.
      if (!empty($section_term['section_references'])) {
        foreach ($section_term['section_references'] as $section_referenced) {
          $this->applyHierarchicalOutline($section_referenced, NULL, $current_hierarchical_outline_index, $depth);
        }
      }
      else {
        // Hold off on giving the sibling a higher sibling number if the
        // hierarchy is suppressed on this term.
        if (empty($section_term['hide_hierarchical_heading'])) {
          $section_term['hierarchical_outline'] = $this->getHierarchialValue($current_heading_number, $current_hierarchical_outline_index);
          $current_heading_number++;
        }
        if (!empty($section_term['children'])) {
          // If the parent's hierarchy is suppressed, pass what it would have
          // gotten as the childs.
          $children_hierarchical_outline_index = $current_hierarchical_outline_index;
          // If the hierarchy is not suppressed, go ahead an give the next group
          // of siblings a new heading level to start at.
          if (empty($section_term['hide_hierarchical_heading'])) {
            $children_hierarchical_outline_index = $this->getNextHierarchicalOutline($current_hierarchical_outline_index);
          }
          // The depth is ALWAYS increased if going down a level, although if
          // the parent hierarchy level might not be if the parent is
          // suppressed.
          $section_term['children'] = $this->applyHierarchicalOutline($section_id, $section_term['children'], $children_hierarchical_outline_index, $depth + 1);
        }
      }
    }
    return $section_info;
  }

  /**
   * Retrieves the hierarchical outline number type given the current.
   *
   * @param string $index
   *   A key of the HIERARCHICAL_OUTLINE constant from this class.
   *
   * @return int
   *   The next key in HIERARCHICAL_OUTLINE or 0 if wrapping around to start.
   */
  protected function getNextHierarchicalOutline($index) {
    $index++;
    if (isset(self::HIERARCHICAL_OUTLINE[$index])) {
      return $index;
    }
    // Wrap around to the start if it goes past the max index.
    return 0;
  }

  /**
   * Set the section ID that is not referenced from another section.
   */
  protected function setRootSectionId() {
    $referenced_sections = [];
    foreach ($this->sectionReferences as $section_references) {
      // $tid is the key of the $section_references array if needed. It is the
      // term ID that the reference was made in.
      foreach ($section_references as $target_section_ids) {
        foreach ($target_section_ids as $target_section_id) {
          $referenced_sections[] = $target_section_id;
        }
      }
    }
    $this->rootSectionId = current(array_diff($this->sectionIds, $referenced_sections));
  }

  /**
   * Validates that the section info can properly be used to display a plan.
   *
   * @return bool
   *   Returns true if everything validated otherwise false. Use
   *   self::getErrors() to get the errors created.
   */
  protected function validateSectionReferences() {
    $referenced_sections = [];
    foreach ($this->sectionReferences as $source_section => $section_references) {
      foreach ($section_references as $tid => $target_section_ids) {
        foreach ($target_section_ids as $target_section_id) {
          $referenced_sections[$target_section_id][] = ['tid' => $tid, 'source_section' => $source_section];
        }
      }
    }
    $errors = [];
    foreach ($referenced_sections as $target_section_id => $items) {
      if (count($items) > 1) {
        $tids = [];
        foreach ($items as $item) {
          $tids[] = $item['tid'];
        }
        $errors[] = 'Section ' . $target_section_id . ' cannot be referenced more than once. See terms ' . implode(', ', $tids);
      }
    }
    if (count($referenced_sections) < (count($this->sectionIds) - 1)) {
      $errors[] = 'Not every section is referenced, except one root section, are referenced.';
    }
    elseif (count($referenced_sections) === (count($this->sectionIds))) {
      $errors[] = 'You must leave one section that is not referenced as the root section.';
    }
    if (!empty($errors)) {
      $this->errors = array_merge($this->errors, $errors);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Return the display of the hierarchical outline number.
   *
   * @param int $number
   *   A digit that corresponds an increasing sibling item.
   * @param int $current_hierarchical_outline_index
   *   The type of hierarchical outline type as an index in the
   *   HIERARCHICAL_OUTLINE array constant.
   *
   * @return string
   *   The display value.
   */
  protected function getHierarchialValue($number, $current_hierarchical_outline_index) {
    switch (self::HIERARCHICAL_OUTLINE[$current_hierarchical_outline_index]) {
      case self::ROMAN_NUMERALS_UC:
        return $this->numberToRomanNumeralUc($number);

      case self::ENGLISH_ALPHABET_UC:
        return $this->numberToEnglishAlphabetUc($number);

      default:
        return $number;
    }
  }

  /**
   * Converts a number to a english alphabet uppercase value.
   *
   * @param int $number
   *   A digit that corresponds an increasing sibling item.
   *
   * @return string
   *   An uppercase english letter.
   */
  protected function numberToEnglishAlphabetUc($number) {
    $range = range('A', 'Z');
    $number--;
    if (!empty($range[$number])) {
      return $range[$number];
    }
    $highest = count($range);
    $repeat = ceil($number / $highest);
    $index = $number % $highest;
    if ($index === 0) {
      $repeat++;
    }
    return str_repeat($range[$index], $repeat);
  }

  /**
   * Converts a number to a roman numeral uppercase value.
   *
   * @param int $number
   *   A digit that corresponds an increasing sibling item.
   *
   * @return string
   *   An uppercase roman numeral.
   */
  protected function numberToRomanNumeralUc($number) {
    $map = [
      'M' => 1000,
      'CM' => 900,
      'D' => 500,
      'CD' => 400,
      'C' => 100,
      'XC' => 90,
      'L' => 50,
      'XL' => 40,
      'X' => 10,
      'IX' => 9,
      'V' => 5,
      'IV' => 4,
      'I' => 1,
    ];
    $returnValue = '';
    while ($number > 0) {
      foreach ($map as $roman => $int) {
        if ($number >= $int) {
          $number -= $int;
          $returnValue .= $roman;
          break;
        }
      }
    }
    return $returnValue;
  }

}
