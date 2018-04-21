<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_plus\Entity\Migration;

/**
 * Class FeedsMigrateImporterListBuilder.
 *
 * @package Drupal\feeds_migrate
 */
class FeedsMigrateImporterListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Importer');
    $header['migrator'] = $this->t('Migration');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    dpm($entity);
    /** @var \Drupal\migrate_plus\Entity\Migration $migration */
    $migration = Migration::load($entity->source);

    $data['label'] = $entity->label();
    $data['migrator'] = $migration->label();

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
    return $operations;
  }

}
