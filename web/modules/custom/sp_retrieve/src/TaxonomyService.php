<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * Constructs a new TaxonomyService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
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

}
