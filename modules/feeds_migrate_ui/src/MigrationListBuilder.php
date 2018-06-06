<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\feeds_migrate_ui\Form\MigrationForm;

/**
 * Class FeedsMigrateImporterListBuilder.
 *
 * @package Drupal\feeds_migrate
 */
class MigrationListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Importer');
    return $header + parent::buildHeader();
  }

  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $operations['edit_mapping'] = [
      'title' => t('Edit Fields'),
      'weight' => -10,
      'url' => $this->ensureDestination($entity->toUrl('edit-form', ['step' => MigrationForm::STEP_FINALIZE])),
    ];
//    \Drupal\Core\Routing\UrlGeneratorInterface::generateFromRoute()
  return $operations;
}

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $data['label'] = $entity->label();

    $row = [
      'class' => $entity->status() ? 'enabled' : 'disabled',
      'data' => $data + parent::buildRow($entity),
    ];
    return $row;
  }

}
