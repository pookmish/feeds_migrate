<?php

namespace Drupal\feeds_migrate_ui\Plugin\feeds_migrate\field_processor;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds_migrate_ui\FeedsMigrateUiFieldProcessorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DateRange.
 *
 * @FeedsMigrateUiFieldProcessor(
 *   id = "default_processor",
 *   title = @Translation("Default Processor"),
 *   fields = {}
 * )
 */
class DefaultProcessor extends FeedsMigrateUiFieldProcessorBase {

  /**
   * Field Type Manager Service.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, FieldTypePluginManager $field_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $field = $this->configuration['field'];
    if ($field instanceof BaseFieldDefinition || $field instanceof BaseFieldOverride) {
      return $this->buildBaseFieldForm($field);
    }

    return $this->buildContentFieldForm($field);
  }

  /**
   * Build the configuration form for a base field component.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition|\Drupal\Core\Field\Entity\BaseFieldOverride $field
   *   The base field definition.
   *
   * @return array
   *   Form entry.
   */
  protected function buildBaseFieldForm($field) {
    $element = [
      '#type' => 'textfield',
      '#title' => $field->getLabel(),
      '#title_display' => 'invisible',
      '#default_value' => $this->getFieldSelector($field->getName()),
    ];
    return $element;
  }

  /**
   * Build a form for the field config.
   *
   * @param FieldConfigInterface $field
   *   Field config entity.
   *
   * @return array
   *   Form element for the field.
   */
  protected function buildContentFieldForm(FieldConfigInterface $field) {
    try {
      $item_instance = $this->fieldTypeManager->createInstance($field->getType(), ['field_definition' => $field]);
      $field_properties = $item_instance->getProperties();
    }
    catch (\Exception $e) {
      return [];
    }

    if (count($field_properties) == 1) {
      $element = [
        '#type' => 'textfield',
        '#title' => $field->getLabel(),
        '#title_display' => 'invisible',
        '#default_value' => $this->getFieldSelector($field->getName()),
      ];
      return $element;
    }

    $element = [];
    foreach ($field_properties as $column_name => $property) {
      $element[$column_name] = [
        '#type' => 'textfield',
        '#title' => $column_name,
        '#default_value' => $this->getFieldSelector($field->getName() . '/' . $column_name),
      ];
    }
    return $element;
  }

}
