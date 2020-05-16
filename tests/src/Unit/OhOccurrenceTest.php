<?php

declare(strict_types = 1);

namespace Drupal\Tests\oh\Unit;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOccurrence;
use Drupal\Tests\UnitTestCase;

/**
 * Tests OhOccurrence class.
 *
 * @group oh
 * @coversDefaultClass \Drupal\oh\OhOccurrence
 */
class OhOccurrenceTest extends UnitTestCase {

  /**
   * Test message default value.
   *
   * @covers ::getMessages
   */
  public function testMessageDefault(): void {
    $occurrence = $this->createOccurrence();
    $this->assertEquals([], $occurrence->getMessages());
  }

  /**
   * Test message setter.
   *
   * @covers ::getMessages
   */
  public function testMessageSetter(): void {
    $occurrence = $this->createOccurrence();
    $text = $this->randomMachineName();
    $occurrence->addMessage($text);
    $this->assertEquals($text, implode(',', $occurrence->getMessages()));

    $occurrence->setMessages([]);
    $this->assertEquals([], $occurrence->getMessages());
  }

  /**
   * Test is open default value.
   *
   * @covers ::isOpen
   */
  public function testIsOpenDefault(): void {
    $occurrence = $this->createOccurrence();
    $this->assertFalse($occurrence->isOpen(), 'Default value is false');
  }

  /**
   * Test is open setter.
   *
   * @covers ::setIsOpen
   */
  public function testIsOpenSetter(): void {
    $occurrence = $this->createOccurrence();

    $occurrence->setIsOpen(TRUE);
    $this->assertTrue($occurrence->isOpen());

    $occurrence->setIsOpen(FALSE);
    $this->assertFalse($occurrence->isOpen());
  }

  /**
   * Tests cachability.
   *
   * @covers ::getCacheContexts
   * @covers ::getCacheTags
   * @covers ::getCacheMaxAge
   */
  public function testCachability(): void {
    $cacheContextsManager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cacheContextsManager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cacheContextsManager);
    \Drupal::setContainer($container);

    $occurrence = $this->createOccurrence();

    $contexts = ['user.roles'];
    $tags = ['hello', 'world'];
    $maxAge = 1337;

    $occurrence
      ->addCacheContexts($contexts)
      ->addCacheTags($tags)
      ->mergeCacheMaxAge($maxAge);

    $cachable = (new CacheableMetadata())
      ->addCacheableDependency($occurrence);

    $this->assertEquals($contexts, $cachable->getCacheContexts());
    $this->assertEquals($tags, $cachable->getCacheTags());
    $this->assertEquals($maxAge, $cachable->getCacheMaxAge());
  }

  /**
   * Test a occurrence is range is trimmed off.
   *
   * @covers ::trimWithinRange
   */
  public function testTrimWithinRange(): void {
    $occurrence = new OhOccurrence(
      new \DateTime('7am 13 February 1998'),
      new \DateTime('9pm 13 February 1998')
    );

    $occurrence->trimWithinRange(new OhDateRange(
      new \DateTime('9am 13 February 1998'),
      new \DateTime('5pm 13 February 1998')
    ));

    $this->assertEquals(new \DateTime('9am 13 February 1998'), $occurrence->getStart());
    $this->assertEquals(new \DateTime('5pm 13 February 1998'), $occurrence->getEnd());
  }

  /**
   * Create a new occurrence.
   *
   * @return \Drupal\oh\OhOccurrence
   *   New occurrence object.
   */
  protected function createOccurrence() {
    // Args are hard coded since occurrences don't implement any new constructor
    // args over OhDateRange class.
    $args = [
      new \DateTime('yesterday'),
      new \DateTime('tomorrow'),
    ];
    return new OhOccurrence(...$args);
  }

}
