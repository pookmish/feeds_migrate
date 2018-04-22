<?php

namespace Drupal\feeds_migrate\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Url;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate_plus\Entity\Migration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeedsMigrateImporterForm.
 *
 * @package Drupal\feeds_migrate\Form
 */
class FeedsMigrateImporterForm extends EntityForm {

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @var \Drupal\feeds_migrate\AuthenticationFormPluginManager
   */
  protected $authPluginManager;

  /**
   * @var \Drupal\feeds_migrate\DataFetcherFormPluginManager
   */
  protected $dataFetcherPluginManager;

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.feeds_migrate.authentication_form'),
      $container->get('plugin.manager.feeds_migrate.data_fetcher_form'),
      $container->get('date.formatter'),
      $container->get('queue'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AuthenticationFormPluginManager $auth_plugins, DataFetcherFormPluginManager $data_fetcher_plugins, DateFormatterInterface $date_formatter, QueueFactory $queue, ModuleHandlerInterface $module_handler) {
    $this->authPluginManager = $auth_plugins;
    $this->dataFetcherPluginManager = $data_fetcher_plugins;
    $this->dateFormatter = $date_formatter;
    $this->queueFactory = $queue;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the @type.', [
        '@type' => $entity->getEntityType()->getLabel(),
      ]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\\' . $entity->getEntityType()->getClass() . '::load',
        'replace_pattern' => '[^a-z0-9_]+',
        'replace' => '_',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $sources = [];
    /** @var Migration $migration */
    foreach (Migration::loadMultiple() as $migration) {
      $sources[$migration->id()] = $migration->label();
    }
    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration Source'),
      '#options' => $sources,
      '#default_value' => $entity->source,
      '#required' => TRUE,
      '#attributes' => [
        'disabled' => !empty($entity->source),
      ],
    ];

    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];
    $form['period_settings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Settings'),
      '#tree' => FALSE,
    ];

    $times = [
      900,
      1800,
      3600,
      10800,
      21600,
      43200,
      86400,
      259200,
      604800,
      2419200,
    ];
    $times = array_combine($times, $times);
    foreach ($times as &$time) {
      $time = $this->dateFormatter->formatInterval($time);
      $time = $this->t('Every @time', ['@time' => $time]);
    }

    $additonal_options = [
      FeedsMigrateImporterInterface::SCHEDULE_NEVER => $this->t('Off'),
      FeedsMigrateImporterInterface::SCHEDULE_CONTINUOUSLY => $this->t('As often as possible'),
    ];

    $form['period_settings']['importPeriod'] = [
      '#type' => 'select',
      '#title' => $this->t('Import period'),
      '#options' => $additonal_options + $times,
      '#description' => $this->t('Choose how often a importer should run.'),
      '#default_value' => $entity->importPeriod,
    ];
    $form['processor_settings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Processor Settings'),
      '#tree' => FALSE,
    ];
    $form['processor_settings']['existing'] = [
      '#type' => 'radios',
      '#title' => $this->t('Update Existing Content'),
      '#default_value' => $entity->existing ?: 0,
      '#options' => [
        0 => $this->t('Do not update existing content'),
        1 => $this->t('Replace existing content'),
        2 => $this->t('Update existing content'),
      ],
    ];
    $form['processor_settings']['orphans'] = [
      '#type' => 'select',
      '#title' => $this->t('Orphaned Items'),
      '#default_value' => $entity->orphans ?: '__keep',
      '#options' => [
        '_keep' => $this->t('Keep'),
        '_delete' => $this->t('Delete'),
      ],
    ];

    $form['dataFetcherSettings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Data Fetcher Settings'),
      '#tree' => TRUE,
    ];

    foreach ($this->dataFetcherPluginManager->getDefinitions() as $id => $data_fetcher) {
      $plugin = $this->dataFetcherPluginManager->createInstance($id);
      $element = $plugin->buildForm($form['dataFetcherSettings'], $form_state);
      $form['dataFetcherSettings'][$id] = $element;
    }

    $form['authSettings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Authentication Settings'),
      '#tree' => TRUE,
    ];

    if ($this->moduleHandler->moduleExists('key')) {
      $form['authSettings']['auth_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Authentication Type'),
        '#options' => [],
        '#empty_option' => $this->t('- None -'),
        '#default_value' => key($entity->authSettings ?: []),
      ];

      foreach ($this->authPluginManager->getDefinitions() as $id => $authentication) {
        $plugin = $this->authPluginManager->createInstance($id);
        $element = $plugin->buildForm($form['authSettings'], $form_state);
        $element['secret_key']['#states'] = [
          'visible' => [
            ':input[name*="auth_type"]' => ['value' => $id],
          ],
        ];
        $form['authSettings'][$id] = $element;
        $form['authSettings']['auth_type']['#options'][$id] = $authentication['title'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $auth_type = $form_state->getValue(['authSettings', 'auth_type']);
    if (empty($auth_type)) {
      $form_state->unsetValue('authSettings');
    }
    else {
      $auth_settings = $form_state->getValue('authSettings');
      foreach (array_keys($auth_settings) as $id) {
        if ($id != $auth_type) {
          unset($auth_settings[$id]);
        }
      }
      $form_state->setValue('authSettings', $auth_settings);
    }

    $data_fetcher = $form_state->getValue('dataFetcherSettings');
    foreach (array_keys($data_fetcher) as $id) {

    }
    $form_state->setValue('dataFetcherSettings', $data_fetcher);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromRoute('entity.feeds_migrate_importer.collection'));
    return parent::save($form, $form_state);
  }

}
