<?php

namespace Drupal\sp_create;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\sp_retrieve\NodeService;
use Drupal\user\Entity\User;

/**
 * Class CustomEntitiesService.
 */
class InitService {

  use StringTranslationTrait;

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The node service.
   *
   * @var \Drupal\sp_retrieve\NodeService
   */
  protected $nodeService;

  /**
   * Constructs a new TaxonomyService object.
   *
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory for retrieving the site front page configuration.
   */
  public function __construct(NodeService $node_service, ConfigFactoryInterface $config) {
    $this->nodeService = $node_service;
    $this->config = $config;
  }

  /**
   * Initialize content and configuration that needs to be set dynamically.
   *
   * @return array
   *   An array of translated messages.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function init() {
    $messages = [];
    /** @var \Drupal\node\Entity\Node $homepageNode */
    $homepageNid = $this->nodeService->getHomepageNid();
    if ($homepageNid === FALSE) {
      $messages[] = $this->t('Homepage has not been created yet, please import content first.');
    }
    else {
      $messages[] = $this->t('Homepage exists');
      $homepageNode = $this->nodeService->load($homepageNid);
    }
    if (NULL === $homepageNode) {
      $messages[] = $this->t('Homepage unable to be loaded, unable to check front page content.');
    }
    else {
      $homepageUrl = $homepageNode->url('canonical');
      $front = $this->config->get('system.site')->get('page.front');
      if ($front !== $homepageUrl) {
        $this->config->getEditable('system.site')
          ->set('page.front', $homepageUrl)
          ->save();
        $messages[] = $this->t('Front URL updated from @old to @new', [
          '@old' => $front,
          '@new' => $homepageUrl,
        ]);
      }
      else {
        $messages[] = $this->t('Success, the front page URL did not need to be updated');
      }
    }
    // User 1 needs the administrator role for admin search menu.
    $admin = User::load(1);
    if (!$admin->hasRole('administrator')) {
      $admin->addRole('administrator');
      $admin->save();
      $messages[] = $this->t('User 1 given the administrator role');
    }
    else {
      $messages[] = $this->t('Success, user 1 already has the administrator role');
    }
    return $messages;
  }

}
