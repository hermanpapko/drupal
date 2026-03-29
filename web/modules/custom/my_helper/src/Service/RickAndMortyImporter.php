<?php

declare(strict_types=1);

namespace Drupal\my_helper\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

final class RickAndMortyImporter {
  private LoggerChannelInterface $logger;

  public function __construct(
    private readonly RickAndMortyApiClient $apiClient,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('my_helper');
  }

  public function import(int $page = 1): void {
    $characters = $this->apiClient->getCharacters($page);

    if (empty($characters)) {
      $this->logger->warning('No results found.');
      return;
    }

    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $importedCount = 0;

    foreach ($characters as $char) {
      $existingNodes = $nodeStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'api_item')
        ->condition('field_api_id', (int) $char['id'])
        ->execute();

      $nodeData = [
        'type' => 'api_item',
        'title' => $char['name'],
        'field_api_id' => (int) $char['id'],
        'field_status' => $char['status'],
        'field_species' => $char['species'],
      ];

      if (!empty($existingNodes)) {
        $nid = reset($existingNodes);
        $node = $nodeStorage->load($nid);

        foreach ($nodeData as $fieldName => $value) {
          $node->set($fieldName, $value);
        }
        $node->save();
      } else {
        $node = $nodeStorage->create($nodeData);
        $node->save();
      }

      $importedCount++;
    }

    $this->logger->info("Imported {$importedCount} characters.", [
      '@count' => $importedCount,
    ]);
  }
}
