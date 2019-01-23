<?php

namespace Drupal\sp_create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Drupal\sp_retrieve\CustomEntitiesService;

/**
 * Class UpdatePlanYearService.
 */
class UpdatePlanYearService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The entity clone service.
   *
   * @var \Drupal\sp_create\CloneService
   */
  protected $clone;

  /**
   * The custom entities retrieval service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * The vocabulary ID of the section base to copy from.
   *
   * @var string
   */
  public static $sectionBaseVocabularyName = 'section_base';

  /**
   * Constructs a new CreateStatePlanService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\sp_create\CloneService $clone
   *   The entity clone service.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, CloneService $clone, CustomEntitiesService $custom_entities_retrieval) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->clone = $clone;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
  }

  /**
   * Drop all term hierarchy from this section.
   *
   * The section meta data should be updated by this point and the given section
   * may not be found on the plan year entity.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSectionHierarchy($plan_year_id, $section_id) {
    $section_vocabulary_id = $this->createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->getSectionVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $query = $term_storage->getQuery();
      $query->condition('vid', $section_vocabulary_id);
      foreach ($query->execute() as $tid) {
        $term_storage->load($tid)->delete();
      }
    }
  }

  /**
   * Copy all term hierarchy from a different plan year to this section.
   *
   * The section meta data should be updated by this point with the new plan
   * year to copy.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $plan_year_id_to_copy
   *   The plan year to copy hierarchy from to this section.
   */
  public function copySectionHierarchy($plan_year_id, $section_id, $plan_year_id_to_copy) {
    // @TODO: Clone all terms in section_$plan_year_id_to_copy_$section_id to section_$plan_year_id_$section_id.
  }

  /**
   * Remove a section from a plan year meta data.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSectionMeta($plan_year_id, $section_id) {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->entityTypeManager->getStorage('plan_year')->load($plan_year_id);
    $plan_year->removeSection($section_id);
    $plan_year->save();
  }

  /**
   * Update or add a new section and the plan year it's copied from.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   * @param string $plan_year_id_to_copy
   *   The plan year to copy hierarchy from to this section.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateSectionMeta($plan_year_id, $section_id, $plan_year_id_to_copy = '') {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->entityTypeManager->getStorage('plan_year')->load($plan_year_id);
    $plan_year->addSection($section_id, $plan_year_id_to_copy);
    $plan_year->save();
  }

  /**
   * Remove all content tagged with this plan year section.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSectionContent($plan_year_id, $section_id) {
    $section_vocabulary_id = $this->createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->getSectionVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      // Remove all section content that is tagged with this section vocabulary.
      $node_storage = $this->entityTypeManager->getStorage('node');
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $query = $term_storage->getQuery();
      $query->condition('vid', $section_vocabulary_id);
      foreach ($query->execute() as $tid) {
        $node_query = \Drupal::database()->select('taxonomy_index', 'ti');
        $node_query->fields('ti', ['nid']);
        $node_query->condition('ti.tid', $tid);
        foreach ($node_query->execute() as $node_record) {
          $node_storage->load($node_record->nid)->delete();
        }
      }
      // @TODO: Remove all state_plan_year_section nodes for each group in this plan year that have a field_section = $section_id.
    }

  }

  /**
   * Remove this plan year section vocabulary.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeSection($plan_year_id, $section_id) {
    $section_vocabulary_id = $this->createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->getSectionVocabulary($section_vocabulary_id);
    if (NULL !== $section_vocabulary) {
      $section_vocabulary->delete();
    }
  }

  /**
   * Add this plan year section vocabulary.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addSection($plan_year_id, $section_id) {
    $section_vocabulary_id = $this->createSectionVocabularyId($plan_year_id, $section_id);
    $section_vocabulary = $this->getSectionVocabulary($section_vocabulary_id);
    if (NULL === $section_vocabulary) {
      $new_label = $this->customEntitiesRetrieval->getLabel('plan_year', $plan_year_id) . ' - ' . $this->customEntitiesRetrieval->getLabel('section', $section_id);
      $this->clone->cloneBundle(
        'taxonomy_term',
        self::$sectionBaseVocabularyName,
        $section_vocabulary_id,
        $new_label
      );
    }
  }

  /**
   * Create the string to be used for a section vocabulary ID.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   * @param string $section_id
   *   A section ID.
   *
   * @return string
   *   A 32 character string section vocabulary ID.
   */
  protected function createSectionVocabularyId($plan_year_id, $section_id) {
    return "section_{$plan_year_id}_{$section_id}";
  }

  /**
   * Retrieve a section vocabulary.
   *
   * @param string $section_vocabulary_id
   *   The section vocabulary ID.
   *
   * @return \Drupal\taxonomy\Entity\Vocabulary|null
   *   The vocabulary or null if it does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSectionVocabulary($section_vocabulary_id) {
    return $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($section_vocabulary_id);
  }

}
