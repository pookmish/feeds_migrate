<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining  entities.
 */
interface FeedsMigrateImporterInterface extends ConfigEntityInterface {

  /**
   * Indicates that a feed should never be scheduled.
   */
  const SCHEDULE_NEVER = -1;

  /**
   * Indicates that a feed should be imported as often as possible.
   */
  const SCHEDULE_CONTINUOUSLY = 0;

}
