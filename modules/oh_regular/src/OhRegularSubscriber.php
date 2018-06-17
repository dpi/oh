<?php

namespace Drupal\oh_regular;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\oh\Event\OhEvents;
use Drupal\oh\Event\OhRegularEvent;
use Drupal\oh\OhOccurrence;
use Drupal\oh\OhUtility;
use Drupal\oh_regular\Entity\OhRegularMap;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for OH events.
 */
class OhRegularSubscriber implements EventSubscriberInterface {

  const REGULAR_MAPPING_CID = 'oh:field_mapping:regular';

  /**
   * Regular map storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $regularMapStorage;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Construct OhRegularSubscriber service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache) {
    $this->regularMapStorage = $entityTypeManager->getStorage('oh_regular_map');
    $this->cache = $cache;
  }

  /**
   * Generates regular hours from field mapping.
   *
   * @param \Drupal\oh\Event\OhRegularEvent $event
   *   Regular hours event.
   */
  public function regularHoursField(OhRegularEvent $event): void {
    $entity = $event->getEntity();
    $mapping = $this->getMapping($entity->getEntityTypeId(), $entity->bundle());

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

  /**
   * Get field mapping.
   *
   * @param string $entityTypeId
   *   The entity type Id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   List of field names.
   */
  protected function getMapping(string $entityTypeId, string $bundle): array {
    $mapping = $this->cache->get(static::REGULAR_MAPPING_CID);
    if (FALSE === $mapping) {
      /** @var \Drupal\oh_regular\OhRegularMapInterface[] $maps */
      $maps = $this->regularMapStorage->loadMultiple();
      $mapping = [];
      foreach ($maps as $map) {
        $mapping[$map->getMapEntityType()][$map->getMapBundle()] = array_map(
          function (array $fieldMap) {
            return $fieldMap['field_name'];
          },
          $map->getRegularFields()
        );
      }

      $this->cache
        ->set(static::REGULAR_MAPPING_CID, $mapping, Cache::PERMANENT, [OhRegularMap::CACHE_TAG_ALL]);
    }
    else {
      $mapping = $mapping->data;
    }

    return $mapping[$entityTypeId][$bundle] ?? [];
  }

}
