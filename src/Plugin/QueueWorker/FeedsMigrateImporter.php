<?php

namespace Drupal\feeds_migrate\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\feeds_migrate\FeedsMigrateImporterInterface $feeds_migrate_importer */
    $feeds_migrate_importer = $this->entityTypeManager->getStorage('feeds_migrate_importer')
      ->load($data);
    $migrate_executable = $feeds_migrate_importer->getExecutable();
    $migrate_executable->import();
  }

}
