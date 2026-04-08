<?php

declare(strict_types=1);

namespace Drupal\my_helper\Service;



use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;

final class RickAndMortyApiClient {
  private const API_URL = 'https://rickandmortyapi.com/api/character';
  private LoggerChannelInterface $logger;

  public function __construct(
    private readonly ClientInterface $httpClient,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->logger = $loggerFactory->get('my_helper');
  }

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

      $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
      return $data['results'] ?? [];
    } catch (\Throwable $exception) {
      $this->logger->error($exception->getMessage());
    }
    return [];
  }
}
