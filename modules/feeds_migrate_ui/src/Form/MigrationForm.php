<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
use Drupal\feeds_migrate\DataParserPluginManager;
use Drupal\feeds_migrate_ui\FeedsMigrateUiFieldManager;
use Drupal\feeds_migrate_ui\FeedsMigrateUiParserSuggestion;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MigrationForm.
 *
 * @package Drupal\feeds_migrate_ui\Form
 */
class MigrationForm extends EntityForm {

  /**
   * Form steps.
   */
  const STEP_ONE = 1;

  const STEP_TWO = 2;

  const STEP_THREE = 3;

  const STEP_FOUR = 4;

  const STEP_FINALIZE = 4;

  /**
   * Current step for the form.
   *
   * @var int
   */
  protected $currentStep = 1;

  /**
   * Fill This.
   *
   * @var \Drupal\feeds_migrate_ui\FeedsMigrateUiParserSuggestion
   */
  protected $parserSuggestion;

  /**
   * Fill This.
   *
   * @var \Drupal\migrate_plus\AuthenticationPluginManager
   */
  protected $authPlugins;

  /**
   * Fill This.
   *
   * @var \Drupal\migrate_plus\DataFetcherPluginManager
   */
  protected $fetcherPlugins;

  /**
   * Fill This.
   *
   * @var \Drupal\feeds_migrate_ui\FeedsMigrateUiFieldProcessorManager
   */
  protected $fieldProcessorManager;

  /**
   * Fill This.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Fill This.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * Fill This.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleManager;

  /**
   * Fill This.
   *
   * @var \Drupal\feeds_migrate\DataParserPluginManager
   */
  protected $parserManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.feeds_migrate.data_parser_form'),
      $container->get('feeds_migrate_ui.parser_suggestion'),
      $container->get('plugin.manager.feeds_migrate.authentication_form'),
      $container->get('plugin.manager.feeds_migrate.data_fetcher_form'),
      $container->get('plugin.manager.feeds_migrate_ui.field'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DataParserPluginManager $parser_manager, FeedsMigrateUiParserSuggestion $parser_suggestion, AuthenticationFormPluginManager $authentication_plugins, DataFetcherFormPluginManager $fetcher_plugins, FeedsMigrateUiFieldManager $field_processor, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle, EntityFieldManager $field_manager) {
    $this->parserManager = $parser_manager;
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
    if ($this->currentStep == self::STEP_ONE) {
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
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function chooseEntityTypeStep(array &$form, FormStateInterface $form_state) {
    $parser_plugin_id = $this->entity->source['data_parser_plugin'] ?: NULL;
    if ($parser_plugin_id) {
      /** @var \Drupal\feeds_migrate\DataParserFormBase $parser_plugin */
      $parser_plugin = $this->parserManager->createInstance($parser_plugin_id);
      $form['parser'][$parser_plugin_id] = $parser_plugin->buildConfigurationForm($form, $form_state);
    }

    $form['ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique Selector Path'),
      '#default_value' => $this->getUniqueSelector($this->entity),
      '#required' => TRUE,
    ];

    $entity_types = [];
    /** @var \Drupal\Core\Entity\EntityTypeInterface $definition */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_id => $definition) {
      if ($definition->entityClassImplements('Drupal\Core\Entity\FieldableEntityInterface')) {
        $entity_types[$entity_id] = $definition->getLabel();
      }
    }

    $chosen_type = $form_state->getValue('entity_type') ?: $this->getEntityTypeFromMigration();

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $entity_types,
      '#default_value' => $chosen_type,
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
    $bundle = $this->getEntityBunddleFromMigration();

    if ($chosen_type) {
      $form['entity_bundle']['#type'] = 'select';
      $form['entity_bundle']['#title'] = $this->t('Entity Bundle');
      $form['entity_bundle']['#required'] = TRUE;
      $form['entity_bundle']['#default_value'] = $bundle;
      foreach ($this->bundleManager->getBundleInfo($chosen_type) as $id => $bundle) {
        $form['entity_bundle']['#options'][$id] = $bundle['label'];
      }
    }
  }

  /**
   * Get the unique value selector path.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Migration entity.
   *
   * @return string
   *   The selector path.
   */
  protected function getUniqueSelector(EntityInterface $entity) {
    $source = $entity->get('source');
    if (empty($source['ids'])) {
      return NULL;
    }
    $field_name = key($source['ids']);
    foreach ($source['fields'] as $field_selector) {
      if ($field_selector['name'] == $field_name) {
        return $field_selector['selector'];
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function mapEntityFieldsStep(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($this->getEntityTypeFromMigration());
    /** @var \Drupal\Core\Entity\ContentEntityType $entity_type */
    $entity_type = $entity_storage->getEntityType();

    $bundle_fields = $this->fieldManager->getFieldDefinitions($entity_type->id(), $this->getEntityBunddleFromMigration());
    $good_keys = ['published', 'label', 'uid'];
    foreach ($entity_type->get('entity_keys') as $key => $field_name) {
      if (in_array($key, $good_keys)) {
        continue;
      }
      unset($bundle_fields[$field_name]);
    }


    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Selectors'),
        $this->t('Processing Settings'),
      ],
    ];

    /** @var \Drupal\field\Entity\FieldConfig $field */
    foreach ($bundle_fields as $field_name => $field) {
      $table[$field_name] = $this->buildFieldRow($field, $form, $form_state);
    }

    $form['mapping'] = $table;
  }

  /**
   * Build the table field row.
   *
   * @param FieldDefinitionInterface $field
   *   Field definitino.
   * @param array $form
   *   Current form.
   * @param FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   The built field row.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function buildFieldRow(FieldDefinitionInterface $field, array $form, FormStateInterface $form_state) {
    $field_name = $field->getName();
    $label = $field->getLabel();

    $field_row = [
      'human_name' => [
        'data' => [
          '#plain_text' => $label,
        ],
      ],
    ];

    /** @var \Drupal\feeds_migrate_ui\FeedsMigrateUiFieldInterface $plugin */
    $plugin = $this->fieldProcessorManager->getFieldPlugin($field, $this->entity);
    $field_row['selectors']['data'] = $plugin->buildConfigurationForm($form, $form_state);

    // Base button element for the various plugin settings actions.
    $base_button = [
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#field_name' => $field_name,
    ];
    $field_row['settings_edit']['data'] = $base_button;

    $field_row['settings_edit']['data'] += [
      '#type' => 'image_button',
      '#name' => $field_name . '_settings_edit',
      '#src' => 'core/misc/icons/787878/cog.svg',
      '#attributes' => [
        'class' => ['field-plugin-settings-edit'],
        'alt' => $this->t('Edit'),
      ],
      '#op' => 'edit',
      // Do not check errors for the 'Edit' button, but make sure we get
      // the value of the 'plugin type' select.
      '#limit_validation_errors' => [['fields', $field_name, 'type']],
      '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
      '#suffix' => '</div>',
    ];

    return $field_row;
  }

  /**
   * Find the entity type the migration is importing into.
   *
   * @return string
   *   Machine name of the entity type eg 'node'.
   */
  protected function getEntityTypeFromMigration() {
    $destination = $this->entity->destination['plugin'];
    if (strpos($destination, ':') !== FALSE) {
      list(, $entity_type) = explode(':', $destination);
      return $entity_type;
    }
  }

  /**
   * The bundle the migration is importing into.
   *
   * @return string
   *   Entity type bundle eg 'article'.
   */
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
    if ($this->currentStep == self::STEP_FINALIZE) {
      parent::submitForm($form, $form_state);

      \Drupal::messenger()
        ->addMessage($this->t('Saved Migration %label', ['%label' => $this->entity->label()]));
      /** @var \Drupal\migrate_plus\Entity\Migration $entity */
      $form_state->setRedirect('entity.migration.collection');
      return;
    }

    $this->currentStep++;
    $form_state->cleanValues();
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    switch ($this->currentStep) {
      case self::STEP_ONE:
        $this->copyFormValuesToEntityStepOne($entity, $form, $form_state);
        break;

      case self::STEP_TWO:
        $this->copyFormValuesToEntityStepTwo($entity, $form, $form_state);
        break;

      case self::STEP_THREE:
        $this->copyFormValuesToEntityStepThree($entity, $form, $form_state);
        break;

      default:
        $this->copyFormValuesToEntityStepFour($entity, $form, $form_state);
        break;
    }
  }

  /**
   * Copies top-level form values to entity properties.
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function copyFormValuesToEntityStepOne(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    $source = $entity->get('source') ?: [];
    $source['plugin'] = 'url';
    $source['data_fetcher_plugin'] = $form_state->getTriggeringElement()['#name'];
    $entity->set('source', $source);
  }

  /**
   * Copies top-level form values to entity properties.
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntityStepTwo(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $source = $entity->get('source') ?: [];
    $fetcher_plugin_id = $source['data_fetcher_plugin'];
    /** @var \Drupal\feeds_migrate\DataFetcherFormInterface $fetcher_plugin */
    $fetcher_plugin = $this->fetcherPlugins->createInstance($fetcher_plugin_id);
    $parser_data = $fetcher_plugin->getParserData($form, $form_state);

    if ($parser_plugin = $this->parserSuggestion->getSuggestedParser($parser_data)) {
      $source = $entity->get('source');
      $source['data_parser_plugin'] = $parser_plugin->getPluginId();
      $source['urls'] = $parser_data;
      $entity->set('source', $source);
    }
  }

  /**
   * Copies top-level form values to entity properties.
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function copyFormValuesToEntityStepThree(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $source = $entity->get('source');
    if (!empty($source['data_parser_plugin'])) {
      /** @var \Drupal\feeds_migrate\DataParserFormInterface $parser_plugin */
      $parser_plugin = $this->parserManager->createInstance($source['data_parser_plugin']);
      $parser_plugin->copyFormValuesToEntity($entity, $form, $form_state);
    }

    if ($entity_type = $form_state->getValue('entity_type')) {
      $entity->set('destination', ['plugin' => 'entity:' . $entity_type]);
    }


    if ($entity_bundle = $form_state->getValue('entity_bundle')) {
      $source = $entity->get('source') ?: [];

      $id_selector = $form_state->getValue('ids');
      $source['ids'] = ['guid' => ['type' => 'string']];

      $source['fields'][] = [
        'name' => 'guid',
        'label' => 'guid',
        'selector' => $id_selector,
      ];

      $source['constants']['bundle'] = $entity_bundle;
      $entity->set('source', $source);

      $process = $entity->get('process') ?: [];
      $bundle_key = $this->getBundleKey();
      $process[$bundle_key] = 'constants/bundle';
      $entity->set('process', $process);
    }

  }

  /**
   * Get the bundle key for the configured entity type on the migration.
   *
   * @return string|null
   *   Bundle Key.
   */
  protected function getBundleKey() {
    try {
      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_storage */
      $entity_storage = $this->entityTypeManager->getStorage($this->getEntityTypeFromMigration());
    }
    catch (\Exception $e) {
      return NULL;
    }
    /** @var \Drupal\Core\Entity\ContentEntityType $entity_type */
    $entity_type = $entity_storage->getEntityType();
    return $entity_type->get('entity_keys')['bundle'] ?: NULL;
  }

  /**
   * Copies top-level form values to entity properties.
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntityStepFour(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->cleanEmptyFieldValues($values);
    $bundle_key = $this->getBundleKey();

    $process = $entity->get('process') ?: [];
    $process = [
      $bundle_key => $process[$bundle_key],
    ];

    $source = $entity->get('source') ?: [];
    $id_name = key($source['ids']);
    $guid_selector = NULL;
    foreach ($source['fields'] as $delta => $field) {
      if ($field['name'] == $id_name) {
        $guid_selector = $field;
        break;
      }
    }
    $source['fields'] = $guid_selector ? [$guid_selector] : [];

    foreach ($values['mapping'] as $field => $field_data) {
      $selectors = $field_data['selectors']['data'];

      if (is_string($selectors)) {
        $source['fields'][] = [
          'name' => $field,
          'label' => $field,
          'selector' => $selectors,
        ];
        $process[$field] = $field;
      }
      else {
        foreach ($selectors as $column => $selector) {
          $source['fields'][] = [
            'name' => "{$field}__$column",
            'label' => "{$field}__$column",
            'selector' => $selector,
          ];

          $process["$field/$column"] = "{$field}__$column";
        }
      }
    }

    $entity->set('process', $process);
    $entity->set('source', $source);
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
