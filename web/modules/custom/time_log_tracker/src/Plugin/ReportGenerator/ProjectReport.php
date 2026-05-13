<?php

namespace Drupal\time_log_tracker\Plugin\ReportGenerator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\time_log_tracker\Attribute\ReportGenerator;
use Drupal\time_log_tracker\Plugin\ReportGeneratorBase;

/**
 * Provides a report for a specific project.
 */
#[ReportGenerator(
  id: 'project_report',
  label: new TranslatableMarkup('Project Report')
)]
class ProjectReport extends ReportGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['project_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      '#selection_settings' => ['target_bundles' => ['project']],
      '#title' => $this->t('Select Project'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['project_id'] ?? NULL ? $this->entityTypeManager->getStorage('node')->load($this->configuration['project_id']) : NULL,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReport(): array {
    $project_id = $this->configuration['project_id'] ?? NULL;
    if (!$project_id) {
      return ['#markup' => $this->t('Please select a project.')];
    }

    $time_logs = $this->entityTypeManager->getStorage('time_log')->loadByProperties([
      'project' => $project_id,
    ]);

    $task_data = [];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $log */
    foreach ($time_logs as $log) {
      $task_id = $log->get('task')->target_id;
      if (!isset($task_data[$task_id])) {
        $task_node = $log->get('task')->entity;
        $task_data[$task_id] = [
          'title' => $task_node ? $task_node->label() : $this->t('Unknown Task'),
          'time' => 0,
        ];
      }
      $task_data[$task_id]['time'] += (int) $log->get('time_spent')->value;
    }

    $rows = [];
    foreach ($task_data as $data) {
      $rows[] = [
        $data['title'],
        $data['time'] . ' min',
      ];
    }

    return [
      '#type' => 'table',
      '#header' => [$this->t('Task'), $this->t('Time Spent')],
      '#rows' => $rows,
      '#empty' => $this->t('No time logs found for this project.'),
    ];
  }

}
