<?php

declare(strict_types=1);

namespace Drupal\time_log_tracker\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\time_log_tracker\Plugin\ReportGeneratorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\time_log_tracker\Event\ReportGeneratedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a form for generating reports using ReportGenerator plugins.
 */
final class ReportGeneratorForm extends FormBase {

  /**
   * Constructs a new ReportGeneratorForm object.
   */
  public function __construct(
    protected ReportGeneratorManager $pluginManager,
    protected RendererInterface $renderer,
    protected EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('plugin.manager.report_generator'),
      $container->get('renderer'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'time_log_tracker_report_generator_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $plugins = $this->pluginManager->getDefinitions();
    $options = [];
    foreach ($plugins as $id => $definition) {
      $options[$id] = $definition['label'];
    }

    $form['report_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Report Type'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'callback' => '::ajaxPluginFormCallback',
        'wrapper' => 'plugin-config-wrapper',
      ],
      '#default_value' => $form_state->getValue('report_type'),
    ];

    $form['plugin_config'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'plugin-config-wrapper'],
    ];

    $selected_plugin = $form_state->getValue('report_type');

    if ($selected_plugin) {
      /** @var \Drupal\time_log_tracker\Plugin\ReportGeneratorInterface $plugin */
      $plugin = $this->pluginManager->createInstance($selected_plugin);

      $form['plugin_config']['settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Report Settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      ];
      $form['plugin_config']['settings'] += $plugin->buildConfigurationForm([], $form_state);

      $form['plugin_config']['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('Generate Report'),
        ],
      ];
    }

    $form['send_report'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send report via email'),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient Email'),
      '#states' => [
        'visible' => [
          ':input[name="send_report"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="send_report"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Container for the results.
    $form['report_results'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'report-results-wrapper'],
    ];

    if ($report_output = $form_state->get('report_output')) {
      $form['report_results']['content'] = $report_output;
    }

    return $form;
  }

  /**
   * Ajax callback for the plugin form.
   */
  public function ajaxPluginFormCallback(array &$form, FormStateInterface $form_state): array {
    return $form['plugin_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $selected_plugin = $form_state->getValue('report_type');
    if ($selected_plugin) {
      // Create instance with current configuration from form state.
      $config = $form_state->getValue('settings') ?: [];
      $plugin = $this->pluginManager->createInstance($selected_plugin, $config);

      $report = $plugin->generateReport();
      $form_state->set('report_output', $report);
      $form_state->setRebuild();

      // Dispatch the event if requested.
      if ($form_state->getValue('send_report')) {
        $email = $form_state->getValue('email');

        $event = new ReportGeneratedEvent($report, (string) $email);
        $this->eventDispatcher->dispatch($event, ReportGeneratedEvent::EVENT_NAME);

        $this->messenger()->addStatus($this->t('Report generation triggered and email will be sent to @email.', [
          '@email' => $email,
        ]));
      }
    }
  }

}
