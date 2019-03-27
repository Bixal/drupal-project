<?php

namespace Drupal\sp_create;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\sp_retrieve\NodeService;
use Drupal\Core\Entity\EntityStorageException;

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
   */
  public function init() {
    $messages = [];
    /** @var \Drupal\node\Entity\Node $homepageNode */
    $homepageNid = $this->nodeService->getHomepageNid();
    if ($homepageNid === FALSE) {
      $homepageNode = $this->createHomepage();
      $messages[] = $this->t('Homepage created');
    }
    else {
      $messages[] = $this->t('Homepage already exists');
      $homepageNode = $this->nodeService->load($homepageNid);
    }
    if (NULL === $homepageNode) {
      $messages[] = $this->t('Homepage unable to be loaded, unable to check front page content.');
    }
    else {
      $homepageUrl = $homepageNode->url('canonical');
      var_dump($homepageUrl);
      $front = $this->config->get('system.site')->get('page.front');
      var_dump($front);
      if ($front !== $homepageUrl) {
        $this->config->getEditable('system.site')->set('page.front', $homepageUrl)->save();
        $messages[] = $this->t('Front URL updated from @old to @new', ['@old' => $front, '@new' => $homepageUrl]);
      }
      else {
        $messages[] = $this->t('The front page URL did not need to be updated');
      }
    }
    return $messages;
  }

  /**
   * Create a homepage node.
   *
   * @return \Drupal\node\Entity\Node|null
   *   Returns the created homepage node or null.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createHomepage() {
    $node = Node::create([
      'type' => 'homepage',
      'title' => 'Homepage',
      'status' => 1,
    ]);
    $node->setOwner($this->nodeService->getAutomatedNodeOwner());
    try {
      $node->save();
      return $node;
    }
    catch (EntityStorageException $exception) {
      return NULL;
    }

  }

}
