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
    $element['item_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Selector'),
    ];
    return $element;
  }

}
