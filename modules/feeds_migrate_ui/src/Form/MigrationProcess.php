<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate_ui\FeedsMigrateUiProcessManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MigrationProcess.
 *
 * @package Drupal\feeds_migrate_ui\Form
 */
class MigrationProcess extends MigrationFormBase {

  /**
   * @var string
   */
  protected $fieldName;

  /**
   * Fill out.
   *
   * @var \Drupal\feeds_migrate_ui\FeedsMigrateUiProcessManager
   */
  protected $processManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.feeds_migrate_ui.process'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(FeedsMigrateUiProcessManager $process_manager, EntityFieldManager $field_manager) {
    $this->fieldName = $this->getRequest()->get('field');
    $this->processManager = $process_manager;
    $this->fieldManager = $field_manager;
  }

  /**
   * Page title.
   *
   * @return string
   *   Page title.
   */
  public function getTitle() {
    return 'WORKS';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $rows = [];

    $column_count = count($this->getHeaders());

    $add_button = [
      '#type' => 'submit',
      '#value' => 'Add New',
      '#ajax' => [
        'wrapper' => '',
      ],
    ];

    $process = $this->entity->get('process');
    $this->fieldName .= '/target_id';

    if (is_array($process[$this->fieldName])) {
      foreach ($process[$this->fieldName] as $process_config) {
        
        try {
          /** @var \Drupal\feeds_migrate_ui\FeedsMigrateUiProcessInterface $process_plugin */
          $process_plugin = $this->processManager->createInstance($process_config['plugin'], $process_config);
          $row[] = $process_plugin->buildConfigurationForm($form, $form_state);
        }
        catch (\Exception $e) {
          $rows[] = [
            'data' => [
              [
                'data' => $this->t('Broken Handler'),
                'colspan' => $column_count - 1,
              ],
              [
                'data' => $this->t('Delete'),
              ],
            ],
          ];
        }
      }
    }
    $rows[] = [
      'data' => [
        ['data' => $add_button, 'colspan' => $column_count],
      ],
    ];

    $form['process'] = [
      '#type' => 'table',
      '#header' => $this->getHeaders(),
      '#rows' => $rows,
    ];
    return $form;
  }

  //  protected function getField() {
  //    if ($this->field) {
  //      return $this->field;
  //    }
  //    $field_name = $this->requestStack->getCurrentRequest()->get('field');
  //    $entity_type = $this->getEntityTypeFromMigration();
  //    $entity_bundle = $this->getEntityBunddleFromMigration();
  //
  //    $bundle_fields = $this->fieldManager->getFieldDefinitions($entity_type, $entity_bundle);
  //    $this->field = $bundle_fields[$field_name];
  //    return $this->field;
  //  }

  /**
   * Get table headers.
   *
   * @return array
   *   Table headers.
   */
  protected function getHeaders() {
    return [
      $this->t('Process'),
      $this->t('Operations'),
    ];
  }

}
