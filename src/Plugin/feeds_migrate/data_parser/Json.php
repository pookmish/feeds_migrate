<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\data_parser;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\DataParserFormBase;

/**
 * @DataParserForm(
 *   id = "json",
 *   title = @Translation("Json"),
 *   parent = "json"
 * )
 */
class Json extends DataParserFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\feeds_migrate\Entity\FeedsMigrateImporter $entity */
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    $source = $entity->get('source');
    $element['item_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JSON Item Selector'),
      '#default_value' => $source['item_selector'] ?: '',
      '#required' => TRUE,
    ];
    return $element;
  }

}
