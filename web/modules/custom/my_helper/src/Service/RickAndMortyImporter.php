<?php

declare(strict_types=1);

namespace Drupal\my_helper\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Imports character data from the Rick and Morty API.
 */
final class RickAndMortyImporter {
  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  public function __construct(
    private readonly RickAndMortyApiClient $apiClient,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly FileSystemInterface $fileSystem,
  ) {
    $this->logger = $loggerFactory->get('my_helper');
  }

  /**
   * Imports characters from the API.
   *
   * @param int $page
   *   The page number to import.
   */
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
        'field_species' => $char['species'],
      ];

      // Handle Image.
      if (!empty($char['image'])) {
        $mediaId = $this->getOrCreateMedia($char['image'], $char['name']);
        if ($mediaId) {
          $nodeData['field_api_image'] = ['target_id' => $mediaId];
        }
      }

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

      $importedCount++;
    }

    $this->logger->info("Imported {$importedCount} characters.", [
      '@count' => $importedCount,
    ]);
  }

  /**
   * Downloads image and creates Media entity.
   */
  private function getOrCreateMedia(string $url, string $name): ?int {
    try {
      $directory = 'public://rick_and_morty';
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $filename = basename(parse_url($url, PHP_URL_PATH));
      $destination = $directory . '/' . $filename;

      // Check if file already exists.
      $fileStorage = $this->entityTypeManager->getStorage('file');
      $existingFiles = $fileStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uri', $destination)
        ->execute();

      if (!empty($existingFiles)) {
        $fid = reset($existingFiles);
      }
      else {
        $data = file_get_contents($url);
        if ($data === FALSE) {
          throw new \Exception("Could not download image from $url");
        }
        $file = $this->fileSystem->saveData($data, $destination, FileExists::Replace);
        $fileEntity = $fileStorage->create([
          'uri' => $file,
          'status' => 1,
        ]);
        $fileEntity->save();
        $fid = $fileEntity->id();
      }

      // Check if Media already exists for this file.
      $mediaStorage = $this->entityTypeManager->getStorage('media');
      $existingMedia = $mediaStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('bundle', 'image')
        ->condition('field_media_image.target_id', $fid)
        ->execute();

      if (!empty($existingMedia)) {
        return (int) reset($existingMedia);
      }

      /** @var \Drupal\media\MediaInterface $media */
      $media = $mediaStorage->create([
        'bundle' => 'image',
        'name' => $name,
        'uid' => 1,
        'field_media_image' => [
          'target_id' => $fid,
          'alt' => $name,
        ],
      ]);
      $media->save();
      return (int) $media->id();

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to import media for @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

}
