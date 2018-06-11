<?php

namespace Drupal\oh;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Defines a date range.
 */
class OhDateRange {

  /**
   * The start date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $start;

  /**
   * The end date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $end;

  /**
   * Constructs a new OhDateRange.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start date.
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The end date.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function __construct(DrupalDateTime $start, DrupalDateTime $end) {
    $this->setStart($start);
    $this->setEnd($end);
  }

  /**
   * Get the start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The start date.
   */
  public function getStart(): DrupalDateTime {
    return $this->start;
  }

  /**
   * Set the start date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start date.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function setStart(DrupalDateTime $start) {
    // Clone to ensure references are lost.
    $this->start = clone $start;
    $this->validateDates();
    return $this;
  }

  /**
   * @return \Drupal\Core\Datetime\DrupalDateTime
   */
  public function getEnd(): DrupalDateTime {
    return $this->end;
  }

  /**
   * Set the end date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The end date.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function setEnd(DrupalDateTime $end) {
    // Clone to ensure references are lost.
    $this->end = clone $end;
    $this->validateDates();
    return $this;
  }

  /**
   * Validates the start and end dates.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  protected function validateDates() {
    // Wait until both start and end are set before validating.
    if ($this->start && $this->end) {
      if ($this->start->getTimezone()->getName() !== $this->end->getTimezone()->getName()) {
        throw new \InvalidArgumentException('Provided dates must be in same timezone.');
      }
    }
  }

  /**
   * Helper callback for uasort() to sort date range objects by start time.
   *
   * @param \Drupal\oh\OhDateRange $a
   *   A date range object.
   * @param \Drupal\oh\OhDateRange $b
   *   A date range object.
   *
   * @return int
   *   Whether date range A is lower than date range B.
   */
  public static function sort(OhDateRange $a, OhDateRange $b): int {
    return ($a->getStart() < $b->getStart()) ? -1 : 1;
  }

  /**
   * Ensures this date range occurs within another date range.
   *
   * @param \Drupal\oh\OhDateRange $outerRange
   *   The outer date range.
   *
   * @return bool
   *   Returns true if inner range is within outer range. Exception otherwise.
   *
   * @throws \Exception
   *   Thrown if this date range exceeds the boundaries of the outer date range.
   */
  public function isBetween(OhDateRange $outerRange): bool {
    $outerStart = OhUtility::toPhpDateTime($outerRange->getStart());
    $outerEnd = OhUtility::toPhpDateTime($outerRange->getEnd());

    $innerStart = OhUtility::toPhpDateTime($this->start);
    if ($innerStart < $outerStart) {
      throw new \Exception('Inner date starts before outer date starts.');
    }
    if ($innerStart > $outerEnd) {
      throw new \Exception('Inner date starts after outer date ends.');
    }

    $innerEnd = OhUtility::toPhpDateTime($this->end);
    if ($innerEnd < $outerStart) {
      throw new \Exception('Inner date ends before outer date starts.');
    }
    if ($innerEnd > $outerEnd) {
      throw new \Exception('Inner date ends after outer date ends.');
    }

    return TRUE;
  }

}
