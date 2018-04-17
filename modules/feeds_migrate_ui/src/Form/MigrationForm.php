<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
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
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.feeds_migrate.authentication_form'),
      $container->get('plugin.manager.feeds_migrate.data_fetcher_form'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(AuthenticationFormPluginManager $authentication_plugins, DataFetcherFormPluginManager $fetcher_plugins, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle) {
    $this->authPlugins = $authentication_plugins;
    $this->fetcherPlugins = $fetcher_plugins;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleManager = $entity_bundle;
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
    $form['group'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group'),
      '#default_value' => $entity->migration_group,
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
      '#ajax' => [
        'callback' => '::authenticationSettingsForm',
        'wrapper' => 'authentication-settings',
        'effect' => 'fade',
      ],
    ];
    $form['authentication_settings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="authentication-settings">',
      '#suffix' => '</div>',
    ];

    $fetcher_options = [];
    foreach ($this->fetcherPlugins->getDefinitions() as $fetcher_plugin) {
      $fetcher_options[$fetcher_plugin['id']] = $fetcher_plugin['title'];
    }
    $form['data_fetcher_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Fetcher'),
      '#options' => $fetcher_options,
      '#default_value' => $entity->source['data_fetcher_plugin'] ?: NULL,
      '#required' => TRUE,
    ];

    $form['data_fetcher']['settings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="fetcher-settings">',
      '#suffix' => '</div>',
    ];

    $form['fetcher'] = [];
    $form['processor'] = [];

    /** @var \Drupal\Core\Entity\ContentEntityTypeInterface $entity_type */
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $entity_types['entity:' . $entity_type->id()] = $entity_type->getLabel();
      }
    }

    asort($entity_types);
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $entity_types,
      '#default_value' => $entity->destination['plugin'] ?: NULL,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::entityTypeOptionsForm',
        'wrapper' => 'bundle-option',
        'effect' => 'fade',
      ],
    ];

    $selected_type = str_replace('entity:', '', $entity->destination['plugin']);
    $bundles = $this->bundleManager->getBundleInfo($selected_type);

    foreach ($bundles as &$bundle) {
      $bundle = $bundle['label'];
    }
    $form['entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Bundle'),
      '#options' => $bundles,
      '#default_value' => $entity->process['bundle'] ?: NULL,
      '#required' => TRUE,
      '#prefix' => '<div id="bundle-option">',
      '#suffix' => '</div>',
    ];

    $form['#process'][] = [$this, 'processSettingsForms'];
    return $form;
  }

  public function processSettingsForms(array &$element, FormStateInterface $form_state, array &$complete_form) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromRoute('entity.migration.collection'));
    return parent::save($form, $form_state);
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function authenticationSettingsForm(&$form, FormStateInterface $form_state) {
    return $form['authentication_settings'];
  }

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public static function entityTypeOptionsForm(&$form, FormStateInterface $form_state) {
    return $form['entity_bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
