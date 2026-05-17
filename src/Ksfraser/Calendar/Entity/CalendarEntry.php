<?php
/**
 * CalendarEntry Entity
 *
 * Core entity representing any calendar entry (event, task, activity, etc.)
 *
 * @package Ksfraser\Calendar\Entity
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class CalendarEntry
{
    public const TYPE_EVENT = 'event';
    public const TYPE_TASK = 'task';
    public const TYPE_CALL = 'call';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_BIRTHDAY = 'birthday';
    public const TYPE_ANNIVERSARY = 'anniversary';
    public const TYPE_RENEWAL = 'renewal';
    public const TYPE_TIMETRACKING = 'timetracking';
    public const TYPE_BLOCKED = 'blocked';
    public const TYPE_SHIFT = 'shift';

    // Shift types (for HRM integration)
    public const SHIFT_MORNING = 'Morning';
    public const SHIFT_AFTERNOON = 'Afternoon';
    public const SHIFT_NIGHT = 'Night';
    public const SHIFT_SWING = 'Swing';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_NO_SHOW = 'no_show';
    
    // Meeting specific statuses
    public const STATUS_MEETING_PLANNED = 'meeting_planned';
    public const STATUS_MEETING_HELD = 'meeting_held';
    public const STATUS_MEETING_NOT_HELD = 'meeting_not_held';
    public const STATUS_MEETING_RESCHEDULED = 'meeting_rescheduled';
    
    // Call specific statuses
    public const STATUS_CALL_PLANNED = 'call_planned';
    public const STATUS_CALL_HELD = 'call_held';
    public const STATUS_CALL_RNA = 'call_rna'; // Ring No Answer
    public const STATUS_CALL_VMAIL = 'call_vmail'; // Voicemail left
    public const STATUS_CALL_RNA_FOLLOWUP = 'call_rna_followup';
    public const STATUS_CALL_VMAIL_FOLLOWUP = 'call_vmail_followup';

    public const SOURCE_PM = 'pm';
    public const SOURCE_CRM = 'crm';
    public const SOURCE_HRM = 'hrm';
    public const SOURCE_CLIENT = 'client';
    public const SOURCE_ICAL = 'ical';
    public const SOURCE_USER = 'user';

    private ?int $id;
    private string $source;
    private string $sourceId;
    private string $sourceType;
    private string $title;
    private string $description;
    private ?DateTime $startDate;
    private ?DateTime $endDate;
    private ?string $allDay;
    private string $timezone;
    private string $location;
    private string $assignedTo;
    private ?string $userId;
    private ?string $customerId;
    private ?string $projectId;
    private ?string $taskId;
    private ?string $contactId;
    private string $status;
    private string $priority;
    private string $category;
    private bool $reminder;
    private ?int $reminderMinutes;
    private string $color;
    private bool $private;
    private ?string $recurrenceRule;
    private ?int $recurrenceId;
    private bool $inactive;
    private ?DateTime $createdAt;
    private ?DateTime $updatedAt;

    public function __construct(
        string $source,
        string $sourceId,
        string $sourceType,
        string $title,
        ?DateTime $startDate = null,
        ?string $id = null
    ) {
        $this->id = $id !== null ? (int) $id : null;
        $this->source = $source;
        $this->sourceId = $sourceId;
        $this->sourceType = $sourceType;
        $this->title = $title;
        $this->startDate = $startDate;
        $this->description = '';
        $this->endDate = null;
        $this->allDay = 'no';
        $this->timezone = date_default_timezone_get();
        $this->location = '';
        $this->assignedTo = '';
        $this->userId = null;
        $this->customerId = null;
        $this->projectId = null;
        $this->taskId = null;
        $this->contactId = null;
        $this->status = self::STATUS_PENDING;
        $this->priority = 'medium';
        $this->category = '';
        $this->reminder = false;
        $this->reminderMinutes = null;
        $this->color = '';
        $this->private = false;
        $this->recurrenceRule = null;
        $this->recurrenceId = null;
        $this->inactive = false;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStartDate(): ?DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?DateTime $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?DateTime $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getAllDay(): string
    {
        return $this->allDay;
    }

    public function setAllDay(string $allDay): self
    {
        $this->allDay = $allDay;
        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->allDay === 'yes';
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;
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

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }

    public function setProjectId(?string $projectId): self
    {
        $this->projectId = $projectId;
        return $this;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function setTaskId(?string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function getContactId(): ?string
    {
        return $this->contactId;
    }

    public function setContactId(?string $contactId): self
    {
        $this->contactId = $contactId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function hasReminder(): bool
    {
        return $this->reminder;
    }

    public function getReminderMinutes(): ?int
    {
        return $this->reminderMinutes;
    }

    public function setReminder(bool $reminder, ?int $minutes = 15): self
    {
        $this->reminder = $reminder;
        $this->reminderMinutes = $minutes;
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

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    public function getRecurrenceRule(): ?string
    {
        return $this->recurrenceRule;
    }

    public function setRecurrenceRule(?string $rrule): self
    {
        $this->recurrenceRule = $rrule;
        return $this;
    }

    public function getRecurrenceId(): ?int
    {
        return $this->recurrenceId;
    }

    public function setRecurrenceId(?int $recurrenceId): self
    {
        $this->recurrenceId = $recurrenceId;
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

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function getDuration(): ?int
    {
        if ($this->startDate === null || $this->endDate === null) {
            return null;
        }
        return (int) ($this->endDate->getTimestamp() - $this->startDate->getTimestamp());
    }

    public function isOverdue(): bool
    {
        if ($this->endDate === null) {
            return false;
        }
        return $this->endDate < new DateTime() && $this->status !== self::STATUS_COMPLETED;
    }

    public function isToday(): bool
    {
        if ($this->startDate === null) {
            return false;
        }
        return $this->startDate->format('Y-m-d') === date('Y-m-d');
    }

    public function isPast(): bool
    {
        if ($this->endDate === null) {
            return $this->startDate !== null && $this->startDate < new DateTime();
        }
        return $this->endDate < new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'title' => $this->title,
            'description' => $this->description,
            'start_date' => ($this->startDate !== null ? $this->startDate->format(DateTimeInterface::ATOM) : null),
            'end_date' => ($this->endDate !== null ? $this->endDate->format(DateTimeInterface::ATOM) : null),
            'all_day' => $this->allDay,
            'timezone' => $this->timezone,
            'location' => $this->location,
            'assigned_to' => $this->assignedTo,
            'user_id' => $this->userId,
            'customer_id' => $this->customerId,
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'contact_id' => $this->contactId,
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            'reminder' => $this->reminder,
            'reminder_minutes' => $this->reminderMinutes,
            'color' => $this->color,
            'private' => $this->private,
            'recurrence_rule' => $this->recurrenceRule,
            'recurrence_id' => $this->recurrenceId,
            'inactive' => $this->inactive,
            'created_at' => ($this->createdAt !== null ? $this->createdAt->format(DateTimeInterface::ATOM) : null),
            'updated_at' => ($this->updatedAt !== null ? $this->updatedAt->format(DateTimeInterface::ATOM) : null),
        ];
    }

    public static function fromArray(array $data): self
    {
        $entry = new self(
            $data['source'] ?? '',
            $data['source_id'] ?? '',
            $data['source_type'] ?? '',
            $data['title'] ?? '',
            ($data['start_date'] !== null) ? new DateTime($data['start_date']) : null,
            $data['id'] ?? null
        );

        $entry->setDescription($data['description'] ?? '');
        if (isset($data['end_date'])) {
            $entry->setEndDate(new DateTime($data['end_date']));
        }
        $entry->setAllDay($data['all_day'] ?? 'no');
        $entry->setTimezone($data['timezone'] ?? date_default_timezone_get());
        $entry->setLocation($data['location'] ?? '');
        $entry->setAssignedTo($data['assigned_to'] ?? '');
        $entry->setUserId($data['user_id'] ?? null);
        $entry->setCustomerId($data['customer_id'] ?? null);
        $entry->setProjectId($data['project_id'] ?? null);
        $entry->setTaskId($data['task_id'] ?? null);
        $entry->setContactId($data['contact_id'] ?? null);
        $entry->setStatus($data['status'] ?? 'pending');
        $entry->setPriority($data['priority'] ?? 'medium');
        $entry->setCategory($data['category'] ?? '');
        if (isset($data['reminder'])) {
            $entry->setReminder((bool) $data['reminder'], $data['reminder_minutes'] ?? 15);
        }
        $entry->setColor($data['color'] ?? '');
        $entry->setPrivate((bool) ($data['private'] ?? false));
        $entry->setRecurrenceRule($data['recurrence_rule'] ?? null);
        $entry->setRecurrenceId($data['recurrence_id'] ?? null);
        $entry->setInactive((bool) ($data['inactive'] ?? false));

        return $entry;
    }

    public static function fromPMTask(\Ksfraser\ProjectManagement\Entity\Task $task): self
    {
        $entry = new self(
            self::SOURCE_PM,
            $task->getTaskId(),
            self::TYPE_TASK,
            $task->getName(),
            $task->getStartDate()
        );

        $entry->setDescription($task->getDescription());
        $entry->setEndDate($task->getEndDate());
        $entry->setAssignedTo($task->getAssignedTo());
        $entry->setProjectId($task->getProjectId());
        $entry->setTaskId($task->getTaskId());
        $entry->setStatus($task->getStatus());
        $entry->setPriority($task->getPriority());
        $entry->setCategory('Project Tasks');

        return $entry;
    }

    public static function fromCRMActivity(array $activity): self
    {
        $entry = new self(
            self::SOURCE_CRM,
            (string) ($activity['id'] ?? ''),
            $activity['communication_type'] ?? 'activity',
            $activity['subject'] ?? $activity['communication_type'] ?? 'Activity',
            ($activity['created_at'] !== null) ? new DateTime($activity['created_at']) : null
        );

        $entry->setDescription($activity['message'] ?? '');
        $entry->setCustomerId($activity['debtor_no'] ?? null);
        $entry->setContactId($activity['contact_id'] ?? null);
        $entry->setStatus($activity['status'] ?? 'completed');
        $entry->setAssignedTo($activity['assigned_to'] ?? '');

        if ($activity['communication_type'] === 'meeting') {
            $entry->setSourceType(self::TYPE_MEETING);
        } elseif ($activity['communication_type'] === 'phone') {
            $entry->setSourceType(self::TYPE_CALL);
        }

        return $entry;
    }

    public static function fromRosterShift(\Ksfraser\Roster\Entity\Roster $roster): self
    {
        $entry = new self(
            self::SOURCE_HRM,
            (string) $roster->getId(),
            self::TYPE_SHIFT,
            $roster->getShift() . ' Shift',
            (($roster->getDate() !== null) && ($roster->getStartTime() !== null))
                ? new DateTime($roster->getDate() . ' ' . $roster->getStartTime())
                : null
        );

        if (($roster->getDate() !== null) && ($roster->getEndTime() !== null)) {
            $entry->setEndDate(new DateTime($roster->getDate() . ' ' . $roster->getEndTime()));
        }

        $entry->setDescription($roster->getNotes() ?? '');
        $entry->setAssignedTo((string) $roster->getEmployeeId());
        $entry->setStatus($roster->getStatus() ?? 'Scheduled');
        $entry->setCategory('Work Shift');

        // Set color based on shift type
        switch ($roster->getShift()) {
            case self::SHIFT_MORNING:
                $entry->setColor('#FF9800'); // Orange
                break;
            case self::SHIFT_AFTERNOON:
                $entry->setColor('#2196F3'); // Blue
                break;
            case self::SHIFT_NIGHT:
                $entry->setColor('#9C27B0'); // Purple
                break;
            case self::SHIFT_SWING:
                $entry->setColor('#F44336'); // Red
                break;
            default:
                $entry->setColor('#607D8B'); // Grey
        }

        return $entry;
    }
}