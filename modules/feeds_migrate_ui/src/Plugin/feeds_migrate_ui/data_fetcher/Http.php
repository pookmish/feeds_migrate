<?php

namespace Drupal\feeds_migrate_ui\Plugin\feeds_migrate_ui\data_fetcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate_ui\DataFetcherFormInterface;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @DataFetcherForm(
 *   id = "http",
 *   title = @Translation("Http"),
 *   parent = "http"
 * )
 */
class Http implements DataFetcherFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do here.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array &$form, FormStateInterface $form_state) {
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
    ];
  }

}
