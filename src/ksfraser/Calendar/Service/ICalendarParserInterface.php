<?php
/**
 * Parses iCalendar text into CalendarEntry records.
 *
 * @package ksfraser\Calendar\Service
 */

declare(strict_types=1);

namespace ksfraser\Calendar\Service;

use ksfraser\Calendar\Entity\CalendarEntry;

/**
 * iCalendar parsing, kept behind an interface because the writing library cannot
 * read.
 *
 * eluceo/ical renders .ics and nothing else -- it has no parser. Reading .ics
 * needs a second library (craigk5n/icalendar is the usual choice), which this
 * module deliberately does not depend on: adding an undeclared dependency to
 * enable a feature nobody has asked for is worse than refusing clearly.
 *
 * Implementing this interface and passing it to ICalService is all that is
 * needed to turn import on; export never needs it.
 *
 * @package ksfraser\Calendar\Service
 */
interface ICalendarParserInterface
{
    /**
     * @param string $content Raw iCalendar text.
     * @return CalendarEntry[] Entries parsed from it; empty when it held none.
     * @throws ImportFailedException When the content cannot be read.
     */
    public function parse(string $content): array;
}
