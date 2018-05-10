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

  protected function getFieldSelector($field_name) {
    if (!isset($this->configuration['entity']->process[$field_name])) {
      return NULL;
    }

    $process = $this->configuration['entity']->process[$field_name];
    if (is_string($process)) {
      return $this->getSourceSelector($process);
    }

    foreach ($process as $process_config) {
      if (isset($process_config['source']) && is_string($process_config['source'])) {
        return $this->getSourceSelector($process_config['source']);
      }
    }
  }

  protected function getSourceSelector($source_name) {
    foreach ($this->configuration['entity']->source['fields'] as $source_selector) {
      if ($source_selector['name'] == $source_name) {
        return $source_selector['selector'];
      }
    }
  }

}
