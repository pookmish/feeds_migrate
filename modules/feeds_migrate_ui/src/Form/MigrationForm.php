<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
use Drupal\feeds_migrate_ui\FeedsMigrateUiFieldProcessorManager;
use Drupal\feeds_migrate_ui\FeedsMigrateUiParserSuggestion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MigrationForm.
 *
 * @package Drupal\feeds_migrate_ui\Form
 */
class MigrationForm extends EntityForm {

  /**
   * Steps used in form.
   */
  const STEP_ONE = 1;

  const STEP_TWO = 2;

  const STEP_THREE = 3;

  const STEP_FOUR = 4;

  const STEP_FINALIZE = 4;

  /**
   * @var \Drupal\feeds_migrate_ui\FeedsMigrateUiParserSuggestion
   */
  protected $parserSuggestion;

  /**
   * @var \Drupal\migrate_plus\AuthenticationPluginManager
   */
  protected $authPlugins;

  /**
   * @var \Drupal\migrate_plus\DataFetcherPluginManager
   */
  protected $fetcherPlugins;

  /**
   * @var \Drupal\feeds_migrate_ui\FeedsMigrateUiFieldProcessorManager
   */
  protected $fieldProcessorManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleManager;

  protected $currentStep = self::STEP_ONE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('feeds_migrate_ui.parser_suggestion'),
      $container->get('plugin.manager.feeds_migrate.authentication_form'),
      $container->get('plugin.manager.feeds_migrate.data_fetcher_form'),
      $container->get('plugin.manager.feeds_migrate_ui.field_processor'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FeedsMigrateUiParserSuggestion $parser_suggestion, AuthenticationFormPluginManager $authentication_plugins, DataFetcherFormPluginManager $fetcher_plugins, FeedsMigrateUiFieldProcessorManager $field_processor, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle, EntityFieldManager $field_manager) {
    $this->parserSuggestion = $parser_suggestion;
    $this->authPlugins = $authentication_plugins;
    $this->fetcherPlugins = $fetcher_plugins;
    $this->fieldProcessorManager = $field_processor;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleManager = $entity_bundle;
    $this->fieldManager = $field_manager;
  }

  /**
   * Get the title of the form at the given step.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translated page title for the current step.
   */
  public function getTitle() {
    switch ($this->currentStep) {
      case self::STEP_ONE:
        return $this->t('Data Selection');

      case self::STEP_TWO:
        return $this->t('Use existing data');

      case self::STEP_THREE:
        return $this->t('Entity Selection');

      default:
        return $this->t('Mapping Data');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    if ($this->currentStep == self::STEP_ONE && $this->entity->isNew()) {
      return [];
    }

    $actions = parent::actions($form, $form_state);
    if ($this->currentStep < self::STEP_FINALIZE) {
      $actions['submit']['#value'] = $this->t('Next');
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;

    switch ($this->currentStep) {
      case self::STEP_ONE:
        $this->getDataFetcherStep($form, $form_state);
        break;

      case self::STEP_TWO:
        $this->inputDataStep($form, $form_state);
        break;

      case self::STEP_THREE:
        $this->chooseEntityTypeStep($form, $form_state);
        break;

      case self::STEP_FOUR:
        $this->mapEntityFieldsStep($form, $form_state);
        break;

      default:
        $this->mapEntityFieldsStep($form, $form_state);
        break;
    }
    return $form;
  }

  /**
   * Get the data fetcher.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function getDataFetcherStep(array &$form, FormStateInterface $form_state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the @type.', [
        '@type' => $this->entity->getEntityType()->getLabel(),
      ]),
      '#required' => TRUE,
    ];

    $entity_class = $this->entity->getEntityType()->getClass();
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\\' . $entity_class . '::load',
        'replace_pattern' => '[^a-z0-9_]+',
        'replace' => '_',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['data'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Start with some data'),
      '#tree' => TRUE,
    ];

    foreach ($this->fetcherPlugins->getDefinitions() as $plugin_definition) {
      $form['data'][$plugin_definition['id']] = [
        '#type' => 'submit',
        '#value' => $plugin_definition['title'],
        '#name' => $plugin_definition['id'],
      ];
    }

  }

  /**
   * Start with some data.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function inputDataStep(array &$form, FormStateInterface $form_state) {
    $plugin_id = $form_state->getTriggeringElement()['#name'];

    try {
      /** @var \Drupal\feeds_migrate\DataFetcherFormInterface $fether_plugin */
      $fether_plugin = $this->fetcherPlugins->createInstance($plugin_id);
    }
    catch (\Exception $e) {
      $this->currentStep++;
      $form_state->setRebuild();
      return;
    }
    $element = $fether_plugin->buildForm($form, $form_state);
    $form[$plugin_id] = $element;
    $form['fetcher_plugin'] = [
      '#type' => 'hidden',
      '#value' => $plugin_id,
    ];

    $form['actions']['_skip'] = [
      '#type' => 'submit',
      '#value' => $this->t('Skip'),
      '#name' => '_skip',
    ];
  }

  /**
   * Build the form for the user to choose the entity type to import into.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function chooseEntityTypeStep(array &$form, FormStateInterface $form_state) {
    $entity_types = [];
    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_id => $definition) {
      if ($definition->entityClassImplements('Drupal\Core\Entity\FieldableEntityInterface')) {
        $entity_types[$entity_id] = $definition->getLabel();
      }
    }
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $entity_types,
      '#empty_option' => $this->t('- Choose -'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entityTypeChosenAjax',
        'wrapper' => 'entity-bundle',
      ],
    ];

    $form['entity_bundle'] = [
      '#prefix' => '<div id="entity-bundle">',
      '#suffix' => '</div>',
    ];
    if ($chosen_type = $form_state->getValue('entity_type')) {
      $form['entity_bundle']['#type'] = 'select';
      $form['entity_bundle']['#title'] = $this->t('Entity Bundle');
      $form['entity_bundle']['#required'] = TRUE;
      foreach ($this->bundleManager->getBundleInfo($chosen_type) as $id => $bundle) {
        $form['entity_bundle']['#options'][$id] = $bundle['label'];
      }
    }
  }

  /**
   * Ajax callback for entity type selection.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return mixed
   *   The entity bundle field.
   */
  public function entityTypeChosenAjax(array $form, FormStateInterface $form_state) {
    return $form['entity_bundle'];
  }

  /**
   * Build mapping form.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function mapEntityFieldsStep(array &$form, FormStateInterface $form_state) {
    $bundle_fields = $this->fieldManager->getFieldDefinitions($this->getEntityTypeFromMigration(), $this->getEntityBunddleFromMigration());

    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($bundle_fields as $field_id => $field) {
      $group = 'custom';
      if ($field instanceof BaseFieldDefinition || $field instanceof BaseFieldOverride) {
        $group = 'base';
      }

      if (!isset($form['fields'][$group])) {
        $form['fields'][$group] = [
          '#type' => 'fieldset',
          '#title' => $group,
        ];
      }

      /** @var \Drupal\feeds_migrate_ui\FeedsMigrateUiFieldProcessorInterface $plugin */
      $plugin = $this->fieldProcessorManager->getFieldPlugin($field, $this->entity);
      $form['fields'][$group][$field_id] = $plugin->buildConfigurationForm($form, $form_state);
    }
  }

  protected function getEntityTypeFromMigration() {
    $destination = $this->entity->destination['plugin'];
    list(, $entity_type) = explode(':', $destination);
    return $entity_type;
  }

  protected function getEntityBunddleFromMigration() {
    if (!empty($this->entity->source['constants']['bundle'])) {
      return $this->entity->source['constants']['bundle'];
    }
  }

  /**
   * Build unique selection form.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function uniqueItemStep(array $form, FormStateInterface $form_state) {
    $form['unique_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique Identifier'),
      '#description' => $this->t('Specify the unique item for each entry to allow for updates.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $form_state->cleanValues();
    $storage['submitted_data'][$this->currentStep] = $form_state->getValues();
    $form_state->setStorage($storage);

    if ($this->currentStep == self::STEP_FINALIZE) {
      parent::submitForm($form, $form_state);
      return;
    }

    $this->currentStep++;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $this->cleanEmptyFieldValues($storage);
    foreach ($storage['submitted_data'] as $step => $step_data) {
      switch ($step) {
        case self::STEP_ONE:
          $entity->set('label', $step_data['label']);
          $entity->set('id', $step_data['id']);

          break;

        case self::STEP_TWO:
          $source = $entity->get('source') ?: [];
          $source['data_fetcher_plugin'] = $step_data['fetcher_plugin'];
          // TODO call plugin submit.
          $source['urls'] = $step_data[$source['data_fetcher_plugin']]['url'];
          $entity->set('source', $source);
          break;

        case self::STEP_THREE:
          $entity->set('destination', ['plugin' => 'entity:' . $step_data['entity_type']]);

          $process = $entity->get('process') ?: [];
          $process['bundle'] = 'constants/bundle';
          $entity->set('process', $process);

          $source = $entity->get('source') ?: [];
          $source['constants']['bundle'] = $step_data['entity_bundle'];
          $entity->set('source', $source);

          break;

        default:
          $process = $entity->get('process') ?: [];
          $source = $entity->get('source') ?: [];

          foreach ($step_data['fields'] as $fields) {
            foreach ($fields as $field => $field_paths) {
              if (is_array($field_paths)) {
                foreach ($field_paths as $column => $selector) {
                  $source['fields'][] = [
                    'name' => "{$field}__$column",
                    'label' => "{$field}__$column",
                    'selector' => $selector,
                  ];

                  $process["$field/$column"] = "{$field}__$column";
                }
              }
              else {
                $source['fields'][] = [
                  'name' => $field,
                  'label' => $field,
                  'selector' => $field_paths,
                ];
                $process[$field] = $field;
              }
            }
          }

          $entity->set('process', $process);
          $entity->set('source', $source);

          break;
      }
    }
  }

  /**
   * Remove empty values.
   *
   * @param mixed $values
   *   Anything other than an object.
   */
  protected function cleanEmptyFieldValues(&$values) {
    if (!is_array($values)) {
      return;
    }
    foreach ($values as &$value) {
      $this->cleanEmptyFieldValues($value);
    }
    $values = array_filter($values);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->currentStep == self::STEP_FINALIZE) {
      return parent::save($form, $form_state);
    }
  }

}
