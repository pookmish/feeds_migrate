<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\authentication;

use Drupal\feeds_migrate\AuthenticationFormPluginBase;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate\Plugin\Migration;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @AuthenticationForm(
 *   id = "oauth2",
 *   title = @Translation("Oauth2"),
 *   parent = "oauth2"
 * )
 */
class Oauth2 extends AuthenticationFormPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterMigration(FeedsMigrateImporterInterface $importer, Migration $migration) {
    if (!empty($importer->authSettings['oauth2']['secret_key'])) {

      $key = $this->keyProvider->getKey($importer->authSettings['oauth2']['secret_key'])
        ->getKeyValues();

      $source_config = $migration->getSourceConfiguration();
      $source_config['authentication']['base_uri'] = '';
      $source_config['authentication']['token_url'] = '';
      $source_config['authentication']['grant_type'] = '';
      $source_config['authentication']['client_id'] = '';
      $source_config['authentication']['client_secret'] = '';
      $migration->set('source', $source_config);
    }
  }

}
