<?php

declare(strict_types = 1);

namespace Drupal\Tests\oh\Kernel;

/**
 * Common functions for using oh_test module.
 */
trait OhTestTrait {

  /**
   * Set the scenarios for regular test service.
   *
   * @param array $scenarios
   *   A list of scenarios.
   */
  protected function setRegularScenarios(array $scenarios): void {
    $this->container->get('oh_test.regular')
      ->setScenarios($scenarios);
  }

  /**
   * Set the scenarios for exception test service.
   *
   * @param array $scenarios
   *   A list of scenarios.
   */
  protected function setExceptionScenarios(array $scenarios): void {
    $this->container->get('oh_test.exceptions')
      ->setScenarios($scenarios);
  }

}
