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
use PHPUnit\Framework\TestCase;

class CalendarEntryTest extends TestCase
{
    private CalendarEntry $entry;
    private DateTime $startDate;

    protected function setUp(): void
    {
        $this->startDate = new DateTime('2024-01-01 09:00:00');
        $this->entry = new CalendarEntry(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            startDate: $this->startDate
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
}