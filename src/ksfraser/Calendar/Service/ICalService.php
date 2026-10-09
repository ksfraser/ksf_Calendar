<?php
/**
 * ICalService -- build .ical documents from CalendarEntry records.
 *
 * THIS IS HOW KSF SENDS EVENT INVITES. Every attendee-facing .ics the CRM and the
 * Calendar app emit is produced here, so a break in this class is a break in
 * customer-facing email.
 *
 * @package ksfraser\Calendar\Service
 */

declare(strict_types=1);

namespace ksfraser\Calendar\Service;

use DateTimeImmutable;
use DateTimeInterface;
use Eluceo\iCal\Domain\Entity\Calendar as ICalCalendar;
use Eluceo\iCal\Domain\Entity\Event as ICalEvent;
use Eluceo\iCal\Domain\Enum\EventStatus;
use Eluceo\iCal\Domain\ValueObject\Category;
use Eluceo\iCal\Domain\ValueObject\Date as ICalDate;
use Eluceo\iCal\Domain\ValueObject\DateTime as ICalDateTime;
use Eluceo\iCal\Domain\ValueObject\Location as ICalLocation;
use Eluceo\iCal\Domain\ValueObject\SingleDay;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\UniqueIdentifier;
use Eluceo\iCal\Presentation\Component\Property;
use Eluceo\iCal\Presentation\Component\Property\Value\TextValue;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use ksfraser\Calendar\Entity\CalendarEntry;
use ksfraser\Calendar\Entity\CalendarSource;
use ksfraser\Calendar\Exception\ImportFailedException;
use ksfraser\Calendar\Exception\ImportUnavailableException;
use Psr\Log\LoggerInterface;

/**
 * iCal import/export.
 *
 * ## Export is the supported half, and it is the half that matters
 *
 * eluceo/ical is a *writer*. It renders iCalendar but does not parse it, so every
 * export in this module is built with it and works with the installed 2.14.0.
 *
 * ## Import needs a parser, and does not have one
 *
 * Parsing .ics requires a library the module does not depend on (see
 * ICalendarParserInterface). Rather than add an undeclared dependency, parsing is
 * injected: supply a parser and the import methods work; supply none and they
 * throw a typed exception saying exactly what is missing. Nothing fails silently.
 *
 * This class was previously quarantined in legacy/ because it was written against
 * eluceo/ical **1.x** and could not be loaded at all -- `PropertyFactory\FactoryTrait`
 * and `Parameter\Value\ValueDateTime` were both removed in 2.x. It has been ported
 * to the 2.x domain/presentation model:
 *
 *   1.x                                    2.x
 *   ──────────────────────────────────���─────────────────────────────
 *   Component\Calendar                     Domain\Entity\Calendar
 *   Component\Event                        Domain\Entity\Event
 *   Parameter\Value\ValueDateTime          Domain\ValueObject\DateTime
 *   (n/a)                                  Domain\ValueObject\Date
 *   setDtStart/setDtEnd                    setOccurrence(SingleDay|TimeSpan)
 *   setUniqueId(string)                    new UniqueIdentifier(string)
 *   setStatus(string)                      setStatus(EventStatus::X())
 *   (string) $component                    CalendarFactory::createCalendar()
 *
 * @package ksfraser\Calendar\Service
 */
class ICalService
{
    /** Timezone used when the entry does not name one. */
    public const DEFAULT_TIMEZONE = 'UTC';

    /**
     * PRODID identifies the producer of the file, and some clients display it.
     *
     * eluceo/ical's own default is "-//eluceo/ical//2.0//EN", which tells a
     * recipient nothing about who sent the invite, so it is replaced.
     */
    public const PRODUCT_IDENTIFIER = '-//ksf//Calendar//EN';

    /** @var LoggerInterface */
    private $logger;

    /** @var ICalendarParserInterface|null */
    private $parser;

    /**
     * @param LoggerInterface                 $logger
     * @param ICalendarParserInterface|null   $parser Null until a .ics parser is
     *        supplied. Export works without one; import raises rather than
     *        pretending to have read the file.
     */
    public function __construct(LoggerInterface $logger, ?ICalendarParserInterface $parser = null)
    {
        $this->logger = $logger;
        $this->parser = $parser;
    }

    // ── export ────────────────────────────────────────────────────────────────

    /**
     * Render entries as a single .ics document.
     *
     * @param CalendarEntry[] $entries
     * @param string          $calendarName
     * @return string The iCalendar text.
     */
    public function exportEntries(array $entries, string $calendarName = 'KSF Calendar'): string
    {
        $events = array();

        foreach ($entries as $entry) {
            if (!$entry instanceof CalendarEntry) {
                // Skip rather than fatal: a feed must not break because one row
                // was the wrong type.
                continue;
            }

            $events[] = $this->buildEvent($entry);
        }

        return $this->render($events, $calendarName);
    }

    /**
     * Render ONE entry as a single-event .ics.
     *
     * This is the per-attendee invite: a customer and an employee each get their
     * own file, rather than one feed containing everyone's meetings.
     *
     * @param CalendarEntry $entry
     * @param string        $calendarName
     * @return string
     */
    public function exportEntry(CalendarEntry $entry, string $calendarName = 'Invitation'): string
    {
        return $this->render(array($this->buildEvent($entry)), $calendarName);
    }

    /**
     * Write a rendered feed to disk.
     *
     * @param CalendarEntry[] $entries
     * @param string          $filePath
     * @param string          $calendarName
     * @return bool
     */
    public function exportEntriesToFile(array $entries, string $filePath, string $calendarName = 'KSF Calendar'): bool
    {
        return $this->write($this->exportEntries($entries, $calendarName), $filePath);
    }

    /**
     * Render the entries a source is configured to expose.
     *
     * @param CalendarSource  $source
     * @param CalendarEntry[] $entries
     * @return string
     */
    public function exportSource(CalendarSource $source, array $entries): string
    {
        $kept = array();

        foreach ($entries as $entry) {
            if ($entry instanceof CalendarEntry && $this->shouldIncludeEntry($entry, $source)) {
                $kept[] = $entry;
            }
        }

        return $this->exportEntries($kept, $source->getName() !== '' ? $source->getName() : 'KSF Calendar');
    }

    /**
     * The HTTP headers that make a browser download a feed.
     *
     * @param string $filename
     * @return array<string,string>
     */
    public function downloadHeaders(string $filename = 'calendar.ics'): array
    {
        return array(
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        );
    }

    /**
     * A token a public feed URL can be keyed on.
     *
     * @param CalendarSource $source
     * @return string
     */
    public function generateSourceToken(CalendarSource $source): string
    {
        return hash('sha256', $source->getId() . $source->getName() . date('Y-m-d'));
    }

    // ── import (requires an injected parser) ─────────────────────────────────

    /**
     * @param string $url
     * @return CalendarEntry[]
     * @throws ImportUnavailableException When no parser is configured.
     * @throws ImportFailedException When the fetch or parse fails.
     */
    public function importFromUrl(string $url): array
    {
        $content = $this->fetchUrl($url);

        if ($content === null) {
            throw new ImportFailedException('Could not fetch iCalendar data from ' . $url);
        }

        return $this->importFromString($content);
    }

    /**
     * @param string $filePath
     * @return CalendarEntry[]
     * @throws ImportUnavailableException
     * @throws ImportFailedException
     */
    public function importFromFile(string $filePath): array
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new ImportFailedException('Cannot read iCalendar file: ' . $filePath);
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new ImportFailedException('Cannot read iCalendar file: ' . $filePath);
        }

        return $this->importFromString($content);
    }

    /**
     * @param string $content
     * @return CalendarEntry[]
     * @throws ImportUnavailableException
     */
    public function importFromString(string $content): array
    {
        if ($this->parser === null) {
            throw new ImportUnavailableException(
                'No iCalendar parser is configured. eluceo/ical renders iCalendar but cannot '
                . 'parse it; supply an ICalendarParserInterface (for example one backed by '
                . 'craigk5n/icalendar) to enable import. Export does not need one.'
            );
        }

        return $this->parser->parse($content);
    }

    /**
     * @return bool
     */
    public function canImport(): bool
    {
        return $this->parser !== null;
    }

    // ── internals ─────────────────────────────────────────────────────────────

    /**
     * Map one CalendarEntry onto an eluceo/ical 2.x Event.
     *
     * @param CalendarEntry $entry
     * @return ICalEvent
     */
    private function buildEvent(CalendarEntry $entry): ICalEvent
    {
        // The UID is what lets a calendar client recognise an UPDATE as the same
        // event rather than a new one, so it must be stable across exports. Source
        // plus source id is stable; the current timestamp is not.
        $uid = $entry->getSource() . '-' . $entry->getSourceId() . '@ksfii.org';

        $event = new ICalEvent(new UniqueIdentifier($uid));

        $event->setSummary($entry->getTitle());

        $description = $entry->getDescription();

        if ($description !== '') {
            $event->setDescription($description);
        }

        $location = $entry->getLocation();

        if ($location !== '') {
            $event->setLocation(new ICalLocation($location));
        }

        $occurrence = $this->buildOccurrence($entry);

        if ($occurrence !== null) {
            $event->setOccurrence($occurrence);
        }

        $updated = $entry->getUpdatedAt() ?: $entry->getCreatedAt();

        if ($updated !== null) {
            $event->setLastModified(new ICalDateTime($this->toImmutable($updated), true));
        }

        $categories = $this->buildCategories($entry);

        if ($categories !== array()) {
            $event->setCategories($categories);
        }

        $status = $this->mapStatusToIcal($entry->getStatus());

        if ($status !== null) {
            $event->setStatus($status);
        }

        return $event;
    }

    /**
     * 2.x models DTSTART/DTEND as a single Occurrence value object.
     *
     * All-day entries become a SingleDay; timed entries become a TimeSpan. A
     * missing end date on a timed entry gets +1 hour rather than a zero-length
     * span, because several clients reject a zero duration.
     *
     * @param CalendarEntry $entry
     * @return SingleDay|TimeSpan|null
     */
    private function buildOccurrence(CalendarEntry $entry)
    {
        $start = $entry->getStartDate();

        if ($start === null) {
            return null;
        }

        $startImmutable = $this->toImmutable($start);

        if ($entry->isAllDay()) {
            return new SingleDay(new ICalDate($startImmutable));
        }

        $end = $entry->getEndDate();
        $endImmutable = $end === null
            ? $startImmutable->modify('+1 hour')
            : $this->toImmutable($end);

        // A span that ends before it starts is worse than no span at all.
        if ($endImmutable <= $startImmutable) {
            $endImmutable = $startImmutable->modify('+1 hour');
        }

        return new TimeSpan(
            new ICalDateTime($startImmutable, true),
            new ICalDateTime($endImmutable, true)
        );
    }

    /**
     * @param CalendarEntry $entry
     * @return Category[]
     */
    private function buildCategories(CalendarEntry $entry): array
    {
        $names = array();

        foreach (array($entry->getSource(), $entry->getSourceType(), $entry->getCategory()) as $candidate) {
            $candidate = trim((string)$candidate);

            if ($candidate === '') {
                continue;
            }

            // Duplicates would render as repeated CATEGORIES entries.
            $names[$candidate] = true;
        }

        $out = array();

        foreach (array_keys($names) as $name) {
            $out[] = new Category($name);
        }

        return $out;
    }

    /**
     * CalendarEntry statuses are lowercase words; iCalendar wants an EventStatus.
     *
     * COMPLETED has no iCalendar equivalent -- iCalendar only defines TENTATIVE,
     * CONFIRMED and CANCELLED -- so a completed event is exported as CONFIRMED
     * rather than dropped, because dropping it would silently remove a meeting
     * that did happen.
     *
     * @param string $status
     * @return EventStatus|null
     */
    private function mapStatusToIcal(string $status): ?EventStatus
    {
        switch ($status) {
            case CalendarEntry::STATUS_CONFIRMED:
                return EventStatus::CONFIRMED();

            case CalendarEntry::STATUS_CANCELLED:
                return EventStatus::CANCELLED();

            case CalendarEntry::STATUS_PENDING:
            case CalendarEntry::STATUS_COMPLETED:
                return EventStatus::CONFIRMED();

            default:
                return null;
        }
    }

    /**
     * @param ICalEvent[] $events
     * @param string       $calendarName
     * @return string
     */
    private function render(array $events, string $calendarName): string
    {
        $calendar = new ICalCalendar($events);
        $calendar->setProductIdentifier(self::PRODUCT_IDENTIFIER);

        $component = (new CalendarFactory())->createCalendar($calendar);

        // eluceo/ical 2.x has NO calendar-name support: CalendarFactory renders only
        // PRODID, VERSION, CALSCALE and X-PUBLISHED-TTL, and the Calendar entity has
        // no name property at all. X-WR-CALNAME is what Outlook shows as the
        // calendar name and what most invite templates key off, so it is injected
        // here rather than accepting a $calendarName parameter and silently
        // discarding it.
        if ($calendarName !== '') {
            $component = $component->withProperty(
                new Property('X-WR-CALNAME', new TextValue($calendarName))
            );
        }

        return (string)$component;
    }

    /**
     * @param string $content
     * @param string $filePath
     * @return bool
     */
    private function write(string $content, string $filePath): bool
    {
        $bytes = @file_put_contents($filePath, $content);

        if ($bytes === false) {
            $this->logger->error('Could not write iCalendar feed', array('path' => $filePath));

            return false;
        }

        return true;
    }

    /**
     * The library wants DateTimeImmutable; the entity stores DateTimeInterface.
     *
     * @param DateTimeInterface $value
     * @return DateTimeImmutable
     */
    private function toImmutable(DateTimeInterface $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        return DateTimeImmutable::createFromFormat('U.u', $value->format('U.u'))
            ->setTimezone($value->getTimezone());
    }

    /**
     * @param CalendarEntry  $entry
     * @param CalendarSource $source
     * @return bool
     */
    private function shouldIncludeEntry(CalendarEntry $entry, CalendarSource $source): bool
    {
        $type = $entry->getSourceType();

        if ($type !== '' && $type !== $source->getSourceType()) {
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     * @return string|null
     */
    private function fetchUrl(string $url): ?string
    {
        $context = stream_context_create(array(
            'http' => array(
                'timeout'             => 30,
                'user_agent'          => 'KSF-Calendar/1.0',
                'follow_location'     => 1,
                'max_redirects'       => 5,
            ),
        ));

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            return null;
        }

        return $content;
    }
}