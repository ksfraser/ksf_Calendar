<?php
/**
 * CalendarEntryDeletedEvent
 *
 * @package ksfraser\Calendar\Event
 */

declare(strict_types=1);

namespace ksfraser\Calendar\Event;

use ksfraser\Calendar\Entity\CalendarEntry;
use Psr\EventDispatcher\StoppableEventInterface;

class CalendarEntryDeletedEvent implements StoppableEventInterface
{
    private $propagationStopped = false;
    private $entry;

    public function __construct(
        CalendarEntry $entry
    ) {
        $this->entry = $entry;
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
