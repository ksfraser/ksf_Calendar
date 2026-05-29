<?php
/**
 * CalendarEntryDTOTest
 *
 * Verifies that CalendarEntryDTO correctly maps CalendarEntry fields to the
 * FullCalendar-ready array format (toFullCalendarArray) and to the plain
 * serialisation format (toArray / fromArray).
 *
 * Covers v1.3.0 additions:
 *   - direction, meeting_number, meeting_passcode in extendedProps
 *   - online_url and phone_number already present (regression guard)
 *
 * @package Ksfraser\Calendar\Tests\Unit\DTO
 * @since   1.3.0
 *
 * @UML     class CalendarEntryDTO
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\DTO;

use PHPUnit\Framework\TestCase;
use Ksfraser\Calendar\DTO\CalendarEntryDTO;
use Ksfraser\Calendar\Entity\CalendarEntry;

/**
 * @covers \Ksfraser\Calendar\DTO\CalendarEntryDTO
 */
class CalendarEntryDTOTest extends TestCase
{
    // ---------------------------------------------------------------
    // toFullCalendarArray — regression guard for 1.1.0 fields
    // ---------------------------------------------------------------

    /**
     * online_url and phone_number must appear in extendedProps.
     *
     * @since 1.1.0
     */
    public function testFullCalendarArrayContainsOnlineUrlAndPhoneNumber(): void
    {
        $dto = $this->makeDtoWithEntity([
            'online_url'   => 'https://meet.example.com/test',
            'phone_number' => '+1-800-555-0199',
        ]);

        $arr   = $dto->toFullCalendarArray();
        $props = $arr['extendedProps'];

        $this->assertArrayHasKey('online_url',   $props);
        $this->assertArrayHasKey('phone_number', $props);
        $this->assertSame('https://meet.example.com/test', $props['online_url']);
        $this->assertSame('+1-800-555-0199', $props['phone_number']);
    }

    // ---------------------------------------------------------------
    // toFullCalendarArray — v1.3.0 new fields
    // ---------------------------------------------------------------

    /**
     * direction must appear in extendedProps.
     *
     * @since 1.3.0
     */
    public function testFullCalendarArrayContainsDirection(): void
    {
        $dto  = $this->makeDtoWithEntity(['direction' => CalendarEntry::DIRECTION_OUTBOUND]);
        $arr  = $dto->toFullCalendarArray();

        $this->assertArrayHasKey('direction', $arr['extendedProps']);
        $this->assertSame(CalendarEntry::DIRECTION_OUTBOUND, $arr['extendedProps']['direction']);
    }

    /**
     * meeting_number must appear in extendedProps.
     *
     * @since 1.3.0
     */
    public function testFullCalendarArrayContainsMeetingNumber(): void
    {
        $dto = $this->makeDtoWithEntity(['meeting_number' => '800-555-CONF']);
        $arr = $dto->toFullCalendarArray();

        $this->assertArrayHasKey('meeting_number', $arr['extendedProps']);
        $this->assertSame('800-555-CONF', $arr['extendedProps']['meeting_number']);
    }

    /**
     * meeting_passcode must appear in extendedProps.
     *
     * @since 1.3.0
     */
    public function testFullCalendarArrayContainsMeetingPasscode(): void
    {
        $dto = $this->makeDtoWithEntity(['meeting_passcode' => 'secret99']);
        $arr = $dto->toFullCalendarArray();

        $this->assertArrayHasKey('meeting_passcode', $arr['extendedProps']);
        $this->assertSame('secret99', $arr['extendedProps']['meeting_passcode']);
    }

    /**
     * When all three v1.3.0 fields are null the keys must still be present (null values).
     *
     * @since 1.3.0
     */
    public function testFullCalendarArrayV130FieldsNullWhenNotSet(): void
    {
        $dto  = $this->makeDtoWithEntity([]);
        $arr  = $dto->toFullCalendarArray();
        $props = $arr['extendedProps'];

        $this->assertArrayHasKey('direction',        $props);
        $this->assertArrayHasKey('meeting_number',   $props);
        $this->assertArrayHasKey('meeting_passcode', $props);
        $this->assertNull($props['direction']);
        $this->assertNull($props['meeting_number']);
        $this->assertNull($props['meeting_passcode']);
    }

    // ---------------------------------------------------------------
    // fromEntity — v1.3.0 fields propagated
    // ---------------------------------------------------------------

    /**
     * fromEntity() must copy direction/meeting_number/meeting_passcode
     * from the CalendarEntry into the DTO.
     *
     * @since 1.3.0
     */
    public function testFromEntityCopiesV130Fields(): void
    {
        $entry = new CalendarEntry('user', '1', CalendarEntry::TYPE_CONFERENCE_CALL, 'Bridge Call');
        $entry->setDirection(CalendarEntry::DIRECTION_INBOUND);
        $entry->setMeetingNumber('123-456-789');
        $entry->setMeetingPasscode('bridge99');

        $dto   = CalendarEntryDTO::fromEntity($entry);
        $props = $dto->toFullCalendarArray()['extendedProps'];

        $this->assertSame(CalendarEntry::DIRECTION_INBOUND, $props['direction']);
        $this->assertSame('123-456-789', $props['meeting_number']);
        $this->assertSame('bridge99',    $props['meeting_passcode']);
    }

    // ---------------------------------------------------------------
    // fromArray / toArray round-trip — v1.3.0 fields
    // ---------------------------------------------------------------

    /**
     * fromArray() must read direction/meeting_number/meeting_passcode and
     * toArray() must write them back unchanged.
     *
     * @since 1.3.0
     */
    public function testFromArrayToArrayRoundTripV130Fields(): void
    {
        $data = [
            'source_type'      => CalendarEntry::TYPE_CONFERENCE_CALL,
            'title'            => 'Weekly Standup',
            'start_date'       => '2026-06-01 09:00:00',
            'direction'        => CalendarEntry::DIRECTION_OUTBOUND,
            'meeting_number'   => '555-MEET',
            'meeting_passcode' => 'daily',
        ];

        $dto = CalendarEntryDTO::fromArray($data);
        $arr = $dto->toArray();

        $this->assertSame(CalendarEntry::DIRECTION_OUTBOUND, $arr['direction']);
        $this->assertSame('555-MEET', $arr['meeting_number']);
        $this->assertSame('daily',    $arr['meeting_passcode']);
    }

    // ---------------------------------------------------------------
    // toFullCalendarArray — v1.4.0 new fields in extendedProps
    // ---------------------------------------------------------------

    /**
     * All eight v1.4.0 fields must appear in extendedProps with correct values.
     *
     * @since 1.4.0
     */
    public function testFullCalendarArrayContainsV140Fields(): void
    {
        $dto = $this->makeDtoWithEntity([
            'is_scheduled'      => 1,
            'parent_entry_id'   => 5,
            'guest_policy'      => CalendarEntry::GUEST_POLICY_INVITEES_ONLY,
            'is_billable'       => 1,
            'billable_rate'     => 150.00,
            'billable_currency' => 'CAD',
            'auto_invoice'      => 1,
            'sales_order_id'    => 'SO-42',
        ]);

        $props = $dto->toFullCalendarArray()['extendedProps'];

        $this->assertArrayHasKey('is_scheduled',      $props);
        $this->assertArrayHasKey('parent_entry_id',   $props);
        $this->assertArrayHasKey('guest_policy',      $props);
        $this->assertArrayHasKey('is_billable',       $props);
        $this->assertArrayHasKey('billable_rate',     $props);
        $this->assertArrayHasKey('billable_currency', $props);
        $this->assertArrayHasKey('auto_invoice',      $props);
        $this->assertArrayHasKey('sales_order_id',    $props);

        $this->assertTrue($props['is_scheduled']);
        $this->assertSame(5,    $props['parent_entry_id']);
        $this->assertSame(CalendarEntry::GUEST_POLICY_INVITEES_ONLY, $props['guest_policy']);
        $this->assertTrue($props['is_billable']);
        $this->assertSame(150.00, $props['billable_rate']);
        $this->assertSame('CAD',  $props['billable_currency']);
        $this->assertTrue($props['auto_invoice']);
        $this->assertSame('SO-42', $props['sales_order_id']);
    }

    /**
     * When v1.4.0 fields are absent/null, extendedProps keys must still be present.
     *
     * @since 1.4.0
     */
    public function testFullCalendarArrayV140FieldsNullWhenNotSet(): void
    {
        $dto   = $this->makeDtoWithEntity([]);
        $props = $dto->toFullCalendarArray()['extendedProps'];

        $this->assertArrayHasKey('is_scheduled',      $props);
        $this->assertArrayHasKey('parent_entry_id',   $props);
        $this->assertArrayHasKey('guest_policy',      $props);
        $this->assertArrayHasKey('is_billable',       $props);
        $this->assertArrayHasKey('billable_rate',     $props);
        $this->assertArrayHasKey('billable_currency', $props);
        $this->assertArrayHasKey('auto_invoice',      $props);
        $this->assertArrayHasKey('sales_order_id',    $props);
    }

    // ---------------------------------------------------------------
    // fromEntity — v1.4.0 fields propagated
    // ---------------------------------------------------------------

    /**
     * fromEntity() must copy all eight v1.4.0 fields from CalendarEntry into the DTO.
     *
     * @since 1.4.0
     */
    public function testFromEntityCopiesV140Fields(): void
    {
        $entry = new CalendarEntry('user', '1', CalendarEntry::TYPE_MEETING, 'Billable Session');
        $entry->setIsScheduled(true);
        $entry->setParentEntryId(3);
        $entry->setGuestPolicy(CalendarEntry::GUEST_POLICY_OWNER_ONLY);
        $entry->setIsBillable(true);
        $entry->setBillableRate(200.00);
        $entry->setBillableCurrency('USD');
        $entry->setAutoInvoice(true);
        $entry->setSalesOrderId('SO-99');

        $dto   = CalendarEntryDTO::fromEntity($entry);
        $arr   = $dto->toArray();

        $this->assertTrue($arr['is_scheduled']);
        $this->assertSame(3,    $arr['parent_entry_id']);
        $this->assertSame(CalendarEntry::GUEST_POLICY_OWNER_ONLY, $arr['guest_policy']);
        $this->assertTrue($arr['is_billable']);
        $this->assertSame(200.00, $arr['billable_rate']);
        $this->assertSame('USD',  $arr['billable_currency']);
        $this->assertTrue($arr['auto_invoice']);
        $this->assertSame('SO-99', $arr['sales_order_id']);
    }

    // ---------------------------------------------------------------
    // fromArray / toArray round-trip — v1.4.0 fields
    // ---------------------------------------------------------------

    /**
     * fromArray() must read all v1.4.0 fields and toArray() must write them back.
     *
     * @since 1.4.0
     */
    public function testFromArrayToArrayRoundTripV140Fields(): void
    {
        $data = [
            'source_type'       => CalendarEntry::TYPE_MEETING,
            'title'             => 'Invoice Session',
            'start_date'        => '2026-09-10 09:00:00',
            'is_scheduled'      => 1,
            'parent_entry_id'   => 10,
            'guest_policy'      => CalendarEntry::GUEST_POLICY_INVITEES_ONLY,
            'is_billable'       => 1,
            'billable_rate'     => 99.99,
            'billable_currency' => 'EUR',
            'auto_invoice'      => 1,
            'sales_order_id'    => 'SO-2026-007',
        ];

        $dto = CalendarEntryDTO::fromArray($data);
        $arr = $dto->toArray();

        $this->assertTrue($arr['is_scheduled']);
        $this->assertSame(10,   $arr['parent_entry_id']);
        $this->assertSame(CalendarEntry::GUEST_POLICY_INVITEES_ONLY, $arr['guest_policy']);
        $this->assertTrue($arr['is_billable']);
        $this->assertSame(99.99,         $arr['billable_rate']);
        $this->assertSame('EUR',         $arr['billable_currency']);
        $this->assertTrue($arr['auto_invoice']);
        $this->assertSame('SO-2026-007', $arr['sales_order_id']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Build a CalendarEntryDTO via fromArray(), injecting the supplied overrides
     * into a base data array.  Used to avoid repeating construction boilerplate.
     *
     * @param array $overrides  Fields to merge into the base data array.
     * @return CalendarEntryDTO
     */
    private function makeDtoWithEntity(array $overrides): CalendarEntryDTO
    {
        $base = [
            'source'      => 'user',
            'source_id'   => '1',
            'source_type' => CalendarEntry::TYPE_CONFERENCE_CALL,
            'title'       => 'Test',
            'start_date'  => '2026-06-01 10:00:00',
        ];

        return CalendarEntryDTO::fromArray(array_merge($base, $overrides));
    }
}
