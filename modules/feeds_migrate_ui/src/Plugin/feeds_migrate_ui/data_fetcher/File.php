<?php

namespace Drupal\feeds_migrate_ui\Plugin\feeds_migrate_ui\data_fetcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate_ui\DataFetcherFormInterface;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @DataFetcherForm(
 *   id = "file",
 *   title = @Translation("File"),
 *   parent = "file"
 * )
 */
class File implements DataFetcherFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form['directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('File Upload Directory'),
      '#default_value' => 'public://migrate',
    ];
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
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('File Upload'),
      '#default_value' => '',
    ];
  }

}
