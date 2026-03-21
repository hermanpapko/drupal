<?php

namespace Drupal\my_helper;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

class GreetingGenerator
{

  public function __construct(
    protected Connection $database,
    protected AccountProxyInterface $account,
  )
  {
  }

  public function getGreetingData($name)
  {
    $name = $this->account->getDisplayName();

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
      'current_time' => date('H:i'),
    ];
  }
}
