<?php

declare(strict_types = 1);

namespace Drupal\Tests\oh\Unit;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setupDrupalDateTime();
  }

  /**
   * Prepares Drupal container so DrupalDateTime class can be used.
   */
  protected function setupDrupalDateTime() {
    // DrupalDateTime wants to access the language manager.
    $languageManager = $this->getMockForAbstractClass(LanguageManagerInterface::class);
    $languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(['id' => 'en'])));

    $container = new ContainerBuilder();
    $container->set('language_manager', $languageManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

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
    $this->setupDrupalDateTime();
    $data = [];

    $data['simple opening'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
      ],
    ];

    $data['simple closure'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc']),
      ],
    ];

    $data['closure encompasses opening'] = [
      [
        // Opening should be erased entirely.
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 8:00:00am'),
          new DrupalDateTime('1 oct 2019 6:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 8:00:00am'),
          new DrupalDateTime('1 oct 2019 6:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
    ];

    $data['individual openings'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 2:30:00pm'),
          new DrupalDateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 2:30:00pm'),
          new DrupalDateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
    ];

    $data['intersecting openings'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 10:30:00am'),
          new DrupalDateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc', 'xyz']),
      ],
    ];

    $data['open end intersect closure / closure intersect closure'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 10:30:00am'),
          new DrupalDateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['def']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 12:00:00pm'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 10:30:00am'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 10:30:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['def', 'xyz']),
      ],
    ];

    $data['open start intersect closure / closure intersect closure'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:30:00am'),
          new DrupalDateTime('1 oct 2019 4:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 12:00:00pm'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['def']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
          new DrupalDateTime('1 oct 2019 7:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:30:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['abc', 'def']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
          new DrupalDateTime('1 oct 2019 7:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['xyz']),
      ],
    ];

    $data['closure over opening'] = [
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
          new DrupalDateTime('1 oct 2019 1:30:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
      ],
      [
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 9:00:00am'),
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 1:00:00pm'),
          new DrupalDateTime('1 oct 2019 1:30:00pm'),
        ))->setIsOpen(FALSE)->setMessages(['xyz']),
        (new OhOccurrence(
          new DrupalDateTime('1 oct 2019 1:30:00pm'),
          new DrupalDateTime('1 oct 2019 5:00:00pm'),
        ))->setIsOpen(TRUE)->setMessages(['abc']),
      ],
    ];

    return $data;
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
