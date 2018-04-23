<?php

namespace Drupal\feeds_migrate;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\migrate_plus\Entity\Migration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeedsMigrateImporterListBuilder.
 *
 * @package Drupal\feeds_migrate
 */
class FeedsMigrateImporterListBuilder extends ConfigEntityListBuilder {

  protected $dateFormatter;

  protected $dateTime;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter, TimeInterface $time) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->dateTime = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Importer');
    $header['migrator'] = $this->t('Migration');
    $header['last'] = $this->t('Last Imported');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\migrate_plus\Entity\Migration $migration */
    $migration = Migration::load($entity->source);

    $data['label'] = $entity->label();
    $data['migrator'] = $migration->label();

    $data['last'] = $this->t('Never');
    if ($entity->lastRan) {
      $data['last'] = $this->dateFormatter->formatDiff($entity->lastRan, $this->dateTime->getRequestTime(), ['granularity' => 1]);
    }

    $row = [
      'class' => $entity->status() ? 'enabled' : 'disabled',
      'data' => $data + parent::buildRow($entity),
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['import'] = [
      'title' => t('Import'),
      'weight' => -10,
      'url' => $this->ensureDestination($entity->toUrl('import')),
    ];
    $operations['rollback'] = [
      'title' => t('Rollback'),
      'weight' => -9,
      'url' => $this->ensureDestination($entity->toUrl('rollback')),
    ];
    return $operations;
  }

}
