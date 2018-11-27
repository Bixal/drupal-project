<?php

namespace Drupal\sp_create\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\sp_create\CreateStatePlanService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class CreateStatePlanCommand.
 *
 * @Drupal\Console\Annotations\DrupalCommand (
 *     extension="sp_create",
 *     extensionType="module"
 * )
 */
class CreateStatePlanCommand extends ContainerAwareCommand {

  /**
   * Drupal\sp_create\CreateStatePlanService definition.
   *
   * @var \Drupal\sp_create\CreateStatePlanService
   */
  protected $createStatePlan;

  /**
   * Constructs a new CreateCommand object.
   *
   * @param \Drupal\sp_create\CreateStatePlanService $createStatePlanService
   *   The expire state plan service.
   */
  public function __construct(CreateStatePlanService $createStatePlanService) {
    parent::__construct();
    $this->createStatePlan = $createStatePlanService;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sp_create:state_plan')
      ->setDescription($this->trans('commands.sp_create.state_plan.description'))
      ->addOption(
        'log',
        NULL,
        InputOption::VALUE_OPTIONAL,
        $this->trans('commands.sp_create.state_plan.options.log'),
        1
      )
      ->addArgument(
        'plan-year',
        InputArgument::REQUIRED,
        $this->trans('commands.sp_create.state_plan.arguments.planyear')
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $log = $input->getOption('log');
    $plan_year = $input->getArgument('plan-year');
    $this->createStatePlan->createFromStateGroups($plan_year, $this->getIo(), $log);
    $this->getIo()->info($this->trans('commands.sp_create.state_plan.messages.success'));
  }

}
