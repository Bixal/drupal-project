<?php

namespace Drupal\sp_create\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Drupal\sp_create\UpdatePlanYearContentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sp_retrieve\NodeService;

/**
 * Change the moderation state of entities.
 *
 * @Action(
 *   id = "copy_answers_action",
 *   label = @Translation("Copy all eligible answers from one plan year to another"),
 *   type = "node"
 * )
 */
class CopyAnswers extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The node retrieval service.
   *
   * @var \Drupal\sp_retrieve\NodeService
   */
  protected $nodeService;

  /**
   * The update plan year content service.
   *
   * @var \Drupal\sp_create\UpdatePlanYearContentService
   */
  protected $updatePlanYearContentService;

  /**
   * ModerateEntities constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node retrieval service.
   * @param \Drupal\sp_create\UpdatePlanYearContentService $update_plan_year_content_service
   *   The update plan year content service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, NodeService $node_service, UpdatePlanYearContentService $update_plan_year_content_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
    $this->nodeService = $node_service;
    $this->updatePlanYearContentService = $update_plan_year_content_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('messenger'),
      $container->get('sp_retrieve.node'),
      $container->get('sp_create.update_plan_year_content')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(Node $entity = NULL) {
    // Since ALL entities are still passed to execute, they need to be checked
    // that they can still be copied to.
    $summary = $this->nodeService->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYearSummary($entity->id());
    if (empty($summary['count'])) {
      return;
    }

    // Go through each state plan year section and then all content in that
    // section that can be copied to.
    foreach ($this->nodeService->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYear($entity->id()) as $copiable_answers_section) {
      if (empty($copiable_answers_section['state_plan_year_answers'])) {
        continue;
      }
      foreach ($copiable_answers_section['state_plan_year_answers'] as $answer_nid_from_and_to) {
        $this->updatePlanYearContentService->copyStatePlanYearAnswer($answer_nid_from_and_to['from'], $answer_nid_from_and_to['to'], TRUE);
      }
    }
    $this->messenger->addStatus($this->formatPlural($summary['count'], 'Copied %count piece of content into %title', 'Copied %count pieces of content into %title', ['%count' => $summary['count'], '%title' => $entity->getTitle()]));
  }

  /**
   * Display an error message and return the form array.
   *
   * @param array $form
   *   The current form.
   * @param string $message
   *   An error message to display.
   *
   * @return array
   *   The form array.
   */
  protected function unsupported(array $form, $message = 'The view does not support passing entities correctly.') {
    // Remove submit button if not supported.
    unset($form['actions']['submit']);
    $this->messenger->addError($message);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // We're going to create our own list.
    unset($form['list']);
    $storage = $form_state->getStorage();
    if (empty($storage['views_bulk_operations']['list'])) {
      return $this->unsupported($form);
    }
    $entity_ids_to_load = [];
    $supported = [];
    $unsupported = [];
    foreach ($storage['views_bulk_operations']['list'] as $entity_ids) {
      // No need to look further if the view does not pass the entity type and
      // ID.
      if (empty($entity_ids[2]) || empty($entity_ids[3])) {
        return $this->unsupported($form);
      }
      // Skip any non node entities.
      if ('node' !== $entity_ids[2]) {
        $unsupported[] = $this->t('Only state plan year nodes can have answers copied. Entities of type %type are not supported.', ['%type' => $entity_ids[2]]);
        continue;
      }
      $entity_ids_to_load[] = $entity_ids[3];
    }
    foreach ($entity_ids_to_load as $state_plan_year_nid) {
      $summary = $this->nodeService->getStatePlanYearAnswersWithCopiableAnswersByStatePlanYearSummary($state_plan_year_nid);
      if (!empty($summary['count'])) {
        $supported[] = $summary['message'];
      }
      else {
        $unsupported[] = $summary['message'];
      }
    }
    if (!empty($supported)) {
      $form['supported'] = [
        '#type' => 'details',
        '#title' => $this->formatPlural(count($supported), 'Ready to copy answers to the following plan', 'Ready to copy answers to the following %count plans', ['%count' => count($supported)]),
        '#open' => TRUE,
      ];
      foreach ($supported as $item) {
        $form['supported'][] = [
          '#type' => 'markup',
          '#markup' => $item,
        ];
      }
    }
    else {
      // Cannot submit if no supported plans are available.
      unset($form['actions']['submit']);
    }
    if (!empty($unsupported)) {
      $form['unsupported'] = [
        '#type' => 'details',
        '#title' => $this->formatPlural(count($unsupported), 'The following plan have no eligible answers to copy.', 'The following %count plans have no eligible answers to copy.', ['%count' => count($unsupported)]),
        '#open' => TRUE,
      ];
      foreach ($unsupported as $item) {
        $form['unsupported'][] = [
          '#type' => 'markup',
          '#markup' => $item,
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // There is no additional data to capture, if they submit form we copy all
    // content in the state plan years that were submitted.
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowedIfHasPermission($account, 'copy state plan year answers');

    return $return_as_object ? $access : $access->isAllowed();
  }

}
