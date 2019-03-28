<?php

namespace Drupal\sp_create\Command;

use Drupal\sp_create\InitService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;

/**
 * Class InitCommand.
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="sp_create",
 *     extensionType="module"
 * )
 */
class InitCommand extends ContainerAwareCommand {

  /**
   * The init service.
   *
   * @var \Drupal\sp_create\InitService
   */
  protected $initService;

  /**
   * Constructs a new CloneBundleCommand object.
   *
   * @param \Drupal\sp_create\InitService $init_service
   *   The init service.
   */
  public function __construct(InitService $init_service) {
    parent::__construct();
    $this->initService = $init_service;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sp_create:init')
      ->setDescription($this->trans('commands.sp_create.init.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info($this->trans('commands.sp_create.init.messages.start'));
    foreach ($this->initService->init() as $message) {
      $this->getIo()->info($message);
    }
    $this->getIo()->info($this->trans('commands.sp_create.init.messages.success'));
  }

}
