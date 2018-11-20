<?php

namespace Drupal\sp_expire\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\sp_expire\ExpireStatePlanService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class ExpireCommand.
 *
 * @DrupalCommand (
 *     extension="sp_expire",
 *     extensionType="module"
 * )
 */
class ExpireCommand extends ContainerAwareCommand {

  /**
   * Drupal\sp_expire\ExpireStatePlanService definition.
   *
   * @var \Drupal\sp_expire\ExpireStatePlanService
   */
  protected $expireStatePlan;

  /**
   * Constructs a new ExpireCommand object.
   *
   * @param \Drupal\sp_expire\ExpireStatePlanService $expire_state_plan
   *   The expire state plan service.
   */
  public function __construct(ExpireStatePlanService $expire_state_plan) {
    parent::__construct();
    $this->expireStatePlan = $expire_state_plan;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sp:expire')
      ->setDescription($this->trans('commands.sp.expire.description'))
      ->addOption(
        'log',
        null,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.sp.expire.options.log'),
        1
      )
      ->addArgument(
        'days-ago',
        InputArgument::OPTIONAL,
        $this->trans('commands.sp.expire.arguments.daysago')
      )
      ->setAliases(['expire']);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $log = $input->getOption('log');
    $daysAgo = $input->getArgument('days-ago');
    $messages = $this->expireStatePlan->expireModeratedContent($daysAgo, $log);
    $this->getIo()->info($this->trans('commands.sp.expire.messages.success'));
    foreach ($messages as $message) {
      $this->getIo()->info($message);
    }
  }
}
