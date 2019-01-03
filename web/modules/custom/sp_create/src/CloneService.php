<?php

namespace Drupal\sp_create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sp_create\Form\SpCreateEntityCloneForm;

/**
 * Class CloneService.
 */
class CloneService {

  /**
   * All bundle info.
   *
   * @var array
   */
  protected $bundles;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new CloneService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service
   *   The bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundles = $bundle_info_service->getAllBundleInfo();
  }

  /**
   * Clone a bundle.
   *
   * @param string $entity_type
   *   An entity type ID.
   * @param string $bundle
   *   A bundle ID.
   * @param string $cloned_bundle_id
   *   The bundle ID of the cloned bundle.
   * @param string $cloned_label
   *   The label of the cloned bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The duplicated bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function cloneBundle($entity_type, $bundle, $cloned_bundle_id, $cloned_label) {
    if (!isset($this->bundles[$entity_type][$bundle])) {
      throw new \Exception(sprintf('The given entity and bundle combination of %s and %s do not exist.', $entity_type, $bundle));
    }
    $form_state = new FormState();
    $values = [
      'op' => 'Clone',
      'id' => $cloned_bundle_id,
      'label' => $cloned_label,
    ];
    $form_state->setValues($values);
    // This takes, for example, 'node' and gets back 'node_type' because we
    // are cloning a bundle not a single entity.
    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type)->getBundleEntityType();
    $entity_storage = $this->entityTypeManager->getStorage($bundle_entity_type);
    $entity = $entity_storage->load($bundle);
    // The form EntityCloneForm was overriden so that the entity could be passed
    // in programmatically instead of pulling from the router.
    $form_state->set('entity', $entity);
    $errors = $this->translatableArrayToStringArray($this->submit($form_state));
    if (!empty($errors)) {
      throw new \Exception($errors);
    }
    return $form_state->get('cloned_entity');
  }

  /**
   * Clone a single entity.
   *
   * @param string $entity_type
   *   An entity type ID.
   * @param string $bundle
   *   A bundle ID.
   * @param int $entity_id
   *   A entity ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The duplicated entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function cloneEntity($entity_type, $bundle, $entity_id = NULL) {
    if (!isset($this->bundles[$entity_type][$bundle])) {
      throw new \Exception(sprintf('The given entity and bundle combination of %s and %s do not exist.', $entity_type, $bundle));
    }
    $form_state = new FormState();
    $values = [
      'op' => 'Clone',
    ];
    $form_state->setValues($values);
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $entity = $entity_storage->load($entity_id);
    // The form EntityCloneForm was overriden so that the entity could be passed
    // in programmatically instead of pulling from the router.
    $form_state->set('entity', $entity);
    $errors = $this->translatableArrayToStringArray($this->submit($form_state));
    if (!empty($errors)) {
      throw new \Exception($errors);
    }
    return $form_state->get('cloned_entity');
  }

  /**
   * Submit the form that will clone the entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   An array of error messages if any occurred.
   */
  protected function submit(FormStateInterface $form_state) {
    \Drupal::formBuilder()->submitForm(SpCreateEntityCloneForm::class, $form_state);
    return $form_state->getErrors();
  }

  /**
   * Convert an array of translated strings to a comma separated string.
   *
   * @param array $translatable_array
   *   An array of translated strings.
   *
   * @return string
   *   The translated strings as a string or an empty string if none.
   */
  protected function translatableArrayToStringArray(array $translatable_array) {
    if (!empty($translatable_array)) {
      $translatable_array_strings = [];
      foreach ($translatable_array as $translatable_array_string) {
        $translatable_array_strings[] = (string) $translatable_array_string;
      }
      return implode(", ", $translatable_array_strings);
    }
    return '';
  }

}
