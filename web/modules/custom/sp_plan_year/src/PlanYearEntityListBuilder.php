<?php

namespace Drupal\sp_plan_year;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Plan Year entities.
 */
class PlanYearEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Plan Year');
    $header['sections'] = $this->t('Sections Added to Plan Year');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $entity */
    $row['sections'] = count($entity->getSections());
    return $row + parent::buildRow($entity);
  }

}
