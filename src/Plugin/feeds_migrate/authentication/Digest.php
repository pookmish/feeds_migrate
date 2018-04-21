<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\authentication;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\AuthenticationFormPluginBase;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate\Plugin\Migration;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @AuthenticationForm(
 *   id = "digest",
 *   title = @Translation("Digest"),
 *   parent = "digest"
 * )
 */
class Digest extends AuthenticationFormPluginBase {

  /**
   * {@inheritdoc}
   */
  public function alterMigration(FeedsMigrateImporterInterface $importer, Migration $migration) {
    // TODO: Implement alterMigration() method.
  }

}
