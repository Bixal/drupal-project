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
   * Return the taxonomy storage query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The node storage query.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getQuery() {
    return $this->entityTypeManager->getStorage('taxonomy_term')->getQuery();
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
      $term = $this->load($nested_sorted_term['tid']);
      $display_info = $this->getDisplayInfoFromSectionYearTerm($term);
      if (!empty($nested_sorted_term['children'])) {
        $display_info['children'] = $this->getSectionVocabDisplayInfo($nested_sorted_term['children']);
      }
      $return[] = $display_info;
    }
    return $return;
  }

  /**
   * Load a term.
   *
   * @param string $tid
   *   The term ID.
   *
   * @return \Drupal\taxonomy\Entity\Term|null
   *   The term or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function load($tid) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
  }

  /**
   * Get all triggers that change the access of content or sections.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array of triggers.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPlanYearTriggers($plan_year_id) {
    $return = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single('plan_year', $plan_year_id);
    if (NULL === $plan_year) {
      return $return;
    }
    foreach ($plan_year->getSections() as $section) {
      foreach ($this->getVocabularyTids(
        PlanYearInfo::createSectionVocabularyId($plan_year->id(), $section->id()), FALSE
      ) as $tid) {
        $section_term = $this->load($tid);
        $return = array_merge($return, $this->getTriggerInfoFromSectionYearTerm($section_term));
      }
    }
    return $return;
  }

  /**
   * Retrieve the triggers for section term in a plan year.
   *
   * @param \Drupal\taxonomy\Entity\Term $section_year_term
   *   A section year term.
   *
   * @return array
   *   An array of fields that can be used to display a section year term.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTriggerInfoFromSectionYearTerm(Term $section_year_term) {
    $return = [];
    $plan_year_id_and_section_id = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($section_year_term->bundle());
    $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($section_year_term);
    if (FALSE === $plan_year_id_and_section_id) {
      return $return;
    }
    if ($section_year_term->get('field_input_from_state')->isEmpty()) {
      return $return;
    }
    /** @var \Drupal\sp_field\Plugin\Field\FieldType\SectionEntryItem $item */
    foreach ($section_year_term->get('field_input_from_state') as $item) {
      $value = $item->getValue();
      // All values are required for a proper trigger.
      if (empty($value['access_term_field_uuid']) || empty($value['access_option']) || empty($value['access_value'])) {
        continue;
      }
      // The target can be either a complete section or a single piece of
      // content.
      $type = !empty($value['section']) ? 'section_vocabulary' : 'content';
      // The ID is a section vocab ID or the content's term_field_uuid.
      $id = ($type === 'section_vocabulary') ? PlanYearInfo::createSectionVocabularyId($plan_year_id, $value['section']) : $value['term_field_uuid'];
      $return[] = [
        // This is a reference to a section or answer node filled out by the
        // state that has its status conditionally changed based on the
        // 'condition'.
        // Only valid if: The section is enabled and has a vocab in the
        // current year.
        'target' => [
          'type' => $type,
          'id' => $id,
        ],
        // This is a reference to a yes / no node filled out by the state that
        // will determine the status of the 'target'.
        // Only valid if: The referenced node is a yes / no answer node.
        'condition' => [
          'option' => $value['access_option'],
          'term_field_uuid' => $value['access_term_field_uuid'],
          'value' => $value['access_value'],
        ],
        'validation' => [
          // The term ID so we can link the term that needs a fix.
          'tid' => $section_year_term->id(),
          // The 'Field Unique ID' that made the reference (the target).
          'target_term_field_uuid' => $value['term_field_uuid'],
          // The vocabulary referenced does not exist.
          'target_section_missing' => FALSE,
          // The field unique ID that they entered in the condition does not
          // exist.
          'condition_source_missing' => FALSE,
          // The field unique ID that they entered in the condition does not
          // reference a yes / no answer. This will be the term ID of the non
          // yes or no question referencing term.
          'condition_source_not_a_yes_no' => 0,
          // The target and the condition have the same field unique ID.
          'target_and_condition_same' => FALSE,
        ],
      ];
    }
    foreach ($return as &$item) {
      if ('section_vocabulary' === $item['target']['type']) {
        if (NULL === $this->getVocabulary($item['target']['id'])) {
          $item['validation']['target_section_missing'] = TRUE;
        }
      }
      if (!empty($item['condition']['term_field_uuid'])) {
        // The target content and source content are the same.
        if ('content' === $item['target']['type']) {
          if ($item['target']['type'] === $item['condition']['term_field_uuid']) {
            $item['validation']['target_and_condition_same'] = TRUE;
          }
        }
        // Retrieve the term in the current year that has this unique field
        // UUID.
        $condition_section_year_term_id = $this->getSectionVocabTermByPlanYearIdTermFieldUuid($plan_year_id, $item['condition']['term_field_uuid']);
        $item['validation']['condition_source_missing'] = TRUE;
        if (FALSE === $condition_section_year_term_id) {
          continue;
        }
        // Make sure that this referenced term with the given field UUID exists
        // and and that it creates a yes / no question.
        $condition_section_year_term = $this->load($condition_section_year_term_id);
        $content_info = $this->getStatePlanYearContentInfoFromSectionYearTerm($condition_section_year_term);
        if (!empty($content_info['content'])) {
          foreach ($content_info['content'] as $term_field_uuid => $content_item) {
            if ($term_field_uuid === $item['condition']['term_field_uuid']) {
              $item['validation']['condition_source_missing'] = FALSE;
              if (!in_array($content_item['node_bundle'], [PlanYearInfo::SPYA_BOOL_BUNDLE_OPTIONAL, PlanYearInfo::SPYA_BOOL_BUNDLE_REQUIRED])) {
                $item['validation']['condition_source_not_a_yes_no'] = $condition_section_year_term_id;
              }
              break;
            }
          }
        }
      }
    }
    return $return;
  }

  /**
   * Retrieve a section vocab term by plan year ID and term field UUID.
   *
   * @param string $plan_year_id
   *   The plan year ID.
   * @param string $term_field_uuid
   *   A term field UUID.
   *
   * @return int|bool
   *   A term ID or false.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSectionVocabTermByPlanYearIdTermFieldUuid($plan_year_id, $term_field_uuid) {
    return current($this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', 'section_' . $plan_year_id . '%', 'LIKE')
      ->condition('field_input_from_state.term_field_uuid', $term_field_uuid)
      ->range(0, 1)
      ->execute());
  }

}
