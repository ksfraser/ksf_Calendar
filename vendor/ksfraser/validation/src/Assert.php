<?php

namespace Ksfraser\Validation;

use Ksfraser\Validation\Exception\ValidationException;

/**
 * Minimal assertion helpers for PHP 7.3+.
 *
 * This intentionally overlaps some of the old Origin-style `is_*` and size/range checks,
 * but in a composable form that doesn't require inheritance.
 */
final class Assert
{
    private function __construct()
    {
    }

    /**
     * @param mixed $value
     * @param string|null $field
     */
    public static function notNull($value, $field = null)
    {
        if ($value === null) {
            throw new ValidationException(self::msg($field, 'must not be null'));
        }
    }

    /**
     * @param mixed $value
     * @param string|null $field
     */
    public static function isString($value, $field = null)
    {
        if (!is_string($value)) {
            throw new ValidationException(self::msg($field, 'must be a string'));
        }
    }

    /**
     * @param mixed $value
     * @param string|null $field
     */
    public static function notEmptyString($value, $field = null)
    {
        self::isString($value, $field);
        if (trim($value) === '') {
            throw new ValidationException(self::msg($field, 'must not be empty'));
        }
    }

    /**
     * @param mixed $value
     * @param int $max
     * @param string|null $field
     */
    public static function stringMaxLen($value, $max, $field = null)
    {
        self::isString($value, $field);
        if ($max < 0) {
            throw new ValidationException(self::msg($field, 'invalid max length'));
        }
        if (strlen($value) > $max) {
            throw new ValidationException(self::msg($field, 'must be at most ' . (int)$max . ' characters'));
        }
    }

    /**
     * @param mixed $value
     * @param int $min
     * @param string|null $field
     */
    public static function stringMinLen($value, $min, $field = null)
    {
        self::isString($value, $field);
        if ($min < 0) {
            throw new ValidationException(self::msg($field, 'invalid min length'));
        }
        if (strlen($value) < $min) {
            throw new ValidationException(self::msg($field, 'must be at least ' . (int)$min . ' characters'));
        }
    }

    /**
     * @param mixed $value
     * @param string|null $field
     */
    public static function isInt($value, $field = null)
    {
        if (!is_int($value)) {
            throw new ValidationException(self::msg($field, 'must be an integer'));
        }
    }

    /**
     * Accepts an int or a numeric string that represents an int.
     *
     * @param mixed $value
     * @param string|null $field
     */
    public static function isIntish($value, $field = null)
    {
        if (is_int($value)) {
            return;
        }

        if (is_string($value) && preg_match('/^-?\\d+$/', $value) === 1) {
            return;
        }

        throw new ValidationException(self::msg($field, 'must be an integer (or integer-like string)'));
    }

    /**
     * @param mixed $value
     * @param int|null $min
     * @param int|null $max
     * @param string|null $field
     */
    public static function intBetween($value, $min, $max, $field = null)
    {
        self::isIntish($value, $field);
        $intVal = (int)$value;

        if ($min !== null && $intVal < (int)$min) {
            throw new ValidationException(self::msg($field, 'must be >= ' . (int)$min));
        }
        if ($max !== null && $intVal > (int)$max) {
            throw new ValidationException(self::msg($field, 'must be <= ' . (int)$max));
        }
    }

    /**
     * @param mixed $value
     * @param array $allowed
     * @param string|null $field
     */
    public static function oneOf($value, array $allowed, $field = null)
    {
        if (!in_array($value, $allowed, true)) {
            throw new ValidationException(self::msg($field, 'must be one of the allowed values'));
        }
    }

    /**
     * @param string|null $field
     * @param string $message
     */
    private static function msg($field, $message)
    {
        if ($field === null || $field === '') {
            return $message;
        }
        return (string)$field . ' ' . $message;
    }
}
