<?php

namespace Drupal\time_log_tracker\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Interface for ReportGenerator plugins.
 */
interface ReportGeneratorInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Generates the report render array.
   *
   * @return array
   *   A renderable array containing the report.
   */
  public function generateReport(): array;

}
