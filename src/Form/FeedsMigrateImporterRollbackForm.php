<?php

namespace Drupal\feeds_migrate\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class FeedsMigrateImporterRollbackForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Rollback %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Rollback %label items?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url("entity.feeds_migrate_importer.collection");
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->lastRan = 0;
    $entity->save();

    /** @var \Drupal\feeds_migrate\FeedsMigrateExecutable $migrate_executable */
    $migrate_executable = $this->entity->getExecutable();
    $migrate_executable->rollback();
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
