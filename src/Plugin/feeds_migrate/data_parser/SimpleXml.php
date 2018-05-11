<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\data_parser;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate\DataParserFormBase;

/**
 * @DataParserForm(
 *   id = "simple_xml",
 *   title = @Translation("SimpleXml"),
 *   parent = "simple_xml"
 * )
 */
class SimpleXml extends DataParserFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\feeds_migrate\Entity\FeedsMigrateImporter $entity */
    $entity = $form_state->getBuildInfo()['callback_object']->getEntity();
    $source = $entity->get('source');
    $element['item_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('XML Item Selector'),
      '#default_value' => $source['item_selector'] ?: '',
      '#required' => TRUE,
    ];
    return $element;
  }

}
