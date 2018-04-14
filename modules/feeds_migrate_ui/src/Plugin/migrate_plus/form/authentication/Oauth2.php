<?php

namespace Drupal\feeds_migrate_ui\Plugin\migrate_plus\form\authentication;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate_ui\AuthenticationFormInterface;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @AuthenticationForm(
 *   id = "oauth2",
 *   title = @Translation("Oauth2"),
 *   parent = "oauth2"
 * )
 */
class Oauth2 implements AuthenticationFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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

}
