<?php

declare(strict_types=1);

namespace Drupal\my_helper\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_events\EntityEventType;
use Drupal\entity_events\Event\EntityEvent;
use Drupal\my_helper\Event\BatchFinishedEvent;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to events for my_helper module.
 */
class ApiItemSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs an ApiItemSubscriber object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly MessengerInterface $messenger,
    private readonly ?PasswordGeneratorInterface $passwordGenerator = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EntityEventType::INSERT => ['handlePresave'],
      EntityEventType::DELETE => ['handleDelete'],
      BatchFinishedEvent::EVENT_NAME => ['handleBatchFinished'],
    ];
  }

  /**
   * Handles user creation and assignment when an ApiItem is saved.
   */
  public function handlePresave(EntityEvent $event): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $event->getEntity();

    if ($node->bundle() !== 'api_item') {
      return;
    }

    $status = $node->hasField('field_species') ? $node->get('field_species')->value : 'unknown';
    if (empty($status)) {
      $status = 'unknown';
    }

    $role_machine_name = 'rm_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $status));

    if (!Role::load($role_machine_name)) {
      Role::create([
        'id' => $role_machine_name,
        'label' => 'RM Species: ' . ucfirst((string) $status),
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
      $node->setOwnerId(1);
    }
  }

  /**
   * Handles user deletion when an ApiItem is deleted.
   */
  public function handleDelete(EntityEvent $event): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $event->getEntity();

    if ($node->bundle() !== 'api_item') {
      return;
    }

    $owner_id = (int) $node->getOwnerId();

    if ($owner_id > 1) {
      $user = User::load($owner_id);
      if ($user && str_starts_with($user->getAccountName(), 'rm_character_')) {
        $user->delete();
      }
    }
  }

  /**
   * Handles the batch finished event.
   */
  public function handleBatchFinished(BatchFinishedEvent $event): void {
    if ($event->success) {
      $imported = $event->results['imported'] ?? 0;
      $deleted = $event->results['deleted'] ?? 0;
      if ($imported > 0) {
        $this->messenger->addMessage($this->t('Successfully imported @count items.', ['@count' => $imported]));
      }
      if ($deleted > 0) {
        $this->messenger->addMessage($this->t('Successfully deleted @count items.', ['@count' => $deleted]));
      }
    }
    else {
      $this->messenger->addError($this->t('An error occurred during the batch process.'));
    }
  }

}
