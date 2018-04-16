<?php

namespace Drupal\feeds_migrate\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\feeds_migrate\FeedsMigrateImporterInterface;
use Drupal\migrate_plus\Entity\Migration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeedsMigrateImporterForm.
 *
 * @package Drupal\feeds_migrate\Form
 */
class FeedsMigrateImporterForm extends EntityForm {

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the @type.', [
        '@type' => $entity->getEntityType()->getLabel(),
      ]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\\' . $entity->getEntityType()->getClass() . '::load',
        'replace_pattern' => '[^a-z0-9_]+',
        'replace' => '_',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $sources = [];
    /** @var Migration $migration */
    foreach (Migration::loadMultiple() as $migration) {
      $sources[$migration->id()] = $migration->label();
    }
    $form['source'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration Source'),
      '#options' => $sources,
      '#default_value' => $entity->source,
      '#required' => TRUE,
    ];

    $form['plugin_settings'] = [
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    ];
    $form['period_settings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Settings'),
      '#tree' => FALSE,
    ];

    $times = [
      900,
      1800,
      3600,
      10800,
      21600,
      43200,
      86400,
      259200,
      604800,
      2419200,
    ];
    $times = array_combine($times, $times);
    foreach ($times as &$time) {
      $time = $this->dateFormatter->formatInterval($time);
      $time = $this->t('Every @time', ['@time' => $time]);
    }

    $additonal_options = [
      FeedsMigrateImporterInterface::SCHEDULE_NEVER => $this->t('Off'),
      FeedsMigrateImporterInterface::SCHEDULE_CONTINUOUSLY => $this->t('As often as possible'),
    ];

    $form['period_settings']['importPeriod'] = [
      '#type' => 'select',
      '#title' => $this->t('Import period'),
      '#options' => $additonal_options + $times,
      '#description' => $this->t('Choose how often a importer should run.'),
      '#default_value' => $entity->importPeriod,
    ];
    $form['processor_settings'] = [
      '#type' => 'details',
      '#group' => 'plugin_settings',
      '#title' => $this->t('Processor Settings'),
      '#tree' => FALSE,
    ];
    $form['processor_settings']['existing'] = [
      '#type' => 'radios',
      '#title' => $this->t('Update Existing Content'),
      '#default_value' => $entity->existing ?: 0,
      '#options' => [
        $this->t('Do not update existing content'),
        $this->t('Replace existing content'),
        $this->t('Update existing content'),
      ],
    ];
    $form['processor_settings']['orphans'] = [
      '#type' => 'select',
      '#title' => $this->t('Orphaned Items'),
      '#default_value' => $entity->orphans ?: '__keep',
      '#options' => [
        '_keep' => $this->t('Keep'),
        '_delete' => $this->t('Delete'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl(Url::fromRoute('entity.feeds_migrate_importer.collection'));
    return parent::save($form, $form_state);
  }

}
