<?php
/**
 * CalendarException
 *
 * @package Ksfraser\Calendar\Exception
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Exception;

use Ksfraser\Exceptions\Calendar\CalendarException as BaseCalendarException;

class CalendarException extends BaseCalendarException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}