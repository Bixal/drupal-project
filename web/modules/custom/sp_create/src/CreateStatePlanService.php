<?php

namespace Drupal\sp_create;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityStorageException;
use Psr\Log\LoggerInterface;

/**
 * Class CreateStatePlanService.
 */
class CreateStatePlanService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new CreateStatePlanService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * Create a state plan node for each state group node.
   *
   * This method uses the name of each group + the plan year given to create
   * a state plan for each state group. It is safe to run multiple times and
   * will not create multiples. The plan year is a hidden field that only
   * admins can access that will be used to determine if the state plan has been
   * created yet. Only this script should be setting the plan year value.
   *
   * @param string|int $planYear
   *   A four character year.
   * @param null|\Drupal\Console\Core\Style\DrupalStyle $io
   *   Optional object to allow for console output.
   * @param bool $debugInfo
   *   Optional parameter to allow logging debug messages to watchdog.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createFromStateGroups($planYear, $io = NULL, $debugInfo = FALSE) {
    $this->massagePlanYear($planYear);
    // Run time logger that needs the IO object to allow printing to the
    // console.
    $logger = new DebugLogger($this->logger, $debugInfo, $io);
    $group_storage = $this->entityTypeManager->getStorage('group');
    $state_groups = $group_storage
      ->getQuery()
      ->condition('type', 'state')
      ->execute();

    if (!empty($state_groups)) {
      $logger->debug('Attempting to create state plans for @state_group_cnt state groups for plan year @plan_year.', [
        '@state_group_cnt' => count($state_groups),
        '@plan_year' => $planYear,
      ]);
      // The moderation state constraint validator will fail if we don't
      // log in a user that has access to save from draft > draft.
      $author = User::load(4);
      user_login_finalize($author);
      // Keep track of all state groups that had a state plan created for them.
      $created = [];
      // Keep track of all state groups that already had a state plan for the
      // given plan year.
      $skipped = [];
      // Process each state group node.
      foreach ($state_groups as $state_gid) {
        /** @var \Drupal\group\Entity\Group $state_group_entity */
        $state_group_entity = $group_storage->load($state_gid);
        // Get the title of the group node, this will be used for the state
        // plan title.
        $state_group_title = $state_group_entity->label();
        if (empty($this->getStatePlanByPlanYear($state_group_entity, $planYear))) {
          /** @var \Drupal\node\Entity\Node $state_plan_entity */
          $state_plan_entity = $this->entityTypeManager->getStorage('node')->create([
            'type'        => 'state_plan',
            'title'       => sprintf('%s - %d', $state_group_title, $planYear),
            'field_plan_year' => $planYear,
          ]);
          $state_plan_entity->setOwner($author);
          $state_plan_entity->enforceIsNew(TRUE);
          // Make sure that all requirements for node creation were met.
          $violations = $state_plan_entity->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $error = " Field: " . $violation->getPropertyPath();
              $invalid_value = $violation->getInvalidValue();
              if (is_scalar($invalid_value)) {
                $error .= " Value: " . $invalid_value;
              }
              $error .= " Message: " . (string) $violation->getMessage();
              $logger->error('Unable to create a state plan for @state_group_label for plan year @plan_year. Error: @error', [
                '@state_group_label' => $state_group_title,
                '@plan_year' => $planYear,
                '@error' => $error,
              ]);
            }
          }
          else {
            try {
              // Save the node and add it as group content.
              $state_plan_entity->save();
              $state_group_entity->addContent($state_plan_entity, 'group_node:' . $state_plan_entity->getType());
            }
            catch (EntityStorageException $exception) {
              $logger->error('Unable to create a state plan for @state_group_label for plan year @plan_year. Error: @error', [
                '@state_group_label' => $state_group_title,
                '@plan_year' => $planYear,
                '@error' => $exception->getMessage(),
              ]);
            }
            $created[] = $state_group_title;
          }
        }
        else {
          $skipped[] = $state_group_title;
        }
      }
      $logger->info('The following state groups had a state plan created for them for plan year @plan_year: @states', [
        '@plan_year' => $planYear,
        '@states' => count($created) ? implode(', ', $created) : 'none',
      ]);
      $logger->info('The following state groups already had a state plan for plan year @plan_year: @states', [
        '@plan_year' => $planYear,
        '@states' => count($skipped) ? implode(', ', $skipped) : 'none',
      ]);
    }
    else {
      $logger->info('No state plans will be created for plan year @plan_year, no state groups were found.', [
        '@plan_year' => $planYear,
      ]);
    }
  }

  /**
   * Retrieve the node ID of the state plan in the given group and plan year.
   *
   * @param \Drupal\group\Entity\Group $stateGroup
   *   The current state group.
   * @param string|int $planYear
   *   A four character year.
   *
   * @return int
   *   The nid of the state plan for the current year, 0 if none exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Exception
   */
  public function getStatePlanByPlanYear(Group $stateGroup, $planYear) {
    $this->massagePlanYear($planYear);
    $state_plan_nids = [];
    // Get all the state plan nids in the current group that correspond to
    // the group relations.
    /** @var \Drupal\group\Entity\GroupContent $state_group_content_entity */
    foreach ($stateGroup->getContent('group_node:state_plan') as $state_group_content_entity) {
      $state_plan_nids[] = $state_group_content_entity->get('entity_id')->get(0)->getValue()['target_id'];
    }
    // There is no need to check by plan year if a group has no state plans.
    if (empty($state_plan_nids)) {
      $return = [];
    }
    else {
      // Determine if there is already a state plan node for the current plan
      // year for the current state group.
      $return = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->latestRevision()
        ->condition('nid', $state_plan_nids, 'in')
        ->condition('field_plan_year', $planYear, '=')
        ->execute();
    }
    if (count($return) > 1) {
      throw new \Exception(sprintf('There is more than one state plan for plan year %d in group %s (%d).', $planYear, $stateGroup->label(), $stateGroup->id()));
    }
    elseif (empty($return)) {
      return 0;
    }
    return current($return);
  }

  /**
   * Force plan year to be a 4 digit year or 0 on invalid.
   *
   * @param string|int $planYear
   *   A four character year.
   */
  protected function massagePlanYear(&$planYear) {
    // Force plan year to 0 if it's not year like.
    $planYear = (int) $planYear;
    if (strlen((string) $planYear) !== 4) {
      $planYear = 0;
    }
  }

}
