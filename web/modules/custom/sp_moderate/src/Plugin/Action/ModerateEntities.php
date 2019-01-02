<?php

namespace Drupal\sp_moderate\Plugin\Action;

use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;

/**
 * Change the moderation state of entities.
 *
 * @Action(
 *   id = "moderate_entities_action",
 *   label = @Translation("Change the moderation state of entities"),
 *   type = ""
 * )
 */
class ModerateEntities extends ConfigurableActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validator;

  /**
   * ModerateEntities constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation information service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validator
   *   The state transition validation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_info, MessengerInterface $messenger, AccountInterface $current_user, StateTransitionValidationInterface $validator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInfo = $moderation_info;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->validator = $validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('messenger'),
      $container->get('current_user'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(EditorialContentEntityBase $entity = NULL) {
    // Since ALL entities are still passed to execute, they need to be checked
    // that they can handle the given workflow and transition.
    $workflow = $this->getWorkFlowAndLatestRev($entity, FALSE);
    if (NULL === $workflow) {
      return;
    }
    // Determine the new moderation state that applies to this transition.
    $new_state = NULL;
    $transition_entities = $this->validator->getValidTransitions($entity, $this->currentUser);
    foreach ($transition_entities as $transition_entity) {
      if ($transition_entity->id() === $this->configuration['transition_id']) {
        $new_state = $transition_entity->to();
        break;
      }
    }
    if (NULL === $new_state) {
      return;
    }

    // Set a new moderation state.
    $entity->set('moderation_state', $new_state->id());

    $time = time();
    // Always make sure a new revision is created.
    $entity->setNewRevision(TRUE);
    // Optional, set a log message for this revision.
    $entity->setRevisionLogMessage($this->configuration['revision_log_message']);
    // Optional, set a new time for the revision log message. This will
    // default to the last revision log time.
    $entity->setRevisionCreationTime($time);
    // Optional, set a new time for the updated time. This will default
    // to the last revision changed time.
    $entity->setChangedTime($time);
    // This is REQUIRED and I'm not sure why. If this is this flag is not
    // set, the revision will not show in the revisions tab.
    $entity->setRevisionTranslationAffected(TRUE);

    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'transition_id' => '',
      'workflow_id' => '',
      'revision_log_message' => '',
    ];
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
    $this->messenger->addError($message);
    return $form;
  }

  /**
   * Retrieve the workflow for the given entity.
   *
   * This will also overwrite the $entity with the latest revision if it is not
   * the $entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   * @param bool $showErrors
   *   If true, will add messenger warnings.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   Returns the workflow for the entity or null if unable.
   */
  protected function getWorkFlowAndLatestRev(EntityInterface &$entity, $showErrors = TRUE) {
    if (FALSE === $entity instanceof EditorialContentEntityBase) {
      if ($showErrors) {
        $this->messenger->addWarning($this->t('"@entity" is not an editorial entity, it cannot change state.', ['@entity' => $entity->label()]));
      }
      return NULL;
    }
    $workflow = $this->moderationInfo->getWorkflowForEntity($entity);
    if (NULL === $workflow) {
      if ($showErrors) {
        $this->messenger->addWarning($this->t('"@entity" is not a moderated entity, it cannot change state.', ['@entity' => $entity->label()]));
      }
      return NULL;
    }
    // We only want to work with the latest revision.
    if (FALSE === $this->moderationInfo->isLatestRevision($entity)) {
      $entity = $this->moderationInfo->getLatestRevision($entity->getEntityTypeId(), $entity->id());
    }
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Remove the submit button, we'll use individual ones for each group.
    unset($form['actions']['submit']);
    $storage = $form_state->getStorage();
    if (empty($storage['views_bulk_operations']['list'])) {
      return $this->unsupported($form);
    }
    $total_entities = 0;
    $entity_ids_to_load = [];
    foreach ($storage['views_bulk_operations']['list'] as $entity_ids) {
      if (empty($entity_ids[2]) || empty($entity_ids[3])) {
        return $this->unsupported($form);
      }
      // Key: entity type Values: entity IDs.
      $entity_ids_to_load[$entity_ids[2]][] = $entity_ids[3];
      $total_entities++;
    }
    $transitions = [];
    foreach ($entity_ids_to_load as $entity_type => $entity_ids) {
      $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_ids);
      foreach ($entities as $entity) {
        if (empty($entity->moderation_state)) {
          continue;
        }
        $current_moderation_state_id = $entity->moderation_state->value;
        $workflow = $this->getWorkFlowAndLatestRev($entity, TRUE);
        if (NULL === $workflow) {
          continue;
        }
        // Any entity that makes it this far, is a revisionable and workflow
        // enabled entity.
        /** @var \Drupal\Core\Entity\EditorialContentEntityBase $entity */
        $current_moderation_state_label = $workflow->getTypePlugin()->getState($current_moderation_state_id)->label();
        $workflow->getTypePlugin()->getState($entity->moderation_state->value)->label();
        $latest_moderation_state_label = $workflow->getTypePlugin()->getState($entity->moderation_state->value)->label();;
        // Determine what transitions this entity can make.
        $transition_entities = $this->validator->getValidTransitions($entity, $this->currentUser);
        /** @var \Drupal\workflows\Transition $transition */
        if (!empty($transition_entities)) {
          foreach ($transition_entities as $transition_id => $transition) {
            $transitions[$workflow->id()]['transitions'][$transition_id]['label'] = $transition->label();
            $transitions[$workflow->id()]['label'] = $workflow->label();
            $extra = " (Current State: " . $current_moderation_state_label;
            // Make it clear the latest revision is different than current.
            if ($current_moderation_state_label !== $latest_moderation_state_label) {
              $extra .= ", Latest State: " . $latest_moderation_state_label;
            }
            $extra .= ")";
            $transitions[$workflow->id()]['transitions'][$transition_id]['entities'][] = [
              'id' => $entity->id(),
              'label' => $entity->label() . $extra,
            ];
          }
        }
      }
      if (count($entity_ids) !== count($entities)) {
        return $this->unsupported($form, $this->t('All entities of type @type were unable to be loaded.', ['@type' => $entity_type]));
      }
    }
    // No need to show the rest of the form if there are no transitions.
    if (empty($transitions)) {
      return $this->unsupported($form, $this->t('None of the selected entities have any transitions available to them. Your account may not have permission to transitions that might be supported.'));
    }

    foreach ($transitions as $workflow_id => $workflow_info) {
      $form['revision_log_message'] = [
        '#type' => 'textarea',
        '#title' => $this
          ->t('Revision Log Message'),
        '#required' => TRUE,
        '#rows' => 4,
        '#maxlength' => 255,
        '#description' => $this->t('First choose a revision log message for the transition you are applying.'),
      ];
      $form['workflow'] = [
        '#type' => 'details',
        '#title' => t('Workflow: @workflow', ['@workflow' => $workflow_info['label']]),
        '#description' => t('The following are all the transitions available for the given entities in this workflow. Note that all entities may not be able to follow every transition. One may only choose one transition at a time.'),
        '#open' => TRUE,
      ];
      foreach ($workflow_info['transitions'] as $transition_id => $transition_info) {
        $form['workflow'][$transition_id] = [
          '#type' => 'details',
          '#title' => t('@transition: @trans_num of @total_num selected entities can make the transition', [
            '@trans_num' => count($transition_info['entities']),
            '@total_num' => $total_entities,
            '@transition' => $transition_info['label'],
          ]),
          '#open' => FALSE,
        ];
        $entity_labels = [];
        $entity_ids = [];
        foreach ($transition_info['entities'] as $entity) {
          $entity_labels[] = $entity['label'];
          $entity_ids[] = $entity['id'];
        }
        $form['workflow'][$transition_id]['list'] = [
          '#theme' => 'item_list',
          '#items' => $entity_labels,
        ];
        $form['workflow'][$transition_id]['submit'] = [
          '#type' => 'submit',
          '#value' => $transition_info['label'],
          // This will make a new duplicate form state value of 'submit:x'.
          // That will let us know which transition they want to make since
          // it will only be set for the clicked.
          '#name' => 'submit:' . $transition_id . ':' . $workflow_id,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // The key looks like: submit:TRANSITION_ID:WORKFLOW_ID.
      if ('submit:' === substr($key, 0, 7)) {
        $values = explode(':', $key);
        $this->configuration['transition_id'] = $values[1];
        $this->configuration['workflow_id'] = $values[2];
        $this->configuration['revision_log_message'] = $form_state->getValue('revision_log_message');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // Since it is unknown in this method what transition they are going to make
    // the access check will be done in the execute method, skipping any items
    // they don't have access to set moderation on.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $object */
    $access = $object->access('view', $account, TRUE);

    return $return_as_object ? $access : $access->isAllowed();
  }

}
