<?php

namespace Drupal\feeds_migrate_ui\Form;

use Drupal\Core\Entity\EntityForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MigrationFormBase.
 *
 * @package Drupal\feeds_migrate_ui\Form
 */
abstract class MigrationFormBase extends EntityForm {

  /**
   * Fill This.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * Find the entity type the migration is importing into.
   *
   * @return string
   *   Machine name of the entity type eg 'node'.
   */
  protected function getEntityTypeFromMigration() {
    $destination = $this->entity->destination['plugin'];
    if (strpos($destination, ':') !== FALSE) {
      list(, $entity_type) = explode(':', $destination);
      return $entity_type;
    }
  }

  /**
   * The bundle the migration is importing into.
   *
   * @return string
   *   Entity type bundle eg 'article'.
   */
  protected function getEntityBunddleFromMigration() {
    if (!empty($this->entity->source['constants']['bundle'])) {
      return $this->entity->source['constants']['bundle'];
    }
  }

}
