<?php

namespace Drupal\feeds_migrate\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\feeds_migrate\FeedsMigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;


/**
 * A Feeds migrate importer.
 *
 * @QueueWorker(
 *   id = "feeds_migrate_importer",
 *   title = @Translation("Feeds Migrate Importer"),
 *   cron = {"time" = 60}
 * )
 */
class FeedsMigrateImporter extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.feeds_migrate.authentication_form'),
      $container->get('plugin.manager.feeds_migrate.data_fetcher_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MigrationPluginManagerInterface $migration_manager, AuthenticationFormPluginManager $authentication_plugins, DataFetcherFormPluginManager $data_fetcher_plugins) {
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationManager = $migration_manager;
    $this->authenticationManager = $authentication_plugins;
    $this->dataFetcherManager = $data_fetcher_plugins;
    $this->message = new MigrateMessage();
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\feeds_migrate\FeedsMigrateImporterInterface $feeds_migrate_importer */
    $feeds_migrate_importer = $this->entityTypeManager->getStorage('feeds_migrate_importer')
      ->load($data);
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migrations = $this->migrationManager->createInstances($feeds_migrate_importer->source);
    $this->migration = reset($migrations);
    $this->migration->interruptMigration(MigrationInterface::RESULT_STOPPED);
    $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
    $source_configuration = $this->migration->getSourceConfiguration();

    foreach ($this->dataFetcherManager->getDefinitions() as $definition) {

      if ($definition['parent'] == $source_configuration['data_fetcher_plugin']) {
        $fetcher_instance = $this->dataFetcherManager->createInstance($definition['id']);
        $fetcher_instance->alterMigration($feeds_migrate_importer, $this->migration);
      }
    }

    $migrate_executable = new FeedsMigrateExecutable($this->migration, $this->message);
    $migrate_executable->import();


  }

}
