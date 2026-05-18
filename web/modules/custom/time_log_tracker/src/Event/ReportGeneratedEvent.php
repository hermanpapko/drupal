<?php

declare(strict_types=1);

namespace Drupal\time_log_tracker\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a time log report is generated and needs to be sent.
 */
final class ReportGeneratedEvent extends Event {

  /**
   * The event name.
   */
  public const EVENT_NAME = 'time_log_tracker.report_generated';

  /**
   * Constructs the object.
   *
   * @param array $reportData
   *   The generated report data.
   * @param string $email
   *   The recipient email address.
   */
  public function __construct(
    public readonly array $reportData,
    public readonly string $email,
  ) {}

}
