<?php

declare(strict_types=1);

namespace Drupal\my_helper\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to ApiItem events.
 */
class ApiItemSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an ApiItemSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface|null $passwordGenerator
   *   The password generator service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly ?PasswordGeneratorInterface $passwordGenerator = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [];
  }

  /**
   * Handles user creation and assignment when an ApiItem is saved.
   */
  public function handlePresave(NodeInterface $node): void {
    if ($node->getType() !== 'api_item') {
      return;
    }

    $status = $node->get('field_status')->value;
    if (empty($status)) {
      $status = 'unknown';
    }

    $role_machine_name = 'rm_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $status));

    if (!Role::load($role_machine_name)) {
      Role::create([
        'id' => $role_machine_name,
        'label' => 'RM Status: ' . ucfirst((string) $status),
      ])->save();
    }

    $api_id = $node->get('field_api_id')->value;
    $username = 'rm_character_' . $api_id;

    $existing_users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['name' => $username]);
    $user = reset($existing_users);

    if (!$user) {
      try {
        /** @var \Drupal\user\UserInterface $user */
        $user = User::create();
        if ($this->passwordGenerator) {
          $password = $this->passwordGenerator->generate();
        }
        else {
          // Fallback for very old versions or misconfigured environments.
          $password = bin2hex(random_bytes(10));
        }
        $user->setPassword($password);
        $user->enforceIsNew();
        $user->setEmail($username . '@example.com');
        $user->setUsername($username);
        $user->addRole($role_machine_name);
        $user->activate();
        $user->save();
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('my_helper')->error('Could not create a user: ' . $e->getMessage());
        $user = NULL;
      }
    }

    if ($user) {
      $node->setOwnerId($user->id());
    }
    else {
      // Default to admin (UID 1) if user creation failed.
      $node->setOwnerId(1);
    }
  }

  /**
   * Handles user deletion when an ApiItem is deleted.
   */
  public function handleDelete(NodeInterface $node): void {
    if ($node->getType() !== 'api_item') {
      return;
    }

    $owner_id = (int) $node->getOwnerId();

    // Only delete if it's not the default admin user.
    if ($owner_id > 1) {
      $user = User::load($owner_id);

      if ($user && str_starts_with($user->getAccountName(), 'rm_character_')) {
        $user->delete();
      }
    }
  }

}
