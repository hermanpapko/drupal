<?php

declare (strict_types=1);

namespace Drupal\my_helper;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

class LoginHandler {

  use StringTranslationTrait;

  public function __construct(
    protected readonly LoggerInterface $logger,
    protected readonly MessengerInterface $messenger,
  ) {}

  public function onUserLogin(AccountInterface $account): void {
    $this->logger->notice('User @name has logged in', [
      '@name' => $account->getDisplayName(),
    ]);

    $this->messenger->addStatus($this->t('Welcome back, @name!', [
      '@name' => $account->getDisplayName(),
    ]));
  }

}
