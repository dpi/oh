<?php

namespace Drupal\oh;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;

/**
 * Defines an opening hours occurrence.
 */
class OhOccurrence extends OhDateRange implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

  /**
   * Message to add to the occurrence.
   *
   * @var string[]
   */
  protected $messages = [];

  /**
   * Whether this occurrence is open.
   *
   * @var bool
   */
  protected $open = FALSE;

  /**
   * Add a message for the occurrence.
   *
   * @param string $message
   *   A message for the occurrence.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function addMessage(string $message) {
    $this->messages[] = $message;
    return $this;
  }

  /**
   * Set the messages for the occurrence.
   *
   * @param string[] $messages
   *   The messages for the occurrence.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function setMessages(array $messages) {
    $this->messages = $messages;
    return $this;
  }

  /**
   * Get the messages for the occurrence.
   *
   * @return string[]
   *   The messages for the occurrence.
   */
  public function getMessages(): array {
    return $this->messages;
  }

  /**
   * Set whether this occurrence is open.
   *
   * @param bool $open
   *   Whether this occurrence is open.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function setIsOpen(bool $open) {
    $this->open = $open;
    return $this;
  }

  /**
   * Get whether this occurrence is open.
   *
   * @return bool
   *   Whether this occurrence is open.
   */
  public function isOpen(): bool {
    return $this->open;
  }

}
