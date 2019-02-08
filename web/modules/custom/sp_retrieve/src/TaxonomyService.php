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

}
