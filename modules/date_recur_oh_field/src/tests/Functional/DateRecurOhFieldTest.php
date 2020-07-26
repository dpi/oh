<?php

declare(strict_types = 1);

namespace Drupal\Tests\date_recur_oh_field\Functional;

use Drupal\Core\Url;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests field.
 *
 * @group date_recur_oh_field
 */
class DateRecurOhFieldTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime_range',
    'date_recur',
    'date_recur_oh_field',
    'oh',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $fieldStorage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'foo',
      'type' => 'date_recur_oh',
      'settings' => [
        'datetime_type' => DateRecurItem::DATETIME_TYPE_DATETIME,
      ],
    ]);
    $fieldStorage->save();

    $field = [
      'field_storage' => $fieldStorage,
      'bundle' => 'entity_test',
    ];
    FieldConfig::create($field)->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
    $entityDisplayRepository = \Drupal::service('entity_display.repository');
    $display = $entityDisplayRepository->getFormDisplay('entity_test', 'entity_test');
    $component = $display->getComponent('foo');
    $component['region'] = 'content';
    $component['type'] = 'date_recur_basic_widget';
    $component['settings'] = [];
    $display->setComponent('foo', $component);
    $display->save();
  }

  /**
   * Tests validator with a checkbox widget when unchecked.
   */
  public function testValidatorUnchecked(): void {
    $this->drupalLogin($this->createUser(['administer entity_test content']));

    $this->drupalGet(Url::fromRoute('entity.entity_test.add_form'));

    $edit = [
      'foo[0][value][date]' => '2008-06-17',
      'foo[0][value][time]' => '12:00:00',
      'foo[0][end_value][date]' => '2008-06-17',
      'foo[0][end_value][time]' => '12:00:00',
      'foo[0][timezone]' => 'Asia/Singapore',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
  }

}
