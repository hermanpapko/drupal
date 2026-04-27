<?php

declare(strict_types=1);

namespace Drupal\my_helper\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when the API batch is finished.
 */
class BatchFinishedEvent extends Event {

  const EVENT_NAME = 'my_helper_api_batch_finished';

  /**
   * Constructs a new BatchFinishedEvent.
   *
   * @param bool $success
   *   Whether the batch succeeded.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   The batch operations.
   */
  public function __construct(
    public readonly bool $success,
    public readonly array $results,
    public readonly array $operations,
  ) {}

}
