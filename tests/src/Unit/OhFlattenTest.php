<?php

declare(strict_types = 1);

namespace Drupal\Tests\oh\Unit;

use Drupal\oh\OhDateRange;
use Drupal\oh\OhOccurrence;
use Drupal\oh\OhUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Closure/opening computation tests.
 *
 * Tests occurrences are flattened so none overlap.
 *
 * @coversDefaultClass \Drupal\oh\OhUtility
 * @group date_recur
 */
class OhFlattenTest extends UnitTestCase {

  /**
   * Tests occurrences are flattened.
   *
   * @param \Drupal\oh\OhOccurrence[] $occurrences
   *   A series of occurrences to flatten.
   * @param \Drupal\oh\OhOccurrence[] $expected
   *   Expected flattened occurrences.
   *
   * @dataProvider providerCompute
   * @covers ::flattenOccurrences
   */
  public function testFlattening(array $occurrences, array $expected): void {
    $flattened = OhUtility::flattenOccurrences($occurrences);
    $this->assertCompare($expected, $flattened);
  }

  /**
   * Provides data for testing.
   *
   * @return array
   *   Data for testing.
   */
  public function providerCompute() {
    $data = [];

    $data['simple opening'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
      ],
    ];

    $data['simple closure'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc']),
      ],
    ];

    $data['closure encompasses opening'] = [
      [
        // Opening should be erased entirely.
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 8:00:00am'),
          new \DateTime('1 oct 2019 6:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 8:00:00am'),
          new \DateTime('1 oct 2019 6:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
    ];

    $data['individual openings'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 2:30:00pm'),
          new \DateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 2:30:00pm'),
          new \DateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
    ];

    $data['intersecting openings'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 10:30:00am'),
          new \DateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc', 'xyz']),
      ],
    ];

    $data['open end intersect closure / closure intersect closure'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 10:30:00am'),
          new \DateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['def']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 12:00:00pm'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 10:30:00am'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 10:30:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['def', 'xyz']),
      ],
    ];

    $data['open start intersect closure / closure intersect closure'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:30:00am'),
          new \DateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 12:00:00pm'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['def']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 1:00:00pm'),
          new \DateTime('1 oct 2019 7:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:30:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc', 'def']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 5:00:00pm'),
          new \DateTime('1 oct 2019 7:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
    ];

    $data['closure over opening'] = [
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 1:00:00pm'),
          new \DateTime('1 oct 2019 1:30:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new \DateTime('1 oct 2019 9:00:00am'),
          new \DateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 1:00:00pm'),
          new \DateTime('1 oct 2019 1:30:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
        (new OhOccurrence(
          new \DateTime('1 oct 2019 1:30:00pm'),
          new \DateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
      ],
    ];

    return $data;
  }

  /**
   * Tests flattening when occurrences in different time zones are provided.
   *
   * Exception should not be thrown when different time zones are provided.
   *
   * @covers ::flattenOccurrences
   */
  public function testFlatteningDifferentTimeZones(): void {
    // Cant create an occurrence with different time zones because the object
    // prevents it. Instead create a scenario where there is overlap and markers
    // intersect.
    $occurrences = [
      (new OhOccurrence(
        // Sydney: GMT+10.
        new \DateTime('1 oct 2019 9:00:00am', new \DateTimeZone('Australia/Sydney')),
        new \DateTime('1 oct 2019 5:00:00pm', new \DateTimeZone('Australia/Sydney')),
      ))->setIsOpen(TRUE),
      (new OhOccurrence(
        // Singapore: GMT+8.
        // 11am Sydney time.
        new \DateTime('1 oct 2019 9:00:00am', new \DateTimeZone('Asia/Singapore')),
        new \DateTime('1 oct 2019 5:00:00pm', new \DateTimeZone('Asia/Singapore')),
      ))->setIsOpen(TRUE),
      (new OhOccurrence(
        // Cairo: UTC+2.
        // 11:30am Sydney time.
        new \DateTime('1 oct 2019 3:30:00am', new \DateTimeZone('Africa/Cairo')),
        new \DateTime('1 oct 2019 3:45:00am', new \DateTimeZone('Africa/Cairo')),
      ))->setIsOpen(FALSE),
    ];

    $result = OhUtility::flattenOccurrences($occurrences);
    $this->assertCount(3, $result);

    $this->assertEquals('Tue, 01 Oct 2019 09:00:00 +1000', $result[0]->getStart()->format('r'));
    $this->assertEquals('Australia/Sydney', $result[0]->getStart()->getTimezone()->getName());
    $this->assertEquals('Tue, 01 Oct 2019 11:30:00 +1000', $result[0]->getEnd()->format('r'));
    $this->assertEquals('Australia/Sydney', $result[0]->getEnd()->getTimezone()->getName());

    $this->assertEquals('Tue, 01 Oct 2019 11:30:00 +1000', $result[1]->getStart()->format('r'));
    $this->assertEquals('Australia/Sydney', $result[1]->getStart()->getTimezone()->getName());
    $this->assertEquals('Tue, 01 Oct 2019 11:45:00 +1000', $result[1]->getEnd()->format('r'));
    $this->assertEquals('Australia/Sydney', $result[1]->getEnd()->getTimezone()->getName());

    $this->assertEquals('Tue, 01 Oct 2019 11:45:00 +1000', $result[2]->getStart()->format('r'));
    $this->assertEquals('Australia/Sydney', $result[2]->getStart()->getTimezone()->getName());
    $this->assertEquals('Tue, 01 Oct 2019 19:00:00 +1000', $result[2]->getEnd()->format('r'));
    $this->assertEquals('Australia/Sydney', $result[2]->getEnd()->getTimezone()->getName());
  }

  /**
   * Compares two arrays of occurrences.
   *
   * @param \Drupal\oh\OhOccurrence[] $expected
   *   Expected array of occurrences.
   * @param \Drupal\oh\OhOccurrence[] $actual
   *   Actual array of occurrences.
   */
  public function assertCompare(array $expected, array $actual) {
    usort($expected, [OhDateRange::class, 'sort']);
    usort($actual, [OhDateRange::class, 'sort']);
    $stringFrom = function (OhOccurrence $occurrence) {
      return sprintf(
        '%s: %s - %s [%s]',
        ($occurrence->isOpen() ? 'OPEN' : 'CLOSED'),
        $occurrence->getStart()->format('r'),
        $occurrence->getEnd()->format('r'),
        implode('|', $occurrence->getMessages()),
      );
    };
    $this->assertEquals(array_map($stringFrom, $expected), array_map($stringFrom, $actual));
  }

}
