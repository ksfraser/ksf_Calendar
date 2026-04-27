<?php

namespace Ksfraser\Validation\Exception;

/**
 * Thrown when validation fails.
 *
 * Kept in this package so callers don't need to depend on a larger exceptions repo.
 */
class ValidationException extends \InvalidArgumentException
{
}
