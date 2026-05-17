<?php
/**
 * CalendarService
 *
 * Unified calendar service - aggregates PM tasks, CRM activities, HRM, client dates
 *
 * @package Ksfraser\Calendar\Service
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Service;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarEntry;
use Ksfraser\Calendar\Entity\CalendarSource;
use Ksfraser\Calendar\Contract\DatabaseAdapterInterface;
use Ksfraser\Calendar\Contract\ProjectServiceInterface;
use Ksfraser\Calendar\Event\CalendarEntryCreatedEvent;
use Ksfraser\Calendar\Event\CalendarEntryUpdatedEvent;
use Ksfraser\Calendar\Event\CalendarEntryDeletedEvent;
use Ksfraser\Calendar\Exception\CalendarException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class CalendarService
{
    private const TABLE_ENTRIES = 'fa_cal_entries';
    private const TABLE_SOURCES = 'fa_cal_sources';

    private $db;
    private $events;
    private $logger;
    private $projectService;

    public function __construct(
        DatabaseAdapterInterface $db,
        EventDispatcherInterface $events,
        LoggerInterface $logger,
        ProjectServiceInterface $projectService = null
    ) {
        $this->db = $db;
        $this->events = $events;
        $this->logger = $logger;
        $this->projectService = $projectService;
    }

    public function createEntry(array $data): CalendarEntry
    {
        $this->logger->info('Creating calendar entry', ['title' => $data['title'] ?? '']);
        $this->validateEntryData($data);

        $entryId = $this->getNextEntryId();

        $entry = new CalendarEntry(
            $data['source'] ?? CalendarEntry::SOURCE_USER,
            $data['source_id'] ?? $entryId,
            $data['source_type'] ?? CalendarEntry::TYPE_EVENT,
            $data['title'],
            isset($data['start_date']) ? new DateTime($data['start_date']) : null,
            $entryId
        );

        if (isset($data['end_date'])) {
            $entry->setEndDate(new DateTime($data['end_date']));
        }
        if (isset($data['description'])) {
            $entry->setDescription($data['description']);
        }
        if (isset($data['assigned_to'])) {
            $entry->setAssignedTo($data['assigned_to']);
        }
        if (isset($data['user_id'])) {
            $entry->setUserId($data['user_id']);
        }
        if (isset($data['customer_id'])) {
            $entry->setCustomerId($data['customer_id']);
        }
        if (isset($data['project_id'])) {
            $entry->setProjectId($data['project_id']);
        }
        if (isset($data['task_id'])) {
            $entry->setTaskId($data['task_id']);
        }
        if (isset($data['status'])) {
            $entry->setStatus($data['status']);
        }
        if (isset($data['priority'])) {
            $entry->setPriority($data['priority']);
        }
        if (isset($data['location'])) {
            $entry->setLocation($data['location']);
        }
        if (isset($data['color'])) {
            $entry->setColor($data['color']);
        }
        if (isset($data['all_day']) && $data['all_day']) {
            $entry->setAllDay('yes');
        }
        if (isset($data['private'])) {
            $entry->setPrivate((bool) $data['private']);
        }

        $this->saveEntry($entry);
        $this->events->dispatch(new CalendarEntryCreatedEvent($entry));

        $this->logger->info('Calendar entry created', ['id' => $entryId]);
        return $entry;
    }

    public function getEntry(int $id): CalendarEntry
    {
        $sql = "SELECT * FROM " . self::TABLE_ENTRIES . " WHERE id = ?";
        $row = $this->db->fetchAssoc($sql, [(string) $id]);

        if (!$row) {
            throw new CalendarException("Calendar entry $id not found");
        }

        return CalendarEntry::fromArray($row);
    }

    public function updateEntry(int $id, array $data): CalendarEntry
    {
        $entry = $this->getEntry($id);

        if (isset($data['title'])) {
            $entry->setTitle($data['title']);
        }
        if (isset($data['description'])) {
            $entry->setDescription($data['description']);
        }
        if (isset($data['start_date'])) {
            $entry->setStartDate(new DateTime($data['start_date']));
        }
        if (array_key_exists('end_date', $data)) {
            $entry->setEndDate($data['end_date'] ? new DateTime($data['end_date']) : null);
        }
        if (isset($data['assigned_to'])) {
            $entry->setAssignedTo($data['assigned_to']);
        }
        if (isset($data['status'])) {
            $entry->setStatus($data['status']);
        }
        if (isset($data['priority'])) {
            $entry->setPriority($data['priority']);
        }
        if (isset($data['location'])) {
            $entry->setLocation($data['location']);
        }
        if (isset($data['color'])) {
            $entry->setColor($data['color']);
        }

        $this->saveEntry($entry);
        $this->events->dispatch(new CalendarEntryUpdatedEvent($entry));

        $this->logger->info('Calendar entry updated', ['id' => $id]);
        return $entry;
    }

    public function deleteEntry(int $id): void
    {
        $entry = $this->getEntry($id);

        $sql = "UPDATE " . self::TABLE_ENTRIES . " SET inactive = 1 WHERE id = ?";
        $this->db->executeUpdate($sql, [(string) $id]);

        $this->events->dispatch(new CalendarEntryDeletedEvent($entry));
        $this->logger->info('Calendar entry deleted', ['id' => $id]);
    }

    public function getEntriesForDateRange(
        DateTime $start,
        DateTime $end,
        array $filters = []
    ): array {
        $sql = "SELECT * FROM " . self::TABLE_ENTRIES . " WHERE
                inactive = 0
                AND (
                    (start_date BETWEEN ? AND ?)
                    OR (end_date BETWEEN ? AND ?)
                    OR (start_date <= ? AND end_date >= ?)
                )";

        $params = [
            $start->format('Y-m-d'), $end->format('Y-m-d'),
            $start->format('Y-m-d'), $end->format('Y-m-d'),
            $start->format('Y-m-d'), $end->format('Y-m-d'),
        ];

        if (!empty($filters['source'])) {
            $sql .= " AND source = ?";
            $params[] = $filters['source'];
        }

        if (!empty($filters['source_type'])) {
            if (is_array($filters['source_type'])) {
                $placeholders = implode(',', array_fill(0, count($filters['source_type']), '?'));
                $sql .= " AND source_type IN ($placeholders)";
                $params = array_merge($params, $filters['source_type']);
            } else {
                $sql .= " AND source_type = ?";
                $params[] = $filters['source_type'];
            }
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['project_id'])) {
            $sql .= " AND project_id = ?";
            $params[] = $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['include_private'])) {
            $sql .= " AND private = 0";
        }

        $sql .= " ORDER BY start_date ASC";

        $rows = $this->db->fetchAll($sql, $params);

        return array_map(function($row) {
            return CalendarEntry::fromArray($row);
        }, $rows);
    }

    public function getEntriesForUser(string $userId, DateTime $start, DateTime $end): array
    {
        return $this->getEntriesForDateRange($start, $end, [
            'assigned_to' => $userId,
            'include_private' => false,
        ]);
    }

    public function getEntriesForCustomer(string $customerId, DateTime $start, DateTime $end): array
    {
        return $this->getEntriesForDateRange($start, $end, [
            'customer_id' => $customerId,
        ]);
    }

    public function getEntriesForProject(string $projectId, DateTime $start, DateTime $end): array
    {
        return $this->getEntriesForDateRange($start, $end, [
            'project_id' => $projectId,
        ]);
    }

    public function getEntriesForTask(string $taskId): array
    {
        $sql = "SELECT * FROM " . self::TABLE_ENTRIES . "
                WHERE task_id = ? AND inactive = 0 ORDER BY start_date ASC";
        $rows = $this->db->fetchAll($sql, [$taskId]);

        return array_map(function($row) {
            return CalendarEntry::fromArray($row);
        }, $rows);
    }

    /**
     * Get unscheduled tasks for a user (tasks without start date)
     *
     * @param string $userId
     * @return array<int, CalendarEntry>
     */
    public function getUnscheduledTasksForUser(string $userId): array
    {
        if ($this->projectService === null) {
            return [];
        }

        $tasks = $this->projectService->getTasksByAssignee($userId);
        $unscheduled = [];

        foreach ($tasks as $task) {
            if ($task->getStartDate() === null && $task->getStatus() !== 'Completed') {
                $entry = CalendarEntry::fromPMTask($task);
                $entry->setUserId($userId);
                $unscheduled[] = $entry;
            }
        }

        usort($unscheduled, function ($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            $priorityA = $priorityOrder[$a->getPriority()] ?? 0;
            $priorityB = $priorityOrder[$b->getPriority()] ?? 0;

            if ($priorityA !== $priorityB) {
                return $priorityB - $priorityA;
            }

            $dateA = $a->getEndDate() ? $a->getEndDate()->getTimestamp() : PHP_INT_MAX;
            $dateB = $b->getEndDate() ? $b->getEndDate()->getTimestamp() : PHP_INT_MAX;

            return $dateA - $dateB;
        });

        return $unscheduled;
    }

    public function syncPMTasks(string $userId): int
    {
        if ($this->projectService === null) {
            $this->logger->warning('ProjectService not available, skipping PM sync');
            return 0;
        }

        $count = 0;
        $start = new DateTime('-1 year');
        $end = new DateTime('+1 year');

        try {
            $tasks = $this->projectService->getTasksByAssignee($userId);

            foreach ($tasks as $task) {
                $entry = CalendarEntry::fromPMTask($task);
                $entry->setUserId($userId);
                $entry->setAssignedTo($userId);

                $existing = $this->findEntryBySource(
                    CalendarEntry::SOURCE_PM,
                    $task->getTaskId()
                );

                if ($existing === null) {
                    $this->saveEntry($entry);
                    $count++;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to sync PM tasks', ['error' => $e->getMessage()]);
        }

        $this->logger->info('Synced PM tasks', ['count' => $count]);
        return $count;
    }

    public function syncCRMActivities(string $userId): int
    {
        $count = 0;

        $sql = "SELECT * FROM " . TB_PREF . "fa_crm_communications
                WHERE assigned_to = ? AND inactive = 0
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";

        $rows = $this->db->fetchAll($sql, [$userId]);

        foreach ($rows as $activity) {
            $entry = CalendarEntry::fromCRMActivity($activity);
            $entry->setUserId($userId);

            $existing = $this->findEntryBySource(
                CalendarEntry::SOURCE_CRM,
                (string) ($activity['id'] ?? '')
            );

            if ($existing === null) {
                $this->saveEntry($entry);
                $count++;
            }
        }

        $this->logger->info('Synced CRM activities', ['count' => $count]);
        return $count;
    }

    public function createSource(array $data): CalendarSource
    {
        $source = CalendarSource::fromArray($data);
        $this->saveSource($source);
        return $source;
    }

    public function getSourcesForUser(string $userId): array
    {
        $sql = "SELECT * FROM " . self::TABLE_SOURCES . "
                WHERE inactive = 0
                AND (assigned_to = ? OR visibility = 'public')
                AND enabled = 1
                ORDER BY name";
        $rows = $this->db->fetchAll($sql, [$userId]);

        return array_map(function($row) {
            return CalendarSource::fromArray($row);
        }, $rows);
    }

    public function getEntryCountByDate(DateTime $date): array
    {
        $sql = "SELECT source_type, COUNT(*) as cnt
                FROM " . self::TABLE_ENTRIES . "
                WHERE inactive = 0
                AND (
                    start_date = ? OR
                    (start_date <= ? AND end_date >= ?)
                )
                GROUP BY source_type";

        $dateStr = $date->format('Y-m-d');
        $rows = $this->db->fetchAll($sql, [$dateStr, $dateStr, $dateStr]);

        $result = ['total' => 0];
        foreach ($rows as $row) {
            $result[$row['source_type']] = (int) $row['cnt'];
            $result['total'] += (int) $row['cnt'];
        }

        return $result;
    }

    private function validateEntryData(array $data): void
    {
        if (empty($data['title'])) {
            throw new CalendarException('Title is required');
        }

        if (empty($data['start_date'])) {
            throw new CalendarException('Start date is required');
        }

        if (!empty($data['end_date'])) {
            $start = new DateTime($data['start_date']);
            $end = new DateTime($data['end_date']);
            if ($end < $start) {
                throw new CalendarException('End date cannot be before start date');
            }
        }
    }

    private function getNextEntryId(): string
    {
        $sql = "SELECT MAX(CAST(id AS UNSIGNED)) + 1 as next_id FROM " . self::TABLE_ENTRIES;
        $result = $this->db->fetchAssoc($sql);
        return (string) ($result['next_id'] ?? 1);
    }

    private function saveEntry(CalendarEntry $entry): void
    {
        $data = $entry->toArray();

        $exists = $this->db->fetchAssoc(
            "SELECT id FROM " . self::TABLE_ENTRIES . " WHERE id = ?",
            [(string) ($data['id'] ?? 0)]
        );

        if ($exists) {
            $sql = "UPDATE " . self::TABLE_ENTRIES . " SET
                    title = ?, description = ?, start_date = ?, end_date = ?,
                    all_day = ?, location = ?, assigned_to = ?, user_id = ?,
                    customer_id = ?, project_id = ?, task_id = ?, contact_id = ?,
                    status = ?, priority = ?, color = ?, private = ?,
                    reminder = ?, reminder_minutes = ?, updated_at = NOW()
                    WHERE id = ?";
        } else {
            $sql = "INSERT INTO " . self::TABLE_ENTRIES . " (
                    source, source_id, source_type, title, description,
                    start_date, end_date, all_day, timezone, location,
                    assigned_to, user_id, customer_id, project_id, task_id, contact_id,
                    status, priority, category, reminder, reminder_minutes, color, private,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        }

        $this->db->executeUpdate($sql, [
            $entry->getSource(),
            $entry->getSourceId(),
            $entry->getSourceType(),
            $entry->getTitle(),
            $entry->getDescription(),
            $entry->getStartDate() !== null ? $entry->getStartDate()->format('Y-m-d') : null,
            $entry->getEndDate() !== null ? $entry->getEndDate()->format('Y-m-d') : null,
            $entry->getAllDay(),
            $entry->getTimezone(),
            $entry->getLocation(),
            $entry->getAssignedTo(),
            $entry->getUserId(),
            $entry->getCustomerId(),
            $entry->getProjectId(),
            $entry->getTaskId(),
            $entry->getContactId(),
            $entry->getStatus(),
            $entry->getPriority(),
            $entry->getCategory(),
            $entry->hasReminder() ? 1 : 0,
            $entry->getReminderMinutes(),
            $entry->getColor(),
            $entry->isPrivate() ? 1 : 0,
            $entry->getId() !== null ? (string) $entry->getId() : null,
        ]);
    }

    private function saveSource(CalendarSource $source): void
    {
        $data = $source->toArray();
        $sql = "INSERT INTO " . self::TABLE_SOURCES . " (
                name, type, source, url, color, enabled,
                show_events, show_tasks, show_calls, show_meetings,
                show_client_dates, show_birthdays, show_anniversaries, show_renewals, show_time_tracking,
                visibility, assigned_to, user_id, apikey,
                inactive, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";

        $this->db->executeUpdate($sql, [
            $source->getName(),
            $source->getType(),
            $source->getSource(),
            $source->getUrl(),
            $source->getColor(),
            $source->isEnabled() ? 1 : 0,
            $source->shouldShowEvents() ? 1 : 0,
            $source->shouldShowTasks() ? 1 : 0,
            $source->shouldShowCalls() ? 1 : 0,
            $source->shouldShowMeetings() ? 1 : 0,
            $source->shouldShowClientDates() ? 1 : 0,
            $source->shouldShowBirthdays() ? 1 : 0,
            $source->shouldShowAnniversaries() ? 1 : 0,
            $source->shouldShowRenewals() ? 1 : 0,
            $source->shouldShowTimeTracking() ? 1 : 0,
            $source->getVisibility(),
            $source->getAssignedTo(),
            $source->getUserId(),
            $source->getApiKey(),
        ]);
    }

    private function findEntryBySource(string $source, string $sourceId): ?CalendarEntry
    {
        $sql = "SELECT * FROM " . self::TABLE_ENTRIES . "
                WHERE source = ? AND source_id = ? AND inactive = 0";
        $row = $this->db->fetchAssoc($sql, [$source, $sourceId]);

        return $row ? CalendarEntry::fromArray($row) : null;
    }
}