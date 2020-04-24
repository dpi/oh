<?php

namespace Drupal\Tests\oh\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;
use Drupal\oh\OhOccurrence;

/**
 * Tests OhOccurrence class.
 *
 * @group oh
 * @coversDefaultClass \Drupal\oh\OhOccurrence
 */
class OhOccurrenceTest extends KernelTestBase {

  /**
   * Test message default value.
   *
   * @covers ::getMessages
   */
  public function testMessageDefault() {
    $occurrence = $this->createOccurrence();
    $this->assertEquals([], $occurrence->getMessages());
  }

  /**
   * Test message setter.
   *
   * @covers ::getMessages
   */
  public function testMessageSetter() {
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
  public function testIsOpenDefault() {
    $occurrence = $this->createOccurrence();
    $this->assertFalse($occurrence->isOpen(), 'Default value is false');
  }

  /**
   * Test is open setter.
   *
   * @covers ::setIsOpen
   */
  public function testIsOpenSetter() {
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
  public function testCachability() {
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
   * Create a new occurrence.
   *
   * @return \Drupal\oh\OhOccurrence
   *   New occurrence object.
   */
  protected function createOccurrence() {
    // Args are hard coded since occurrences don't implement any new constructor
    // args over OhDateRange class.
    $args = [
      new DrupalDateTime('yesterday'),
      new DrupalDateTime('tomorrow'),
    ];
    return new OhOccurrence(...$args);
  }

}
