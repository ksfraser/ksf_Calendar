<?php
/**
 * CalendarService createReminderEntry Tests (v1.6.0)
 *
 * Covers:
 *   createReminderEntry(int $parentEntryId, int $minutesBefore): CalendarEntry
 *
 * PHP 7.4+ compatible — no PHP 8+ syntax.
 *
 * @package Ksfraser\Calendar\Tests\Unit
 * @since   1.6.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ksfraser\Calendar\Service\CalendarService;
use Ksfraser\Calendar\Entity\CalendarEntry;
use Ksfraser\Calendar\Contract\DatabaseAdapterInterface;
use Ksfraser\Calendar\Exception\CalendarException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ksfraser\Calendar\Service\CalendarService
 */
class CalendarServiceReminderTest extends TestCase
{
    /** @var DatabaseAdapterInterface|MockObject */
    private $db;

    /** @var EventDispatcherInterface|MockObject */
    private $events;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CalendarService */
    private $service;

    protected function setUp(): void
    {
        $this->db     = $this->createMock(DatabaseAdapterInterface::class);
        $this->events = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new CalendarService($this->db, $this->events, $this->logger);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /** Minimal parent entry row so getEntry() succeeds. */
    private function makeParentRow(array $override = []): array
    {
        return array_merge([
            'id'          => '10',
            'source'      => 'user',
            'source_id'   => '10',
            'source_type' => 'event',
            'title'       => 'Parent Meeting',
            'description' => '',
            'start_date'  => '2026-09-01 10:00:00',
            'end_date'    => '2026-09-01 11:00:00',
            'all_day'     => 'no',
            'timezone'    => 'UTC',
            'location'    => '',
            'status'      => 'pending',
            'priority'    => 'medium',
            'color'       => '',
            'private'     => '0',
            'inactive'    => '0',
        ], $override);
    }

    // ---------------------------------------------------------------
    // createReminderEntry — success path
    // ---------------------------------------------------------------

    /**
     * createReminderEntry() returns a CalendarEntry.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntryReturnsCalendarEntry(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(), null);

        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('55');

        $reminder = $this->service->createReminderEntry(10, 30);

        $this->assertInstanceOf(CalendarEntry::class, $reminder);
    }

    /**
     * createReminderEntry() sets source_type to 'reminder'.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntrySetsSourceTypeReminder(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(), null);
        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('56');

        $reminder = $this->service->createReminderEntry(10, 30);

        $this->assertSame(CalendarEntry::TYPE_REMINDER, $reminder->getSourceType());
    }

    /**
     * createReminderEntry() links the child to the parent via parent_entry_id.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntrySetsParentEntryId(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(['id' => '10']), null);
        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('57');

        $reminder = $this->service->createReminderEntry(10, 15);

        $this->assertSame(10, $reminder->getParentEntryId());
    }

    /**
     * createReminderEntry() sets reminder flag to true.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntrySetsReminderFlag(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(), null);
        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('58');

        $reminder = $this->service->createReminderEntry(10, 30);

        $this->assertTrue($reminder->hasReminder());
    }

    /**
     * createReminderEntry() sets reminder_minutes to the given minutesBefore.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntrySetsReminderMinutes(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(), null);
        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('59');

        $reminder = $this->service->createReminderEntry(10, 45);

        $this->assertSame(45, $reminder->getReminderMinutes());
    }

    /**
     * createReminderEntry() dispatches CalendarEntryCreatedEvent.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntryDispatchesCreatedEvent(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(), null);
        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('60');

        $this->events->expects($this->once())
            ->method('dispatch');

        $this->service->createReminderEntry(10, 30);
    }

    /**
     * createReminderEntry() assigns the DB-generated id to the returned entry.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntryAssignsId(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls($this->makeParentRow(), null);
        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('99');

        $reminder = $this->service->createReminderEntry(10, 30);

        $this->assertSame(99, $reminder->getId());
    }

    // ---------------------------------------------------------------
    // createReminderEntry — failure path
    // ---------------------------------------------------------------

    /**
     * createReminderEntry() throws CalendarException when the parent does not exist.
     *
     * @since 1.6.0
     */
    public function testCreateReminderEntryThrowsWhenParentNotFound(): void
    {
        $this->db->method('fetchAssoc')->willReturn(null);

        $this->expectException(CalendarException::class);

        $this->service->createReminderEntry(999, 30);
    }
}
