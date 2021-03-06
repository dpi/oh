<?php

namespace Drupal\oh;

use DateTimeInterface;

/**
 * Defines a date range.
 */
class OhDateRange {

  /**
   * The start date.
   *
   * @var \DateTimeInterface
   */
  protected $start;

  /**
   * The end date.
   *
   * @var \DateTimeInterface
   */
  protected $end;

  /**
   * Constructs a new OhDateRange.
   *
   * @param \DateTimeInterface $start
   *   The start date.
   * @param \DateTimeInterface $end
   *   The end date.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function __construct(DateTimeInterface $start, DateTimeInterface $end) {
    $this->setStart($start);
    $this->setEnd($end);
  }

  /**
   * Get the start date.
   *
   * @return \DateTimeInterface
   *   The start date.
   */
  public function getStart(): DateTimeInterface {
    return clone $this->start;
  }

  /**
   * Set the start date.
   *
   * @param \DateTimeInterface $start
   *   The start date.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function setStart(DateTimeInterface $start) {
    // Clone to ensure references are lost.
    $this->start = clone $start;
    $this->validateDates();
    return $this;
  }

  /**
   * Get the end date.
   *
   * @return \DateTimeInterface
   *   The end date.
   */
  public function getEnd(): DateTimeInterface {
    return clone $this->end;
  }

  /**
   * Set the end date.
   *
   * @param \DateTimeInterface $end
   *   The end date.
   *
   * @return $this
   *   Return object for chaining.
   *
   * @throws \InvalidArgumentException
   *   When there is a problem with the start and/or end date.
   */
  public function setEnd(DateTimeInterface $end) {
    // Clone to ensure references are lost.
    $this->end = clone $end;
    $this->validateDates();
    return $this;
  }

  /**
   * Calculates the difference between the start and end dates.
   *
   * @return \DateInterval
   *   The difference between the start and end dates.
   */
  public function diff(): \DateInterval {
    return $this->end->diff($this->start);
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

      if ($this->end < $this->start) {
        throw new \InvalidArgumentException('End date must not occur before start date.');
      }
    }
  }

  /**
   * Callback for \usort()/\uasort() to sort date range objects by start time.
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
    return $a->getStart() <=> $b->getStart();
  }

  /**
   * Ensures a date range occurs within this date range.
   *
   * @param \Drupal\oh\OhDateRange $innerRange
   *   The inner date range.
   * @param bool $partial
   *   Changes the mode so inner values must intersect outer values in any way.
   *
   * @return bool
   *   Returns true if inner range is within outer range. Exception otherwise.
   *
   * @throws \Exception
   *   Thrown if this date range exceeds the boundaries of the outer date range.
   */
  public function isWithin(OhDateRange $innerRange, bool $partial = FALSE): bool {
    $outerStart = $this->start;
    $outerEnd = $this->end;

    $innerStart = $innerRange->getStart();
    $innerEnd = $innerRange->getEnd();

    if ($partial) {
      // Either inner value must be within.
      if ($innerEnd < $outerStart || $innerStart >= $outerEnd) {
        throw new \Exception('Either inner value is not within outer dates.');
      }
    }
    else {
      if ($innerStart < $outerStart) {
        if (!$partial || $innerStart > $outerEnd) {
          throw new \Exception('Inner date starts before outer date.');
        }
      }
      if ($innerStart > $outerEnd) {
        throw new \Exception('Inner date starts after outer date.');
      }

      // Dont test for inner end less than outer start because inner-start <
      // outer-start throw first.
      if ($innerEnd > $outerEnd) {
        throw new \Exception('Inner date ends after outer date.');
      }
    }

    return TRUE;
  }

}
