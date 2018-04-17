<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\data_fetcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\DataFetcherFormPluginBase;
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
class Http extends DataFetcherFormPluginBase {

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
    $source_config = $migration->getSourceConfiguration();
    $source_config['urls'] = '';
    $migration->set('source', $source_config);
  }

}
