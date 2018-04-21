<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\migrate\Plugin\Migration;

/**
 * Interface AuthenticationFormPluginInterface.
 *
 * @package Drupal\feeds_migrate
 */
interface AuthenticationFormInterface extends PluginFormInterface {

  /**
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function buildForm($form, FormStateInterface $form_state);

  /**
   * @param \Drupal\feeds_migrate\FeedsMigrateImporterInterface $importer
   * @param \Drupal\feeds_migrate\FeedsMigrateExecutable $migration
   *
   * @return mixed
   */
  public function alterMigration(FeedsMigrateImporterInterface $importer, Migration $migration);

}
