<?php
/**
 * CalendarEntryDTO
 *
 * @package Ksfraser\Calendar\DTO
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\DTO;

use DateTime;

class CalendarEntryDTO
{
    public function __construct(
        private readonly ?int $id = null,
        private readonly string $source = '',
        private readonly string $sourceId = '',
        private readonly string $sourceType = 'event',
        private readonly string $title = '',
        private readonly string $description = '',
        private readonly string $startDate = '',
        private readonly ?string $endDate = null,
        private readonly string $allDay = 'no',
        private readonly string $timezone = '',
        private readonly string $location = '',
        private readonly string $assignedTo = '',
        private readonly ?string $userId = null,
        private readonly ?string $customerId = null,
        private readonly ?string $projectId = null,
        private readonly ?string $taskId = null,
        private readonly ?string $contactId = null,
        private readonly string $status = 'pending',
        private readonly string $priority = 'medium',
        private readonly string $category = '',
        private readonly bool $reminder = false,
        private readonly ?int $reminderMinutes = null,
        private readonly string $color = '',
        private readonly bool $private = false,
        private readonly ?string $recurrenceRule = null,
        private readonly bool $editable = true,
        private readonly bool $overdue = false,
        private readonly bool $today = false,
        private readonly ?string $createdAt = null,
        private readonly ?string $updatedAt = null
    ) {
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
            id: isset($data['id']) ? (int) $data['id'] : null,
            source: $data['source'] ?? '',
            sourceId: $data['source_id'] ?? '',
            sourceType: $data['source_type'] ?? 'event',
            title: $data['title'] ?? '',
            description: $data['description'] ?? '',
            startDate: $data['start_date'] ?? '',
            endDate: $data['end_date'] ?? null,
            allDay: $data['all_day'] ?? 'no',
            timezone: $data['timezone'] ?? date_default_timezone_get(),
            location: $data['location'] ?? '',
            assignedTo: $data['assigned_to'] ?? '',
            userId: $data['user_id'] ?? null,
            customerId: $data['customer_id'] ?? null,
            projectId: $data['project_id'] ?? null,
            taskId: $data['task_id'] ?? null,
            contactId: $data['contact_id'] ?? null,
            status: $data['status'] ?? 'pending',
            priority: $data['priority'] ?? 'medium',
            category: $data['category'] ?? '',
            reminder: (bool) ($data['reminder'] ?? false),
            reminderMinutes: isset($data['reminder_minutes']) ? (int) $data['reminder_minutes'] : null,
            color: $data['color'] ?? '',
            private: (bool) ($data['private'] ?? false),
            recurrenceRule: $data['recurrence_rule'] ?? null,
            editable: !($data['private'] ?? false),
            overdue: $overdue,
            today: $today,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null
        );
    }

    public static function fromEntity(\Ksfraser\Calendar\Entity\CalendarEntry $entity): self
    {
        return new self(
            id: $entity->getId(),
            source: $entity->getSource(),
            sourceId: $entity->getSourceId(),
            sourceType: $entity->getSourceType(),
            title: $entity->getTitle(),
            description: $entity->getDescription(),
            startDate: ($entity->getStartDate() !== null ? $entity->getStartDate()->format('c') : ''),
            endDate: ($entity->getEndDate() !== null ? $entity->getEndDate()->format('c') : ''),
            allDay: $entity->getAllDay(),
            timezone: $entity->getTimezone(),
            location: $entity->getLocation(),
            assignedTo: $entity->getAssignedTo(),
            userId: $entity->getUserId(),
            customerId: $entity->getCustomerId(),
            projectId: $entity->getProjectId(),
            taskId: $entity->getTaskId(),
            contactId: $entity->getContactId(),
            status: $entity->getStatus(),
            priority: $entity->getPriority(),
            category: $entity->getCategory(),
            reminder: $entity->hasReminder(),
            reminderMinutes: $entity->getReminderMinutes(),
            color: $entity->getColor(),
            private: $entity->isPrivate(),
            recurrenceRule: $entity->getRecurrenceRule(),
            editable: !$entity->isPrivate(),
            overdue: $entity->isOverdue(),
            today: $entity->isToday(),
            createdAt: ($entity->getCreatedAt() !== null ? $entity->getCreatedAt()->format('c') : null),
            updatedAt: ($entity->getUpdatedAt() !== null ? $entity->getUpdatedAt()->format('c') : null)
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