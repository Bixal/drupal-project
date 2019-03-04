<?php

namespace Drupal\sp_create\Command;

use Drupal\config_split\Entity\ConfigSplitEntity;
use Drupal\sp_retrieve\CustomEntitiesService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

/**
 * Class ConfigSplitsCreateCommand.
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="sp_create",
 *     extensionType="module"
 * )
 */
class ConfigSplitsCreateCommand extends ContainerAwareCommand {

  /**
   * Drupal\sp_create\ConfigSplitsCreateCommand definition.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesService;

  /**
   * Constructs a new CreateCommand object.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_service
   *   The expire state plan service.
   */
  public function __construct(CustomEntitiesService $custom_entities_service) {
    parent::__construct();
    $this->customEntitiesService = $custom_entities_service;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sp_create.config_splits_create')
      ->setDescription($this->trans('commands.sp_create.config_splits_create.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $absolute_all_splits_path = DRUPAL_ROOT . '/../config/plan_years';
    $directories = glob($absolute_all_splits_path . '/*', GLOB_ONLYDIR);
    $plan_year_labels = $this->customEntitiesService->labels('plan_year');
    $plan_year_directories = [];
    if (!empty($directories)) {
      foreach ($directories as $directory) {
        $plan_year_directories[substr($directory, -4)] = $directory;
      }
      $plan_year_directories_remove = array_diff(array_keys($plan_year_directories), array_keys($plan_year_labels));
      foreach ($plan_year_directories_remove as $plan_year_id) {
        $this->getIo()
          ->warning('The configuration directory for deleted plan year ' . $plan_year_id . ' (' . $plan_year_directories[$plan_year_id] . ') is no longer being used for configuration and should be deleted.');
      }
    }
    $config_splits_created = 0;
    $config_splits_dirs_needed_to_be_created = 0;
    foreach ($plan_year_labels as $plan_year_id => $plan_year_label) {
      $config_split_id = 'plan_year_' . $plan_year_id;
      /* @var \Drupal\config_split\Entity\ConfigSplitEntity $config_split */
      $config_split = $this->customEntitiesService->single('config_split', $config_split_id);
      if (NULL === $config_split) {
        $relative_split_path = '../config/plan_years/plan_year_' . $plan_year_id;
        $absolute_split_path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $relative_split_path;
        $this->getIo()->info($this->trans('Creating the config split for plan year ' . $plan_year_id));
        /* @var \Drupal\config_split\Entity\ConfigSplitEntity $new_config_split */
        $new_config_split = ConfigSplitEntity::create(
          [
            'id' => $config_split_id,
            'label' => 'Plan Year ' . $plan_year_label,
            'description' => 'All the configuration specific to the plan year ' . $plan_year_label . '.',
            'blacklist' => ['sp_plan_year.plan_year.' . $plan_year_id, '*_' . $plan_year_id . '_*'],
            'folder' => $relative_split_path,
          ]
        );
        $new_config_split->save();
        $config_splits_created++;
        if (FALSE === file_prepare_directory($absolute_split_path, FILE_CREATE_DIRECTORY)) {
          $this->getIo()->error('Unable to create directory ' . $absolute_split_path . '. You must create this directory manually before exporting configuration. This error will not appear again.');
          $config_splits_dirs_needed_to_be_created++;
        }
        else {
          $this->getIo()->info($this->trans('The folder for config split plan year ' . $plan_year_id . ' has been created.'));
        }
      }
    }
    if ($config_splits_created > 0) {
      $this->getIo()->info($config_splits_created . ' config splits have been created.');
    }
    else {
      $this->getIo()->info('No config splits have been created.');
    }
    if ($config_splits_dirs_needed_to_be_created > 0) {
      $this->getIo()->warning('There are ' . $config_splits_dirs_needed_to_be_created . ' config splits directories that were not created. They must be created before exporting configuration.');
    }
    elseif ($config_splits_created > 0) {
      $this->getIo()->info('Please run configuration export now.');
    }
  }

}
