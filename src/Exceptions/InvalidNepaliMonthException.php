<?php

declare(strict_types=1);

namespace Sambat\NepaliCalendar\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when a Nepali month number is outside the valid 1-12 range.
 */
class InvalidNepaliMonthException extends InvalidArgumentException {}
