<?php

declare(strict_types=1);

namespace Drupal\my_helper\Commands;

use Drush\Commands\DrushCommands;
use Drupal\my_helper\Service\RickAndMortyImporter;
use Drush\Attributes as CLI;

final class MyHelperCommands extends DrushCommands {

  public function __construct(
    private readonly RickAndMortyImporter $importer
  ) {
    parent::__construct();
  }

  #[CLI\Command(name: 'my_helper:import-rm', aliases: ['rm-import'])]
  #[CLI\Help(description: 'Starts importing characters from the Rick and Morty API.', synopsis: 'Import data')]
  #[CLI\Usage(name: 'my_helper:import-rm', description: 'Imports the first page of characters')]
  public function importCharacters(): void {
    $this->output()
      ->writeln('<info>Starting character import (Page 1)...</info>');

    // Run the import
    $this->importer->import(1);

    $this->logger()
      ->success('Import completed! Check Drupal logs for details.');
  }
}
