<?php

namespace Drupal\sp_retrieve;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_plan_year\Entity\PlanYearEntity;

/**
 * Class MixedEntityService.
 *
 * This service was created to stop circular references in services. If
 * functionality requires that both taxonomy and node service are required,
 * it should be added here.
 *
 * Eventually, node & taxonomy service should have only basic functionality in
 * them and then we can add more 'Mixed' services that are named based on what
 * they need to do.
 */
class MixedEntityService {

  use StringTranslationTrait;

  /**
   * The node service.
   *
   * @var \Drupal\sp_retrieve\NodeService
   */
  protected $nodeService;

  /**
   * Retrieve custom entities.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * Retrieve taxonomy service.
   *
   * @var \Drupal\sp_retrieve\TaxonomyService
   */
  protected $taxonomyService;

  /**
   * SP Retrieve cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new TaxonomyService object.
   *
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node service.
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Retrieve custom entities.
   * @param \Drupal\sp_retrieve\TaxonomyService $taxonomy_service
   *   The taxonomy service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   SP Retrieve cache bin.
   */
  public function __construct(NodeService $node_service, CustomEntitiesService $custom_entities_retrieval, TaxonomyService $taxonomy_service, CacheBackendInterface $cache) {
    $this->nodeService = $node_service;
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->taxonomyService = $taxonomy_service;
    $this->cache = $cache;
  }

  /**
   * Find all pieces of state plan answers that are referenced by section terms.
   *
   * If a term is updated and the type of state plan answer is changed or
   * removed, this will be used to find that "orphan" content that needs to
   * go away.
   *
   * @return array
   *   An array state plan year answer node IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getOrphansStatePlanYearAnswers() {
    $cid = __METHOD__;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $orphans = [];
    foreach (PlanYearInfo::getSpyaNodeBundles() as $node_bundle) {
      foreach ($this->nodeService->getQuery()
        ->condition('type', $node_bundle)
        ->accessCheck(FALSE)
        ->execute() as $state_plan_year_answer_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_year_answer */
        $state_plan_year_answer = $this->nodeService->load($state_plan_year_answer_nid);
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $section_year_term_field */
        $section_year_term_field = $state_plan_year_answer->get('field_section_year_term')->get(0);
        // If there is no term referenced, this piece of content is in a bad
        // state.
        if (empty($section_year_term_field)) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $section_year_term_tid = $section_year_term_field->getValue()['target_id'];
        /** @var \Drupal\taxonomy\Entity\Term $section_year_term */
        $section_year_term = $this->taxonomyService->load($section_year_term_tid);
        // Make sure the referenced term still exists.
        if (NULL === $section_year_term) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $state_plan_year_content_info = $this->taxonomyService->getStatePlanYearContentInfoFromSectionYearTerm($section_year_term);
        // Ensure that the node type matches between the node and the term.
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $plan_year_field */
        $plan_year_field = $state_plan_year_answer->get('field_plan_year');
        // If there is no plan year referenced, this piece of content is in a
        // bad state.
        if ($plan_year_field->isEmpty()) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $plan_year_id = $plan_year_field->getString();
        // The plan year from the node and term don't line up.
        if ($state_plan_year_content_info['plan_year_id'] !== $plan_year_id) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $section_field */
        $section_field = $state_plan_year_answer->get('field_section');
        // If there is no section referenced, this piece of content is in a
        // bad state.
        if ($section_field->isEmpty()) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $section_id = $section_field->getString();
        // The section from the node and term don't line up.
        if ($state_plan_year_content_info['section_id'] !== $section_id) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        /** @var \Drupal\Core\Field\FieldItemList $field_unique_id_reference_field */
        $field_unique_id_reference_field = $state_plan_year_answer->get('field_field_unique_id_reference');
        // If there is no field unique ID referenced, this piece of content is
        // in a bad state.
        if ($field_unique_id_reference_field->isEmpty()) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        $field_unique_id_reference = $field_unique_id_reference_field->getString();
        // The section from the node and term don't line up.
        if (empty($state_plan_year_content_info['content'][$field_unique_id_reference])) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        // Odds are that this referenced term changed from a 'node' to be shown
        // to states to a 'section' placeholder.
        if (!empty($state_plan_year_content_info['content'][$field_unique_id_reference]['section'])) {
          $orphans[] = $state_plan_year_answer_nid;
          continue;
        }
        // The node type to be created if the term does not match
        // the node type created.
        if ($node_bundle !== $state_plan_year_content_info['content'][$field_unique_id_reference]['node_bundle']) {
          $orphans[] = $state_plan_year_answer_nid;
        }
      }
    };
    $this->cache->set($cid, $orphans, Cache::PERMANENT, $this->nodeService->getMissingContentCacheTags());
    return $orphans;
  }

  /**
   * Can a state plan year answer value be copied from one year to another?
   *
   * This will not copy if:
   *   * The given nodes don't exist.
   *   * The plan years of both nodes match.
   *   * The field that determines that the answers are the 'same' just from
   *      different years does not match.
   *   * Any of the given nodes are orphans and are to be deleted.
   *   * There is no value to copy from.
   *   * There is already a value saved to.
   *
   * @param string $from_state_plan_year_answer_nid
   *   A state plan year answer node ID to copy the value from.
   * @param string $to_state_plan_year_answer_nid
   *   A state plan year answer node ID to copy the value to.
   *
   * @return bool
   *   True if this answer can be copied.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function canCopyStatePlanYearAnswer($from_state_plan_year_answer_nid, $to_state_plan_year_answer_nid) {
    /** @var \Drupal\node\Entity\Node $from */
    $from = $this->customEntitiesRetrieval->single('node', $from_state_plan_year_answer_nid);
    if (NULL === $from) {
      throw new \Exception(sprintf('The "from" state plan year answer node did not exist (%d)', $from_state_plan_year_answer_nid));
    }
    /** @var \Drupal\node\Entity\Node $to */
    $to = $this->customEntitiesRetrieval->single('node', $to_state_plan_year_answer_nid);
    if (NULL === $to) {
      throw new \Exception(sprintf('The "to" state plan year answer node did not exist (%d)', $to_state_plan_year_answer_nid));
    }
    $plan_year_id_from = PlanYearInfo::getPlanYearIdFromEntity($from);
    $plan_year_id_to = PlanYearInfo::getPlanYearIdFromEntity($to);
    if ($plan_year_id_from === $plan_year_id_to) {
      throw new \Exception(sprintf('The "to" (%d) and "from" (%d) plan year IDs cannot copy from the same year (%s).', $to->id(), $from->id(), PlanYearInfo::getPlanYearIdFromEntity($to)));
    }
    if ($from->get('field_field_unique_id_reference')->getString() !== $to->get('field_field_unique_id_reference')->getString()) {
      throw new \Exception(sprintf('The "to" (%d) and "from" (%d) state plan year answer nodes are not descended from the same term in a different year.', $to->id(), $from->id()));
    }
    $orphans = $this->getOrphansStatePlanYearAnswers();
    // If either the from or to are orphans (to be deleted) do not copy their
    // value.
    if (in_array($to->id(), $orphans, TRUE)) {
      return FALSE;
    }
    if (in_array($from->id(), $orphans, TRUE)) {
      return FALSE;
    }
    $from_value_field = PlanYearInfo::getStatePlanYearAnswerValueField($from);
    // There is nothing to copy from, no answer was given.
    if ($from_value_field->isEmpty()) {
      return FALSE;
    }
    $to_value_field = PlanYearInfo::getStatePlanYearAnswerValueField($to);
    // The state already answered this question, do not overwrite.
    if (!$to_value_field->isEmpty()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Retrieve count of the total state plan year answers to copy and a message.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   *
   * @return array
   *   'Count' will be greater than 0 if there is plan year answer to copy and
   *   'message' will hold the corresponding supported or unsupported message.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getStatePlanYearAnswersWithCopiableAnswersByStatePlanYearSummary($state_plan_year_nid) {
    $return = [
      'count' => 0,
      'message' => '',
    ];
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $this->nodeService->load($state_plan_year_nid);
    if (PlanYearInfo::SPY_BUNDLE !== $state_plan_year->getType()) {
      $return['message'] = $this->t('%title is not a a state plan year, %type are not supported.', [
        '%title' => $state_plan_year->getTitle(),
        '%type' => $state_plan_year->getType(),
      ]);
      return $return;
    }
    $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($state_plan_year);
    if (!empty($this->getOrphansStatePlanYearAnswers()) || !empty($this->getMissingPlanYearAnswers($plan_year_id))) {
      $return['message'] = Link::createFromRoute($this->t('%title must update content before copying answers.', ['%title' => $state_plan_year->getTitle()]), 'entity.plan_year.content', [PlanYearEntity::ENTITY => $plan_year_id])
        ->toString();
    }
    $copiable_answers_by_section = $this->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYear($state_plan_year->id());
    if (empty($copiable_answers_by_section)) {
      $return['message'] = $this->t('%title has no eligible answers to copy from a previous plan year.', ['%title' => $state_plan_year->getTitle()]);
      return $return;
    }
    $copiable_answers_total = 0;
    foreach (array_column($copiable_answers_by_section, 'state_plan_year_answers') as $state_plan_year_answers) {
      $copiable_answers_total += count($state_plan_year_answers);
    };
    $return['message'] = $this->formatPlural($copiable_answers_total, '%title can copy %count answer from a previous plan year.', '%title can copy %count answers from a previous plan year.', ['%count' => $copiable_answers_total, '%title' => $state_plan_year->getTitle()]);
    $return['count'] = $copiable_answers_total;
    return $return;
  }

  /**
   * Retrieve all state plan year answers that can be copied in this plan year.
   *
   * Since this is using a state plan year node ID, this will retrieve only
   * a single groups content.
   *
   * @param string $state_plan_year_nid
   *   A state plan year node ID.
   *
   * @return array
   *   An array keyed by section ID with sub-arrays with keys of 'to' and 'from'
   *   that hold the node ID of the content to copy from and to.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function getStatePlanYearAnswersWithCopiableAnswersByStatePlanYear($state_plan_year_nid) {
    $cid = __METHOD__ . $state_plan_year_nid;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $return = [];
    /** @var \Drupal\node\Entity\Node $state_plan_year */
    $state_plan_year = $this->nodeService->load($state_plan_year_nid);
    if (NULL === $state_plan_year) {
      throw new \Exception(sprintf('Unable to load state plan year %s', $state_plan_year_nid));
    }
    $plan_year_id = PlanYearInfo::getPlanYearIdFromEntity($state_plan_year);
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single('plan_year', $plan_year_id);
    // If this plan year is not copying from any years, no need to check.
    $copy_from_plan_year_section = array_filter($plan_year->getCopyFromPlanYearSectionArray());
    if (empty($copy_from_plan_year_section)) {
      return $return;
    }
    // Use the state plan year node to determine what group this is.
    $group_id = $this->customEntitiesRetrieval->getGroupId($state_plan_year);
    foreach ($copy_from_plan_year_section as $section_id => $plan_year_id_from) {
      $state_plan_year_section_nid = $this->nodeService->getStatePlanYearSectionByStatePlanYearAndSection($state_plan_year_nid, $section_id);
      if (empty($state_plan_year_section_nid)) {
        continue;
      }
      // All state plan year answers for a this state in the section.
      $state_plan_year_answers_to_nids = $this->nodeService->getStatePlanYearAnswersByStatePlanYearSection($state_plan_year_section_nid);
      if (empty($state_plan_year_answers_to_nids)) {
        continue;
      }
      // Get the state plan year that is being copied from.
      $state_plan_year_from_nid = $this->nodeService->getStatePlanYearByPlanYearAndGroupId($plan_year_id_from, $group_id);
      if (empty($state_plan_year_from_nid)) {
        continue;
      }
      // Get the state plan year section that the state plan year answer to be
      // copied from belongs to.
      $state_plan_year_section_from_nid = $this->nodeService->getStatePlanYearSectionByStatePlanYearAndSection($state_plan_year_from_nid, $section_id);
      foreach ($state_plan_year_answers_to_nids as $state_plan_year_answer_to_nid) {
        /** @var \Drupal\node\Entity\Node $state_plan_year_answer_to */
        $state_plan_year_answer_to = $this->nodeService->load($state_plan_year_answer_to_nid);
        // The to and from should share this same unique ID.
        $shared_field_field_unique_id_reference = $state_plan_year_answer_to->get('field_field_unique_id_reference')->getString();
        // Retrieve the state plan answer that will be copied from.
        $state_plan_year_answer_from_nid = $this->nodeService->getStatePlanYearAnswerByStatePlanYearSectionAndFieldUniqueId($state_plan_year_section_from_nid, $shared_field_field_unique_id_reference);
        // Make sure that the from piece of state plan year content can be
        // found.
        if (empty($state_plan_year_answer_from_nid)) {
          continue;
        }
        // Now that all the information needed is found, determine if copying
        // is possible.
        if (TRUE !== $this->canCopyStatePlanYearAnswer($state_plan_year_answer_from_nid, $state_plan_year_answer_to_nid)) {
          continue;
        }
        if (empty($return[$section_id])) {
          $return[$section_id] = [
            'state_plan_year_section_from' => $state_plan_year_section_from_nid,
            'state_plan_year_answers' => [],
          ];
        }
        $return[$section_id]['state_plan_year_answers'][] = ['from' => $state_plan_year_answer_from_nid, 'to' => $state_plan_year_answer_to_nid];
      }
    }
    $this->cache->set($cid, $return, Cache::PERMANENT, $this->nodeService->getCopyAnswersCacheTags($state_plan_year_nid));
    return $return;
  }

  /**
   * Get all groups that are missing plan year answers in the given plan year.
   *
   * Plan year content is ALL nodes (including answers) while answers is just
   * the plan year answer nodes.
   *
   * This cache is based off of all 'missing' content and is only effected
   * if new terms are added or removed, not the 'copy answers' cache that is
   * effected whenever a term is updated.
   *
   * @param string $plan_year_id
   *   A plan year ID.
   *
   * @return array
   *   An array fields for missing answers that can be used to create them.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getMissingPlanYearAnswers($plan_year_id) {
    $cid = __METHOD__ . $plan_year_id;
    $cache = $this->cache->get($cid);
    if (FALSE !== $cache) {
      return $cache->data;
    }
    $return = [];
    // This array will be keyed by section ID and created as needed.
    $state_plan_year_section_nids = [];
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, $plan_year_id);
    foreach ($plan_year->getSections() as $section) {
      // Get all the terms in this section vocabulary.
      $plan_year_vid = PlanYearInfo::createSectionVocabularyId($plan_year_id, $section->id());
      foreach ($this->taxonomyService->getVocabularyTids($plan_year_vid) as $tid) {
        /** @var \Drupal\taxonomy\Entity\Term $section_year_term */
        $section_year_term = $this->customEntitiesRetrieval->single('taxonomy_term', $tid);
        $state_plan_year_content_info = $this->taxonomyService->getStatePlanYearContentInfoFromSectionYearTerm($section_year_term);
        $section_id = $state_plan_year_content_info['section_id'];
        // The 'content' key holds which nodes need to be created.
        foreach ($state_plan_year_content_info['content'] as $content_info) {
          // If this term field is not referencing a bundle to be created, skip
          // it.
          if (empty($content_info['node_bundle'])) {
            continue;
          }
          // Wait till the last minute to get all the state plan section node
          // IDs that need to be checked.
          if (!isset($state_plan_year_section_nids[$section_id])) {
            $state_plan_year_section_nids[$section_id] = $this->nodeService->getStatePlanYearSectionsByPlanYearAndSectionId($plan_year_id, $section_id);
          }
          // Check that this referenced answer already exists for each
          // groups state plan year section node.
          foreach ($state_plan_year_section_nids[$section_id] as $state_plan_year_section_nid) {
            if (empty($this->nodeService->getStatePlanYearAnswer(
              $content_info['node_bundle'],
              $content_info['term_field_uuid'],
              $plan_year_id,
              $section_id,
              $tid,
              $state_plan_year_section_nid
            ))) {
              $return[] = [
                'node_bundle' => $content_info['node_bundle'],
                'field_unique_id_reference' => $content_info['term_field_uuid'],
                'plan_year' => $plan_year_id,
                'section' => $section_id,
                'section_year_term' => $tid,
                'state_plan_year_section' => $state_plan_year_section_nid,
              ];
            }
          }
        }
      }
    }
    $this->cache->set($cid, $return, Cache::PERMANENT, $this->nodeService->getMissingContentCacheTags($plan_year_id));
    return $return;
  }

}
