<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
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
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $column_count = count($this->getHeaders());

    $add_button = [
      'plugin' => [
        '#type' => 'select',
        '#title' => $this->t('Select Plugin'),
        '#title_display' => 'invisible',
        '#options' => $this->getPluginOptions(),
        '#empty_option' => $this->t('- Select Plugin -'),
      ],
      'add' => [
        '#type' => 'submit',
        '#value' => 'Add New',
        '#ajax' => [
          'wrapper' => '',
        ],
      ],
    ];

    $process = $this->entity->get('process');
    if (strpos($this->fieldName, 'target_id') === FALSE) {
      $this->fieldName .= '/target_id';
    }


    $table = [
      '#prefix' => '<div id="process-table-wrapper">',
      '#suffix' => '</div>',
      '#type' => 'table',
      '#header' => $this->getHeaders(),
      '#footer' => [
        'data' => [
          ['data' => $add_button, 'colspan' => $column_count],
        ],
      ],
      '#attributes' => [
        'id' => 'process-table',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'row-weight',
        ],
      ],
    ];

    if (is_array($process[$this->fieldName])) {
      foreach ($process[$this->fieldName] as $delta => $process_config) {
        $table[$process_config['plugin'] . '_' . $delta] = $this->buildRow($process_config, $delta, $form, $form_state);
      }
    }

    $form['process_plugins'] = $table;

    return $form;
  }

  /**
   * @return array
   */
  protected function getPluginOptions() {
    $options = [];
    foreach ($this->processManager->getDefinitions() as $plugin_defintion) {
      $options[$plugin_defintion['id']] = $plugin_defintion['title'];
    }
    return $options;
  }

  /**
   * @param $process_config
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function buildRow($process_config, $position, array $form, FormStateInterface $form_state) {
    try {
      /** @var \Drupal\feeds_migrate_ui\FeedsMigrateUiProcessBase $process_plugin */
      $process_plugin = $this->processManager->createInstance($process_config['plugin'], $process_config);
      $label = $process_plugin->label();
    }
    catch (\Exception $e) {
      $label = $this->t('Broken/Missing Handler');
    }
    $key = $process_config['plugin'] . '_' . $position;
    $base_button = [
      '#plugin' => $key,
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'process-table-wrapper',
        'effect' => 'fade',
      ],
    ];
    $edit_button = $base_button + [
        '#type' => 'image_button',
        '#src' => 'core/misc/icons/787878/cog.svg',
        '#op' => 'edit',
        '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
        '#suffix' => '</div>',
        '#name' => $key . '_edit',
        '#attributes' => [
          'class' => ['field-plugin-settings-edit'],
          'alt' => $this->t('Edit'),
        ],
      ];

    $row = [
      'name' => ['data' => ['#plain_text' => $label]],
      'ops' => ['data' => $edit_button],
      'weight' => [
        'data' => [
          '#type' => 'textfield',
          '#title' => $this->t('Weight for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#size' => 3,
          '#default_value' => $position,
          '#attributes' => ['class' => ['row-weight']],
        ],
      ],
      '#attributes' => [
        'class' => ['draggable'],
      ],
    ];

    if ($form_state->get('plugin_settings_edit') === $key) {
      $row['ops'] = [];

      if ($process_plugin) {
        $row['ops'] = $process_plugin->buildConfigurationForm($form, $form_state);
        $row['ops']['actions']['update'] = $base_button + [
            '#type' => 'submit',
            '#value' => $this->t('Update'),
            '#op' => 'update',
            '#name' => $key . '_update',
          ];
      }
      $row['ops']['actions']['delete'] = $base_button + [
          '#type' => 'submit',
          '#value' => $this->t('Delete'),
          '#op' => 'delete',
          '#name' => $key . '_delete',
        ];
    }

    return $row;
  }

  /**
   * Edit button submit handler.
   */
  public function multistepSubmit($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];
    $form_state->setRebuild();
    $form_state->set('plugin_settings_edit', NULL);

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $form_state->set('plugin_settings_edit', $trigger['#plugin']);
        break;
    }
  }

  /**
   * Ajax submit.
   */
  public static function multistepAjax(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];
    $form_state->setRebuild();

    switch ($op) {
      case 'delete':
        unset($form['process_plugins'][$trigger['#plugin']]);
        break;
    }

    return $form['process_plugins'];
  }

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
      $this->t('Weight'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //    parent::submitForm($form, $form_state); // TODO: Change the autogenerated stub
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //    parent::validateForm($form, $form_state); // TODO: Change the autogenerated stub
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
//        parent::copyFormValuesToEntity($entity, $form, $form_state); // TODO: Change the autogenerated stub
  }

}
