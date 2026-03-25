<?php

namespace Drupal\my_helper;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountProxyInterface;


class GreetingGenerator
{

  public function __construct(
    protected Connection $database,
    protected AccountProxyInterface $account,
    protected TimeInterface $time,
    protected DateFormatterInterface $dateFormatter,
  )
  {
  }

  public function getGreetingData($name)
  {
    $name = ($this->account->isAuthenticated()) ? $this->account->getDisplayName() : $name;

    $count = $this->database->select('users_field_data', 'u')
      ->condition('u.uid', 0, '>')
      ->condition('u.default_langcode', 1)
      ->countQuery()
      ->execute()
      ->fetchField();

    $current_timestamp = $this->time->getRequestTime();

    $hour = (int) $this->dateFormatter->format($current_timestamp, 'custom', 'G');

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
      'current_time' => $this->dateFormatter->format($current_timestamp, 'custom', 'H:i'),
    ];
  }
}
