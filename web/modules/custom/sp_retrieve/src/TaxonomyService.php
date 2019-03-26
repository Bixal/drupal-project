<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\sp_create\PlanYearInfo;

/**
 * Class CustomEntitiesService.
 */
class TaxonomyService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Retrieve custom entities.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * Constructs a new TaxonomyService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Retrieve custom entities.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CustomEntitiesService $custom_entities_retrieval) {
    $this->entityTypeManager = $entity_type_manager;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
  }

  /**
   * Retrieve a vocabulary.
   *
   * @param string $vid
   *   The section vocabulary ID.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary|null
   *   The vocabulary or null if it does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getVocabulary($vid) {
    return $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vid);
  }

  /**
   * Retrieve the term IDs of a vocabulary.
   *
   * @param string $vid
   *   A vocabulary ID.
   * @param bool $top_level_only
   *   Flag that, if true, will return only the very top level terms.
   *
   * @return array
   *   An array of term IDs of the given vocabulary ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getVocabularyTids($vid, $top_level_only = FALSE) {
    $query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', $vid)
      ->accessCheck(FALSE);
    if (TRUE === $top_level_only) {
      $query->condition('parent', 0);
    }
    return $query->execute();
  }

  /**
   * Get a vocabulary as a nested structure instead a long list.
   *
   * @param string $vid
   *   A vocabulary ID.
   *
   * @return array
   *   A nested array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getNestedSortedTerms($vid) {
    /** @var \Drupal\taxonomy\TermStorage $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $tree = $term_storage->loadTree($vid);
    $items = [];
    foreach ($tree as $item) {
      $items[$item->tid]['tid'] = $item->tid;
      $items[$item->tid]['name'] = $item->name;
      $items[$item->tid]['weight'] = $item->weight;
      $items[$item->tid]['parent'] = $item->parents[0];

    }
    $items = $this->makeNested($items);
    return $items;
  }

  /**
   * Create a nested array from an array of terms.
   *
   * @param array $source
   *   This needs to be an array keyed term ID with a parent term ID key.
   *
   * @return array
   *   A nested array.
   */
  public function makeNested(array $source) {
    $nested = [];
    foreach ($source as &$s) {
      // No parent_id so we put it in the root of the array.
      if (empty($s['parent'])) {
        $nested[] = &$s;
      }
      else {
        $pid = $s['parent'];
        if (!empty($source[$pid])) {
          // If the parent ID exists in the source array, add it to the
          // 'children' array of the parent after initializing it.
          if (!isset($source[$pid]['children'])) {
            $source[$pid]['children'] = [];
          }
          $source[$pid]['children'][] = &$s;
        }
      }
    }
    return $nested;
  }

  /**
   * Retrieve pertinent info to identify state plan content creation info.
   *
   * Each section term can be used to create many, none, or one piece of state
   * plan year content.
   *
   * @param \Drupal\taxonomy\Entity\Term $section_year_term
   *   A section year term.
   *
   * @return array
   *   An array with common elements in the top and individual items in the
   *   content key.
   */
  public function getStatePlanYearContentInfoFromSectionYearTerm(Term $section_year_term) {
    $plan_year_id_and_section_id = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($section_year_term->bundle());
    $return['plan_year_id'] = !empty($plan_year_id_and_section_id['plan_year_id']) ? $plan_year_id_and_section_id['plan_year_id'] : '';
    $return['section_id'] = !empty($plan_year_id_and_section_id['section_id']) ? $plan_year_id_and_section_id['section_id'] : '';
    $return['content'] = [];
    if (!$section_year_term->get('field_input_from_state')->isEmpty()) {
      /** @var \Drupal\sp_field\Plugin\Field\FieldType\SectionEntryItem $item */
      foreach ($section_year_term->get('field_input_from_state') as $item) {
        $value = $item->getValue();
        $return['content'][$value['term_field_uuid']] = $value;

      }
    }
    return $return;
  }

  /**
   * Retrieve pertinent info to display a section term in a plan year.
   *
   * @param \Drupal\taxonomy\Entity\Term $section_year_term
   *   A section year term.
   *
   * @return array
   *   An array of fields that can be used to display a section year term.
   */
  public function getDisplayInfoFromSectionYearTerm(Term $section_year_term) {
    $plan_year_id_and_section_id = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($section_year_term->bundle());
    if (FALSE === $plan_year_id_and_section_id) {
      return [];
    }
    $return = $this->getStatePlanYearContentInfoFromSectionYearTerm($section_year_term);
    $return['tid'] = $section_year_term->id();
    // Don't show the number nor the formatting. If a section is referenced
    // by a term that has it's name hidden, the top level term will start at
    // the hierarchical heading of this term.
    $return['hide_name'] = $section_year_term->get('field_hide_name')->getString();
    // Don't show the number but it takes up the same place with the same
    // formatting.
    $return['hide_hierarchical_heading'] = !empty($return['hide_name']) || $section_year_term->get('field_hide_hierarchical_heading')->getString();
    $return['shown_on_toc'] = $section_year_term->get('field_shown_on_toc')->getString();
    $return['section_references'] = [];
    $return['weight'] = $section_year_term->getWeight();
    $return['tid'] = $section_year_term->id();
    $return['name'] = $section_year_term->get('field_display_name')->isEmpty() ? $section_year_term->getName() : $section_year_term->get('field_display_name')->getString();
    if (!$section_year_term->get('field_input_from_state')->isEmpty()) {
      /** @var \Drupal\sp_field\Plugin\Field\FieldType\SectionEntryItem $item */
      foreach ($section_year_term->get('field_input_from_state') as $item) {
        $value = $item->getValue();
        if (empty($value['section'])) {
          continue;
        }
        $return['section_references'][$value['term_field_uuid']] = $value['section'];
      }
    }
    return $return;
  }

  /**
   * Take an entire plan year and add display info to each term.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array of section plan year display info keyed by section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPlanYearDisplayInfo($plan_year_id) {
    $return = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single('plan_year', $plan_year_id);
    if (NULL === $plan_year) {
      return $return;
    }
    foreach ($plan_year->getSections() as $section) {
      $nested_sorted_terms = $this->getNestedSortedTerms(
        PlanYearInfo::createSectionVocabularyId($plan_year->id(), $section->id())
      );
      $return[$section->id()] = $this->getSectionVocabDisplayInfo($nested_sorted_terms);
    }
    return $return;
  }

  /**
   * Retrieve the plan year section display info.
   *
   * @param array $nested_sorted_terms
   *   Takes the results of self::getNestedSortedTerms() and adds more
   *   information to it for display purposes.
   *
   * @return array
   *   The display info as a nested array for a section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSectionVocabDisplayInfo(array $nested_sorted_terms) {
    $return = [];
    foreach ($nested_sorted_terms as $nested_sorted_term) {
      /** @var \Drupal\taxonomy\Entity\Term $term */
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($nested_sorted_term['tid']);
      $display_info = $this->getDisplayInfoFromSectionYearTerm($term);
      if (!empty($nested_sorted_term['children'])) {
        $display_info['children'] = $this->getSectionVocabDisplayInfo($nested_sorted_term['children']);
      }
      $return[] = $display_info;
    }
    return $return;
  }

}
