<?php
/**
 * @package ksfraser\Calendar\Tests\Unit
 */

declare(strict_types=1);

namespace ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ksfraser\Calendar\Entity\CalendarEntry;
use ksfraser\Calendar\Exception\ImportFailedException;
use ksfraser\Calendar\Exception\ImportUnavailableException;
use ksfraser\Calendar\Service\ICalService;
use ksfraser\Calendar\Service\ICalendarParserInterface;
use Psr\Log\NullLogger;

/**
 * The .ical export is customer-facing: it is what CRM invites are built from.
 *
 * These tests assert the RENDERED iCalendar text, not just that a method returns
 * a string, because a string is not the same as a valid feed.
 */
class ICalServiceTest extends TestCase
{
    /** @var ICalService */
    private $service;

    protected function setUp(): void
    {
        $this->service = new ICalService(new NullLogger());
    }

    /**
     * @param array $overrides
     * @return CalendarEntry
     */
    private function entry(array $overrides = array()): CalendarEntry
    {
        $source = $overrides['source'] ?? 'crm';
        $sourceId = $overrides['sourceId'] ?? '1234';
        $title = $overrides['title'] ?? 'Site visit';

        // The entity takes these through the constructor, and wants the MUTABLE
        // DateTime -- setStartDate() is typed ?DateTime, not ?DateTimeInterface.
        $start = self::date($overrides['start'] ?? '2026-03-24 09:00:00');

        $entry = new CalendarEntry(
            $source,
            $sourceId,
            $overrides['sourceType'] ?? CalendarEntry::TYPE_MEETING,
            $title,
            $start
        );

        $entry->setDescription($overrides['description'] ?? 'Annual service call');
        $entry->setLocation($overrides['location'] ?? 'Rochester');
        $entry->setStatus($overrides['status'] ?? CalendarEntry::STATUS_CONFIRMED);
        $entry->setEndDate(isset($overrides['end']) ? self::date($overrides['end']) : self::date('2026-03-24 10:30:00'));

        if (!empty($overrides['allDay'])) {
            $entry->setAllDay('yes');
        }

        if (isset($overrides['category'])) {
            $entry->setCategory($overrides['category']);
        }

        if (isset($overrides['noEnd'])) {
            $entry->setEndDate(null);
        }

        return $entry;
    }

    /**
     * @param string $value
     * @return \DateTime
     */
    private static function date(string $value): \DateTime
    {
        return new \DateTime($value);
    }

    public function testExportedFeedIsAWellFormedICalendarDocument(): void
    {
        $ics = $this->service->exportEntries(array($this->entry()));

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('END:VEVENT', $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('PRODID:', $ics);
    }

    /**
     * RFC 5545 requires CRLF line endings. Some clients accept LF, others refuse
     * the file -- this is the single most common reason a generated feed is
     * rejected.
     */
    public function testFeedUsesCrLfLineEndings(): void
    {
        $ics = $this->service->exportEntries(array($this->entry()));

        $this->assertStringContainsString("\r\n", $ics);
        $this->assertDoesNotMatchRegularExpression('/(?<!\r)\n/', $ics, 'bare LF found: iCalendar requires CRLF');
    }

    public function testSummaryIsPresent(): void
    {
        $ics = $this->service->exportEntries(array($this->entry()));

        $this->assertStringContainsString('SUMMARY:Site visit', $ics);
    }

    public function testDescriptionAndLocationArePresent(): void
    {
        $ics = $this->service->exportEntries(array($this->entry()));

        $this->assertStringContainsString('Annual service call', $ics);
        $this->assertStringContainsString('Rochester', $ics);
    }

    /**
     * A stable UID is what lets a client treat a re-sent invite as an UPDATE to
     * the same event instead of creating a duplicate.
     */
    public function testUidIsStableAcrossExports(): void
    {
        $first = $this->service->exportEntries(array($this->entry()));
        $second = $this->service->exportEntries(array($this->entry()));

        // Deliberately NOT asserting the feeds are byte-identical: DTSTAMP is
        // "now", so that would be a time-dependent test that passes only when both
        // exports land in the same second. The UID is the property that actually
        // matters -- it is what makes a client treat a re-send as an UPDATE rather
        // than a duplicate event.
        $this->assertSame(
            $this->extract($first, 'UID'),
            $this->extract($second, 'UID')
        );
        $this->assertSame('crm-1234@ksfii.org', $this->extract($first, 'UID'));
    }

    public function testProdidIdentifiesKsfRatherThanTheLibrary(): void
    {
        $ics = $this->service->exportEntries(array($this->entry()));

        $this->assertSame(ICalService::PRODUCT_IDENTIFIER, $this->extract($ics, 'PRODID'));
        $this->assertStringNotContainsString('eluceo', $ics);
    }

    /**
     * @param string $ics
     * @param string $property
     * @return string
     */
    private function extract(string $ics, string $property): string
    {
        preg_match('/^' . preg_quote($property, '/') . ':([^\r\n]*)/m', $ics, $m);

        return isset($m[1]) ? trim($m[1]) : '';
    }

    public function testUidDiffersForDifferentSourceEntries(): void
    {
        $a = $this->service->exportEntries(array($this->entry(array('sourceId' => '1'))));
        $b = $this->service->exportEntries(array($this->entry(array('sourceId' => '2'))));

        $this->assertStringContainsString('UID:crm-1@ksfii.org', $a);
        $this->assertStringContainsString('UID:crm-2@ksfii.org', $b);
    }

    public function testTimedEventEmitsDateTimeWithUtcZulu(): void
    {
        $ics = $this->service->exportEntries(array($this->entry()));

        $this->assertMatchesRegularExpression('/DTSTART:\d{8}T\d{6}Z/', $ics);
        $this->assertMatchesRegularExpression('/DTEND:\d{8}T\d{6}Z/', $ics);
    }

    /**
     * An all-day event must NOT carry a time component: DTSTART;VALUE=DATE.
     */
    public function testAllDayEventEmitsDateOnly(): void
    {
        $ics = $this->service->exportEntries(array($this->entry(array('allDay' => true))));

        $this->assertStringContainsString('DTSTART;VALUE=DATE:', $ics);
        $this->assertDoesNotMatchRegularExpression('/DTSTART:\d{8}T/', $ics, 'an all-day event must not carry a time');
    }

    /**
     * A zero-length span is rejected by several clients, so a missing end date
     * becomes a one-hour default.
     */
    public function testTimedEventWithoutAnEndStillGetsAnEnd(): void
    {
        $ics = $this->service->exportEntries(array($this->entry(array('noEnd' => true))));

        $this->assertMatchesRegularExpression('/DTSTART:\d{8}T\d{6}Z/', $ics);
        $this->assertMatchesRegularExpression('/DTEND:\d{8}T\d{6}Z/', $ics, 'an end date is required');
    }

    public function testEventEndingBeforeItStartsIsCorrected(): void
    {
        $ics = $this->service->exportEntries(array($this->entry(array(
            'start' => '2026-03-24 10:00:00',
            'end'   => '2026-03-24 09:00:00',
        ))));

        $this->assertMatchesRegularExpression('/DTEND:\d{8}T\d{6}Z/', $ics);
    }

    public function testCancelledStatusIsExported(): void
    {
        $ics = $this->service->exportEntries(array(
            $this->entry(array('status' => CalendarEntry::STATUS_CANCELLED)),
        ));

        $this->assertStringContainsString('STATUS:CANCELLED', $ics);
    }

    /**
     * COMPLETED has no iCalendar equivalent; dropping the event would hide a
     * meeting that did happen, so it is exported as CONFIRMED.
     */
    public function testCompletedStatusIsExportedAsConfirmed(): void
    {
        $ics = $this->service->exportEntries(array(
            $this->entry(array('status' => CalendarEntry::STATUS_COMPLETED)),
        ));

        $this->assertStringContainsString('STATUS:CONFIRMED', $ics);
    }

    public function testCategoriesAreDeduplicated(): void
    {
        // source is 'crm' and category is 'crm' -- must appear ONCE in CATEGORIES.
        // Counted there specifically, because the UID (crm-1234@ksfii.org) also
        // contains the string.
        $ics = $this->service->exportEntries(array($this->entry(array('category' => 'crm'))));

        preg_match('/CATEGORIES:([^\\r\\n]*)/', $ics, $m);

        $categories = explode(',', trim($m[1]));

        // 'crm' arrives twice (as source and as category) and must appear once.
        $this->assertSame(1, count(array_keys($categories, 'crm', true)));
        $this->assertSame(array('crm', 'meeting'), $categories);
    }

    public function testMultipleEntriesAllAppear(): void
    {
        $ics = $this->service->exportEntries(array(
            $this->entry(array('sourceId' => '1', 'title' => 'First')),
            $this->entry(array('sourceId' => '2', 'title' => 'Second')),
        ));

        $this->assertSame(2, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('SUMMARY:First', $ics);
        $this->assertStringContainsString('SUMMARY:Second', $ics);
    }

    public function testNonEntryValuesAreSkippedRatherThanFatal(): void
    {
        $ics = $this->service->exportEntries(array($this->entry(), 'rubbish', null));

        $this->assertSame(1, substr_count($ics, 'BEGIN:VEVENT'));
    }

    public function testEmptyExportIsStillAValidDocument(): void
    {
        $ics = $this->service->exportEntries(array());

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
        $this->assertStringNotContainsString('BEGIN:VEVENT', $ics);
    }

    /**
     * The per-attendee invite: one event, not a whole feed.
     */
    public function testExportEntryRendersASingleEvent(): void
    {
        $ics = $this->service->exportEntry($this->entry(), 'Your appointment');

        $this->assertSame(1, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('X-WR-CALNAME:Your appointment', $ics);
    }

    public function testDownloadHeadersInviteABrowserToSaveTheFile(): void
    {
        $headers = $this->service->downloadHeaders('invite.ics');

        $this->assertSame('text/calendar; charset=utf-8', $headers['Content-Type']);
        $this->assertStringContainsString('invite.ics', $headers['Content-Disposition']);
    }

    public function testExportToFileWritesTheFeed(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ical');

        try {
            $this->assertTrue($this->service->exportEntriesToFile(array($this->entry()), $path));
            $this->assertStringContainsString('BEGIN:VCALENDAR', (string)file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }

    public function testExportToUnwritablePathReturnsFalse(): void
    {
        $this->assertFalse(
            $this->service->exportEntriesToFile(array($this->entry()), '/nonexistent-dir/x/feed.ics')
        );
    }

    // ── import: refuses clearly rather than pretending ───────────────────────

    public function testImportWithoutAParserRefusesWithAnActionableMessage(): void
    {
        $this->assertFalse($this->service->canImport());

        $this->expectException(ImportUnavailableException::class);
        $this->expectExceptionMessage('eluceo/ical renders iCalendar but cannot parse it');

        $this->service->importFromString('BEGIN:VCALENDAREND:VCALENDAR');
    }

    public function testImportWithAParserWorks(): void
    {
        $parser = new class() implements ICalendarParserInterface {
            public function parse(string $content): array
            {
                return array('parsed' => $content);
            }
        };

        $service = new ICalService(new NullLogger(), $parser);

        $this->assertTrue($service->canImport());
        $this->assertSame(array('parsed' => 'BEGIN:VCALENDAR'), $service->importFromString('BEGIN:VCALENDAR'));
    }

    public function testImportFromMissingFileIsADistinctFailure(): void
    {
        $this->expectException(ImportFailedException::class);

        $this->service->importFromFile('/no/such/file.ics');
    }

    /**
     * ImportUnavailableException must be distinguishable from ImportFailedException:
     * "not enabled yet" and "your file is broken" need different responses.
     */
    public function testImportFailuresAreDistinguishableTypes(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->importFromString('anything');
    }

    public function testExportWorksWithoutAParser(): void
    {
        // The whole point of splitting them: invites must not depend on import.
        $this->assertStringContainsString(
            'BEGIN:VCALENDAR',
            $this->service->exportEntries(array($this->entry()))
        );
    }
}