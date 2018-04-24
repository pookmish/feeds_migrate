<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
use Drupal\migrate_plus\Entity\Migration;
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
    $form['#tree'] = TRUE;
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

    $form['source']['data_fetcher_plugin'] = [
      '#type' => 'select',
      '#title' => $this->t('Data Fetcher'),
      '#options' => $this->fetcherPlugins->getOptions(),
      '#default_value' => $entity->source['data_fetcher_plugin'] ?: NULL,
      '#required' => TRUE,
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
    $form['destination']['plugin'] = [
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

    $form['source']['constants']['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Bundle'),
      '#options' => $bundles,
      '#default_value' => $entity->source['constants']['bundle'] ?: NULL,
      '#required' => TRUE,
      '#prefix' => '<div id="bundle-option">',
      '#suffix' => '</div>',
      '#weight' => 99,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $values = array_diff_key($values, $this->entity->getPluginCollections());
    }

    $this->copyNestedFormValuesToEntity($entity, $values);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param $values
   * @param array $key_path
   */
  protected function copyNestedFormValuesToEntity(EntityInterface $entity, $values, $key_path = []) {

    foreach ($values as $key => $value) {
      $new_path = $key_path;

      if (is_array($value)) {
        $new_path[] = $key;
        $this->copyNestedFormValuesToEntity($entity, $value, $new_path);
        continue;
      }

      $new_path[] = $key;

      $entity_key = array_shift($new_path);
      $original_array = $entity->get($entity_key);
      if (is_array($original_array)) {
        NestedArray::setValue($original_array, $new_path, $value);
        $entity->set($entity_key, $original_array);
        continue;
      }

      $entity->set($entity_key, $value);
    }
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
