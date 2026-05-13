<?php

declare(strict_types=1);

namespace Drupal\time_log_tracker\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a ReportGenerator plugin attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ReportGenerator extends Plugin {

  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
  ) {}

}
