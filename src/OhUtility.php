<?php

namespace Drupal\oh;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Provide standalone utilities assisting opening hours.
 */
class OhUtility {

  /**
   * Downgrades a DrupalDateTime object to PHP date time.
   *
   * Useful in situations where object comparison is used.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $drupalDateTime
   *   A Drupal datetime object.
   *
   * @see https://www.drupal.org/node/2936388
   *
   * @return \Datetime
   *   A PHP datetime object.
   */
  public static function toPhpDateTime(DrupalDateTime $drupalDateTime) {
    return new \DateTime($drupalDateTime->format('r'), $drupalDateTime->getTimezone());
  }

}
