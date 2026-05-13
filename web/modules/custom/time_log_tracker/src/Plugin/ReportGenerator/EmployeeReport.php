<?php

namespace Drupal\time_log_tracker\Plugin\ReportGenerator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\time_log_tracker\Attribute\ReportGenerator;
use Drupal\time_log_tracker\Plugin\ReportGeneratorBase;

/**
 * Provides a report for a specific employee.
 */
#[ReportGenerator(
  id: 'employee_report',
  label: new TranslatableMarkup('Employee Report')
)]
class EmployeeReport extends ReportGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => $this->t('Select Employee'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['user_id'] ?? NULL ? $this->entityTypeManager->getStorage('user')->load($this->configuration['user_id']) : NULL,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function generateReport(): array {
    $user_id = $this->configuration['user_id'] ?? NULL;
    if (!$user_id) {
      return ['#markup' => $this->t('Please select an employee.')];
    }

    $time_logs = $this->entityTypeManager->getStorage('time_log')->loadByProperties([
      'uid' => $user_id,
    ]);

    $report_data = [];
    /** @var \Drupal\Core\Entity\ContentEntityInterface $log */
    foreach ($time_logs as $log) {
      $project_id = $log->get('project')->target_id;
      $task_id = $log->get('task')->target_id;
      $time = (int) $log->get('time_spent')->value;

      if (!isset($report_data[$project_id])) {
        $project_node = $log->get('project')->entity;
        $report_data[$project_id] = [
          'title' => $project_node ? $project_node->label() : $this->t('Unknown Project'),
          'total_time' => 0,
          'tasks' => [],
        ];
      }

      if (!isset($report_data[$project_id]['tasks'][$task_id])) {
        $task_node = $log->get('task')->entity;
        $report_data[$project_id]['tasks'][$task_id] = [
          'title' => $task_node ? $task_node->label() : $this->t('Unknown Task'),
          'time' => 0,
        ];
      }

      $report_data[$project_id]['total_time'] += $time;
      $report_data[$project_id]['tasks'][$task_id]['time'] += $time;
    }

    if (empty($report_data)) {
      return ['#markup' => $this->t('No time logs found for this employee.')];
    }

    $build = [];
    foreach ($report_data as $project) {
      $build[] = [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('@project - @time min', [
          '@project' => $project['title'],
          '@time' => $project['total_time'],
        ]),
      ];
      $items = [];
      foreach ($project['tasks'] as $task) {
        $items[] = $this->t('@task - @time min', [
          '@task' => $task['title'],
          '@time' => $task['time'],
        ]);
      }
      $build[] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }

    return $build;
  }

}
