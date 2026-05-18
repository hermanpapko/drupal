<?php

declare(strict_types=1);

namespace Drupal\time_log_tracker\EventSubscriber;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\time_log_tracker\Event\ReportGeneratedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to report generation events to send emails.
 */
class ReportEmailSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the object.
   */
  public function __construct(
    private readonly MailManagerInterface $mailManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      ReportGeneratedEvent::EVENT_NAME => ['onReportGenerated'],
    ];
  }

  /**
   * Handles the report generated event.
   */
  public function onReportGenerated(ReportGeneratedEvent $event): void {
    $module = 'time_log_tracker';
    $key = 'report_email';
    $to = $event->email;
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Render the report to plain text for the email.
    $report_render = $event->reportData;
    $rendered_output = $this->renderer->renderInIsolation($report_render);

    $params = [
      'message' => (string) $rendered_output,
    ];

    $this->mailManager->mail($module, $key, $to, $langcode, $params, NULL, TRUE);
  }

}
