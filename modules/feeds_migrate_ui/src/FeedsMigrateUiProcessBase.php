<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Class FeedsMigrateuiProcessBase.
 *
 * @package Drupal\feeds_migrate_ui
 */
abstract class FeedsMigrateUiProcessBase extends PluginBase implements FeedsMigrateUiProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    // TODO: Implement getSummary() method.
  }

}
