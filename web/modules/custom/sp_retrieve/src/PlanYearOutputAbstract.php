<?php

namespace Drupal\sp_retrieve;

/**
 * Class PlanYearItemListAbstract.
 *
 * @package Drupal\sp_retrieve
 */
abstract class PlanYearOutputAbstract {

  /**
   * A PlanYearDisplay object.
   *
   * @var \Drupal\sp_retrieve\PlanYearDisplay
   */
  protected $planYearDisplay;

  /**
   * PlanYearItemListAbstract constructor.
   *
   * @param \Drupal\sp_retrieve\PlanYearDisplay $plan_year_display
   *   A PlanYearDisplay object.
   */
  public function __construct(PlanYearDisplay $plan_year_display) {
    $this->planYearDisplay = $plan_year_display;
  }

}
