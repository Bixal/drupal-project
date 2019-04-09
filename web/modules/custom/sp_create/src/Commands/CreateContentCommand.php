<?php

namespace Drupal\sp_create\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\sp_retrieve\CustomEntitiesService;
use Drush\Commands\DrushCommands;
use Drupal\sp_plan_year\Entity\PlanYearEntity;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Drush commandfile to create content.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class CreateContentCommand extends DrushCommands {

  /**
   * The option in the content form to create answers.
   */
  public const SUBMIT_TYPE_MODIFY_ANSWERS = 'modify_answers';

  /**
   * The option in the content form to create plans and sections.
   */
  public const SUBMIT_TYPE_CREATE_PLANS_AND_SECTIONS = 'create_plans_and_sections';

  /**
   * The custom entities retrieval service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * CreateContentCommand constructor.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   The custom entities retrieval service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(CustomEntitiesService $custom_entities_retrieval, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger, FormBuilderInterface $form_builder) {
    parent::__construct();
    $this->customEntitiesService = $custom_entities_retrieval;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
    $this->formBuilder = $form_builder;
  }

  /**
   * Creates the plan year content and answers for a plan year.
   *
   * @param string $plan_year_id
   *   The plan year ID to create content / answers for.
   * @param string $type
   *   A SUBMIT_TYPE constant from this class.
   *
   * @command sp_create:content_and_answers
   *
   * @aliases sp_create_content
   *
   * @usage sp_create:content_and_answers 2018 modify_answers
   * sp_create:content_and_answers 2018 create_plans_and_sections
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function contentAndAnswers($plan_year_id, $type) {
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
    $plan_year = $this->customEntitiesService->single('plan_year', $plan_year_id);
    if (NULL === $plan_year) {
      $this->messenger->addError(dt('Plan year %plan_year_id does not exist.', ['%plan_year_id' => $plan_year_id]));
      return;
    }
    // Attempt to create content.
    $this->submitForm($type, $plan_year);
  }

  /**
   * Submit the plan year entity content form.
   *
   * @param string $type
   *   A SUBMIT_TYPE constant from this class.
   * @param \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year
   *   The plan year entity being acted on.
   */
  protected function submitForm($type, PlanYearEntity $plan_year) {
    $form_state = new FormState();
    $values = [
      self::SUBMIT_TYPE_MODIFY_ANSWERS => $type === self::SUBMIT_TYPE_MODIFY_ANSWERS ? TRUE : FALSE,
      self::SUBMIT_TYPE_CREATE_PLANS_AND_SECTIONS => $type === self::SUBMIT_TYPE_CREATE_PLANS_AND_SECTIONS ? TRUE : FALSE,
    ];
    $form_state->setValues($values);
    /** @var \Drupal\Core\Form\FormBase $plan_year_form */
    $form = $this->entityTypeManager->getFormObject('plan_year', 'content')
      ->setEntity($plan_year);
    $this->formBuilder->submitForm($form, $form_state);
  }

}
