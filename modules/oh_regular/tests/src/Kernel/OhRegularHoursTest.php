<?php

declare(strict_types = 1);

namespace Drupal\Tests\oh_regular\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOpeningHoursInterface;
use Drupal\oh_regular\Entity\OhRegularMap;

/**
 * Tests regular hours.
 *
 * @group oh
 * @coversDefaultClass \Drupal\oh_regular\OhRegularSubscriber
 */
class OhRegularHoursTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'user',
    'field',
    'datetime_range',
    'datetime',
    'date_recur',
    'oh',
    'oh_regular',
    'oh_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $fieldName = 'testfield';
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'entity_test',
      'type' => 'date_recur',
    ]);
    $fieldStorage->save();
    $fieldInstance = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'entity_test',
    ]);
    $fieldInstance->save();

    /** @var \Drupal\oh_regular\Entity\OhRegularMap $map */
    $map = OhRegularMap::create([
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $map->setRegularFields([['field_name' => $fieldName]]);
    $map->save();
  }

  /**
   * Test occurrence is trimmed to the requested range..
   */
  public function testTrimmedToRange() {
    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      'testfield' => [
        [
          // 9am-5pm weekdayly.
          'value' => '1997-06-15T23:00:00',
          'end_value' => '1997-06-16T07:00:00',
          'rrule' => 'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR',
          'infinite' => '1',
          'timezone' => 'Asia/Singapore',
        ],
      ],
    ]);

    $range = new OhDateRange(
      new \DateTime('11am 13 February 1998', new \DateTimeZone('Asia/Singapore')),
      new \DateTime('9pm 13 February 1998', new \DateTimeZone('Asia/Singapore'))
    );

    $occurrences = $this->openingHoursService()->getOccurrences($entity, $range);

    $this->assertCount(1, $occurrences);
    $this->assertEquals('Fri, 13 Feb 1998 11:00:00 +0800', $occurrences[0]->getStart()->format('r'));
    $this->assertEquals('Fri, 13 Feb 1998 15:00:00 +0800', $occurrences[0]->getEnd()->format('r'));
  }

  /**
   * Opening hours service.
   *
   * @return \Drupal\oh\OhOpeningHoursInterface
   *   The opening hours service.
   */
  protected function openingHoursService(): OhOpeningHoursInterface {
    return $this->container->get('oh.opening_hours');
  }

}
