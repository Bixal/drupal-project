<?php

namespace Drupal\sp_access\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;
use Drupal\group\Entity\GroupContentType;

/**
 * Provides an access checker for node revisions.
 *
 * @ingroup node_access
 */
class GnodeRevisionAccessCheck implements AccessInterface {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * A static cache of access checks.
   *
   * @var array
   */
  protected $access = [];

  /**
   * Constructs a new GnodeRevisionAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->nodeStorage = $entity_manager->getStorage('node');
  }

  /**
   * Checks routing access for the node revision.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $node_revision
   *   (optional) The node revision ID. If not specified, but $node is, access
   *   is checked for that object's revision.
   * @param \Drupal\node\NodeInterface $node
   *   (optional) A node object. Used for checking access to a node's default
   *   revision when $node_revision is unspecified. Ignored when $node_revision
   *   is specified. If neither $node_revision nor $node are specified, then
   *   access is denied.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(Route $route, AccountInterface $account, $node_revision = NULL, NodeInterface $node = NULL) {
    if ($node_revision) {
      $node = $this->nodeStorage->loadRevision($node_revision);
    }
    $operation = $route->getRequirement('_access_node_revision');
    return AccessResult::allowedIf($node && $this->checkAccess($node, $account, $operation))->cachePerPermissions()->addCacheableDependency($node);
  }

  /**
   * Checks node revision access by group.
   *
   * This check is required because NodeRevisionAccessCheck allows users that
   * have Drupal permissions to access revisions to be allowed to un-published
   * revisions if the current revision is published.
   *
   * Important: One of the following permissions: view all revisions, view
   * $bundle revisions, administer nodes, is still REQUIRED. This check simply
   * limits access if the user is not in the same group as the node.
   *
   * This method requires the patch
   * group-restrict-node-revisions-across-groups.patch in order for the
   * permission to be added to the groups module.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user object representing the user for whom the operation is to be
   *   performed.
   * @param string $op
   *   (optional) The specific operation being checked. Defaults to 'view'.
   *
   * @return bool
   *   TRUE if the operation may be performed, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function checkAccess(NodeInterface $node, AccountInterface $account, $op = 'view') {
    if (!$node || $op !== 'view') {
      // If there was no node to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return TRUE;
    }

    // Statically cache access by node and user account ID.
    $cid = $node->id() . $account->id() . ':' . $op;

    if (!isset($this->access[$cid])) {

      $this->access[$cid] = TRUE;

      if ($account->hasPermission('administer nodes')) {
        return $this->access[$cid];
      }

      $plugin_id = 'group_node:' . $node->bundle();

      // Only act if there are group content types for this node type.
      $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
      if (empty($group_content_types)) {
        return $this->access[$cid];
      }

      // Load all the group content for this node.
      $group_contents = \Drupal::entityTypeManager()
        ->getStorage('group_content')
        ->loadByProperties([
          'type' => array_keys($group_content_types),
          'entity_id' => $node->id(),
        ]);

      // If the node does not belong to any group, we have nothing to say.
      if (empty($group_contents)) {
        return $this->access[$cid];
      }

      /** @var \Drupal\group\Entity\GroupInterface[] $groups */
      $groups = [];
      foreach ($group_contents as $group_content) {
        /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
        $group = $group_content->getGroup();
        $groups[$group->id()] = $group;
      }

      switch ($op) {
        case 'view':
          foreach ($groups as $group) {
            if (FALSE === $group->hasPermission("view revisions $plugin_id entity", $account)) {
              $this->access[$cid] = FALSE;
              return $this->access[$cid];
            }

          }
      }
    }

    return $this->access[$cid];
  }

}
