<?php

namespace Drupal\oh;

use Drupal\Core\Entity\EntityInterface;
use Drupal\oh\Event\OhEvents;
use Drupal\oh\Event\OhExceptionEvent;
use Drupal\oh\OhUtility;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Opening hours service.
 */
class OhOpeningHours implements OhOpeningHoursInterface {

  /**
   * Date format for a day in time.
   */
  const DAY_FORMAT = 'Y-m-d';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs the opening hours service.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getOccurrences(EntityInterface $entity, OhDateRange $range): array {
    $occurrences = $this->getRegularHours($entity, $range);
    $exceptions = $this->getExceptions($entity, $range);

    if ($exceptions) {
      foreach ($exceptions as $exception) {
        $dayKey = $exception->getStart()->format(static::DAY_FORMAT);

        // Remove any regular hour occurrences on the same day as exceptions.
        $occurrences = array_filter($occurrences, function (OhOccurrence $occurrence) use ($dayKey) {
          // Note: Don't account for multi-day. Instead exception-event should
          // produce exceptions for each day
          return $occurrence->getStart()->format(static::DAY_FORMAT) !== $dayKey;
        });
      }

      // Exceptions must be added after above loop otherwise they will void
      // each other. For example: if multiple exceptions occur on the same day
      // only the last would survive.
      array_push($occurrences, ...$exceptions);
    }

    return $occurrences;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegularHours(EntityInterface $entity, OhDateRange $range): array {
    // @todo configurable.
    $fieldName = 'field_regular';

    $betweenStart = OhUtility::toPhpDateTime($range->getStart());
    $betweenEnd = OhUtility::toPhpDateTime($range->getEnd());

    $occurrences = [];
    foreach ($entity->{$fieldName} as $delta => $item) {
      /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */

      // Occurrences uses PHP date time.
      // If you pass DrupalDateTime then it will never terminate because you
      // cannot compare DrupalDateTime with DateTime.
      $itemOccurrences = $item->getOccurrenceHandler()
        ->getOccurrencesForDisplay($betweenStart, $betweenEnd);
      foreach ($itemOccurrences as $itemOccurrence) {
        $occurrences[] = new OhOccurrence($itemOccurrence['value'], $itemOccurrence['end_value']);
      }
    }

    return $occurrences;
  }

  /**
   * {@inheritdoc}
   */
  public function getExceptions(EntityInterface $entity, OhDateRange $range): array {
    $event = new OhExceptionEvent($entity, $range);
    $this->eventDispatcher->dispatch(OhEvents::EXCEPTIONS, $event);
    return $event->getExceptions();
  }

}