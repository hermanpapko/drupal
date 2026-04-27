<?php

declare(strict_types=1);

namespace Drupal\my_helper;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\user\UserInterface;

/**
 * Provides a list controller for the my helper module entity type.
 */
final class TimeLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['label'] = $this->t('Label');
    $header['status'] = $this->t('Status');
    $header['uid'] = $this->t('Author');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\my_helper\TimeLogInterface $entity */
    $row['id'] = $entity->id();
    $row['label'] = $entity->toLink();
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');
    $username_options = [
      'label' => 'hidden',
      'settings' => ['link' => FALSE],
    ];
    $author = $entity->get('uid')->entity;
    if ($author instanceof UserInterface && $author->isAuthenticated()) {
      $username_options['settings']['link'] = TRUE;
    }
    $row['uid']['data'] = $entity->get('uid')->view($username_options);
    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

}
