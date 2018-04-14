<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\migrate_plus\AuthenticationPluginInterface;
use Drupal\migrate_plus\AuthenticationPluginManager;
use Drupal\migrate_plus\DataFetcherPluginInterface;
use Drupal\migrate_plus\DataFetcherPluginManager;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MigrationForm.
 *
 * @package Drupal\feeds_migrate_ui\Form
 */
class MigrationForm extends EntityForm {

  /**
   * @var \Drupal\migrate_plus\AuthenticationPluginManager
   */
  protected $authPlugins;

  /**
   * @var \Drupal\migrate_plus\DataFetcherPluginManager
   */
  protected $fetcherPlugins;

  /**
   * @var \Drupal\migrate_plus\DataParserPluginManager
   */
  protected $parserPlugins;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.migrate_plus.authentication'),
      $container->get('plugin.manager.migrate_plus.data_fetcher'),
      $container->get('plugin.manager.migrate_plus.data_parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AuthenticationPluginManager $authentication_plugins, DataFetcherPluginManager $fetcher_plugins, DataParserPluginManager $parser_plugins) {
    $this->authPlugins = $authentication_plugins;
    $this->fetcherPlugins = $fetcher_plugins;
    $this->parserPlugins = $parser_plugins;
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

    $auth_options = [];
    foreach ($this->authPlugins->getDefinitions() as $auth_plugin) {
      $auth_options[$auth_plugin['id']] = $auth_plugin['title'];
    }
    $form['authentication'] = [
      '#type' => 'select',
      '#title' => $this->t('Authentication Method'),
      '#options' => $auth_options,
      '#empty_option' => $this->t('- None -'),
    ];
    $form['authentication']['settings'] = [
      '#prefix' => '<div id="auth-settings">',
      '#suffix' => '</div>',
    ];

    $fetcher_options = [];
    foreach ($this->fetcherPlugins->getDefinitions() as $fetcher_plugin) {
      $fetcher_options[$fetcher_plugin['id']] = $fetcher_plugin['title'];
    }
    $form['data_fetcher'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Fetcher'),
      '#options' => $fetcher_options,
    ];

    $form['data_fetcher']['settings'] = [
      '#prefix' => '<div id="fetcher-settings">',
      '#suffix' => '</div>',
    ];

    $form['fetcher'] = [];
    $form['processor'] = [];

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => [],
    ];
    $form['entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Bundle'),
      '#options' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromRoute('entity.migration.collection'));
    return parent::save($form, $form_state);
  }

}
