<?php

namespace Drupal\sp_create\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\sp_create\CloneService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CloneBundleCommand.
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="sp_create",
 *     extensionType="module"
 * )
 */
class CloneBundleCommand extends ContainerAwareCommand {

  /**
   * The clone service.
   *
   * @var \Drupal\sp_create\CloneService
   */
  protected $cloneService;

  /**
   * Constructs a new CloneBundleCommand object.
   *
   * @param \Drupal\sp_create\CloneService $clone_service
   *   The expire state plan service.
   */
  public function __construct(CloneService $clone_service) {
    parent::__construct();
    $this->cloneService = $clone_service;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sp_create:clone_bundle')
      ->setDescription($this->trans('commands.sp_create.clone_bundle.description'))
      ->addOption(
        'log',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.sp_create.clone_bundle.options.log'),
        1
      )
      ->addArgument(
        'entity',
        InputArgument::REQUIRED,
        $this->trans('commands.sp_create.clone_bundle.arguments.entity')
      )->addArgument(
        'bundle',
        InputArgument::REQUIRED,
        $this->trans('commands.sp_create.clone_bundle.arguments.bundle')
      )->addArgument(
        'clone_bundle',
        InputArgument::REQUIRED,
        $this->trans('commands.sp_create.clone_bundle.arguments.new_bundle')
      )->addArgument(
        'clone_label',
        InputArgument::REQUIRED,
        $this->trans('commands.sp_create.clone_bundle.arguments.new_bundle')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $entity = $input->getArgument('entity');
    $bundle = $input->getArgument('bundle');
    $clone_bundle = $input->getArgument('clone_bundle');
    $clone_label = $input->getArgument('clone_label');
    try {
      $cloned_entity = $this->cloneService->cloneBundle($entity, $bundle, $clone_bundle, $clone_label);
      $this->getIo()->info("New bundle {$cloned_entity->label()} ({$cloned_entity->id()}) created.");
    }
    catch (\Exception $exception) {
      $this->getIo()->error($exception->getMessage());
    }
    $this->getIo()->info($this->trans('commands.sp_create.clone_bundle.messages.success'));
  }

}
