<?php

namespace Drupal\feeds_migrate_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface AuthenticationFormPluginInterface.
 *
 * @package Drupal\feeds_migrate
 */
interface DataFetcherFormInterface extends PluginFormInterface {

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return mixed
   */
  public function buildForm(array &$form, FormStateInterface $form_state);

}
