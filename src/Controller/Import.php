<?php

namespace Drupal\feeds_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\feeds_migrate\AuthenticationFormPluginManager;
use Drupal\feeds_migrate\DataFetcherFormPluginManager;
use Drupal\feeds_migrate\FeedsMigrateExecutable;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Import.
 *
 * @package Drupal\feeds_migrate\Controller
 */
class Import extends ControllerBase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * @var \Drupal\feeds_migrate\AuthenticationFormPluginManager
   */
  protected $authenticationManager;

  /**
   * @var \Drupal\feeds_migrate\DataFetcherFormPluginManager
   */
  protected $dataFetcherManager;

  /**
   * @var \Drupal\migrate\Plugin\Migration
   */
  public $migration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
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
   * @param \Drupal\feeds_migrate\FeedsMigrateImporterInterface $feeds_migrate_importer
   *
   * @return int|null|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function import(FeedsMigrateImporterInterface $feeds_migrate_importer) {
    $batch = $this->getBatch($feeds_migrate_importer);
    if (!is_array($batch)) {
      $this->messenger()
        ->addError($this->t('Import failed. See database logs for more details'));
      return [];
    }

    batch_set($batch);
    return batch_process();
  }

  /**
   * @param \Drupal\feeds_migrate\FeedsMigrateImporterInterface $feeds_migrate_importer
   *
   * @return array|int
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getBatch(FeedsMigrateImporterInterface $feeds_migrate_importer) {

    /** @var FeedsMigrateExecutable $migrate_executable */
    $migrate_executable = $feeds_migrate_importer->getExecutable();
    $this->migration = $migrate_executable->getMigration();
    $source = $this->migration->getSourcePlugin();

    try {
      $source->rewind();
    }
    catch (\Exception $e) {
      $this->message->display(
        $this->t('Migration failed with source plugin exception: @e', ['@e' => $e->getMessage()]), 'error');
      $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
      return MigrationInterface::RESULT_FAILED;
    }

    $batch = [
      'title' => $this->t('Importing @label', ['@label' => $feeds_migrate_importer->label()]),
      'finished' => [static::class, 'batchFinished'],
      'operations' => [],
    ];

    while ($source->valid()) {
      $row = $source->current();
      $this->sourceIdValues = $row->getSourceIdValues();

      $batch['operations'][] = [
        [static::class, 'batchImportRow'],
        [$migrate_executable, $row],
      ];

      try {
        $source->next();
      }
      catch (\Exception $e) {
        $this->message->display(
          $this->t('Migration failed with source plugin exception: @e',
            ['@e' => $e->getMessage()]), 'error');
        $this->migration->setStatus(MigrationInterface::STATUS_IDLE);
        return MigrationInterface::RESULT_FAILED;
      }
    }

    $feeds_migrate_importer->lastRan = time();
    $feeds_migrate_importer->save();
    return $batch;
  }

  /**
   * @param \Drupal\feeds_migrate\FeedsMigrateExecutable $migrate_executable
   * @param \Drupal\migrate\Row $row
   */
  public static function batchImportRow(FeedsMigrateExecutable $migrate_executable, Row $row, &$context) {
    $migrate_executable->importRow($row);
    $id_map = $row->getIdMap();
    $context['results'][$id_map['source_row_status']][] = $row;
  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  public static function batchFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();

    if (empty($results)) {
      $messenger->addMessage(t('No items processed.'));
    }

    if (!empty($results[MigrateIdMapInterface::STATUS_IMPORTED]) || !empty($results[MigrateIdMapInterface::STATUS_NEEDS_UPDATE])) {
      $count = 0;
      if (!empty($results[MigrateIdMapInterface::STATUS_IMPORTED])) {
        $count = count($results[MigrateIdMapInterface::STATUS_IMPORTED]);
      }
      if (!empty($results[MigrateIdMapInterface::STATUS_NEEDS_UPDATE])) {
        $count += count($results[MigrateIdMapInterface::STATUS_NEEDS_UPDATE]);
      }
      $messenger->addMessage(t('Successfully imported %success items.', ['%success' => $count]));
    }

    if (!empty($results[MigrateIdMapInterface::STATUS_IGNORED])) {
      $messenger->addMessage(t('Skipped %ignored items.', ['%ignored' => count($results[MigrateIdMapInterface::STATUS_IGNORED])]));
    }

    if (!empty($results[MigrateIdMapInterface::STATUS_FAILED])) {
      $messenger->addMessage(t('Failed to import %failed items', ['%failed' => count($results[MigrateIdMapInterface::STATUS_FAILED])]));
    }
  }

}
