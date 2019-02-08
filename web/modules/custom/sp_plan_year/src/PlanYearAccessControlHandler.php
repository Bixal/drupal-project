<?php

namespace Drupal\sp_plan_year;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the plan year entity type.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class PlanYearAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        $permission = 'edit plan year';
        break;

      case 'delete':
        $permission = 'delete plan year';
        break;

      case 'wizard':
        $permission = 'use plan year wizard';
        break;

      case 'content':
        $permission = 'edit plan year content';
        break;

    }
    if (!empty($permission)) {
      if ($account->hasPermission($permission)) {
        return AccessResult::allowed()->cachePerPermissions();
      }
    }
    return AccessResult::neutral()->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    if ($account->hasPermission('add plan year')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    return AccessResult::neutral();
  }

}
