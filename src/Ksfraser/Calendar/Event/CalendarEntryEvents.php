<?php
/**
 * CalendarEntryCreatedEvent
 *
 * @package Ksfraser\Calendar\Event
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Event;

use Ksfraser\Calendar\Entity\CalendarEntry;
use Psr\EventDispatcher\StoppableEventInterface;

class CalendarEntryCreatedEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private readonly CalendarEntry $entry
    ) {
    }

    public function getEntry(): CalendarEntry
    {
        return $this->entry;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

class CalendarEntryUpdatedEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private readonly CalendarEntry $entry
    ) {
    }

    public function getEntry(): CalendarEntry
    {
        return $this->entry;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

class CalendarEntryDeletedEvent implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function __construct(
        private readonly CalendarEntry $entry
    ) {
    }

    public function getEntry(): CalendarEntry
    {
        return $this->entry;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}