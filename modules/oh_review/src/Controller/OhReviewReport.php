<?php

namespace Drupal\oh_review\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\oh\OhDateRange;
use Drupal\oh\OhOpeningHoursInterface;
use Drupal\oh_regular\OhRegularInterface;
use Drupal\oh_review\OhReviewUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a report of opening hours for relevant locations.
 */
class OhReviewReport extends ControllerBase {

  /**
   * Tag to alter the displayed entities.
   */
  const QUERY_TAG_ENTITIES = 'oh_review_report_entities';

  /**
   * The opening hours service.
   *
   * @var \Drupal\oh\OhOpeningHoursInterface
   */
  protected $openingHours;

  /**
   * OH regular service.
   *
   * @var \Drupal\oh_regular\OhRegularInterface
   */
  protected $ohRegular;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Construct a new OhReviewReport.
   *
   * @param \Drupal\oh\OhOpeningHoursInterface $openingHours
   *   Opening hours service.
   * @param \Drupal\oh_regular\OhRegularInterface $ohRegular
   *   OH regular service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer.
   */
  public function __construct(OhOpeningHoursInterface $openingHours, OhRegularInterface $ohRegular, RendererInterface $renderer) {
    $this->openingHours = $openingHours;
    $this->ohRegular = $ohRegular;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('oh.opening_hours'),
      $container->get('oh_regular.mapping'),
      $container->get('renderer')
    );
  }

  /**
   * Creates a report of opening hours for relevant locations.
   *
   * @return array
   *   A render array.
   */
  public function report(): array {
    $cachability = new CacheableMetadata();

    $weeks = 6;

    $build = [];

    $rangeStart = $this->getRangeStart();
    $rangeEnd = (clone $rangeStart)
      ->add(new \DateInterval('P' . $weeks . 'W'));
    $range = new OhDateRange($rangeStart, $rangeEnd);

    // Add a weeks header.
    $header = [
      $this->t('Location'),
      $this->t('Operations'),
    ];
    $weekHeaderStart = clone $rangeStart;
    for ($i = 0; $i < $weeks; $i++) {
      $header[]['data']['#plain_text'] = $this->t('Week of @day', [
        '@day' => $weekHeaderStart->format('jS M Y'),
      ]);
      $weekHeaderStart->modify('+1 week');
    }

    $rows = [];
    foreach ($this->loadEntities() as $entity) {
      assert($entity instanceof EntityInterface);

      $cachability->addCacheableDependency($entity);

      $row = [];
      $row['link'] = $entity->toLink();

      // Use the same method as views' operations field plugin.
      $operations = $this->entityTypeManager()
        ->getListBuilder($entity->getEntityTypeId())
        ->getOperations($entity);
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => $operations,
      ];

      $occurrences = $this->openingHours->getOccurrences($entity, $range);
      foreach ($occurrences as $occurrence) {
        $cachability->addCacheableDependency($occurrence);
      }

      $occurrencesByWeek = OhReviewUtility::occurrencesByWeek($range, $occurrences, TRUE);

      $weekStart = clone $rangeStart;
      foreach ($occurrencesByWeek as $weekCode => $days) {
        $weekEnd = (clone $weekStart)->modify('+1 week');
        $weekEnd->modify('-1 second');
        $weekRange = new OhDateRange($weekStart, $weekEnd);

        $occurrences = array_merge(...array_values($days));

        $element = [
          '#theme' => 'oh_review_report_list',
          '#range' => $weekRange,
          '#occurrences' => $occurrences,
        ];
        // Render now since keeping $occurrences around can eventually consume
        // a lot of memory.
        $row[]['data'] = $this->renderer->render($element);
        $weekStart->modify('+1 week');
      }

      $rows[] = $row;

      // GC everything since memory is precious for this report.
      unset($entity);
      unset($occurrences);
      unset($elm);
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No entities found.'),
      '#sticky' => TRUE,
    ];

    $cachability->applyTo($build);
    $build['#cache']['keys'][] = 'oh_review_report';
    return $build;
  }

  /**
   * Loads entities progressively.
   *
   * @return \Generator|\Drupal\Core\Entity\EntityInterface[]
   *   Generates entities.
   */
  protected function loadEntities() {
    $allMapping = $this->ohRegular->getAllMapping();
    foreach ($allMapping as $entityTypeId => $mapping) {
      $storage = $this->entityTypeManager()
        ->getStorage($entityTypeId);

      $query = $storage->getQuery();
      $entityTypeDefinition = $this->entityTypeManager()
        ->getDefinition($entityTypeId);

      $labelKey = $entityTypeDefinition->getKey('label');
      if ($labelKey) {
        $query->sort($labelKey, 'ASC');
      }

      // Check if this entity supports bundles.
      $bundlesKey = $entityTypeDefinition->getKey('bundle');
      if ($bundlesKey) {
        $bundles = array_keys($mapping);
        $query->condition($bundlesKey, $bundles, 'IN');
      }

      $query->addTag(static::QUERY_TAG_ENTITIES);

      $entityIds = $query->execute();
      foreach (array_chunk($entityIds, 16) as $idChunk) {
        foreach ($storage->loadMultiple($idChunk) as $entity) {
          yield $entity;
        }
        $storage->resetCache($idChunk);
      }
    }

    return NULL;
  }

  /**
   * Get the range start date.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The range start date.
   */
  protected function getRangeStart(): DrupalDateTime {
    $dayMap = [
      'Sunday',
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
    ];

    // Weekday int. 0-6 (Sun-Sat).
    $firstDayInt = $this->config('system.date')
      ->get('first_day');
    $firstDayStr = $dayMap[$firstDayInt];
    // Today day int.
    $today = (new DrupalDateTime())->format('w');
    $weekStartString = ($today == $firstDayInt ? '' : 'last ') . $firstDayStr . ' 00:00';
    return new DrupalDateTime($weekStartString);
  }

}
