<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\migrate_plus\Entity\MigrationInterface;

/**
 * Class FeedsMigrateUiFieldFormManager.
 *
 * @package Drupal\feeds_migrate_ui
 */
class FeedsMigrateUiFieldManager extends DefaultPluginManager {

  /**
   * Constructs a new data_fetcherPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/feeds_migrate/field',
      $namespaces,
      $module_handler,
      'Drupal\feeds_migrate_ui\FeedsMigrateUiFieldInterface',
      'Drupal\feeds_migrate_ui\Annotation\FeedsMigrateUiField');

    $this->alterInfo('feeds_migrate_ui_field_info');
    $this->setCacheBackend($cache_backend, 'feeds_migrate_ui_field');
  }

  /**
   * Get a plugin for a give field type.
   *
   * @param FieldDefinitionInterface $field
   *   The field definition.
   *
   * @return bool|object
   *   The plugin if a form plugin is defined, false if none.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getFieldPlugin(FieldDefinitionInterface $field, MigrationInterface $entity) {
    $config = [
      'field' => $field,
      'entity' => $entity,
    ];

    foreach ($this->getDefinitions() as $plugin_definition) {
      if (in_array($field->getType(), $plugin_definition['fields'])) {
        return $this->createInstance($plugin_definition['id'], $config);
      }
    }

    return $this->createInstance('default', $config);
  }

}
