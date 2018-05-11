<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Class DataFetcherFormPluginBase.
 *
 * @package Drupal\feeds_migrate
 */
abstract class DataFetcherFormPluginBase extends PluginBase implements DataFetcherFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  protected function getFormValue($form, FormStateInterface $form_state) {
    return $form_state->getValue($this->getValueKey());
  }

  protected function getValueKey() {
    return [
      'dataFetcherSettings',
      $this->getPluginId(),
    ];
  }

}
