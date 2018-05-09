<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class FeedsMigrateUiFieldProcessorManager.
 *
 * @package Drupal\feeds_migrate_ui
 */
class FeedsMigrateUiFieldProcessorManager extends DefaultPluginManager {

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
    parent::__construct('Plugin/feeds_migrate/field_processor',
      $namespaces,
      $module_handler,
      'Drupal\feeds_migrate_ui\FeedsMigrateUiFieldProcessorInterface',
      'Drupal\feeds_migrate_ui\Annotation\FeedsMigrateUiFieldProcessor');

    $this->alterInfo('field_processor_form_info');
    $this->setCacheBackend($cache_backend, 'migrate_plus_plugins_field_processor_form');
  }

  /**
   * Get a plugin for a give field type.
   *
   * @param FieldDefinitionInterface $field
   *   The field definition.
   *
   * @return bool|object
   *   The plugin if a processor plugin is defined, false if none.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getFieldPlugin(FieldDefinitionInterface $field) {
    foreach ($this->getDefinitions() as $plugin_definition) {
      if (in_array($field->getType(), $plugin_definition['fields'])) {
        return $this->createInstance($plugin_definition['id'], ['field' => $field]);
      }
    }
    return $this->createInstance('default_processor', ['field' => $field]);
  }

}
