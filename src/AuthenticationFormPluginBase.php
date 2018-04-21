<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AuthenticationFormPluginBase.
 *
 * @package Drupal\feeds_migrate
 */
abstract class AuthenticationFormPluginBase extends PluginBase implements AuthenticationFormInterface {

  protected $keyProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('key.repository'));
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, KeyRepositoryInterface $key_repo) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->keyProvider = $key_repo;
  }

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

  /**
   * {@inheritdoc}
   */
  public function buildForm($form, FormStateInterface $form_state) {
    $element['secret_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Secret key'),
    ];
    return $element;
  }

}
