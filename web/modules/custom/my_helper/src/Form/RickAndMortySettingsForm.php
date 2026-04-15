<?php

declare(strict_types=1);

namespace Drupal\my_helper\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Rick and Morty API settings.
 */
class RickAndMortySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['my_helper.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'my_helper_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['status_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Status filter'),
      '#description' => $this->t('Select the status of characters you want to import.'),
      '#options' => [
        '' => $this->t('All'),
        'alive' => $this->t('Alive'),
        'dead' => $this->t('Dead'),
        'unknown' => $this->t('Unknown'),
      ],
      '#default_value' => $this->config('my_helper.settings')->get('status_filter') ?? '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('my_helper.settings')
      ->set('status_filter', $form_state->getValue('status_filter'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
