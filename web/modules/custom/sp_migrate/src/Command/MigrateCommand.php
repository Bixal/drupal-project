<?php

namespace Drupal\sp_migrate\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Console\Core\Command\ContainerAwareCommand;
use Drupal\Console\Annotations\DrupalCommand;
use Drupal\Core\Database\Connection;

/**
 * Class MigrateCommand.
 *
 * @DrupalCommand (
 *     extension="sp_migrate",
 *     extensionType="module"
 * )
 */
class MigrateCommand extends ContainerAwareCommand {

  /**
   * The database connection needed to query the database.
   *
   * @var \Drupal\Core\Database\Connection
   */

  protected $database;

  /**
   * Constructs a new MyHandler.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The logger interface.
   */
  public function __construct(Connection $database = NULL) {
    parent::__construct();
    if ($database != NULL) {
      $this->database = $database;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('sp_migrate:migrate')
      ->setDescription($this->trans('commands.sp_migrate.migrate.description'));
  }

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output);
    $this->getIo()->info('initialize');
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info('interact');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->getIo()->info('execute');
    $this->getIo()->info($this->trans('commands.sp_migrate.migrate.messages.success'));
    var_dump($this->queryBuilder('ak', '2018', '33121',
                                '2001', '000030275'));
  }

  /**
   * {@inheritdoc}
   */
  protected function queryBuilder($state, $year, $grant_award_id, $form_id, $row) {
    $query = $this->database->query(
      'SELECT narr_text 
              FROM usp_answer_narr 
              WHERE
                state = :value1 AND 
                year = :value2 AND 
                grant_award_id = :value3 AND 
                form_id = :value4 AND 
                row = :value5',
      array(
        ':value1' => $state,
        ':value2' => $year,
        ':value3' => $grant_award_id,
        ':value4' => $form_id,
        ':value5' => $row,
      )
    );
    $data = $query->fetchField();

    return $data;
  }

}
