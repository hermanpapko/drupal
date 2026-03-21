<?php

namespace Drupal\my_helper;

use Drupal\Core\Database\Connection;

class GreetingGenerator
{

  public function __construct(
    protected Connection $database,
  )
  {
  }

  public function getGreetingData($name)
  {
    $count = $this->database->select('users_field_data', 'u')
      ->condition('u.uid', 0, '>')
      ->countQuery()
      ->execute()
      ->fetchField();

    $hour = (int)date('G');

    if ($hour < 12) {
      $greeting = 'Good morning';
    } elseif ($hour < 18) {
      $greeting = 'Good afternoon';
    } else {
      $greeting = 'Good evening';
    }

    return [
      'greeting' => $greeting,
      'user_count' => $count,
      'user_name' => $name,
    ];
  }
}
