<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\authentication;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\AuthenticationFormPluginBase;

/**
 * Provides basic authentication for the HTTP resource.
 *
 * @AuthenticationForm(
 *   id = "basic",
 *   title = @Translation("Basic"),
 *   parent = "basic"
 * )
 */
class Basic extends AuthenticationFormPluginBase {

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

}
