<?php

namespace Drupal\my_helper\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a custom form to log time.
 */
final class TimeLogCustomForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TimeLogCustomForm object.
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
  public function getFormId() {
    return 'my_helper_time_log_custom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Hours spent'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $form['is_overtime'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is this overtime?'),
    ];

    $form['reason'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reason for overtime'),
      '#states' => [
        'visible' => [
          ':input[name="is_overtime"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="is_overtime"]' => ['checked' => TRUE],
        ],
      ],
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $hours = $form_state->getValue('hours');
    if ($hours > 24) {
      $form_state->setErrorByName('hours', $this->t('You cannot log more than 24 hours in a single entry.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('my_helper_my_helper_module');

    $time_log = $storage->create([
      'label' => 'Log: ' . $form_state->getValue('hours') . ' hours',
      'status' => 1,
    ]);
    $time_log->save();

    $this->messenger()->addMessage($this->t('Saved new TimeLog with ID: @id', ['@id' => $time_log->id()]));

    $query = $storage->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->range(0, 10);
    $log_ids = $query->execute();

    if (!empty($log_ids)) {
      $logs = $storage->loadMultiple($log_ids);
      $this->messenger()->addMessage($this->t('Total logs in the database: @count', ['@count' => count($logs)]));
    }
  }

}
