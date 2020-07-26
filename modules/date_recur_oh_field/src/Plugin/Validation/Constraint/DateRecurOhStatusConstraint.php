<?php

declare(strict_types = 1);

namespace Drupal\date_recur_oh_field\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates RRULE strings.
 *
 * @Constraint(
 *   id = "DateRecurOhStatus",
 *   label = @Translation("Validates status is set when other values are set.", context = "Validation"),
 * )
 */
class DateRecurOhStatusConstraint extends Constraint {

  /**
   * Violation message for field values without a status.
   *
   * @var string
   */
  public $statusRequired = 'Status field is required.';

}
