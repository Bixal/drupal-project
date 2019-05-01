<?php

/**
 * @file
 * Contains views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function sp_retrieve_views_data_alter(array &$data) {
  $data['node']['copy_answers'] = [
    'title' => t('Copy Answers'),
    'field' => [
      'title' => t('Copy Answers'),
      'help' => t('Shows if a state plan has any copiable state plan year answers.'),
      'id' => 'copy_answers',
    ],
  ];
  // @all_plans.
  $data['node']['plan_type'] = [
    'title' => t('Display plan type'),
    'field' => [
      'title' => t('Display plan type'),
      'help' => t('Shows either combined or unified.'),
      'id' => 'plan_type',
    ],
  ];
}
