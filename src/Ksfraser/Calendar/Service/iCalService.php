<?php
/**
 * iCalService
 *
 * Import/export iCal feeds using eluceo/ical for creation, php-icalendar-core for parsing
 *
 * @package Ksfraser\Calendar\Service
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Service;

use DateTime;
use DateTimeInterface;
use Ksfraser\Calendar\Entity\CalendarEntry;
use Ksfraser\Calendar\Entity\CalendarSource;
use Eluceo\iCal\Component\Calendar as iCalCalendar;
use Eluceo\iCal\Component\Event as iCalEvent;
use Eluceo\iCal\PropertyFactory\FactoryTrait;
use Psr\Log\LoggerInterface;
use Craigk5n\ICalendar\Reader;
use Craigk5n\ICalendar\Property;

class iCalService
{
    use FactoryTrait;

    private const DEFAULT_TIMEZONE = 'UTC';

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function exportEntries(array $entries, string $calendarName = 'KSF Calendar'): string
    {
        $calendar = new iCalCalendar($calendarName);

        foreach ($entries as $entry) {
            if (!$entry instanceof CalendarEntry) {
                continue;
            }

            $iCalEvent = $this->createICalEvent($entry);
            $calendar->addComponent($iCalEvent);
        }

        return (string) $calendar;
    }

    public function exportEntriesToFile(array $entries, string $filePath, string $calendarName = 'KSF Calendar'): bool
    {
        $content = $this->exportEntries($entries, $calendarName);

        $result = file_put_contents($filePath, $content);

        if ($result === false) {
            $this->logger->error('Failed to write iCal file', ['path' => $filePath]);
            return false;
        }

        $this->logger->info('Exported iCal file', ['path' => $filePath, 'count' => count($entries)]);
        return true;
    }

    public function exportSource(CalendarSource $source, array $entries): string
    {
        $calendarName = $source->getName() ?: 'KSF Calendar';

        $calendar = new iCalCalendar($calendarName);
        $calendar->setTimezone($this->getTimezoneString());

        foreach ($entries as $entry) {
            if ($this->shouldIncludeEntry($entry, $source)) {
                $iCalEvent = $this->createICalEvent($entry);
                $calendar->addComponent($iCalEvent);
            }
        }

        return (string) $calendar;
    }

    public function importFromUrl(string $url): array
    {
        $this->logger->info('Importing iCal from URL', ['url' => $url]);

        $content = $this->fetchUrl($url);

        if ($content === null) {
            throw new \RuntimeException("Failed to fetch iCal URL: $url");
        }

        return $this->parseICalContent($content, CalendarEntry::SOURCE_ICAL);
    }

    public function importFromFile(string $filePath): array
    {
        $this->logger->info('Importing iCal from file', ['path' => $filePath]);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("iCal file not found: $filePath");
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            throw new \RuntimeException("Failed to read iCal file: $filePath");
        }

        return $this->parseICalContent($content, CalendarEntry::SOURCE_ICAL);
    }

    public function importFromString(string $content): array
    {
        return $this->parseICalContent($content, CalendarEntry::SOURCE_ICAL);
    }

    public function generatePublicUrl(CalendarSource $source, string $baseUrl): string
    {
        $token = $this->generateSourceToken($source);
        return rtrim($baseUrl, '/') . '/ical/' . $source->getId() . '/' . $token . '/export.ics';
    }

    private function createICalEvent(CalendarEntry $entry): iCalEvent
    {
        $event = new iCalEvent();

        $uid = $entry->getSource() . '-' . $entry->getSourceId() . '@ksfii.org';
        $event->setUniqueId($uid);

        $event->setSummary($entry->getTitle());

        if ($entry->getDescription()) {
            $event->setDescription($entry->getDescription());
        }

        if ($entry->getLocation()) {
            $event->setLocation($entry->getLocation());
        }

        if ($entry->getStartDate()) {
            if ($entry->isAllDay()) {
                $event->setDtStart(
                    (new \Eluceo\iCal\Parameter\Value\ValueDateTime(
                        DateTime::createFromFormat('Y-m-d', $entry->getStartDate()->format('Y-m-d'))
                    ))->transformToParameter()
                );
            } else {
                $event->setDtStart(
                    new \Eluceo\iCal\Parameter\Value\ValueDateTime(
                        $entry->getStartDate()
                    )
                );
            }
        }

        if ($entry->getEndDate()) {
            if ($entry->isAllDay()) {
                $event->setDtEnd(
                    (new \Eluceo\iCal\Parameter\Value\ValueDateTime(
                        DateTime::createFromFormat('Y-m-d', $entry->getEndDate()->format('Y-m-d'))
                    ))->transformToParameter()
                );
            } else {
                $event->setDtEnd(
                    new \Eluceo\iCal\Parameter\Value\ValueDateTime(
                        $entry->getEndDate()
                    )
                );
            }
        }

        if ($entry->getRecurrenceRule()) {
            $event->setRRule($entry->getRecurrenceRule());
        }

        $event->setCreated(
            new \Eluceo\iCal\Parameter\Value\ValueDateTime(
                $entry->getCreatedAt() ?? new DateTime()
            )
        );

        $event->setModified(
            new \Eluceo\iCal\Parameter\Value\ValueDateTime(
                $entry->getUpdatedAt() ?? new DateTime()
            )
        );

        if ($entry->getColor()) {
            $event->setColor($entry->getColor());
        }

        $categories = [];
        if ($entry->getSource()) {
            $categories[] = $entry->getSource();
        }
        if ($entry->getSourceType()) {
            $categories[] = $entry->getSourceType();
        }
        if ($entry->getCategory()) {
            $categories[] = $entry->getCategory();
        }
        if (!empty($categories)) {
            $event->setCategories($categories);
        }

        $status = $this->mapStatusToIcal($entry->getStatus());
        if ($status) {
            $event->setStatus($status);
        }

        return $event;
    }

    private function parseICalContent(string $content, string $source): array
    {
        $entries = [];

        $reader = new Reader($content);
        $calendar = $reader->parse();

        foreach ($calendar->getComponents() as $component) {
            if (!$component instanceof \Craigk5n\ICalendar\Component\VEvent) {
                continue;
            }

            $entry = $this->parseVEvent($component, $source);
            if ($entry) {
                $entries[] = $entry;
            }
        }

        $this->logger->info('Parsed iCal content', ['count' => count($entries)]);
        return $entries;
    }

    private function parseVEvent(\Craigk5n\ICalendar\Component\VEvent $vevent, string $source): ?CalendarEntry
    {
        $dtstart = $vevent->getDtStart();
        $dtend = $vevent->getDtEnd();

        if (!$dtstart) {
            return null;
        }

        $startDate = $this->parseDateTimeProperty($dtstart);
        $endDate = $this->parseDateTimeProperty($dtend);

        $allDay = $dtstart->getValueType() === 'DATE';

        $summary = '';
        $description = '';
        $location = '';
        $uid = '';
        $rrule = null;

        foreach ($vevent->getProperties() as $property) {
            $value = $property->getValue();
            $value = is_array($value) ? implode(',', $value) : (string) $value;

            switch ($property->getName()) {
                case 'SUMMARY':
                    $summary = $value;
                    break;
                case 'DESCRIPTION':
                    $description = $value;
                    break;
                case 'LOCATION':
                    $location = $value;
                    break;
                case 'UID':
                    $uid = $value;
                    break;
                case 'RRULE':
                    $rrule = $value;
                    break;
            }
        }

        $entry = new CalendarEntry(
            source: $source,
            sourceId: $uid ?: uniqid('ical_'),
            sourceType: CalendarEntry::TYPE_EVENT,
            title: $summary ?: 'Untitled Event',
            startDate: $startDate
        );

        $entry->setEndDate($endDate);
        $entry->setDescription($description);
        $entry->setLocation($location);
        $entry->setAllDay($allDay ? 'yes' : 'no');

        if ($rrule) {
            $entry->setRecurrenceRule($rrule);
        }

        return $entry;
    }

    private function parseDateTimeProperty(?Property $property): ?DateTime
    {
        if (!$property) {
            return null;
        }

        $value = $property->getValue();
        $valueType = $property->getValueType();

        if ($valueType === 'DATE') {
            return DateTime::createFromFormat('Ymd', (string) $value) ?: null;
        }

        return DateTime::createFromFormat('Ymd\THis', (string) $value) ?: null;
    }

    private function shouldIncludeEntry(CalendarEntry $entry, CalendarSource $source): bool
    {
        $enabledTypes = $source->getEnabledSourceTypes();

        if (!empty($enabledTypes) && !in_array($entry->getSourceType(), $enabledTypes, true)) {
            return false;
        }

        if ($source->isEnabled() === false) {
            return false;
        }

        return true;
    }

    private function mapStatusToIcal(string $status)
    {
        switch ($status) {
            case CalendarEntry::STATUS_CONFIRMED:
                return 'CONFIRMED';
            case CalendarEntry::STATUS_CANCELLED:
                return 'CANCELLED';
            case CalendarEntry::STATUS_COMPLETED:
                return 'COMPLETED';
            default:
                return null;
        }
    }

    private function getTimezoneString(): string
    {
        return date_default_timezone_get() ?: self::DEFAULT_TIMEZONE;
    }

    private function generateSourceToken(CalendarSource $source): string
    {
        return hash('sha256', $source->getId() . $source->getName() . date('Y-m-d'));
    }

    private function fetchUrl(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'KSF-Calendar/1.0',
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        return $content !== false ? $content : null;
    }
}