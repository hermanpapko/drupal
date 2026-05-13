<?php

declare(strict_types=1);

namespace Drupal\time_log_tracker\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\time_log_tracker\Attribute\ReportGenerator;

/**
 * Manager for ReportGenerator plugins.
 */
class ReportGeneratorManager extends DefaultPluginManager {

  /**
   * Constructs a new ReportGeneratorManager object.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/ReportGenerator',
      $namespaces,
      $module_handler,
      ReportGeneratorInterface::class,
      ReportGenerator::class
    );
    $this->alterInfo('time_log_tracker_report_generator_info');
    $this->setCacheBackend($cache_backend, 'time_log_tracker_report_generator_plugins');
  }

}
