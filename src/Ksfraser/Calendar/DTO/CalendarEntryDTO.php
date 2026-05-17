<?php
/**
 * CalendarEntryDTO *
 * @package Ksfraser\Calendar\DTO
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\DTO;

use DateTime;

class CalendarEntryDTO
{
    private $id;
    private $source;
    private $sourceId;
    private $sourceType;
    private $title;
    private $description;
    private $startDate;
    private $endDate;
    private $allDay;
    private $timezone;
    private $location;
    private $assignedTo;
    private $userId;
    private $customerId;
    private $projectId;
    private $taskId;
    private $contactId;
    private $status;
    private $priority;
    private $category;
    private $reminder;
    private $reminderMinutes;
    private $color;
    private $private;
    private $recurrenceRule;
    private $editable;
    private $overdue;
    private $today;
    private $createdAt;
    private $updatedAt;

    public function __construct(
        $id = null,
        $source = '',
        $sourceId = '',
        $sourceType = 'event',
        $title = '',
        $description = '',
        $startDate = '',
        $endDate = null,
        $allDay = 'no',
        $timezone = '',
        $location = '',
        $assignedTo = '',
        $userId = null,
        $customerId = null,
        $projectId = null,
        $taskId = null,
        $contactId = null,
        $status = 'pending',
        $priority = 'medium',
        $category = '',
        $reminder = false,
        $reminderMinutes = null,
        $color = '',
        $private = false,
        $recurrenceRule = null,
        $editable = true,
        $overdue = false,
        $today = false,
        $createdAt = null,
        $updatedAt = null
    ) {
        $this->id = $id;
        $this->source = $source;
        $this->sourceId = $sourceId;
        $this->sourceType = $sourceType;
        $this->title = $title;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->allDay = $allDay;
        $this->timezone = $timezone;
        $this->location = $location;
        $this->assignedTo = $assignedTo;
        $this->userId = $userId;
        $this->customerId = $customerId;
        $this->projectId = $projectId;
        $this->taskId = $taskId;
        $this->contactId = $contactId;
        $this->status = $status;
        $this->priority = $priority;
        $this->category = $category;
        $this->reminder = $reminder;
        $this->reminderMinutes = $reminderMinutes;
        $this->color = $color;
        $this->private = $private;
        $this->recurrenceRule = $recurrenceRule;
        $this->editable = $editable;
        $this->overdue = $overdue;
        $this->today = $today;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function toFullCalendarArray(): array
    {
        $endStr = $this->endDate ?: $this->startDate;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'start' => $this->startDate,
            'end' => $endStr,
            'allDay' => $this->allDay === 'yes',
            'color' => $this->color ?: $this->getDefaultColor(),
            'textColor' => '#ffffff',
            'source' => $this->source,
            'sourceType' => $this->sourceType,
            'editable' => $this->editable,
            'extendedProps' => [
                'source' => $this->source,
                'source_id' => $this->sourceId,
                'source_type' => $this->sourceType,
                'description' => $this->description,
                'location' => $this->location,
                'assigned_to' => $this->assignedTo,
                'customer_id' => $this->customerId,
                'project_id' => $this->projectId,
                'task_id' => $this->taskId,
                'status' => $this->status,
                'priority' => $this->priority,
                'overdue' => $this->overdue,
            ],
        ];
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
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
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
            'editable' => $this->editable,
            'overdue' => $this->overdue,
            'today' => $this->today,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $overdue = false;
        $today = false;

        if (!empty($data['start_date'])) {
            $today = date('Y-m-d') === substr($data['start_date'], 0, 10);
        }

        if (!empty($data['end_date'])) {
            $end = new DateTime($data['end_date']);
            $overdue = $end < new DateTime() && ($data['status'] ?? '') !== 'completed';
        }

        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            $data['source'] ?? '',
            $data['source_id'] ?? '',
            $data['source_type'] ?? 'event',
            $data['title'] ?? '',
            $data['description'] ?? '',
            $data['start_date'] ?? '',
            $data['end_date'] ?? null,
            $data['all_day'] ?? 'no',
            $data['timezone'] ?? date_default_timezone_get(),
            $data['location'] ?? '',
            $data['assigned_to'] ?? '',
            $data['user_id'] ?? null,
            $data['customer_id'] ?? null,
            $data['project_id'] ?? null,
            $data['task_id'] ?? null,
            $data['contact_id'] ?? null,
            $data['status'] ?? 'pending',
            $data['priority'] ?? 'medium',
            $data['category'] ?? '',
            (bool) ($data['reminder'] ?? false),
            isset($data['reminder_minutes']) ? (int) $data['reminder_minutes'] : null,
            $data['color'] ?? '',
            (bool) ($data['private'] ?? false),
            $data['recurrence_rule'] ?? null,
            !($data['private'] ?? false),
            $overdue,
            $today,
            $data['created_at'] ?? null,
            $data['updated_at'] ?? null
        );
    }

    public static function fromEntity(\Ksfraser\Calendar\Entity\CalendarEntry $entity): self
    {
        return new self(
            $entity->getId(),
            $entity->getSource(),
            $entity->getSourceId(),
            $entity->getSourceType(),
            $entity->getTitle(),
            $entity->getDescription(),
            ($entity->getStartDate() !== null ? $entity->getStartDate()->format('c') : ''),
            ($entity->getEndDate() !== null ? $entity->getEndDate()->format('c') : ''),
            $entity->getAllDay(),
            $entity->getTimezone(),
            $entity->getLocation(),
            $entity->getAssignedTo(),
            $entity->getUserId(),
            $entity->getCustomerId(),
            $entity->getProjectId(),
            $entity->getTaskId(),
            $entity->getContactId(),
            $entity->getStatus(),
            $entity->getPriority(),
            $entity->getCategory(),
            $entity->hasReminder(),
            $entity->getReminderMinutes(),
            $entity->getColor(),
            $entity->isPrivate(),
            $entity->getRecurrenceRule(),
            !$entity->isPrivate(),
            $entity->isOverdue(),
            $entity->isToday(),
            ($entity->getCreatedAt() !== null ? $entity->getCreatedAt()->format('c') : null),
            ($entity->getUpdatedAt() !== null ? $entity->getUpdatedAt()->format('c') : null)
        );
    }

    private function getDefaultColor(): string
    {
        switch ($this->source) {
            case 'pm':
                return '#2196F3';
            case 'crm':
                return '#4CAF50';
            case 'hrm':
                return '#FF9800';
            case 'client':
                return '#9C27B0';
            case 'ical':
                return '#607D8B';
            default:
                return '#9E9E9E';
        }
    }
}
