<?php

namespace Drupal\oh;

/**
 * Defines an opening hours occurrence.
 *
 * @todo implement RefinableCacheableDependencyInterface.
 * @todo and use \Drupal\Core\Cache\RefinableCacheableDependencyTrait.
 */
class OhOccurrence extends OhDateRange {

  /**
   * Message to add to the occurrence.
   *
   * @var string|NULL
   */
  protected $message;

  /**
   * Set the message for the occurrence.
   *
   * @param string|NULL $message
   *   The message for the occurrence, or NULL if no message.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function setMessage(?string $message) {
    $this->message = $message;
    return $this;
  }

  /**
   * Get the message for the occurrence.
   *
   * @return string|NULL
   *   The message for the occurrence, or NULL if no message.
   */
  public function getMessage(): ?string {
    return $this->message;
  }

}
