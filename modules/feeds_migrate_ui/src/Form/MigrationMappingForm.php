<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MigrationMappingForm.
 *
 * @package Drupal\feeds_migrate\Form
 */
class MigrationMappingForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->getEntity();
    foreach ($entity->source['fields'] as $delta => $field_mapping) {
      $form[$delta]['selector'] = [
        '#type' => 'select',
        '#title' => $this->t('Selector'),
        '#options' => [],
      ];
      $form[$delta]['target'] = [
        '#type' => 'select',
        '#title' => $this->t('Target'),
        '#options' => [],
      ];
    }
    $form['add_new'] = [
      '#type' => 'button',
      '#value' => $this->t('Add New'),
    ];
    return $form;
  }

}
