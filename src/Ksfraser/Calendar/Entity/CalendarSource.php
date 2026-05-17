<?php
/**
 * CalendarSource Entity
 *
 * Represents a calendar source (Google, iCal, PM, CRM, HRM, Client Dates, etc.)
 *
 * @package Ksfraser\Calendar\Entity
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;

class CalendarSource
{
    public const TYPE_INTERNAL = 'internal';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_ICAL = 'ical';
    public const TYPE_GOOGLE = 'google';
    public const TYPE_CALDAV = 'caldav';
    public const TYPE_WEBHOOK = 'webhook';

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_SHARED = 'shared';
    public const VISIBILITY_PUBLIC = 'public';

    private ?int $id;
    private string $name;
    private string $type;
    private string $source;
    private string $url;
    private string $color;
    private bool $enabled;
    private bool $showEvents;
    private bool $showTasks;
    private bool $showCalls;
    private bool $showMeetings;
    private bool $showClientDates;
    private bool $showBirthdays;
    private bool $showAnniversaries;
    private bool $showRenewals;
    private bool $showTimeTracking;
    private string $visibility;
    private string $assignedTo;
    private ?string $userId;
    private ?string $apikey;
    private ?string $lastSync;
    private bool $inactive;
    private ?DateTime $createdAt;

    public function __construct(
        string $name,
        string $type,
        string $source,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->source = $source;
        $this->url = '';
        $this->color = $this->getDefaultColor();
        $this->enabled = true;
        $this->showEvents = true;
        $this->showTasks = true;
        $this->showCalls = true;
        $this->showMeetings = true;
        $this->showClientDates = false;
        $this->showBirthdays = false;
        $this->showAnniversaries = false;
        $this->showRenewals = false;
        $this->showTimeTracking = true;
        $this->visibility = self::VISIBILITY_PRIVATE;
        $this->assignedTo = '';
        $this->userId = null;
        $this->apikey = null;
        $this->lastSync = null;
        $this->inactive = false;
        $this->createdAt = new DateTime();
    }

    private function getDefaultColor(): string
    {
        switch ($this->source) {
            case CalendarEntry::SOURCE_PM:
                return '#2196F3';
            case CalendarEntry::SOURCE_CRM:
                return '#4CAF50';
            case CalendarEntry::SOURCE_HRM:
                return '#FF9800';
            case CalendarEntry::SOURCE_CLIENT:
                return '#9C27B0';
            case CalendarEntry::SOURCE_ICAL:
                return '#607D8B';
            default:
                return '#9E9E9E';
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function shouldShowEvents(): bool
    {
        return $this->showEvents;
    }

    public function shouldShowTasks(): bool
    {
        return $this->showTasks;
    }

    public function shouldShowCalls(): bool
    {
        return $this->showCalls;
    }

    public function shouldShowMeetings(): bool
    {
        return $this->showMeetings;
    }

    public function shouldShowClientDates(): bool
    {
        return $this->showClientDates;
    }

    public function shouldShowBirthdays(): bool
    {
        return $this->showBirthdays;
    }

    public function shouldShowAnniversaries(): bool
    {
        return $this->showAnniversaries;
    }

    public function shouldShowRenewals(): bool
    {
        return $this->showRenewals;
    }

    public function shouldShowTimeTracking(): bool
    {
        return $this->showTimeTracking;
    }

    public function setFilters(array $filters): self
    {
        $this->showEvents = $filters['events'] ?? $this->showEvents;
        $this->showTasks = $filters['tasks'] ?? $this->showTasks;
        $this->showCalls = $filters['calls'] ?? $this->showCalls;
        $this->showMeetings = $filters['meetings'] ?? $this->showMeetings;
        $this->showClientDates = $filters['client_dates'] ?? $this->showClientDates;
        $this->showBirthdays = $filters['birthdays'] ?? $this->showBirthdays;
        $this->showAnniversaries = $filters['anniversaries'] ?? $this->showAnniversaries;
        $this->showRenewals = $filters['renewals'] ?? $this->showRenewals;
        $this->showTimeTracking = $filters['time_tracking'] ?? $this->showTimeTracking;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function getAssignedTo(): string
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(string $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apikey;
    }

    public function setApiKey(?string $apikey): self
    {
        $this->apikey = $apikey;
        return $this;
    }

    public function getLastSync(): ?string
    {
        return $this->lastSync;
    }

    public function setLastSync(?string $lastSync): self
    {
        $this->lastSync = $lastSync;
        return $this;
    }

    public function isInactive(): bool
    {
        return $this->inactive;
    }

    public function setInactive(bool $inactive): self
    {
        $this->inactive = $inactive;
        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getEnabledSourceTypes(): array
    {
        $types = [];
        if ($this->showEvents) {
            $types[] = CalendarEntry::TYPE_EVENT;
        }
        if ($this->showTasks) {
            $types[] = CalendarEntry::TYPE_TASK;
        }
        if ($this->showCalls) {
            $types[] = CalendarEntry::TYPE_CALL;
        }
        if ($this->showMeetings) {
            $types[] = CalendarEntry::TYPE_MEETING;
        }
        if ($this->showClientDates) {
            $types[] = CalendarEntry::TYPE_BIRTHDAY;
            $types[] = CalendarEntry::TYPE_ANNIVERSARY;
            $types[] = CalendarEntry::TYPE_RENEWAL;
        }
        if ($this->showBirthdays) {
            $types[] = CalendarEntry::TYPE_BIRTHDAY;
        }
        if ($this->showAnniversaries) {
            $types[] = CalendarEntry::TYPE_ANNIVERSARY;
        }
        if ($this->showRenewals) {
            $types[] = CalendarEntry::TYPE_RENEWAL;
        }
        if ($this->showTimeTracking) {
            $types[] = CalendarEntry::TYPE_TIMETRACKING;
        }
        return $types;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'source' => $this->source,
            'url' => $this->url,
            'color' => $this->color,
            'enabled' => $this->enabled,
            'show_events' => $this->showEvents,
            'show_tasks' => $this->showTasks,
            'show_calls' => $this->showCalls,
            'show_meetings' => $this->showMeetings,
            'show_client_dates' => $this->showClientDates,
            'show_birthdays' => $this->showBirthdays,
            'show_anniversaries' => $this->showAnniversaries,
            'show_renewals' => $this->showRenewals,
            'show_time_tracking' => $this->showTimeTracking,
            'visibility' => $this->visibility,
            'assigned_to' => $this->assignedTo,
            'user_id' => $this->userId,
            'last_sync' => $this->lastSync,
            'inactive' => $this->inactive,
        ];
    }

    public static function fromArray(array $data): self
    {
        $source = new self(
            name: $data['name'] ?? '',
            type: $data['type'] ?? self::TYPE_INTERNAL,
            source: $data['source'] ?? '',
            id: $data['id'] ?? null
        );

        $source->setUrl($data['url'] ?? '');
        $source->setColor($data['color'] ?? $source->getColor());
        $source->setEnabled((bool) ($data['enabled'] ?? true));
        $source->setVisibility($data['visibility'] ?? self::VISIBILITY_PRIVATE);
        $source->setAssignedTo($data['assigned_to'] ?? '');
        $source->setUserId($data['user_id'] ?? null);
        $source->setApiKey($data['apikey'] ?? null);
        $source->setLastSync($data['last_sync'] ?? null);
        $source->setInactive((bool) ($data['inactive'] ?? false));
        $source->setFilters([
            'events' => (bool) ($data['show_events'] ?? true),
            'tasks' => (bool) ($data['show_tasks'] ?? true),
            'calls' => (bool) ($data['show_calls'] ?? true),
            'meetings' => (bool) ($data['show_meetings'] ?? true),
            'client_dates' => (bool) ($data['show_client_dates'] ?? false),
            'birthdays' => (bool) ($data['show_birthdays'] ?? false),
            'anniversaries' => (bool) ($data['show_anniversaries'] ?? false),
            'renewals' => (bool) ($data['show_renewals'] ?? false),
            'time_tracking' => (bool) ($data['show_time_tracking'] ?? true),
        ]);

        return $source;
    }

    public static function createPMCalendar(string $name = 'Project Management'): self
    {
        $source = new self($name, self::TYPE_INTERNAL, CalendarEntry::SOURCE_PM);
        $source->setFilters([
            'events' => false,
            'tasks' => true,
            'calls' => false,
            'meetings' => false,
            'client_dates' => false,
            'time_tracking' => true,
        ]);
        return $source;
    }

    public static function createCRMTasksCalendar(string $name = 'CRM Tasks'): self
    {
        $source = new self($name, self::TYPE_INTERNAL, CalendarEntry::SOURCE_CRM);
        $source->setFilters([
            'events' => false,
            'tasks' => true,
            'calls' => true,
            'meetings' => true,
            'client_dates' => false,
        ]);
        return $source;
    }

    public static function createClientDatesCalendar(string $name = 'Client Dates'): self
    {
        $source = new self($name, self::TYPE_INTERNAL, CalendarEntry::SOURCE_CLIENT);
        $source->setFilters([
            'events' => false,
            'tasks' => false,
            'calls' => false,
            'meetings' => false,
            'client_dates' => true,
            'birthdays' => true,
            'anniversaries' => true,
            'renewals' => true,
        ]);
        return $source;
    }

    public static function createHRMCalendar(string $name = 'HRM / Time Tracking'): self
    {
        $source = new self($name, self::TYPE_INTERNAL, CalendarEntry::SOURCE_HRM);
        $source->setFilters([
            'events' => false,
            'tasks' => false,
            'time_tracking' => true,
        ]);
        return $source;
    }
}