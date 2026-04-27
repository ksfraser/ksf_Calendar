<?php
/**
 * CalendarEntry Events Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\Event
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Event;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarEntry;
use Ksfraser\Calendar\Event\CalendarEntryCreatedEvent;
use Ksfraser\Calendar\Event\CalendarEntryDeletedEvent;
use Ksfraser\Calendar\Event\CalendarEntryUpdatedEvent;
use PHPUnit\Framework\TestCase;

class CalendarEntryEventsTest extends TestCase
{
    private CalendarEntry $entry;

    protected function setUp(): void
    {
        $this->entry = new CalendarEntry(
            source: 'pm',
            sourceId: 'task-1',
            sourceType: 'task',
            title: 'Test Task',
            startDate: new DateTime('2024-01-01T09:00:00')
        );
    }

    public function testCreatedEventStoresEntry(): void
    {
        $event = new CalendarEntryCreatedEvent($this->entry);

        $this->assertSame($this->entry, $event->getEntry());
    }

    public function testCreatedEventImplementsStoppable(): void
    {
        $event = new CalendarEntryCreatedEvent($this->entry);

        $this->assertFalse($event->isPropagationStopped());
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testUpdatedEventStoresEntry(): void
    {
        $event = new CalendarEntryUpdatedEvent($this->entry);

        $this->assertSame($this->entry, $event->getEntry());
    }

    public function testUpdatedEventImplementsStoppable(): void
    {
        $event = new CalendarEntryUpdatedEvent($this->entry);

        $this->assertFalse($event->isPropagationStopped());
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }

    public function testDeletedEventStoresEntry(): void
    {
        $event = new CalendarEntryDeletedEvent($this->entry);

        $this->assertSame($this->entry, $event->getEntry());
    }

    public function testDeletedEventImplementsStoppable(): void
    {
        $event = new CalendarEntryDeletedEvent($this->entry);

        $this->assertFalse($event->isPropagationStopped());
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
}