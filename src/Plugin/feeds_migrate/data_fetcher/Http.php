<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\data_fetcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\feeds_migrate\DataFetcherFormInterface;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate\Plugin\Migration;

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

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $element['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMigration(FeedsMigrateImporterInterface $importer, Migration $migration) {
    dpm($importer);
    $source_config = $migration->getSourceConfiguration();
    $source_config['urls'] = '';
    $migration->set('source', $source_config);
  }

}
