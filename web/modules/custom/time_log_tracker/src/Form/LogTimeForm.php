<?php

declare(strict_types=1);

namespace Drupal\time_log_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\time_log_tracker\Entity\TimeLog;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides a Time Log Tracker form.
 */
final class LogTimeForm extends FormBase {

  /**
   * Constructs a new LogTimeForm object.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
  ) {
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'time_log_tracker_log_time';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $project_id = NULL): array {
    $form['project'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['project'],
      ],
      '#title' => $this->t('Project'),
      '#required' => TRUE,
      '#default_value' => $project_id ? $this->entityTypeManager->getStorage('node')->load($project_id) : NULL,
    ];

    $form['task'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['task'],
      ],
      '#title' => $this->t('Task'),
      '#required' => TRUE,
    ];

    $form['time_spent'] = [
      '#type' => 'number',
      '#title' => $this->t('Time spent (minutes)'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log Time'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $time_log = TimeLog::create([
      'project' => $form_state->getValue('project'),
      'task' => $form_state->getValue('task'),
      'time_spent' => $form_state->getValue('time_spent'),
    ]);
    $time_log->save();

    $this->messenger()->addStatus($this->t('Time has been logged.'));
    $form_state->setRedirect('<front>');
  }

}
