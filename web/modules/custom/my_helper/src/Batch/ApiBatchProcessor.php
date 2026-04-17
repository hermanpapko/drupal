<?php

declare(strict_types=1);

namespace Drupal\my_helper\Batch;

use Drupal\node\Entity\Node;

/**
 * Processor for the API batch operations.
 */
class ApiBatchProcessor {

  /**
   * Processes the character import.
   *
   * @param array $ids
   *   The character IDs to import.
   * @param array $context
   *   The batch context.
   */
  public static function processImport(array $ids, array &$context): void {
    /** @var \Drupal\my_helper\Service\RickAndMortyApiClient $apiClient */
    $apiClient = \Drupal::service('my_helper.rm_api_client');
    /** @var \Drupal\my_helper\Service\RickAndMortyImporter $importer */
    $importer = \Drupal::service('my_helper.rm_importer');

    $characters = $apiClient->getCharactersByIds($ids);
    $nodeStorage = \Drupal::entityTypeManager()->getStorage('node');

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
      }
      else {
        $node = $nodeStorage->create($nodeData);
        $node->save();
      }
    }

    $context['results']['imported'] = ($context['results']['imported'] ?? 0) + count($characters);
    $context['message'] = "Importing " . count($characters) . " characters...";
  }

  /**
   * Processes the character deletion.
   *
   * @param array $nids
   *   The node IDs to delete.
   * @param array $context
   *   The batch context.
   */
  public static function processDelete(array $nids, array &$context): void {
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $storage->loadMultiple($nids);
    $storage->delete($nodes);

    $context['results']['deleted'] = ($context['results']['deleted'] ?? 0) + count($nids);
    $context['message'] = "Deleting " . count($nids) . " items...";
  }

  /**
   * Finished callback for the batch.
   *
   * @param bool $success
   *   Whether the batch succeeded.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   The batch operations.
   */
  public static function batchFinished($success, $results, $operations): void {
    if ($success) {
      $imported = $results['imported'] ?? 0;
      $deleted = $results['deleted'] ?? 0;
      if ($imported > 0) {
        \Drupal::messenger()->addMessage("Successfully imported $imported items.");
      }
      if ($deleted > 0) {
        \Drupal::messenger()->addMessage("Successfully deleted $deleted items.");
      }
    } else {
      \Drupal::messenger()->addError("An error occurred during the batch process.");
    }
  }
}
