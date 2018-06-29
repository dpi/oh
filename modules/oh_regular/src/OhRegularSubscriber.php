<?php

namespace Drupal\oh_regular;

use Drupal\oh\Event\OhEvents;
use Drupal\oh\Event\OhRegularEvent;
use Drupal\oh\OhOccurrence;
use Drupal\oh\OhUtility;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for OH events.
 */
class OhRegularSubscriber implements EventSubscriberInterface {

  /**
   * OH regular service.
   *
   * @var \Drupal\oh_regular\OhRegularInterface
   */
  protected $ohRegular;

  /**
   * Construct OhRegularSubscriber service.
   *
   * @param \Drupal\oh_regular\OhRegularInterface $ohRegular
   *   OH regular service.
   */
  public function __construct(OhRegularInterface $ohRegular) {
    $this->ohRegular = $ohRegular;
  }

  /**
   * Generates regular hours from field mapping.
   *
   * @param \Drupal\oh\Event\OhRegularEvent $event
   *   Regular hours event.
   */
  public function regularHoursField(OhRegularEvent $event): void {
    $entity = $event->getEntity();
    $mapping = $this->ohRegular->getMapping($entity->getEntityTypeId(), $entity->bundle());

    $range = $event->getRange();
    $betweenStart = OhUtility::toPhpDateTime($range->getStart());
    $betweenEnd = OhUtility::toPhpDateTime($range->getEnd());

    foreach ($mapping as $fieldName) {
      foreach ($entity->{$fieldName} as $item) {
        /** @var \Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem $item */

        // Occurrences uses PHP date time.
        // If you pass DrupalDateTime then it will never terminate because you
        // cannot compare DrupalDateTime with DateTime.
        $itemOccurrences = $item->getOccurrenceHandler()
          ->getOccurrencesForDisplay($betweenStart, $betweenEnd);
        foreach ($itemOccurrences as $itemOccurrence) {
          $occurrence = new OhOccurrence($itemOccurrence['value'], $itemOccurrence['end_value']);
          $occurrence->setIsOpen(TRUE);
          $event->addRegularHours($occurrence);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[OhEvents::REGULAR][] = ['regularHoursField'];
    return $events;
  }

}
