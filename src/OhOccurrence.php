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
   * Informs regular hours for the same day should be voided.
   */
  public const REGULAR_HOUR_INTERACTION_VOID_REGULAR = 0b1;

  /**
   * Informs this occurrence will not trigger regular hours to be voided.
   */
  public const REGULAR_HOUR_INTERACTION_NO_VOID_REGULAR = 0b0;

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
   * Regular hours interaction behaviour.
   *
   * @var int
   */
  protected $regularHourInteraction = self::REGULAR_HOUR_INTERACTION_NO_VOID_REGULAR;

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

  /**
   * Sets interaction behaviour with regular hours.
   *
   * Note: it only takes one exception to trigger voiding.
   *
   * @param int $value
   *   Interaction constant. See static::REGULAR_HOUR_INTERACTION_*.
   *
   * @return $this
   *   Return object for chaining.
   */
  public function setRegularHourInteraction(int $value = self::REGULAR_HOUR_INTERACTION_NO_VOID_REGULAR) {
    $this->regularHourInteraction = $value;
    return $this;
  }

  /**
   * Get interaction behaviour with regular hours.
   *
   * This method should only be used by exceptions, not regular hours.
   *
   * @return mixed
   *   Interaction constant. See static::REGULAR_HOUR_INTERACTION_*.
   */
  public function getRegularHourInteraction(): int {
    return $this->regularHourInteraction;
  }

  /**
   * Whether this occurrence at least a day long.
   *
   * @return bool
   *   Whether this day is considered a full day or longer.
   */
  public function isFullDay(): bool {
    $fullDay = new \DateInterval('PT' . ((60 * 60 * 24) - 60) . 'S');
    $dayAfterStart = (clone $this->getStart()->getPhpDateTime())
      ->add($fullDay);
    return $this->getEnd()->getPhpDateTime() > $dayAfterStart;
  }

}
