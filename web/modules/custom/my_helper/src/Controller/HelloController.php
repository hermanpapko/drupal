<?php

declare(strict_types=1);

namespace Drupal\my_helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\my_helper\GreetingGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class HelloController extends ControllerBase {

  public function __construct(
    protected readonly GreetingGenerator $greetingGenerator
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('my_helper.greeting_generator')
    );
  }

  public function content(string $name): array {
    $data = $this->greetingGenerator->getGreetingData($name);

    return [
      '#theme' => 'my_greeting_template',
      '#greeting_text' => $data['greeting'],
      '#user_count' => $data['user_count'],
      '#user_name' => $data['user_name'],
      '#visit_time' => $data['current_time'],
    ];
  }

}
