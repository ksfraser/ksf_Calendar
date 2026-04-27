<?php
/**
 * CalendarEntryDTO Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\DTO
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\DTO;

use DateTime;
use Ksfraser\Calendar\DTO\CalendarEntryDTO;
use PHPUnit\Framework\TestCase;

class CalendarEntryDTOTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $dto = new CalendarEntryDTO(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task'
        );

        $array = $dto->toArray();
        $this->assertNull($array['id']);
        $this->assertSame('pm', $array['source']);
        $this->assertSame('task-1', $array['source_id']);
    }

    public function testToArrayContainsAllFields(): void
    {
        $dto = new CalendarEntryDTO(
            id: 1,
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            description: 'Task description',
            startDate: '2024-01-01T09:00:00',
            endDate: '2024-01-01T17:00:00',
            allDay: 'yes',
            location: 'Office',
            assignedTo: 'user1',
            status: 'completed',
            priority: 'high'
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertSame(1, $array['id']);
        $this->assertSame('pm', $array['source']);
        $this->assertSame('Test Task', $array['title']);
        $this->assertSame('Task description', $array['description']);
        $this->assertSame('Office', $array['location']);
    }

    public function testToFullCalendarArray(): void
    {
        $dto = new CalendarEntryDTO(
            id: 1,
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            startDate: '2024-01-01T09:00:00',
            endDate: '2024-01-01T17:00:00',
            allDay: 'no',
            color: '#FF5722'
        );

        $array = $dto->toFullCalendarArray();

        $this->assertIsArray($array);
        $this->assertSame(1, $array['id']);
        $this->assertSame('Test Task', $array['title']);
        $this->assertSame('2024-01-01T09:00:00', $array['start']);
        $this->assertFalse($array['allDay']);
        $this->assertSame('#FF5722', $array['color']);
    }

    public function testToFullCalendarArrayDefaultColor(): void
    {
        $dto = new CalendarEntryDTO(
            source: 'crm',
            sourceId: 'activity-1',
            sourceType: 'meeting',
            title: 'Meeting',
            startDate: '2024-01-01T10:00:00',
            color: ''
        );

        $array = $dto->toFullCalendarArray();

        $this->assertSame('#4CAF50', $array['color']);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 1,
            'source' => 'pm',
            'source_id' => 'task-1',
            'source_type' => 'task',
            'title' => 'Test Task',
            'description' => 'Task description',
            'start_date' => '2024-01-01T09:00:00',
            'end_date' => '2024-01-01T17:00:00',
            'all_day' => 'no',
            'location' => 'Office',
            'assigned_to' => 'user1',
            'status' => 'in_progress',
            'priority' => 'high',
        ];

        $dto = CalendarEntryDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertSame(1, $array['id']);
        $this->assertSame('pm', $array['source']);
        $this->assertSame('Test Task', $array['title']);
    }

    public function testFromArrayDetectsTodayDate(): void
    {
        $today = date('Y-m-d') . 'T09:00:00';
        $data = [
            'source' => 'pm',
            'source_id' => 'task-1',
            'source_type' => 'task',
            'title' => 'Today Task',
            'start_date' => $today,
        ];

        $dto = CalendarEntryDTO::fromArray($data);
        $array = $dto->toArray();
        $this->assertTrue($array['today']);
    }

    public function testFromArrayDetectsOverdue(): void
    {
        $data = [
            'source' => 'pm',
            'source_id' => 'task-1',
            'source_type' => 'task',
            'title' => 'Past Task',
            'start_date' => '2020-01-01T09:00:00',
            'end_date' => '2020-01-01T17:00:00',
            'status' => 'pending',
        ];

        $dto = CalendarEntryDTO::fromArray($data);
        $array = $dto->toArray();
        $this->assertTrue($array['overdue']);
    }

    public function testFromEntity(): void
    {
        $entry = new \Ksfraser\Calendar\Entity\CalendarEntry(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            startDate: new DateTime('2024-01-01T09:00:00')
        );
        $entry->setDescription('Task description');
        $entry->setEndDate(new DateTime('2024-01-01T17:00:00'));
        $entry->setLocation('Office');
        $entry->setStatus('in_progress');
        $entry->setPriority('high');

        $dto = CalendarEntryDTO::fromEntity($entry);
        $array = $dto->toArray();

        $this->assertSame('pm', $array['source']);
        $this->assertSame('task-1', $array['source_id']);
        $this->assertSame('Test Task', $array['title']);
    }

    public function testFromEntityDetectsOverdue(): void
    {
        $entry = new \Ksfraser\Calendar\Entity\CalendarEntry(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Overdue Task',
            startDate: new DateTime('2020-01-01T09:00:00')
        );
        $entry->setEndDate(new DateTime('2020-01-01T17:00:00'));
        $entry->setStatus('pending');

        $dto = CalendarEntryDTO::fromEntity($entry);
        $array = $dto->toArray();
        $this->assertTrue($array['overdue']);
    }

    public function testExtendedPropsContainsDetails(): void
    {
        $dto = new CalendarEntryDTO(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            startDate: '2024-01-01T09:00:00',
            customerId: 'cust-1',
            projectId: 'proj-1'
        );

        $array = $dto->toFullCalendarArray();

        $this->assertArrayHasKey('extendedProps', $array);
        $this->assertSame('pm', $array['extendedProps']['source']);
        $this->assertSame('cust-1', $array['extendedProps']['customer_id']);
        $this->assertSame('proj-1', $array['extendedProps']['project_id']);
    }

    public function testEditableWhenNotPrivate(): void
    {
        $dto = new CalendarEntryDTO(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            startDate: '2024-01-01T09:00:00',
            private: false
        );

        $array = $dto->toFullCalendarArray();
        $this->assertTrue($array['editable']);
    }

    public function testNotEditableWhenPrivate(): void
    {
        $dto = new CalendarEntryDTO(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Private Task',
            startDate: '2024-01-01T09:00:00',
            private: true,
            editable: false
        );

        $array = $dto->toFullCalendarArray();
        $this->assertFalse($array['editable']);
    }
}