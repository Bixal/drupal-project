<?php

namespace Drupal\sp_display\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\sp_retrieve\CustomEntitiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class SpDisplayController.
 *
 * @package Drupal\sp_display\Controller
 *
 * @all_plans
 */
class SpDisplayController extends ControllerBase {

  /**
   * Custom entities service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntities;

  /**
   * The custom entities service.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities
   *   The Views data cache object.
   */
  public function __construct(CustomEntitiesService $custom_entities) {
    $this->customEntities = $custom_entities;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sp_retrieve.custom_entities')
    );
  }

  /**
   * Callback for state label autocomplete.
   *
   * Like other autocomplete functions, this function inspects the 'q' query
   * parameter for the string to use to search for suggestions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions for Views tags.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function autocompleteStateLabel(Request $request) {
    $matches = [];
    $search = strtolower($request->query->get('q'));
    $state_labels = $this->customEntities->getAllStateLabels();
    foreach ($state_labels as $gid => $state_label) {
      if (FALSE !== strstr(strtolower($state_label), $search)) {
        $matches[] = ['value' => $state_label . ' (' . $gid . ')', 'label' => $state_label];
      }
    }
    return new JsonResponse($matches);
  }

}
