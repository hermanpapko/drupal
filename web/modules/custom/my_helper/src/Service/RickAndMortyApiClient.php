<?php

declare(strict_types=1);

namespace Drupal\my_helper\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Client for the Rick and Morty API.
 */
final class RickAndMortyApiClient {
  private const API_URL = 'https://rickandmortyapi.com/api/character';

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  public function __construct(
    private readonly ClientInterface $httpClient,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->logger = $loggerFactory->get('my_helper');
  }

  /**
   * Fetches characters from the API.
   *
   * @param int $page
   *   The page number to fetch.
   *
   * @return array
   *   An array of character data.
   */
  public function getCharacters(int $page = 1): array {

    $statusFilter = $this->configFactory->get('my_helper.settings')->get('status_filter');

    $queryParams = ['page' => $page];
    if (!empty($statusFilter)) {
      $queryParams['status'] = $statusFilter;
    }

    try {
      $response = $this->httpClient->request('GET', self::API_URL, [
        'query' => $queryParams,
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
      return $data['results'] ?? [];
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
    }
    return [];
  }

  /**
   * @param array $ids
   *
   * @return array
   */

  public function getCharactersByIds(array $ids): array {
    try {
      $response = $this->httpClient->request('GET', self::API_URL . '/' . implode(',', $ids), [
        'timeout' => 10,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);

      return isset($data['id']) ? [$data] : $data;
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
    }
    return [];
  }

}
