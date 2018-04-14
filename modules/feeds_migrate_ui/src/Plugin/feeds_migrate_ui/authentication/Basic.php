<?php

namespace Drupal\feeds_migrate_ui\Plugin\feeds_migrate_ui\authentication;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate_ui\AuthenticationFormInterface;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @AuthenticationForm(
 *   id = "basic",
 *   title = @Translation("Basic"),
 *   parent = "basic"
 * )
 */
class Basic implements AuthenticationFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['usernmae'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
    ];
    return $form;
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
