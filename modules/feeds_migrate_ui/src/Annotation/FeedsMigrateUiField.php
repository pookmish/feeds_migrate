<?php

namespace Drupal\feeds_migrate_ui\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an authentication annotation object.
 *
 * @see \Drupal\migrate_plus\AuthenticationPluginBase
 * @see \Drupal\migrate_plus\AuthenticationPluginInterface
 * @see \Drupal\migrate_plus\AuthenticationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class FeedsMigrateUiField extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * Fill this out.
   *
   * @var array
   */
  public $fields;

  /**
   * Fill this out.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  public $field;

}
