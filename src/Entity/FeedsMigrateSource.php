<?php

namespace Drupal\feeds_migrate\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\feeds_migrate\FeedsMigrateSourceInterface;

/**
 * Feeds Migrate Importer configuration entity.
 *
 * @ConfigEntityType(
 *   id = "feeds_migrate_source",
 *   label = @Translation("Feeds Migrate Source"),
 *   config_prefix = "source",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   }
 * )
 */
class FeedsMigrateSource extends ConfigEntityBase implements FeedsMigrateSourceInterface {

}
