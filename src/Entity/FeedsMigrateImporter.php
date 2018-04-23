<?php

namespace Drupal\feeds_migrate\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\feeds_migrate\FeedsMigrateExecutable;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate\MigrateMessage;

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
 *       "disable" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterDisableForm",
 *       "rollback" = "Drupal\feeds_migrate\Form\FeedsMigrateImporterRollbackForm"
 *     },
 *   },
 *   config_prefix = "importer",
 *   admin_permission = "administer feeds migrate importers",
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
 *     "disable" = "/admin/content/feeds-migrate/{feeds_migrate_importer}/disable",
 *     "import" = "/admin/content/feeds-migrate/{feeds_migrate_importer}/import",
 *     "rollback" = "/admin/content/feeds-migrate/{feeds_migrate_importer}/rollback"
 *   }
 * )
 */
class FeedsMigrateImporter extends ConfigEntityBase implements FeedsMigrateImporterInterface {

  /**
   * The Asset Injector ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Js Injector label.
   *
   * @var string
   */
  public $label;

  /**
   * Migration source mapping ID.
   *
   * @var string
   */
  public $source;

  public $orphans;

  public $importPeriod;

  public $existing;

  public $dataFetcherSettings;

  public $authSettings;

  public $lastRan = 0;

  /**
   * @var \Drupal\migrate\Plugin\Migration
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);
    if (!empty($this->source)) {
      /** @var \Drupal\migrate\Plugin\MigrationPluginManager $migration_manager */
      $migration_manager = \Drupal::service('plugin.manager.migration');

      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migrations = $migration_manager->createInstances($this->source);
      $this->migration = reset($migrations);
    }
  }

  /**
   * @param string $plugin_id
   *   Data fetcher form plugin id.
   *
   * @return mixed|null
   *   Settings if configured.
   */
  public function getFetcherSettings($plugin_id) {
    if (!empty($this->dataFetcherSettings[$plugin_id])) {
      return $this->dataFetcherSettings[$plugin_id];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    // TODO add dependency on migration entity.
    return $dependencies;
  }

  /**
   * If the priodic import should be executed.
   *
   * @return bool
   *   True if it should be ran on cron.
   */
  public function needsImported() {
    $request_time = \Drupal::time()->getRequestTime();
    if ($this->importPeriod != -1 && ($this->lastRan + $this->importPeriod) <= $request_time) {
      return TRUE;
    }
  }

  /**
   * Get the altered migrate executable object that can run the import.
   *
   * @return \Drupal\feeds_migrate\FeedsMigrateExecutable
   *   The object that can import.
   *
   * @throws \Drupal\migrate\MigrateException
   *   If the executable failed.
   */
  public function getExecutable() {
    $this->alterFetcher();
    if (\Drupal::moduleHandler()->moduleExists('key')) {
      $this->alterAuthentication();
    }
    $messenger = new MigrateMessage();
    if ($this->existing == 2) {
      $this->migration->getIdMap()->prepareUpdate();
    }
    return new FeedsMigrateExecutable($this->migration, $messenger);
  }

  /**
   * Alter the data fetcher from the configured plugin.
   */
  protected function alterFetcher() {
    $fetcher_plugins = \Drupal::service('plugin.manager.feeds_migrate.data_fetcher_form');
    $source_configuration = $this->migration->getSourceConfiguration();

    foreach ($fetcher_plugins->getDefinitions() as $definition) {
      if ($definition['parent'] == $source_configuration['data_fetcher_plugin']) {
        /** @var \Drupal\feeds_migrate\DataFetcherFormInterface $fetcher_instance */
        $fetcher_instance = $fetcher_plugins->createInstance($definition['id']);
        $fetcher_instance->alterMigration($this, $this->migration);
      }
    }
  }

  /**
   * Alter the authentication method from the configured plugin.
   */
  protected function alterAuthentication() {
    $source_configuration = $this->migration->getSourceConfiguration();

    if (empty($source_configuration['authentication']['plugin'])) {
      return;
    }

    $auth_plugins = \Drupal::service('plugin.manager.feeds_migrate.authentication_form');
    foreach ($auth_plugins->getDefinitions() as $definition) {
      if ($definition['parent'] == $source_configuration['authentication']['plugin']) {
        /** @var \Drupal\feeds_migrate\AuthenticationFormInterface $auth_instance */
        $auth_instance = $auth_plugins->createInstance($definition['id']);
        $auth_instance->alterMigration($this, $this->migration);
      }
    }
  }

}
