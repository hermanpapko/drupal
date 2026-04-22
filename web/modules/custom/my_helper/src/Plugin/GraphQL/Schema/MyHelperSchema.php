<?php

declare(strict_types=1);

namespace Drupal\my_helper\Plugin\GraphQL\Schema;

use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistry;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase;

/**
 * @Schema(
 *   id = "my_helper_schema",
 *   name = "My Helper Schema"
 * )
 */
final class MyHelperSchema extends SdlSchemaPluginBase {

  public function getResolverRegistry(): ResolverRegistry {
    $builder = new ResolverBuilder();
    $registry = new ResolverRegistry();

    $this->addQueryResolvers($registry, $builder);
    $this->addNodeApiItemResolvers($registry, $builder);

    return $registry;
  }

  private function addQueryResolvers(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('Query', 'apiItem',
      $builder->produce('entity_load')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['api_item']))
        ->map('id', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'apiItems',
      $builder->produce('query_api_items')
        ->map('limit', $builder->fromArgument('limit'))
        ->map('offset', $builder->fromArgument('offset'))
    );
  }

  private function addNodeApiItemResolvers(ResolverRegistry $registry, ResolverBuilder $builder): void {
    $registry->addFieldResolver('NodeApiItem', 'id',
      $builder->produce('entity_id')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('NodeApiItem', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('NodeApiItem', 'status',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_status.value'))
    );

    $registry->addFieldResolver('NodeApiItem', 'species',
      $builder->produce('property_path')
        ->map('type', $builder->fromValue('entity:node'))
        ->map('value', $builder->fromParent())
        ->map('path', $builder->fromValue('field_species.value'))
    );
  }
}
