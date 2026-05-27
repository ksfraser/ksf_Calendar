<?php
/**
 * CalendarEntry Entity Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\Entity
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Entity;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarEntry;
use Ksfraser\Calendar\Entity\CalendarInvitee;
use PHPUnit\Framework\TestCase;

class CalendarEntryTest extends TestCase
{
    private CalendarEntry $entry;
    private DateTime $startDate;

    protected function setUp(): void
    {
        $this->startDate = new DateTime('2024-01-01 09:00:00');
        $this->entry = new CalendarEntry(
            'pm',
            'task-1',
            'task',
            'Test Task',
            $this->startDate
        );
    }

    public function testConstructorSetsRequiredFields(): void
    {
        $this->assertSame('pm', $this->entry->getSource());
        $this->assertSame('task-1', $this->entry->getSourceId());
        $this->assertSame('task', $this->entry->getSourceType());
        $this->assertSame('Test Task', $this->entry->getTitle());
        $this->assertSame($this->startDate, $this->entry->getStartDate());
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $this->assertSame('', $this->entry->getDescription());
        $this->assertNull($this->entry->getEndDate());
        $this->assertSame('no', $this->entry->getAllDay());
        $this->assertFalse($this->entry->isAllDay());
        $this->assertSame('', $this->entry->getLocation());
        $this->assertSame('', $this->entry->getAssignedTo());
        $this->assertSame(CalendarEntry::STATUS_PENDING, $this->entry->getStatus());
        $this->assertSame('medium', $this->entry->getPriority());
        $this->assertFalse($this->entry->hasReminder());
        $this->assertNull($this->entry->getReminderMinutes());
    }

    public function testSetTitleReturnsSelf(): void
    {
        $result = $this->entry->setTitle('New Title');
        $this->assertSame($this->entry, $result);
        $this->assertSame('New Title', $this->entry->getTitle());
    }

    public function testSetDescriptionReturnsSelf(): void
    {
        $result = $this->entry->setDescription('Task description');
        $this->assertSame($this->entry, $result);
        $this->assertSame('Task description', $this->entry->getDescription());
    }

    public function testSetStartDateReturnsSelf(): void
    {
        $newDate = new DateTime('2024-02-01 10:00:00');
        $result = $this->entry->setStartDate($newDate);
        $this->assertSame($this->entry, $result);
        $this->assertSame($newDate, $this->entry->getStartDate());
    }

    public function testSetEndDateReturnsSelf(): void
    {
        $endDate = new DateTime('2024-01-01 17:00:00');
        $result = $this->entry->setEndDate($endDate);
        $this->assertSame($this->entry, $result);
        $this->assertSame($endDate, $this->entry->getEndDate());
    }

    public function testSetAllDay(): void
    {
        $this->entry->setAllDay('yes');
        $this->assertSame('yes', $this->entry->getAllDay());
        $this->assertTrue($this->entry->isAllDay());

        $this->entry->setAllDay('no');
        $this->assertSame('no', $this->entry->getAllDay());
        $this->assertFalse($this->entry->isAllDay());
    }

    public function testSetLocationReturnsSelf(): void
    {
        $result = $this->entry->setLocation('Conference Room A');
        $this->assertSame($this->entry, $result);
        $this->assertSame('Conference Room A', $this->entry->getLocation());
    }

    public function testSetAssignedToReturnsSelf(): void
    {
        $result = $this->entry->setAssignedTo('user1');
        $this->assertSame($this->entry, $result);
        $this->assertSame('user1', $this->entry->getAssignedTo());
    }

    public function testSetStatusReturnsSelf(): void
    {
        $result = $this->entry->setStatus(CalendarEntry::STATUS_COMPLETED);
        $this->assertSame($this->entry, $result);
        $this->assertSame(CalendarEntry::STATUS_COMPLETED, $this->entry->getStatus());
    }

    public function testSetPriorityReturnsSelf(): void
    {
        $result = $this->entry->setPriority('high');
        $this->assertSame($this->entry, $result);
        $this->assertSame('high', $this->entry->getPriority());
    }

    public function testSetReminderReturnsSelf(): void
    {
        $result = $this->entry->setReminder(true, 30);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->hasReminder());
        $this->assertSame(30, $this->entry->getReminderMinutes());
    }

    public function testSetColorReturnsSelf(): void
    {
        $result = $this->entry->setColor('#FF5722');
        $this->assertSame($this->entry, $result);
        $this->assertSame('#FF5722', $this->entry->getColor());
    }

    public function testSetPrivateReturnsSelf(): void
    {
        $result = $this->entry->setPrivate(true);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->isPrivate());
    }

    public function testSetRecurrenceRuleReturnsSelf(): void
    {
        $result = $this->entry->setRecurrenceRule('FREQ=DAILY');
        $this->assertSame($this->entry, $result);
        $this->assertSame('FREQ=DAILY', $this->entry->getRecurrenceRule());
    }

    public function testSetInactiveReturnsSelf(): void
    {
        $result = $this->entry->setInactive(true);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->isInactive());
    }

    public function testGetDurationWhenNoEndDate(): void
    {
        $duration = $this->entry->getDuration();
        $this->assertNull($duration);
    }

    public function testGetDurationWithEndDate(): void
    {
        $endDate = new DateTime('2024-01-01 10:00:00');
        $this->entry->setEndDate($endDate);

        $duration = $this->entry->getDuration();
        $this->assertSame(3600, $duration);
    }

    public function testGetDurationWithMultipleHours(): void
    {
        $startDate = new DateTime('2024-01-01 09:00:00');
        $endDate = new DateTime('2024-01-01 17:00:00');
        $this->entry->setStartDate($startDate);
        $this->entry->setEndDate($endDate);

        $duration = $this->entry->getDuration();
        $this->assertSame(28800, $duration);
    }

    public function testIsOverdueWhenNoEndDate(): void
    {
        $this->entry->setEndDate(null);
        $this->assertFalse($this->entry->isOverdue());
    }

    public function testIsOverdueWhenPastAndNotCompleted(): void
    {
        $pastDate = new DateTime('2020-01-01');
        $this->entry->setEndDate($pastDate);
        $this->assertTrue($this->entry->isOverdue());
    }

    public function testIsOverdueWhenPastButCompleted(): void
    {
        $pastDate = new DateTime('2020-01-01');
        $this->entry->setEndDate($pastDate);
        $this->entry->setStatus(CalendarEntry::STATUS_COMPLETED);
        $this->assertFalse($this->entry->isOverdue());
    }

    public function testIsTodayWhenMatchesToday(): void
    {
        $today = new DateTime();
        $this->entry->setStartDate($today);
        $this->assertTrue($this->entry->isToday());
    }

    public function testIsTodayWhenDoesNotMatch(): void
    {
        $otherDay = new DateTime('2020-01-01');
        $this->entry->setStartDate($otherDay);
        $this->assertFalse($this->entry->isToday());
    }

    public function testToArray(): void
    {
        $this->entry->setDescription('Test description');
        $this->entry->setLocation('Room A');
        $this->entry->setPriority('high');

        $array = $this->entry->toArray();

        $this->assertIsArray($array);
        $this->assertSame('pm', $array['source']);
        $this->assertSame('task-1', $array['source_id']);
        $this->assertSame('task', $array['source_type']);
        $this->assertSame('Test Task', $array['title']);
        $this->assertSame('Test description', $array['description']);
        $this->assertSame('Room A', $array['location']);
        $this->assertSame('high', $array['priority']);
    }

    public function testFromArray(): void
    {
        $data = [
            'source' => 'crm',
            'source_id' => 'activity-1',
            'source_type' => 'meeting',
            'title' => 'Client Meeting',
            'description' => 'Discuss project requirements',
            'start_date' => '2024-01-15T10:00:00',
            'end_date' => '2024-01-15T11:30:00',
            'all_day' => 'no',
            'location' => 'Office',
            'assigned_to' => 'user1',
            'status' => 'confirmed',
            'priority' => 'high',
        ];

        $entry = CalendarEntry::fromArray($data);

        $this->assertSame('crm', $entry->getSource());
        $this->assertSame('activity-1', $entry->getSourceId());
        $this->assertSame('meeting', $entry->getSourceType());
        $this->assertSame('Client Meeting', $entry->getTitle());
        $this->assertSame('Discuss project requirements', $entry->getDescription());
        $this->assertSame('Office', $entry->getLocation());
        $this->assertSame('user1', $entry->getAssignedTo());
        $this->assertSame(CalendarEntry::STATUS_CONFIRMED, $entry->getStatus());
        $this->assertSame('high', $entry->getPriority());
    }

    public function testSourceTypeConstants(): void
    {
        $this->assertSame('event', CalendarEntry::TYPE_EVENT);
        $this->assertSame('task', CalendarEntry::TYPE_TASK);
        $this->assertSame('call', CalendarEntry::TYPE_CALL);
        $this->assertSame('meeting', CalendarEntry::TYPE_MEETING);
        $this->assertSame('reminder', CalendarEntry::TYPE_REMINDER);
        $this->assertSame('birthday', CalendarEntry::TYPE_BIRTHDAY);
        $this->assertSame('anniversary', CalendarEntry::TYPE_ANNIVERSARY);
        $this->assertSame('renewal', CalendarEntry::TYPE_RENEWAL);
    }

    /**
     * v1.3.0 — Conference Call and Webinar type constants.
     *
     * @since 1.3.0
     */
    public function testV130TypeConstants(): void
    {
        $this->assertSame('conference_call', CalendarEntry::TYPE_CONFERENCE_CALL);
        $this->assertSame('webinar', CalendarEntry::TYPE_WEBINAR);
    }

    /**
     * v1.3.0 — Call direction constants.
     *
     * @since 1.3.0
     */
    public function testDirectionConstants(): void
    {
        $this->assertSame('inbound',  CalendarEntry::DIRECTION_INBOUND);
        $this->assertSame('outbound', CalendarEntry::DIRECTION_OUTBOUND);
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('pending', CalendarEntry::STATUS_PENDING);
        $this->assertSame('confirmed', CalendarEntry::STATUS_CONFIRMED);
        $this->assertSame('cancelled', CalendarEntry::STATUS_CANCELLED);
        $this->assertSame('completed', CalendarEntry::STATUS_COMPLETED);
        $this->assertSame('no_show', CalendarEntry::STATUS_NO_SHOW);
    }

    public function testMeetingStatusConstants(): void
    {
        $this->assertSame('meeting_planned', CalendarEntry::STATUS_MEETING_PLANNED);
        $this->assertSame('meeting_held', CalendarEntry::STATUS_MEETING_HELD);
        $this->assertSame('meeting_not_held', CalendarEntry::STATUS_MEETING_NOT_HELD);
        $this->assertSame('meeting_rescheduled', CalendarEntry::STATUS_MEETING_RESCHEDULED);
    }

    public function testCallOutcomeConstants(): void
    {
        $this->assertSame('call_planned', CalendarEntry::STATUS_CALL_PLANNED);
        $this->assertSame('call_held', CalendarEntry::STATUS_CALL_HELD);
        $this->assertSame('call_rna', CalendarEntry::STATUS_CALL_RNA);
        $this->assertSame('call_vmail', CalendarEntry::STATUS_CALL_VMAIL);
        $this->assertSame('call_rna_followup', CalendarEntry::STATUS_CALL_RNA_FOLLOWUP);
        $this->assertSame('call_vmail_followup', CalendarEntry::STATUS_CALL_VMAIL_FOLLOWUP);
    }

    /**
     * v1.3.0 — Additional call outcome status constants.
     *
     * @since 1.3.0
     */
    public function testV130CallStatusConstants(): void
    {
        $this->assertSame('call_not_completed', CalendarEntry::STATUS_CALL_NOT_COMPLETED);
        $this->assertSame('call_vmail_full',    CalendarEntry::STATUS_CALL_VMAIL_FULL);
        $this->assertSame('call_message_left',  CalendarEntry::STATUS_CALL_MESSAGE_LEFT);
    }

    public function testShiftTypeConstants(): void
    {
        $this->assertSame('shift', CalendarEntry::TYPE_SHIFT);
        $this->assertSame('Morning', CalendarEntry::SHIFT_MORNING);
        $this->assertSame('Afternoon', CalendarEntry::SHIFT_AFTERNOON);
        $this->assertSame('Night', CalendarEntry::SHIFT_NIGHT);
        $this->assertSame('Swing', CalendarEntry::SHIFT_SWING);
    }

    public function testSourceConstants(): void
    {
        $this->assertSame('pm', CalendarEntry::SOURCE_PM);
        $this->assertSame('crm', CalendarEntry::SOURCE_CRM);
        $this->assertSame('hrm', CalendarEntry::SOURCE_HRM);
        $this->assertSame('client', CalendarEntry::SOURCE_CLIENT);
        $this->assertSame('ical', CalendarEntry::SOURCE_ICAL);
    }

    // ---------------------------------------------------------------
    // 1.1.0 fields: onlineUrl, phoneNumber, sendInvites, invitees
    // ---------------------------------------------------------------

    public function testDefaultsForNewFields(): void
    {
        $this->assertNull($this->entry->getOnlineUrl());
        $this->assertNull($this->entry->getPhoneNumber());
        $this->assertFalse($this->entry->getSendInvites());
        $this->assertSame([], $this->entry->getInvitees());
    }

    public function testSetOnlineUrlReturnsSelf(): void
    {
        $result = $this->entry->setOnlineUrl('https://meet.example.com/room-1');
        $this->assertSame($this->entry, $result);
        $this->assertSame('https://meet.example.com/room-1', $this->entry->getOnlineUrl());
    }

    public function testSetOnlineUrlAcceptsNull(): void
    {
        $this->entry->setOnlineUrl('https://example.com');
        $this->entry->setOnlineUrl(null);
        $this->assertNull($this->entry->getOnlineUrl());
    }

    public function testSetPhoneNumberReturnsSelf(): void
    {
        $result = $this->entry->setPhoneNumber('+1-800-555-0199');
        $this->assertSame($this->entry, $result);
        $this->assertSame('+1-800-555-0199', $this->entry->getPhoneNumber());
    }

    public function testSetPhoneNumberAcceptsNull(): void
    {
        $this->entry->setPhoneNumber('555-1234');
        $this->entry->setPhoneNumber(null);
        $this->assertNull($this->entry->getPhoneNumber());
    }

    public function testSetSendInvitesReturnsSelf(): void
    {
        $result = $this->entry->setSendInvites(true);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->getSendInvites());
    }

    public function testAddInviteeAppendsToCollection(): void
    {
        $invitee = new CalendarInvitee(1, CalendarInvitee::TYPE_FA_USER, 'Alice', 'alice@example.com', '1');
        $this->entry->addInvitee($invitee);
        $this->assertCount(1, $this->entry->getInvitees());
        $this->assertSame($invitee, $this->entry->getInvitees()[0]);
    }

    public function testSetInviteesReplacesCollection(): void
    {
        $a = new CalendarInvitee(1, CalendarInvitee::TYPE_FA_USER, 'Alice', 'a@example.com', '1');
        $b = new CalendarInvitee(1, CalendarInvitee::TYPE_CRM_CONTACT, 'Bob', 'b@example.com', '2');
        $this->entry->setInvitees([$a, $b]);
        $this->assertCount(2, $this->entry->getInvitees());
    }

    public function testGetPersonInviteesExcludesResources(): void
    {
        $person   = new CalendarInvitee(1, CalendarInvitee::TYPE_FA_USER, 'Alice', 'a@example.com', '1');
        $resource = new CalendarInvitee(1, CalendarInvitee::TYPE_RESOURCE, 'Room A', '', '9');
        $this->entry->setInvitees([$person, $resource]);

        $people = $this->entry->getPersonInvitees();
        $this->assertCount(1, $people);
        $this->assertSame('Alice', $people[0]->getName());
    }

    public function testGetResourceBookingsExcludesPeople(): void
    {
        $person   = new CalendarInvitee(1, CalendarInvitee::TYPE_FA_USER, 'Alice', 'a@example.com', '1');
        $resource = new CalendarInvitee(1, CalendarInvitee::TYPE_RESOURCE, 'Room A', '', '9');
        $this->entry->setInvitees([$person, $resource]);

        $resources = $this->entry->getResourceBookings();
        $this->assertCount(1, $resources);
        $this->assertSame('Room A', $resources[0]->getName());
    }

    public function testToArrayIncludesNewFields(): void
    {
        $this->entry->setOnlineUrl('https://zoom.example.com/j/123');
        $this->entry->setPhoneNumber('+1-555-9876');
        $this->entry->setSendInvites(true);

        $arr = $this->entry->toArray();

        $this->assertSame('https://zoom.example.com/j/123', $arr['online_url']);
        $this->assertSame('+1-555-9876', $arr['phone_number']);
        $this->assertTrue($arr['send_invites']);
    }

    public function testFromArrayRestoresNewFields(): void
    {
        $data = [
            'source'       => 'user',
            'source_id'    => '1',
            'source_type'  => 'meeting',
            'title'        => 'Team Sync',
            'start_date'   => '2026-05-01 10:00:00',
            'online_url'   => 'https://meet.example.com/sync',
            'phone_number' => '+44-20-1234-5678',
            'send_invites' => 1,
        ];

        $entry = CalendarEntry::fromArray($data);

        $this->assertSame('https://meet.example.com/sync', $entry->getOnlineUrl());
        $this->assertSame('+44-20-1234-5678', $entry->getPhoneNumber());
        $this->assertTrue($entry->getSendInvites());
    }

    // ---------------------------------------------------------------
    // 1.3.0 fields: direction, meetingNumber, meetingPasscode
    // ---------------------------------------------------------------

    /**
     * @since 1.3.0
     */
    public function testDefaultsForV130Fields(): void
    {
        $this->assertNull($this->entry->getDirection());
        $this->assertNull($this->entry->getMeetingNumber());
        $this->assertNull($this->entry->getMeetingPasscode());
    }

    /**
     * @since 1.3.0
     */
    public function testSetDirectionReturnsSelf(): void
    {
        $result = $this->entry->setDirection(CalendarEntry::DIRECTION_OUTBOUND);
        $this->assertSame($this->entry, $result);
        $this->assertSame(CalendarEntry::DIRECTION_OUTBOUND, $this->entry->getDirection());
    }

    /**
     * @since 1.3.0
     */
    public function testSetDirectionAcceptsNull(): void
    {
        $this->entry->setDirection(CalendarEntry::DIRECTION_INBOUND);
        $this->entry->setDirection(null);
        $this->assertNull($this->entry->getDirection());
    }

    /**
     * @since 1.3.0
     */
    public function testSetMeetingNumberReturnsSelf(): void
    {
        $result = $this->entry->setMeetingNumber('123-456-789');
        $this->assertSame($this->entry, $result);
        $this->assertSame('123-456-789', $this->entry->getMeetingNumber());
    }

    /**
     * @since 1.3.0
     */
    public function testSetMeetingNumberAcceptsNull(): void
    {
        $this->entry->setMeetingNumber('999');
        $this->entry->setMeetingNumber(null);
        $this->assertNull($this->entry->getMeetingNumber());
    }

    /**
     * @since 1.3.0
     */
    public function testSetMeetingPasscodeReturnsSelf(): void
    {
        $result = $this->entry->setMeetingPasscode('secret99');
        $this->assertSame($this->entry, $result);
        $this->assertSame('secret99', $this->entry->getMeetingPasscode());
    }

    /**
     * @since 1.3.0
     */
    public function testSetMeetingPasscodeAcceptsNull(): void
    {
        $this->entry->setMeetingPasscode('abc');
        $this->entry->setMeetingPasscode(null);
        $this->assertNull($this->entry->getMeetingPasscode());
    }

    /**
     * toArray() must include the v1.3.0 call/conference fields.
     *
     * @since 1.3.0
     */
    public function testToArrayIncludesV130Fields(): void
    {
        $this->entry->setDirection(CalendarEntry::DIRECTION_INBOUND);
        $this->entry->setMeetingNumber('800-555-0100');
        $this->entry->setMeetingPasscode('pass1234');

        $arr = $this->entry->toArray();

        $this->assertSame(CalendarEntry::DIRECTION_INBOUND, $arr['direction']);
        $this->assertSame('800-555-0100', $arr['meeting_number']);
        $this->assertSame('pass1234',     $arr['meeting_passcode']);
    }

    /**
     * fromArray() round-trip must restore v1.3.0 call/conference fields.
     *
     * @since 1.3.0
     */
    public function testFromArrayRestoresV130Fields(): void
    {
        $data = [
            'source'           => 'user',
            'source_id'        => '5',
            'source_type'      => CalendarEntry::TYPE_CONFERENCE_CALL,
            'title'            => 'Weekly Standup',
            'start_date'       => '2026-06-01 09:00:00',
            'direction'        => CalendarEntry::DIRECTION_OUTBOUND,
            'meeting_number'   => '555-1234',
            'meeting_passcode' => 'daily99',
        ];

        $entry = CalendarEntry::fromArray($data);

        $this->assertSame(CalendarEntry::DIRECTION_OUTBOUND, $entry->getDirection());
        $this->assertSame('555-1234',  $entry->getMeetingNumber());
        $this->assertSame('daily99',   $entry->getMeetingPasscode());
    }

    // ---------------------------------------------------------------
    // 1.3.0 isOpenStatus()
    // ---------------------------------------------------------------

    /**
     * Statuses that are always closed regardless of type.
     *
     * @since 1.3.0
     */
    public function testIsOpenStatusAlwaysClosedStatuses(): void
    {
        foreach ([
            CalendarEntry::STATUS_COMPLETED,
            CalendarEntry::STATUS_CANCELLED,
            CalendarEntry::STATUS_NO_SHOW,
            CalendarEntry::STATUS_MEETING_HELD,
            CalendarEntry::STATUS_MEETING_NOT_HELD,
            CalendarEntry::STATUS_CALL_HELD,
        ] as $status) {
            $this->assertFalse(
                CalendarEntry::isOpenStatus(CalendarEntry::TYPE_EVENT, $status),
                "Expected closed for status '$status'"
            );
        }
    }

    /**
     * Statuses that are always open regardless of type.
     *
     * @since 1.3.0
     */
    public function testIsOpenStatusAlwaysOpenStatuses(): void
    {
        foreach ([
            CalendarEntry::STATUS_PENDING,
            CalendarEntry::STATUS_CONFIRMED,
            CalendarEntry::STATUS_MEETING_PLANNED,
            CalendarEntry::STATUS_MEETING_RESCHEDULED,
            CalendarEntry::STATUS_CALL_PLANNED,
            CalendarEntry::STATUS_CALL_RNA,
            CalendarEntry::STATUS_CALL_RNA_FOLLOWUP,
            CalendarEntry::STATUS_CALL_VMAIL_FOLLOWUP,
            CalendarEntry::STATUS_CALL_NOT_COMPLETED,
            CalendarEntry::STATUS_CALL_VMAIL_FULL,
            CalendarEntry::STATUS_CALL_MESSAGE_LEFT,
        ] as $status) {
            $this->assertTrue(
                CalendarEntry::isOpenStatus(CalendarEntry::TYPE_CALL, $status),
                "Expected open for status '$status'"
            );
        }
    }

    /**
     * call_vmail is contextually closed for TYPE_CALL and TYPE_CONFERENCE_CALL,
     * but open for other types (message delivered, no action for caller).
     *
     * @since 1.3.0
     */
    public function testIsOpenStatusCallVmailContextual(): void
    {
        $this->assertFalse(
            CalendarEntry::isOpenStatus(CalendarEntry::TYPE_CALL, CalendarEntry::STATUS_CALL_VMAIL),
            'call_vmail must be closed for TYPE_CALL'
        );
        $this->assertFalse(
            CalendarEntry::isOpenStatus(CalendarEntry::TYPE_CONFERENCE_CALL, CalendarEntry::STATUS_CALL_VMAIL),
            'call_vmail must be closed for TYPE_CONFERENCE_CALL'
        );
        $this->assertTrue(
            CalendarEntry::isOpenStatus(CalendarEntry::TYPE_EVENT, CalendarEntry::STATUS_CALL_VMAIL),
            'call_vmail should default to open for unrelated types'
        );
    }

    /**
     * Unknown/custom status values default to open.
     *
     * @since 1.3.0
     */
    public function testIsOpenStatusUnknownDefaultsToOpen(): void
    {
        $this->assertTrue(
            CalendarEntry::isOpenStatus(CalendarEntry::TYPE_TASK, 'custom_status_xyz')
        );
    }

    // ---------------------------------------------------------------
    // 1.4.0 fields: is_scheduled, parent_entry_id, guest_policy,
    //               is_billable, billable_rate, billable_currency,
    //               auto_invoice, sales_order_id
    // ---------------------------------------------------------------

    /**
     * Guest policy constants exist with expected values.
     *
     * @since 1.4.0
     */
    public function testGuestPolicyConstants(): void
    {
        $this->assertSame('open',           CalendarEntry::GUEST_POLICY_OPEN);
        $this->assertSame('invitees_only',  CalendarEntry::GUEST_POLICY_INVITEES_ONLY);
        $this->assertSame('owner_only',     CalendarEntry::GUEST_POLICY_OWNER_ONLY);
    }

    /**
     * v1.4.0 fields default to correct zero/null values.
     *
     * @since 1.4.0
     */
    public function testDefaultsForV140Fields(): void
    {
        $this->assertFalse($this->entry->isScheduled());
        $this->assertNull($this->entry->getParentEntryId());
        $this->assertSame(CalendarEntry::GUEST_POLICY_OPEN, $this->entry->getGuestPolicy());
        $this->assertFalse($this->entry->isBillable());
        $this->assertNull($this->entry->getBillableRate());
        $this->assertNull($this->entry->getBillableCurrency());
        $this->assertFalse($this->entry->isAutoInvoice());
        $this->assertNull($this->entry->getSalesOrderId());
    }

    /**
     * setIsScheduled() is fluent and stores the value.
     *
     * @since 1.4.0
     */
    public function testSetIsScheduledReturnsSelf(): void
    {
        $result = $this->entry->setIsScheduled(true);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->isScheduled());
    }

    /**
     * setParentEntryId() is fluent and accepts int or null.
     *
     * @since 1.4.0
     */
    public function testSetParentEntryIdReturnsSelf(): void
    {
        $result = $this->entry->setParentEntryId(42);
        $this->assertSame($this->entry, $result);
        $this->assertSame(42, $this->entry->getParentEntryId());
    }

    /**
     * @since 1.4.0
     */
    public function testSetParentEntryIdAcceptsNull(): void
    {
        $this->entry->setParentEntryId(7);
        $this->entry->setParentEntryId(null);
        $this->assertNull($this->entry->getParentEntryId());
    }

    /**
     * setGuestPolicy() is fluent and stores the value.
     *
     * @since 1.4.0
     */
    public function testSetGuestPolicyReturnsSelf(): void
    {
        $result = $this->entry->setGuestPolicy(CalendarEntry::GUEST_POLICY_INVITEES_ONLY);
        $this->assertSame($this->entry, $result);
        $this->assertSame(CalendarEntry::GUEST_POLICY_INVITEES_ONLY, $this->entry->getGuestPolicy());
    }

    /**
     * setIsBillable() is fluent and stores the value.
     *
     * @since 1.4.0
     */
    public function testSetIsBillableReturnsSelf(): void
    {
        $result = $this->entry->setIsBillable(true);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->isBillable());
    }

    /**
     * setBillableRate() is fluent and accepts float or null.
     *
     * @since 1.4.0
     */
    public function testSetBillableRateReturnsSelf(): void
    {
        $result = $this->entry->setBillableRate(125.50);
        $this->assertSame($this->entry, $result);
        $this->assertSame(125.50, $this->entry->getBillableRate());
    }

    /**
     * @since 1.4.0
     */
    public function testSetBillableRateAcceptsNull(): void
    {
        $this->entry->setBillableRate(100.0);
        $this->entry->setBillableRate(null);
        $this->assertNull($this->entry->getBillableRate());
    }

    /**
     * setBillableCurrency() is fluent and accepts string or null.
     *
     * @since 1.4.0
     */
    public function testSetBillableCurrencyReturnsSelf(): void
    {
        $result = $this->entry->setBillableCurrency('CAD');
        $this->assertSame($this->entry, $result);
        $this->assertSame('CAD', $this->entry->getBillableCurrency());
    }

    /**
     * @since 1.4.0
     */
    public function testSetBillableCurrencyAcceptsNull(): void
    {
        $this->entry->setBillableCurrency('USD');
        $this->entry->setBillableCurrency(null);
        $this->assertNull($this->entry->getBillableCurrency());
    }

    /**
     * setAutoInvoice() is fluent and stores the value.
     *
     * @since 1.4.0
     */
    public function testSetAutoInvoiceReturnsSelf(): void
    {
        $result = $this->entry->setAutoInvoice(true);
        $this->assertSame($this->entry, $result);
        $this->assertTrue($this->entry->isAutoInvoice());
    }

    /**
     * setSalesOrderId() is fluent and accepts string or null.
     *
     * @since 1.4.0
     */
    public function testSetSalesOrderIdReturnsSelf(): void
    {
        $result = $this->entry->setSalesOrderId('SO-2026-001');
        $this->assertSame($this->entry, $result);
        $this->assertSame('SO-2026-001', $this->entry->getSalesOrderId());
    }

    /**
     * @since 1.4.0
     */
    public function testSetSalesOrderIdAcceptsNull(): void
    {
        $this->entry->setSalesOrderId('SO-1');
        $this->entry->setSalesOrderId(null);
        $this->assertNull($this->entry->getSalesOrderId());
    }

    /**
     * toArray() must include all v1.4.0 fields.
     *
     * @since 1.4.0
     */
    public function testToArrayIncludesV140Fields(): void
    {
        $this->entry->setIsScheduled(true);
        $this->entry->setParentEntryId(10);
        $this->entry->setGuestPolicy(CalendarEntry::GUEST_POLICY_OWNER_ONLY);
        $this->entry->setIsBillable(true);
        $this->entry->setBillableRate(200.00);
        $this->entry->setBillableCurrency('USD');
        $this->entry->setAutoInvoice(true);
        $this->entry->setSalesOrderId('SO-99');

        $arr = $this->entry->toArray();

        $this->assertTrue($arr['is_scheduled']);
        $this->assertSame(10, $arr['parent_entry_id']);
        $this->assertSame(CalendarEntry::GUEST_POLICY_OWNER_ONLY, $arr['guest_policy']);
        $this->assertTrue($arr['is_billable']);
        $this->assertSame(200.00, $arr['billable_rate']);
        $this->assertSame('USD', $arr['billable_currency']);
        $this->assertTrue($arr['auto_invoice']);
        $this->assertSame('SO-99', $arr['sales_order_id']);
    }

    /**
     * fromArray() round-trip must restore all v1.4.0 fields.
     *
     * @since 1.4.0
     */
    public function testFromArrayRestoresV140Fields(): void
    {
        $data = [
            'source'            => 'user',
            'source_id'         => '20',
            'source_type'       => CalendarEntry::TYPE_MEETING,
            'title'             => 'Billable Strategy Session',
            'start_date'        => '2026-09-01 14:00:00',
            'is_scheduled'      => 1,
            'parent_entry_id'   => 5,
            'guest_policy'      => CalendarEntry::GUEST_POLICY_INVITEES_ONLY,
            'is_billable'       => 1,
            'billable_rate'     => 150.75,
            'billable_currency' => 'CAD',
            'auto_invoice'      => 1,
            'sales_order_id'    => 'SO-2026-042',
        ];

        $entry = CalendarEntry::fromArray($data);

        $this->assertTrue($entry->isScheduled());
        $this->assertSame(5, $entry->getParentEntryId());
        $this->assertSame(CalendarEntry::GUEST_POLICY_INVITEES_ONLY, $entry->getGuestPolicy());
        $this->assertTrue($entry->isBillable());
        $this->assertSame(150.75, $entry->getBillableRate());
        $this->assertSame('CAD', $entry->getBillableCurrency());
        $this->assertTrue($entry->isAutoInvoice());
        $this->assertSame('SO-2026-042', $entry->getSalesOrderId());
    }

    // ---------------------------------------------------------------
    // v1.6.0 — recurrenceEndDate
    // ---------------------------------------------------------------

    /**
     * recurrenceEndDate defaults to null.
     *
     * @since 1.6.0
     */
    public function testRecurrenceEndDateDefaultsToNull(): void
    {
        $entry = $this->makeEntry();
        $this->assertNull($entry->getRecurrenceEndDate());
    }

    /**
     * setRecurrenceEndDate() stores the DateTime and getRecurrenceEndDate()
     * returns it.
     *
     * @since 1.6.0
     */
    public function testSetRecurrenceEndDateStoresValue(): void
    {
        $date  = new \DateTime('2026-12-31 23:59:59');
        $entry = $this->makeEntry();

        $result = $entry->setRecurrenceEndDate($date);

        $this->assertSame($entry, $result); // fluent
        $this->assertSame($date, $entry->getRecurrenceEndDate());
    }

    /**
     * setRecurrenceEndDate(null) clears the value.
     *
     * @since 1.6.0
     */
    public function testSetRecurrenceEndDateAcceptsNull(): void
    {
        $entry = $this->makeEntry();
        $entry->setRecurrenceEndDate(new \DateTime('2026-12-31'));
        $entry->setRecurrenceEndDate(null);

        $this->assertNull($entry->getRecurrenceEndDate());
    }

    // ---------------------------------------------------------------
    // v1.6.0 — recurrenceCount
    // ---------------------------------------------------------------

    /**
     * recurrenceCount defaults to null.
     *
     * @since 1.6.0
     */
    public function testRecurrenceCountDefaultsToNull(): void
    {
        $entry = $this->makeEntry();
        $this->assertNull($entry->getRecurrenceCount());
    }

    /**
     * setRecurrenceCount() stores the integer and getRecurrenceCount()
     * returns it.
     *
     * @since 1.6.0
     */
    public function testSetRecurrenceCountStoresValue(): void
    {
        $entry  = $this->makeEntry();
        $result = $entry->setRecurrenceCount(10);

        $this->assertSame($entry, $result); // fluent
        $this->assertSame(10, $entry->getRecurrenceCount());
    }

    /**
     * setRecurrenceCount(null) clears the value.
     *
     * @since 1.6.0
     */
    public function testSetRecurrenceCountAcceptsNull(): void
    {
        $entry = $this->makeEntry();
        $entry->setRecurrenceCount(5);
        $entry->setRecurrenceCount(null);

        $this->assertNull($entry->getRecurrenceCount());
    }

    // ---------------------------------------------------------------
    // v1.6.0 — toArray() / fromArray() round-trip
    // ---------------------------------------------------------------

    /**
     * toArray() must include recurrence_end_date and recurrence_count keys.
     *
     * @since 1.6.0
     */
    public function testToArrayIncludesV160Fields(): void
    {
        $date  = new \DateTime('2026-12-31 23:59:59');
        $entry = $this->makeEntry();
        $entry->setRecurrenceEndDate($date);
        $entry->setRecurrenceCount(12);

        $arr = $entry->toArray();

        $this->assertArrayHasKey('recurrence_end_date', $arr);
        $this->assertArrayHasKey('recurrence_count',    $arr);
        $this->assertSame('2026-12-31 23:59:59', $arr['recurrence_end_date']);
        $this->assertSame(12, $arr['recurrence_count']);
    }

    /**
     * toArray() must export null for recurrence_end_date and recurrence_count
     * when they are not set.
     *
     * @since 1.6.0
     */
    public function testToArrayExportsNullWhenV160FieldsNotSet(): void
    {
        $arr = $this->makeEntry()->toArray();

        $this->assertArrayHasKey('recurrence_end_date', $arr);
        $this->assertArrayHasKey('recurrence_count',    $arr);
        $this->assertNull($arr['recurrence_end_date']);
        $this->assertNull($arr['recurrence_count']);
    }

    /**
     * fromArray() must restore recurrenceEndDate and recurrenceCount.
     *
     * @since 1.6.0
     */
    public function testFromArrayRestoresV160Fields(): void
    {
        $data = [
            'source'               => 'user',
            'source_id'            => '30',
            'source_type'          => CalendarEntry::TYPE_EVENT,
            'title'                => 'Weekly Standup',
            'start_date'           => '2026-07-01 09:00:00',
            'recurrence_end_date'  => '2026-12-31 09:00:00',
            'recurrence_count'     => 26,
        ];

        $entry = CalendarEntry::fromArray($data);

        $this->assertInstanceOf(\DateTime::class, $entry->getRecurrenceEndDate());
        $this->assertSame('2026-12-31 09:00:00', $entry->getRecurrenceEndDate()->format('Y-m-d H:i:s'));
        $this->assertSame(26, $entry->getRecurrenceCount());
    }

    /**
     * fromArray() must set null for recurrence_end_date when absent.
     *
     * @since 1.6.0
     */
    public function testFromArraySetsNullForMissingV160Fields(): void
    {
        $data = [
            'source'      => 'user',
            'source_id'   => '31',
            'source_type' => CalendarEntry::TYPE_EVENT,
            'title'       => 'One-off',
            'start_date'  => '2026-08-01 10:00:00',
        ];

        $entry = CalendarEntry::fromArray($data);

        $this->assertNull($entry->getRecurrenceEndDate());
        $this->assertNull($entry->getRecurrenceCount());
    }

    private function makeEntry(): CalendarEntry
    {
        return new CalendarEntry(
            'user',
            '5',
            CalendarEntry::TYPE_EVENT,
            'Test Entry',
            new \DateTime('2026-06-01 10:00:00')
        );
    }
}