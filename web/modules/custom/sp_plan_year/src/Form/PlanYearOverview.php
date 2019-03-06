<?php

namespace Drupal\sp_plan_year\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\sp_create\PlanYearInfo;
use Drupal\sp_retrieve\CustomEntitiesService;
use Drupal\sp_retrieve\NodeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sp_plan_year\Entity\PlanYearEntity;

/**
 * Class PlanYearOverview.
 */
class PlanYearOverview extends FormBase {

  /**
   * The custom entities retrieval service.
   *
   * @var \Drupal\sp_retrieve\CustomEntitiesService
   */
  protected $customEntitiesRetrieval;

  /**
   * The node retrieval service.
   *
   * @var \Drupal\sp_retrieve\NodeService
   */
  protected $nodeService;

  /**
   * PlanYearEntityWizardForm constructor.
   *
   * @param \Drupal\sp_retrieve\CustomEntitiesService $custom_entities_retrieval
   *   Service used to retrieve data on custom entities.
   * @param \Drupal\sp_retrieve\NodeService $node_service
   *   The node retrieval service.
   */
  public function __construct(CustomEntitiesService $custom_entities_retrieval, NodeService $node_service) {
    $this->customEntitiesRetrieval = $custom_entities_retrieval;
    $this->nodeService = $node_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sp_retrieve.custom_entities'),
      $container->get('sp_retrieve.node')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'plan_year_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $plan_year = NULL;
    if (!empty($form_state->getValue('plan_years'))) {
      $plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, $form_state->getValue('plan_years'));
    }
    $plan_year_labels = $this->customEntitiesRetrieval->labels(PlanYearEntity::ENTITY);
    $route_match = $this->getRouteMatch();
    if (NULL === $plan_year) {
      /** @var \Drupal\sp_plan_year\Entity\PlanYearEntity $plan_year */
      $plan_year = $route_match->getParameter(PlanYearEntity::ENTITY);
    }
    $plan_year_info = FALSE;
    if (NULL === $plan_year) {
      /** @var \Drupal\taxonomy\Entity\Term $section_term */
      $section_term = $this->routeMatch->getParameter('taxonomy_term');
      if (NULL !== $section_term) {
        $plan_year_info = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($section_term->getVocabularyId());
      }
    }
    if (NULL === $plan_year) {
      /** @var \Drupal\taxonomy\Entity\Vocabulary $section_vocabulary */
      $section_vocabulary = $this->routeMatch->getParameter('taxonomy_vocabulary');
      if (NULL !== $section_vocabulary) {
        $plan_year_info = PlanYearInfo::getPlanYearIdAndSectionIdFromVid($section_vocabulary->id());
      }
    }
    if (FALSE !== $plan_year_info) {
      $plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, $plan_year_info['plan_year_id']);
    }
    asort($plan_year_labels);
    $plan_year_labels = array_reverse($plan_year_labels, TRUE);
    if (!empty($plan_year_labels)) {
      if (NULL === $plan_year) {
        $plan_year = $this->customEntitiesRetrieval->single(PlanYearEntity::ENTITY, current(array_keys($plan_year_labels)));
      }
      $form['wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'data-wrapper'],
      ];
      $form['wrapper']['plan_years'] = [
        '#type' => 'select',
        '#title' => $this->t('Change to a different plan year'),
        '#options' => $plan_year_labels,
        '#empty_option' => $this->t('- Choose a plan year -'),
        '#default_value' => NULL !== $plan_year ? $plan_year->id() : '',
        '#ajax' => [
          'callback' => [$this, 'changePlanYear'],
          'wrapper' => 'data-wrapper',
        ],
      ];
    }
    $red_link = ['attributes' => ['class' => ['button button--danger']]];
    $quick_links = [
      Link::createFromRoute($this->t('Create a new plan year'), 'entity.plan_year.add_form', [], !empty($plan_year_labels) ? [] : $red_link)->toString(),
      Link::createFromRoute($this->t('All plan years'), 'entity.plan_year.collection')->toString(),
    ];
    if (NULL !== $plan_year) {
      $sections = [];
      /** @var \Drupal\sp_section\Entity\SectionEntity $section */
      foreach ($plan_year->getSections() as $section) {
        $sections[] = Link::createFromRoute($section->label(), 'entity.taxonomy_vocabulary.overview_form', ['taxonomy_vocabulary' => PlanYearInfo::createSectionVocabularyId($plan_year->id(), $section->id())])->toString();
      }
      $form['wrapper']['sections'] = [
        '#type' => 'markup',
        '#markup' => !empty($sections) ? '<strong>Add, remove, and rearrange the hierarchy in a plan year section</strong><ul><li>' . implode('</li><li>', $sections) . '</li></ul><small>The above allows one to create the questions and hierarchy of the plan. Modifying these will cause content that is required to be created. One can finish adding large chunks of a section, then come back and create it all or create it one at a time.</small>' : $this->t('No sections added yet.'),
      ];
      $link_text = empty($sections) ? $this->t('No sections in this plan year, add sections here') : $this->t('Add or remove sections from this plan year');
      $quick_links[] = Link::createFromRoute($link_text, 'entity.plan_year.wizard', [PlanYearEntity::ENTITY => $plan_year->id()], empty($sections) ? $red_link : [])->toString();
      // @TODO: Create a method canCreateStatePlanContent() that encompasses
      // all the logic below, just like
      // nodeService->canCopyStatePlanYearAnswer().
      $show_remove_orphans_links = FALSE;
      $show_missing_answers = FALSE;
      $missing_plans_and_sections = $this->nodeService->getGroupsMissingStatePlanYearsAndStatePlanYearSections($plan_year->id());
      // If empty, there is nothing made yet, if some is returned, there might
      // be some created and some still missing.
      $show_missing_plans_and_sections_link = empty($missing_plans_and_sections) ? TRUE : !empty($missing_plans_and_sections) && $missing_plans_and_sections['at_least_one_missing'];
      // Don't show orphans or answers if content is not created yet.
      if (FALSE === $show_missing_plans_and_sections_link) {
        $orphans = $this->nodeService->getOrphansStatePlanYearAnswers();
        if (!empty($orphans)) {
          $show_remove_orphans_links = TRUE;
        }
        $missing_answers = $this->nodeService->getMissingPlanYearAnswers($plan_year->id());
        if (!empty($missing_answers)) {
          $show_missing_answers = TRUE;
        }
      }
      if ($show_missing_plans_and_sections_link) {
        $quick_links[] = Link::createFromRoute($this->t('There is missing plans or sections in this plan year, create here'), 'entity.plan_year.content', [PlanYearEntity::ENTITY => $plan_year->id()], $red_link)->toString();
      }
      if ($show_missing_answers) {
        $quick_links[] = Link::createFromRoute($this->t('There are missing answers in this plan year, create here'), 'entity.plan_year.content', [PlanYearEntity::ENTITY => $plan_year->id()], $red_link)->toString();
      }
      if ($show_remove_orphans_links) {
        $quick_links[] = Link::createFromRoute($this->t('There are orphan answers in this plan year, remove here'), 'entity.plan_year.content', [PlanYearEntity::ENTITY => $plan_year->id()], $red_link)->toString();
      }
      // If all content, orphans, and answers are created, show the manage state
      // plans view.
      if (empty($show_missing_plans_and_sections_link) && empty($show_remove_orphans_links) && empty($show_missing_answers) && $state_plans_year_nid = $this->nodeService->getStatePlansYearByPlanYear($plan_year->id())) {
        $quick_links[] = Link::createFromRoute($this->t('Manage State Plans'), 'view.manage_plans.moderated_content', [], ['query' => ['plan-year' => $state_plans_year_nid]])->toString();
      }

    }
    $form['wrapper']['quick_links'] = [
      '#type' => 'markup',
      '#markup' => '<hr /><strong>Quick Links</strong><ul><li>' . implode('</li><li>', $quick_links) . '</li></ul>',
    ];

    return $form;
  }

  /**
   * Ajax handler for changing the plan year.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The form to be built.
   */
  public function changePlanYear(array $form, FormStateInterface $form_state) {
    // The entire form is to be reloaded, the value of the new plan year is
    // retrieved from the form state in the build method.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    throw new \Exception('This form is not meant to be submitted.');
  }

}
