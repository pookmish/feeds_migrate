<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeedsMigrateUiFieldProcessorBase.
 *
 * @package Drupal\feeds_migrate_ui
 */
abstract class FeedsMigrateUiFieldProcessorBase extends PluginBase implements FeedsMigrateUiFieldProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

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

}
