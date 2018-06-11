<?php

namespace Drupal\oh\Event;

/**
 * Defines events for opening hours.
 */
final class OhEvents {

  /**
   * Used to add exceptions between a date range.
   *
   * @Event
   *
   * @see \Drupal\oh\Event\OhExceptionEvent
   */
  const EXCEPTIONS = 'oh.exceptions';

}
