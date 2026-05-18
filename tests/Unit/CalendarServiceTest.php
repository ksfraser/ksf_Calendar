<?php
/**
 * Unit tests for CalendarService invitee methods (v1.1)
 *
 * Covers:
 *   searchInvitees, addInvitee (resource auto-accept / auto-decline),
 *   removeInvitee, updateRsvp, getInviteesForEntry, getFreeBusy
 *
 * The DatabaseAdapterInterface is replaced with a PHPUnit mock so no real
 * database is needed. EventDispatcherInterface and LoggerInterface use
 * NullImplementations (PSR stubs via phpunit mocks).
 *
 * PHP 7.4+ compatible — no PHP 8+ syntax.
 *
 * @package Ksfraser\Calendar\Tests\Unit
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit;

use DateTime;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ksfraser\Calendar\Service\CalendarService;
use Ksfraser\Calendar\Entity\CalendarInvitee;
use Ksfraser\Calendar\Contract\DatabaseAdapterInterface;
use Ksfraser\Calendar\Exception\CalendarException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ksfraser\Calendar\Service\CalendarService
 */
class CalendarServiceTest extends TestCase
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Minimal entry row as returned by fetchAssoc. */
    private function makeEntryRow(array $override = []): array
    {
        return array_merge([
            'id'          => '10',
            'source'      => 'user',
            'source_id'   => '10',
            'source_type' => 'meeting',
            'title'       => 'Test Meeting',
            'description' => '',
            'start_date'  => '2026-05-18 10:00:00',
            'end_date'    => '2026-05-18 11:00:00',
            'all_day'     => 'no',
            'timezone'    => 'UTC',
            'location'    => '',
            'online_url'  => '',
            'phone_number'=> '',
            'send_invites'=> '0',
            'assigned_to' => '1',
            'user_id'     => '1',
            'customer_id' => null,
            'project_id'  => null,
            'task_id'     => null,
            'contact_id'  => null,
            'status'      => 'pending',
            'priority'    => 'medium',
            'category'    => '',
            'reminder'    => '0',
            'reminder_minutes' => null,
            'color'       => '',
            'private'     => '0',
            'inactive'    => '0',
            'created_at'  => '2026-05-18 09:00:00',
            'updated_at'  => '2026-05-18 09:00:00',
        ], $override);
    }

    /** Minimal invitee row. */
    private function makeInviteeRow(array $override = []): array
    {
        return array_merge([
            'id'           => '1',
            'entry_id'     => '10',
            'contact_type' => 'fa_user',
            'contact_id'   => '2',
            'name'         => 'Alice Smith',
            'email'        => 'alice@example.com',
            'phone'        => '',
            'rsvp_status'  => 'pending',
            'is_organizer' => '0',
            'is_resource'  => '0',
            'invited_at'   => '2026-05-18 09:00:00',
            'responded_at' => null,
            'inactive'     => '0',
        ], $override);
    }

    // -------------------------------------------------------------------------
    // searchInvitees
    // -------------------------------------------------------------------------

    public function testSearchInviteesEmptyQueryReturnsEmpty(): void
    {
        $this->db->expects($this->never())->method('fetchAll');
        $result = $this->service->searchInvitees('a');
        $this->assertSame([], $result);
    }

    public function testSearchInviteesReturnsUsersFromDb(): void
    {
        $this->db->expects($this->atLeastOnce())
            ->method('fetchAll')
            ->willReturnCallback(function (string $sql, array $params) {
                if (strpos($sql, 'users') !== false) {
                    return [
                        ['user_id' => '3', 'real_name' => 'Alice', 'email' => 'alice@example.com'],
                    ];
                }
                // CRM / resources — throw to simulate module not installed
                throw new \RuntimeException('Table not found');
            });

        $results = $this->service->searchInvitees('ali');
        $this->assertCount(1, $results);
        $this->assertSame(CalendarInvitee::TYPE_FA_USER, $results[0]['contact_type']);
        $this->assertSame('Alice', $results[0]['name']);
    }

    public function testSearchInviteesSkipsCrmWhenTableMissing(): void
    {
        // Users query returns empty; CRM throws; resources throw.
        $this->db->method('fetchAll')
            ->willReturnCallback(function (string $sql) {
                if (strpos($sql, 'users') !== false) {
                    return [];
                }
                throw new \RuntimeException('Table not found');
            });

        $result = $this->service->searchInvitees('test');
        $this->assertIsArray($result);
        // No exception propagated; result is empty array.
        $this->assertCount(0, $result);
    }

    public function testSearchInviteesReturnsCrmContactsWhenAvailable(): void
    {
        $this->db->method('fetchAll')
            ->willReturnCallback(function (string $sql) {
                if (strpos($sql, 'users') !== false) {
                    return [];
                }
                if (strpos($sql, 'fa_crm_contacts') !== false) {
                    return [['id' => '5', 'full_name' => 'Bob Jones', 'email' => 'bob@example.com', 'phone' => '']];
                }
                return [];  // Resources — empty
            });

        $results = $this->service->searchInvitees('bob');
        $this->assertCount(1, $results);
        $this->assertSame(CalendarInvitee::TYPE_CRM_CONTACT, $results[0]['contact_type']);
    }

    // -------------------------------------------------------------------------
    // addInvitee — person
    // -------------------------------------------------------------------------

    public function testAddPersonInviteePersistsRow(): void
    {
        // getEntry calls fetchAssoc for entry + loadInvitees fetchAll.
        $this->db->method('fetchAssoc')
            ->willReturn($this->makeEntryRow());
        $this->db->method('fetchAll')->willReturn([]);
        $this->db->expects($this->once())->method('executeUpdate');

        $invitee = $this->service->addInvitee(10, [
            'contact_type' => 'fa_user',
            'contact_id'   => '2',
            'name'         => 'Alice Smith',
            'email'        => 'alice@example.com',
        ]);

        $this->assertInstanceOf(CalendarInvitee::class, $invitee);
        $this->assertSame(CalendarInvitee::RSVP_PENDING, $invitee->getRsvpStatus());
    }

    public function testAddPersonInviteeThrowsWhenEntryMissing(): void
    {
        $this->db->method('fetchAssoc')->willReturn(null);
        $this->expectException(CalendarException::class);
        $this->service->addInvitee(999, ['name' => 'Alice', 'email' => 'a@example.com']);
    }

    // -------------------------------------------------------------------------
    // addInvitee — resource auto-accept
    // -------------------------------------------------------------------------

    public function testAddResourceAutoAcceptsWhenAvailable(): void
    {
        $callCount = 0;
        $this->db->method('fetchAssoc')
            ->willReturnCallback(function (string $sql) use (&$callCount) {
                $callCount++;
                if (strpos($sql, 'SELECT *') !== false && strpos($sql, 'fa_cal_entries') !== false) {
                    return $this->makeEntryRow();
                }
                // isResourceAvailable — entry time window
                if (strpos($sql, 'start_date, end_date') !== false) {
                    return ['start_date' => '2026-05-18 10:00:00', 'end_date' => '2026-05-18 11:00:00'];
                }
                // Conflict check — null = no conflict
                return null;
            });
        $this->db->method('fetchAll')->willReturn([]);
        $this->db->method('executeUpdate')->willReturn(1);

        $invitee = $this->service->addInvitee(10, [
            'contact_type' => CalendarInvitee::TYPE_RESOURCE,
            'contact_id'   => 'room-1',
            'name'         => 'Boardroom A',
            'email'        => '',
        ]);

        $this->assertSame(CalendarInvitee::RSVP_ACCEPTED, $invitee->getRsvpStatus());
    }

    public function testAddResourceAutoDeclineWhenConflict(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturnCallback(function (string $sql) {
                if (strpos($sql, 'SELECT *') !== false) {
                    return $this->makeEntryRow();
                }
                if (strpos($sql, 'start_date, end_date') !== false) {
                    return ['start_date' => '2026-05-18 10:00:00', 'end_date' => '2026-05-18 11:00:00'];
                }
                // Conflict exists — return a row
                return ['id' => '5'];
            });
        $this->db->method('fetchAll')->willReturn([]);
        $this->db->method('executeUpdate')->willReturn(1);

        $invitee = $this->service->addInvitee(10, [
            'contact_type' => CalendarInvitee::TYPE_RESOURCE,
            'contact_id'   => 'room-1',
            'name'         => 'Boardroom A',
            'email'        => '',
        ]);

        $this->assertSame(CalendarInvitee::RSVP_DECLINED, $invitee->getRsvpStatus());
    }

    // -------------------------------------------------------------------------
    // removeInvitee
    // -------------------------------------------------------------------------

    public function testRemoveInviteeSoftDeletes(): void
    {
        $this->db->expects($this->once())
            ->method('executeUpdate')
            ->with($this->stringContains('inactive = 1'))
            ->willReturn(1);

        $this->service->removeInvitee(1);
    }

    public function testRemoveInviteeThrowsWhenNotFound(): void
    {
        $this->db->method('executeUpdate')->willReturn(0);
        $this->expectException(CalendarException::class);
        $this->service->removeInvitee(999);
    }

    // -------------------------------------------------------------------------
    // updateRsvp
    // -------------------------------------------------------------------------

    public function testUpdateRsvpChangesStatus(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturn($this->makeInviteeRow(['rsvp_status' => 'pending']));
        $this->db->expects($this->once())
            ->method('executeUpdate')
            ->with($this->stringContains('rsvp_status'), $this->anything())
            ->willReturn(1);

        $invitee = $this->service->updateRsvp(1, CalendarInvitee::RSVP_ACCEPTED);
        $this->assertInstanceOf(CalendarInvitee::class, $invitee);
        $this->assertSame(CalendarInvitee::RSVP_ACCEPTED, $invitee->getRsvpStatus());
    }

    public function testUpdateRsvpThrowsWhenInviteeMissing(): void
    {
        $this->db->method('fetchAssoc')->willReturn(null);
        $this->expectException(CalendarException::class);
        $this->service->updateRsvp(999, CalendarInvitee::RSVP_ACCEPTED);
    }

    // -------------------------------------------------------------------------
    // getInviteesForEntry
    // -------------------------------------------------------------------------

    public function testGetInviteesForEntryReturnsArray(): void
    {
        $this->db->method('fetchAll')->willReturn([]);
        $result = $this->service->getInviteesForEntry(10);
        $this->assertIsArray($result);
    }

    public function testGetInviteesForEntryMapsToEntities(): void
    {
        $this->db->method('fetchAll')
            ->willReturn([$this->makeInviteeRow()]);

        $result = $this->service->getInviteesForEntry(10);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CalendarInvitee::class, $result[0]);
        $this->assertSame('Alice Smith', $result[0]->getName());
    }

    public function testGetInviteesForEntryReturnsEmptyWhenNone(): void
    {
        $this->db->method('fetchAll')->willReturn([]);
        $result = $this->service->getInviteesForEntry(10);
        $this->assertCount(0, $result);
    }

    // -------------------------------------------------------------------------
    // getFreeBusy
    // -------------------------------------------------------------------------

    public function testGetFreeBusyReturnsEmptyWhenNoBusy(): void
    {
        $this->db->method('fetchAll')->willReturn([]);
        $result = $this->service->getFreeBusy(
            CalendarInvitee::TYPE_FA_USER,
            '3',
            new DateTime('2026-05-18 00:00:00'),
            new DateTime('2026-05-18 23:59:59')
        );
        $this->assertSame([], $result);
    }

    public function testGetFreeBusyReturnsMappedSlots(): void
    {
        $this->db->method('fetchAll')->willReturn([
            ['start_date' => '2026-05-18 09:00:00', 'end_date' => '2026-05-18 10:00:00'],
            ['start_date' => '2026-05-18 14:00:00', 'end_date' => '2026-05-18 15:00:00'],
        ]);

        $result = $this->service->getFreeBusy(
            CalendarInvitee::TYPE_FA_USER,
            '3',
            new DateTime('2026-05-18 00:00:00'),
            new DateTime('2026-05-18 23:59:59')
        );

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('start', $result[0]);
        $this->assertArrayHasKey('end', $result[0]);
        $this->assertSame('2026-05-18 09:00:00', $result[0]['start']);
    }

    public function testGetFreeBusyFallsBackEndToStartWhenEndNull(): void
    {
        $this->db->method('fetchAll')->willReturn([
            ['start_date' => '2026-05-18 09:00:00', 'end_date' => null],
        ]);

        $result = $this->service->getFreeBusy(
            CalendarInvitee::TYPE_FA_USER,
            '1',
            new DateTime('2026-05-18 00:00:00'),
            new DateTime('2026-05-18 23:59:59')
        );

        $this->assertSame($result[0]['start'], $result[0]['end']);
    }
}
