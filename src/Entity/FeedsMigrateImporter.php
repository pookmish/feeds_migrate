<?php

namespace Drupal\feeds_migrate\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;

/**
 * Feeds Migrate Source configuration entity.
 *
 * @ConfigEntityType(
 *   id = "feeds_migrate_importer",
 *   label = @Translation("Feeds Migrate Importer"),
 *   handlers = {
 *     "list_builder" = "Drupal\feeds_migrate\FeedsMigrateImporterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterForm",
 *       "edit" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterForm",
 *       "delete" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterDeleteForm",
 *       "enable" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterEnableForm",
 *       "disable" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterDisableForm"
 *     },
 *   },
 *   config_prefix = "importer",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/feeds-migrate/{feeds_migrate_importer}",
 *     "edit-form" = "/admin/content/feeds-migrate/{feeds_migrate_importer}",
 *     "delete-form" = "/admin/content/feeds-migrate/{feeds_migrate_importer}/delete",
 *     "enable" = "/admin/content/feeds-migrate/{feeds_migrate_importer}/enable",
 *     "disable" = "/admin/content/feeds-migrate/{feeds_migrate_importer}/disable"
 *   }
 * )
 */
class FeedsMigrateImporter extends ConfigEntityBase implements FeedsMigrateImporterInterface {

}
