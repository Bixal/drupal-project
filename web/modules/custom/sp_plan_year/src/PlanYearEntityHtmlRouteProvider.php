<?php

namespace Drupal\sp_plan_year;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Plan Year entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class PlanYearEntityHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($wizard_route = $this->getWizardRoute($entity_type)) {
      $collection->add("entity.plan_year.wizard", $wizard_route);
    }

    /** @var \Symfony\Component\Routing\Route $route */
    foreach ($collection as $route_name => $route) {
      switch ($route_name) {
        case 'entity.plan_year.collection':
          $route->setRequirements(['_permission' => 'list plan year']);
          break;

      }
    }

    return $collection;
  }

  /**
   * Gets the wizard route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getWizardRoute(EntityTypeInterface $entity_type) {
    $route = new Route('/admin/structure/plan_year/{plan_year}/wizard');
    $route->setDefault('_entity_form', 'plan_year.wizard');
    $route->setDefault('_title', 'Wizard');
    $route->setRequirement('_entity_access', 'plan_year.wizard');
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

}
