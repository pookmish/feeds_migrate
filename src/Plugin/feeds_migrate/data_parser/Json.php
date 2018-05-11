<?php

namespace Drupal\feeds_migrate\Plugin\feeds_migrate\data_parser;

use Drupal\Core\Entity\EntityInterface;
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
    $element['ids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique Selector Path'),
      '#default_value' => $this->getUniqueSelector($entity),
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * Get the unique value selector path.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Migration entity.
   *
   * @return string
   *   The selector path.
   */
  protected function getUniqueSelector(EntityInterface $entity) {
    $source = $entity->get('source');
    if (empty($source['ids'])) {
      return NULL;
    }
    $field_name = key($source['ids']);
    foreach ($source['fields'] as $field_selector) {
      if ($field_selector['name'] == $field_name) {
        return $field_selector['selector'];
      }
    }
  }

}
