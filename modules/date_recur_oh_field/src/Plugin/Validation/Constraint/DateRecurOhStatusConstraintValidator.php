<?php

declare(strict_types = 1);

namespace Drupal\date_recur_oh_field\Plugin\Validation\Constraint;

use Drupal\date_recur_oh_field\Plugin\Field\FieldType\DateRecurOHItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the DateRecurOhStatus constraint.
 */
class DateRecurOhStatusConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    assert($value instanceof DateRecurOHItem);
    assert($constraint instanceof DateRecurOhStatusConstraint);

    // Validator does not apply to field values without RRULE.
    if (empty($value->value) || empty($value->end_value) || empty($value->timezone)) {
      return;
    }

    if (!isset($value->open)) {
      $this->context->buildViolation($constraint->statusRequired)->addViolation();
    }
  }

}
