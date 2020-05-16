<?php

namespace Drupal\Tests\oh\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\oh\OhDateRange;

/**
 * Tests OhDateRange class.
 *
 * @group oh
 * @coversDefaultClass \Drupal\oh\OhDateRange
 */
class OhDateRangeTest extends UnitTestCase {

  /**
   * Test message default value.
   */
  public function testRequiredConstructors() {
    $this->setExpectedException(\ArgumentCountError::class);
    $this->createDateRange();
  }

  /**
   * Tests start and end getters.
   *
   * @covers ::getStart
   * @covers ::getEnd
   */
  public function testGetters() {
    $start = new \DateTime('yesterday');
    $end = new \DateTime('tomorrow');
    $dateRange = $this->createDateRange($start, $end);

    $this->assertEquals($start, $dateRange->getStart());
    $this->assertEquals($end, $dateRange->getEnd());
  }

  /**
   * Tests same time zone validation.
   *
   * @covers ::validateDates
   */
  public function testTimeZoneValidation() {
    $start = new \DateTime('yesterday', new \DateTimezone('Australia/Sydney'));
    $end = new \DateTime('tomorrow', new \DateTimezone('Australia/Sydney'));

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    // Change the timezone.
    $end = new \DateTime('tomorrow', new \DateTimezone('Australia/Perth'));
    $this->setExpectedException(\InvalidArgumentException::class, 'Provided dates must be in same timezone.');
    $this->createDateRange($start, $end);
  }

  /**
   * Tests end occur on or after start.
   *
   * @covers ::validateDates
   */
  public function testEndAfterStartValidation() {
    // Same time.
    $start = new \DateTime('Monday 12:00:00');
    $end = new \DateTime('Monday 12:00:00');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    // End after start.
    $start = new \DateTime('Monday 12:00:00');
    $end = new \DateTime('Monday 12:00:01');

    // No exceptions should throw here.
    $this->createDateRange($start, $end);

    $start = new \DateTime('Monday 12:00:01');
    $end = new \DateTime('Monday 12:00:00');

    $this->setExpectedException(\InvalidArgumentException::class, 'End date must not occur before start date.');
    $this->createDateRange($start, $end);
  }

  /**
   * Tests object sorting.
   *
   * @covers ::sort
   */
  public function testSort() {
    /** @var \Drupal\oh\OhDateRange[] $ranges */
    $ranges = [];

    $start1 = new \DateTime('1 Jan 2016 12:00:00');
    $end1 = new \DateTime('1 Jan 2018 12:00:00');
    $ranges[] = $this->createDateRange($start1, $end1);

    $start2 = new \DateTime('1 Jan 2017 12:00:00');
    $end2 = new \DateTime('1 Jan 2019 12:00:00');
    $ranges[] = $this->createDateRange($start2, $end2);

    $start3 = new \DateTime('1 Jan 2015 12:00:00');
    $end3 = new \DateTime('1 Jan 2017 12:00:00');
    $ranges[] = $this->createDateRange($start3, $end3);

    usort($ranges, [OhDateRange::class, 'sort']);
    $this->assertEquals($start3, $ranges[0]->getStart());
    $this->assertEquals($start1, $ranges[1]->getStart());
    $this->assertEquals($start2, $ranges[2]->getStart());
  }

  /**
   * Tests isWithin utility.
   *
   * @covers ::isWithin
   */
  public function testIsWithin() {
    $outerStart = new \DateTime('1 January 2016');
    $outerEnd = new \DateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new \DateTime('1 March 2016');
    $innerEnd = new \DateTime('31 October 2016');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->assertTrue($outerRange->isWithin($innerRange));

    // Test same.
    // Dates with the exact same start and end time are permitted.
    $innerRange = $outerRange;
    $this->assertTrue($outerRange->isWithin($innerRange));
  }

  /**
   * Tests isWithin utility inner-start starts before outer-end.
   *
   * @covers ::isWithin
   */
  public function testIsWithinInvalidInnerStartBeforeOuterStart() {
    $outerStart = new \DateTime('1 January 2016');
    $outerEnd = new \DateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new \DateTime('1 March 2015');
    $innerEnd = new \DateTime('31 October 2016');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->setExpectedException(\Exception::class, 'Inner date starts before outer date.');
    $outerRange->isWithin($innerRange);
  }

  /**
   * Tests isWithin utility inner-start starts after outer-end.
   *
   * @covers ::isWithin
   */
  public function testIsWithinInvalidInnerStartAfterOuterEnd() {
    $outerStart = new \DateTime('1 January 2016');
    $outerEnd = new \DateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new \DateTime('1 March 2017');
    $innerEnd = new \DateTime('31 October 2017');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->setExpectedException(\Exception::class, 'Inner date starts after outer date.');
    $outerRange->isWithin($innerRange);
  }

  /**
   * Tests isWithin utility inner-end ends after outer-end.
   *
   * @covers ::isWithin
   */
  public function testIsWithinInvalidInnerEndAfterOuterEnd() {
    $outerStart = new \DateTime('1 January 2016');
    $outerEnd = new \DateTime('31 December 2016');
    $outerRange = $this->createDateRange($outerStart, $outerEnd);

    $innerStart = new \DateTime('1 March 2016');
    $innerEnd = new \DateTime('31 October 2017');
    $innerRange = $this->createDateRange($innerStart, $innerEnd);

    $this->setExpectedException(\Exception::class, 'Inner date ends after outer date.');
    $outerRange->isWithin($innerRange);
  }

  /**
   * Test getter mutability.
   *
   * @covers ::getStart
   */
  public function testGetStartMutation() {
    $start = new \DateTime('yesterday', new \DateTimezone('Australia/Sydney'));
    $original = clone $start;
    $end = new \DateTime('tomorrow', new \DateTimezone('Australia/Sydney'));

    $range = $this->createDateRange($start, $end);
    $gotten = $range->getStart();
    $gotten->modify('+1 year');
    $this->assertEquals($original, $range->getStart());
  }

  /**
   * Test getter mutability.
   *
   * @covers ::getEnd
   */
  public function testGetEndMutation() {
    $start = new \DateTime('yesterday', new \DateTimezone('Australia/Sydney'));
    $end = new \DateTime('tomorrow', new \DateTimezone('Australia/Sydney'));
    $original = clone $end;

    $range = $this->createDateRange($start, $end);
    $gotten = $range->getEnd();
    $gotten->modify('+1 year');
    $this->assertEquals($original, $range->getEnd());
  }

  /**
   * Create a new range.
   *
   * @param array $args
   *   Arguments to pass to constructor.
   *
   * @return \Drupal\oh\OhDateRange
   *   New range object.
   */
  protected function createDateRange(...$args) {
    return new OhDateRange(...$args);
  }

}
