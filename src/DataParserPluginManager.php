<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class AuthenticationFormPluginManager.
 *
 * @package Drupal\feeds_migrate
 */
class DataParserPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new AuthenticationPluginManager.
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
    parent::__construct('Plugin/feeds_migrate/data_parser', $namespaces, $module_handler, 'Drupal\feeds_migrate\DataParserFormInterface', 'Drupal\feeds_migrate\Annotation\DataParserForm');

    $this->alterInfo('feeds_migrate_data_parser_info');
    $this->setCacheBackend($cache_backend, 'feeds_migrate_data_parser');
  }

}
