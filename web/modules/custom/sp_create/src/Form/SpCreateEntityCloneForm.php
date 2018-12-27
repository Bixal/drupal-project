<?php

namespace Drupal\sp_create\Form;

use Drupal\entity_clone\Form\EntityCloneForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Implements an entity Clone form.
 *
 * The EntityCloneForm was extended here to allow passing in an entity instead
 * of pulling the entity from the router.
 */
class SpCreateEntityCloneForm extends EntityCloneForm {

  /**
   * Constructs a new Entity Clone form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationManager $string_translation, EventDispatcherInterface $eventDispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslationManager = $string_translation;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Set the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    $this->entityTypeDefinition = $this->entityTypeManager->getDefinition($this->entity->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $form_state->get('entity');
    $form_state->set('entity', NULL);
    if (!is_object($entity) || !($entity instanceof EntityInterface)) {
      throw new \Exception('An entity must be passed to be cloned.');
    }
    $this->setEntity($entity);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function formSetRedirect(FormStateInterface $form_state, EntityInterface $entity) {
    // Instead of creating a redirect, take advantage that the finished cloned
    // entity was passed to this function, set it in the form state so that
    // it can be retrieved.
    $form_state->set('cloned_entity', $entity);
  }
}
