<?php

declare(strict_types=1);

namespace Drupal\my_helper\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

/**
 * Client for the Rick and Morty API using GraphQL.
 */
final class RickAndMortyApiClient {
  private const GRAPHQL_URL = 'https://rickandmortyapi.com/graphql';

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

    $query = '
      query GetCharacters($page: Int, $filter: FilterCharacter) {
        characters(page: $page, filter: $filter) {
          results {
            id
            name
            status
            species
            image
          }
        }
      }
    ';

    $variables = ['page' => $page];

    if (!empty($statusFilter)) {
      $queryParams['status'] = $statusFilter;
    }

    try {
      $response = $this->httpClient->request('POST', self::GRAPHQL_URL, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'body' => json_encode([
          'query' => $query,
          'variables' => (object) $variables,
        ], JSON_THROW_ON_ERROR),
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
      return $data['data']['characters']['results'] ?? [];
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
    }
    return [];
  }

  /**
   * Fetches characters by their IDs.
   *
   * @param array $ids
   *   The character IDs to fetch.
   *
   * @return array
   *   An array of character data.
   */
  public function getCharactersByIds(array $ids): array {
    if (empty($ids)) {
      return [];
    }
    $query = '
      query GetCharactersByIds($ids: [ID!]!) {
        charactersByIds(ids: $ids) {
          id
          name
          status
          species
          image
        }
      }
    ';

    try {
      $response = $this->httpClient->request('POST', self::GRAPHQL_URL, [
        'headers' => [
          'Content-Type' => 'application/json',
          'Accept' => 'application/json',
        ],
        'body' => json_encode([
          'query' => $query,
          'variables' => (object) ['ids' => $ids],
        ], JSON_THROW_ON_ERROR),
        'timeout' => 10,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);

      return $data['data']['charactersByIds'] ?? [];
    }
    catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
    }
    return [];
  }

}
