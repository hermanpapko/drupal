<?php

declare(strict_types=1);

namespace Drupal\my_helper;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a my helper module entity type.
 */
interface TimeLogInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
