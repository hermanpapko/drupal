<?php

declare(strict_types=1);

namespace Drupal\my_helper\Plugin\GraphQL\DataProducer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @DataProducer(
 *   id = "query_api_items",
 *   name = @Translation("Query API Items"),
 *   produces = @ContextDefinition("any", label = @Translation("API Items list")),
 *   consumes = {
 *     "limit" = @ContextDefinition("integer", label = @Translation("Limit"), required = FALSE),
 *     "offset" = @ContextDefinition("integer", label = @Translation("Offset"), required = FALSE)
 *   }
 * )
 */
final class QueryApiItems extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  public function resolve(?int $limit = 10, ?int $offset = 0): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'api_item')
      ->condition('status', 1)
      ->range($offset ?? 0, $limit ?? 10)
      ->sort('created', 'DESC');

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }
}
