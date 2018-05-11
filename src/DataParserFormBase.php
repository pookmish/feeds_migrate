<?php

namespace Drupal\feeds_migrate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Class FeedsMigrateParserBase.
 *
 * @package Drupal\feeds_migrate
 */
abstract class DataParserFormBase extends PluginBase implements DataParserFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValue(['parser', $this->getPluginId()]);
    $id_selector = $values['ids'];
    $values['ids'] = ['guid' => ['type' => 'string']];
    $source = $entity->get('source') ?: [];

    $source['fields'][] = [
      'name' => 'guid',
      'label' => 'guid',
      'selector' => $id_selector,
    ];
    $entity->set('source', array_merge($source, $values));
  }

}
