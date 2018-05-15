<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface FeedsMigrateUiProcessInterface.
 *
 * @package Drupal\feeds_migrate_ui
 */
interface FeedsMigrateUiProcessInterface extends PluginFormInterface {

  /**
   * Fill This out.
   *
   * @return mixed
   *   Fill out.
   */
  public function getSummary();

  /**
   * @return string
   */
  public function label();

}
