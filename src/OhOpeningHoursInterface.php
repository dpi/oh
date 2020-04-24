<?php

namespace Drupal\oh;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for opening hours service.
 */
interface OhOpeningHoursInterface {

  /**
   * Get hours for a location between a range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get occurrences between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An chronologically ordered array of occurrences, where occurrences
   *   do not overlap/intersect. E.g you may receive occurrences:
   *     For:
   *       1. Regular hours: 9am-5pm (message: 'hello world').
   *       2. Closure exception: 12:30-1pm (message: 'closed for lunch').
   *     Returned:
   *       1. Opening 9am-12:30pm (message: 'hello world').
   *       2. Closure 12:30pm-1pm (message: 'closed for lunch').
   *       3. Opening 1pm-5pm (message: 'hello world').
   */
  public function getOccurrences(EntityInterface $entity, OhDateRange $range): array;

  /**
   * Get hours for a location between a range.
   *
   * Closure and opening hours may intersect, requiring computation to resolve.
   * Its recommended to use ::getOccurrences instead as this function flattens
   * opening and closures for easier handling.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get occurrences between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An unordered array of occurrences. Closures time periods will overlap
   *   regular-openings and exception-openings. E.g you may receive occurrences:
   *     For:
   *       1. Regular hours: 9am-5pm (message: 'hello world').
   *       2. Closure exception: 12:30-1pm (message: 'closed for lunch').
   *     Returned:
   *       1. Opening 9am-5pm (message: 'hello world').
   *       2. Closure 12:30pm-1pm (message: 'closed for lunch').
   */
  public function getDimensionalOccurrences(EntityInterface $entity, OhDateRange $range): array;

  /**
   * Get regular hours for a location between a range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get regular hours between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An unordered array of occurrences.
   */
  public function getRegularHours(EntityInterface $entity, OhDateRange $range): array;

  /**
   * Get exceptions for a location between a range.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The location.
   * @param \Drupal\oh\OhDateRange $range
   *   Get exceptions between a range of dates.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An unordered array of occurrences.
   */
  public function getExceptions(EntityInterface $entity, OhDateRange $range): array;

}
