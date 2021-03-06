<?php

namespace Drupal\oh_review;

use Drupal\oh\OhDateRange;
use Drupal\oh\OhOccurrence;

/**
 * Utility for OH Review.
 */
class OhReviewUtility {

  /**
   * Groups occurrences into days and weeks.
   *
   * @param \Drupal\oh\OhDateRange $range
   *   The full occurrence range.
   * @param array $occurrences
   *   An array of occurrences.
   * @param bool $fillDays
   *   Whether create empty days for when there are no occurrences.
   *
   * @return array
   *   An array of occurrences grouped by weeks, then days.
   */
  public static function occurrencesByWeek(OhDateRange $range, array $occurrences, bool $fillDays): array {

    $weekFormat = 'Y-W';
    $dayFormat = 'Y-m-d';

    $occurrencesByDay = [];

    if ($fillDays) {
      // Fill in the days.
      $fillPointer = $range->getStart();
      $fillEnd = $range->getEnd();
      $fillEnd->modify('-1 second');
      while ($fillPointer < $fillEnd) {
        $pointerDay = $fillPointer->format($dayFormat);
        if (!isset($occurrencesByDay[$pointerDay])) {
          $occurrencesByDay[$pointerDay] = [];
        }
        $fillPointer->modify('+1 day');
      }
    }

    uasort($occurrences, [OhOccurrence::class, 'sort']);
    foreach ($occurrences as $occurrence) {
      $day = $occurrence->getStart()->format($dayFormat);
      $occurrencesByDay[$day][] = $occurrence;
    }

    // Group into weeks.
    $groupedByWeek = [];

    foreach ($occurrencesByDay as $dayCode => $occurrences) {
      if (!count($occurrences)) {
        $day = \DateTime::createFromFormat($dayFormat, $dayCode);
        $week = $day->format($weekFormat);
        $groupedByWeek[$week][$dayCode] = $groupedByWeek[$week][$dayCode] ?? [];
      }
      foreach ($occurrences as $occurrence) {
        $week = $occurrence->getStart()->format($weekFormat);
        $day = $occurrence->getStart()->format($dayFormat);
        $groupedByWeek[$week][$day][] = $occurrence;
      }
    }

    return $groupedByWeek;
  }

}
