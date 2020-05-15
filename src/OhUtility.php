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

  /**
   * Flattens occurrences so they do not overlap.
   *
   * Takes occurrences and flattens so that occurrences do not overlap. Some
   * occurrences will be broken up.
   *
   * If occurrences have messages, they will be broken up too. Handle
   * appropriately, such as deduplication, on the front end.
   *
   * @param \Drupal\oh\OhOccurrence[] $occurrences
   *   An array of occurrences to flatten.
   *
   * @return \Drupal\oh\OhOccurrence[]
   *   An array of occurrences whose ranges do not overlap.
   */
  public static function flattenOccurrences(array $occurrences) {
    // Create markers.
    $markers = [];
    foreach ($occurrences as $occurrence) {
      $markers[] = [
        $occurrence->isOpen(),
        $occurrence->getStart()->getPhpDateTime(),
        TRUE,
        $occurrence->getMessages(),
      ];
      $markers[] = [
        $occurrence->isOpen(),
        $occurrence->getEnd()->getPhpDateTime(),
        FALSE,
        $occurrence->getMessages(),
      ];
    }

    // Order markers by time ASC.
    usort($markers, function (array $markerA, array $markerB) {
      return $markerA[1] > $markerB[1];
    });

    // Compute collisions. (Flatten intersecting periods of same type).
    // E.g, for closures [10am-2pm, 11am-3pm], results in: [10am-3pm].
    $markers = static::combineIntersecting($markers, FALSE);
    $markers = static::combineIntersecting($markers, TRUE);

    // Remove opening markers between closure start + end markers.
    $betweenClosed = FALSE;
    foreach ($markers as $k => $marker) {
      [$isOpen, $date, $isStart] = $marker;
      assert($date instanceof \DateTime && is_bool($isOpen) && is_bool($isStart));
      if (!$isOpen) {
        $betweenClosed = $isStart;
      }

      if ($isOpen && $betweenClosed) {
        unset($markers[$k]);
      }
    }

    // Rekey to make sequential.
    $markers = array_values($markers);

    $newOccurrences = [];
    foreach (array_keys($markers) as $k) {
      // Do it this way so markers can be unset in this body:
      $marker = $markers[$k] ?? NULL;
      if (!$marker) {
        continue;
      }

      [$isOpen, $date, $isStart] = $marker;
      assert($date instanceof \DateTime && is_bool($isOpen) && is_bool($isStart));

      if ($isOpen) {
        if ($isStart) {
          $nextMarker = $markers[$k + 1];
          [$nextIsOpen, $nextDate] = $nextMarker;
          assert($nextDate instanceof \DateTime && is_bool($nextIsOpen));

          // If the next marker is an opening, then remove it from iteration.
          if ($nextIsOpen) {
            unset($markers[$k + 1]);
          }

          $nextDate->setTimezone($date->getTimezone());
          $newOccurrences[] = (new OhOccurrence(
            DrupalDateTime::createFromDateTime($date),
            DrupalDateTime::createFromDateTime($nextDate),
          ))->setIsOpen(TRUE)->setMessages($marker[3]);
        }
        else {
          // If this is an end marker, get the previous marker.
          $previousMarker = $markers[$k - 1];
          [, $previousDate] = $previousMarker;
          assert($previousDate instanceof \DateTime);

          $date->setTimezone($previousDate->getTimezone());
          $newOccurrences[] = (new OhOccurrence(
            DrupalDateTime::createFromDateTime($previousDate),
            DrupalDateTime::createFromDateTime($date),
          ))->setIsOpen(TRUE)->setMessages($marker[3]);
        }
      }
      else {
        // Closure markers are always together. In between opening markers were
        // already removed in a previous loop.
        // We ignore end closure markers entirely since they are built with the
        // starter marker here:
        if ($isStart) {
          $nextMarker = $markers[$k + 1];
          [, $nextDate] = $nextMarker;
          assert($nextDate instanceof \DateTime);

          $nextDate->setTimezone($date->getTimezone());
          $newOccurrences[] = (new OhOccurrence(
            DrupalDateTime::createFromDateTime($date),
            DrupalDateTime::createFromDateTime($nextDate),
          ))->setIsOpen(FALSE)->setMessages($marker[3]);
        }
      }
    }

    // Sort, and remove keys.
    usort($newOccurrences, [OhDateRange::class, 'sort']);
    return $newOccurrences;
  }

  /**
   * Combines ranges of same type (openings OR closures) if they intersect.
   *
   * Compute type collisions. (Flatten intersecting type).
   * E.g, for openings [10am-2pm, 11am-3pm], results in: [10am-3pm].
   *
   * @param array $markers
   *   Array of all markers.
   * @param bool $openings
   *   Combine openings or closures.
   *
   * @return array
   *   Removed markers and merged messages.
   */
  protected static function combineIntersecting(array $markers, bool $openings): array {
    $depth = 0;
    $inMarkerKey = NULL;
    foreach ($markers as $k => $marker) {
      [$isOpen, $date, $isStart] = $marker;
      assert($date instanceof \DateTime && is_bool($isOpen) && is_bool($isStart));
      if ($isOpen !== $openings) {
        continue;
      }

      if ($isStart) {
        $depth++;
        if ($depth > 1) {
          // Push message from this marker we are deleting, to the marker we
          // are currently within.
          // @todo change to merge spread in 7.4.
          $markers[$inMarkerKey][3] = array_merge($markers[$inMarkerKey][3], $markers[$k][3]);
          unset($markers[$k]);
        }
        else {
          $inMarkerKey = $k;
        }
      }
      else {
        $depth--;
        if ($depth > 0) {
          unset($markers[$k]);
        }
      }
    }

    return $markers;
  }

}
