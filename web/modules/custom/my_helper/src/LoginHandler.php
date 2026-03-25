<?php

namespace Drupal\my_helper;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

class LoginHandler
{
  use StringTranslationTrait;

  public function __construct(
    protected LoggerInterface $logger,
    protected MessengerInterface $messenger,
  ) {}

  public function onUserLogin(AccountInterface $account) {
    $this->logger->notice('User @name has logged in', [
      '@name' => $account->getDisplayName(),
    ]);

    $this->messenger->addStatus($this->t('Welcome back, @name!', [
      '@name' => $account->getDisplayName(),
    ]));
  }
}
