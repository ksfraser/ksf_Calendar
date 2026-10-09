<?php
/**
 * @package ksfraser\Calendar\Exception
 */

declare(strict_types=1);

namespace ksfraser\Calendar\Exception;

/**
 * Import was requested but no .ics parser is configured.
 *
 * A distinct type rather than a generic failure, so a caller can tell "you have
 * not enabled this yet" apart from "your file is broken" -- the two need very
 * different responses.
 *
 * @package ksfraser\Calendar\Exception
 */
class ImportUnavailableException extends \RuntimeException
{
}
