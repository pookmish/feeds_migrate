<?php

namespace Drupal\feeds_migrate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
    $feeds_migrate_importer->lastRan = time();
    $feeds_migrate_importer->save();

    $migrate_executable = $feeds_migrate_importer->getExecutable();
    $this->migration = $this->migrationManager->createInstance($feeds_migrate_importer->source);
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
        //        $migration->setStatus(MigrationInterface::STATUS_IDLE);
        return MigrationInterface::RESULT_FAILED;
      }
    }

    batch_set($batch);
    return batch_process();
  }

  /**
   * @param \Drupal\feeds_migrate\FeedsMigrateExecutable $migrate_executable
   * @param \Drupal\migrate\Row $row
   */
  public static function batchImportRow(FeedsMigrateExecutable $migrate_executable, Row $row, &$context) {
    $migrate_executable->importRow($row);
    $id_map = $row->getIdMap();

    if ($id_map['destid1']) {
      $context['results']['success'][] = $row;
      return;
    }

    if ($id_map['source_row_status'] == MigrateIdMapInterface::STATUS_FAILED) {
      $context['results']['failed'][] = $row;
      return;
    }

    $context['results']['ignored'][] = $row;
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

    if (!empty($results['success'])) {
      $messenger->addMessage(t('Successfully imported %success items.', ['%success' => count($results['success'])]));
    }

    if (!empty($results['ignored'])) {
      $messenger->addMessage(t('Skipped %ignored items.', ['%ignored' => count($results['ignored'])]));
    }

    if (!empty($results['failed'])) {
      $messenger->addMessage(t('Failed to import %failed items', ['%failed' => count($results['failed'])]));
    }
  }

}
