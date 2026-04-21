<?php

declare(strict_types=1);

namespace Drupal\my_helper\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to run API batch operations.
 */
final class ApiBatchForm extends FormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ApiBatchForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'my_helper_api_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Operation'),
      '#options' => [
        'import' => $this->t('Import'),
        'delete' => $this->t('Delete all ApiItems'),
      ],
      '#required' => TRUE,
    ];

    $form['total_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items'),
      '#default_value' => 50,
      '#min' => 1,
      '#states' => [
        'visible' => [
          ':input[name="operation"]' => ['value' => 'import'],
        ],
      ],
    ];

    $form['batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#default_value' => 10,
      '#min' => 1,
      '#max' => 50,
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $operation = $form_state->getValue('operation');
    $batch_size = (int) $form_state->getValue('batch_size');

    $batch = [
      'title' => $this->t('Batch'),
      'operations' => [],
      'finished' => '\Drupal\my_helper\Batch\ApiBatchProcessor::batchFinished',
    ];

    if ($operation === 'import') {
      $total_items = (int) $form_state->getValue('total_items');

      $all_ids = range(1, $total_items);
      $chunks = array_chunk($all_ids, $batch_size);

      foreach ($chunks as $chunk) {
        $batch['operations'][] = [
          '\Drupal\my_helper\Batch\ApiBatchProcessor::processImport',
          [$chunk],
        ];
      }
    }
    else {
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'api_item');
      $nids = $query->execute();

      $chunks = array_chunk($nids, $batch_size);
      foreach ($chunks as $chunk) {
        $batch['operations'][] = [
          '\Drupal\my_helper\Batch\ApiBatchProcessor::processDelete',
          [$chunk],
        ];
      }
    }

    batch_set($batch);
  }

}
