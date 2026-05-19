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
use Ksfraser\Calendar\Entity\CalendarInvitee;
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
    private const TABLE_ENTRIES  = 'fa_cal_entries';
    private const TABLE_SOURCES  = 'fa_cal_sources';
    private const TABLE_INVITEES = 'fa_cal_invitees';

    /**
     * Default entry duration in minutes used when only one boundary is provided.
     *
     * When start_date is set but end_date is absent, end_date is calculated as
     * start_date + DEFAULT_DURATION_MINUTES.  When end_date is set but start_date
     * is absent, start_date is calculated as end_date - DEFAULT_DURATION_MINUTES.
     * All-day entries use 1 day instead of this value.
     *
     * @var int
     */
    public const DEFAULT_DURATION_MINUTES = 15;

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

        if (array_key_exists('end_date', $data)) {
            $entry->setEndDate($data['end_date'] ? new DateTime($data['end_date']) : null);
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
        if (isset($data['all_day'])) {
            $entry->setAllDay($data['all_day'] === 'yes' ? 'yes' : 'no');
        }
        if (isset($data['private'])) {
            $entry->setPrivate((bool) $data['private']);
        }
        if (isset($data['online_url'])) {
            $entry->setOnlineUrl($data['online_url']);
        }
        if (isset($data['phone_number'])) {
            $entry->setPhoneNumber($data['phone_number']);
        }
        if (isset($data['send_invites'])) {
            $entry->setSendInvites((bool) $data['send_invites']);
        }

        $this->applyDefaultDuration($entry);

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

        $entry = CalendarEntry::fromArray($row);
        $entry->setInvitees($this->loadInvitees($id));

        return $entry;
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
        if (isset($data['all_day'])) {
            $entry->setAllDay($data['all_day'] === 'yes' ? 'yes' : 'no');
        }
        if (array_key_exists('online_url', $data)) {
            $entry->setOnlineUrl($data['online_url']);
        }
        if (array_key_exists('phone_number', $data)) {
            $entry->setPhoneNumber($data['phone_number']);
        }
        if (isset($data['send_invites'])) {
            $entry->setSendInvites((bool) $data['send_invites']);
        }

        $this->applyDefaultDuration($entry);

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

        if (!empty($filters['viewable_by'])) {
            $userId = (int) $filters['viewable_by'];

            // Visibility subquery — resolves invitees via FA person registry.
            //
            // fa_cal_invitees.contact_id stores 0_crm_contacts.id (INT).
            // The person registry links every "hat" (user, crm_contact, etc.)
            // to a canonical crm_persons row via person_id.
            //
            // To find entries visible to $userId:
            //   assigned_to = $userId  (direct ownership)
            //   OR the entry has an invitee whose crm_persons row also has
            //   a 'user' hat whose entity_id = $userId (numeric 0_users.id).
            //
            // entity_id is VARCHAR in 0_crm_contacts, so compare as string.
            //
            // This subquery is inoperative (matches 0 rows for the JOIN path)
            // until the user has been provisioned in crm_contacts by
            // ksf_FA_RBAC.  assigned_to is still respected in that case.
            //
            // @see fa_person_registry_categories.sql — 'user' crm_category
            // @see TODO-AMB-010 Users-to-Contacts provisioning
            $sql .= " AND (assigned_to = ?"
                  . " OR id IN ("
                  .     "SELECT entry_id FROM " . self::TABLE_INVITEES . " i"
                  .     " JOIN crm_contacts ic ON ic.id = i.contact_id"
                  .     " JOIN crm_contacts uc ON uc.person_id = ic.person_id"
                  .                            " AND uc.type = 'user'"
                  .                            " AND uc.entity_id = ?"
                  .     " WHERE i.inactive = 0"
                  . "))";

            $params[] = $userId;
            $params[] = (string) $userId;
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

    /**
     * Fill in the missing date boundary using the configured default duration.
     *
     * Rules:
     *  - start set, end missing  → end  = start + DEFAULT_DURATION_MINUTES (or +1 day for all-day)
     *  - end set, start missing  → start = end   - DEFAULT_DURATION_MINUTES (or -1 day for all-day)
     *  - both set or both missing → no change
     *
     * Called by createEntry() and updateEntry() before saveEntry().
     *
     * @param CalendarEntry $entry  Entry to mutate in place
     * @return void
     *
     * @since 1.2.0
     * @see CalendarService::DEFAULT_DURATION_MINUTES
     */
    private function applyDefaultDuration(CalendarEntry $entry): void
    {
        $start  = $entry->getStartDate();
        $end    = $entry->getEndDate();
        $allDay = $entry->isAllDay();

        if ($start !== null && $end === null) {
            $computed = clone $start;
            if ($allDay) {
                $computed->modify('+1 day');
            } else {
                $computed->modify('+' . self::DEFAULT_DURATION_MINUTES . ' minutes');
            }
            $entry->setEndDate($computed);
            return;
        }

        if ($end !== null && $start === null) {
            $computed = clone $end;
            if ($allDay) {
                $computed->modify('-1 day');
            } else {
                $computed->modify('-' . self::DEFAULT_DURATION_MINUTES . ' minutes');
            }
            $entry->setStartDate($computed);
        }
    }

    private function saveEntry(CalendarEntry $entry): void
    {
        $data = $entry->toArray();

        $exists = $this->db->fetchAssoc(
            "SELECT id FROM " . self::TABLE_ENTRIES . " WHERE id = ?",
            [(string) ($data['id'] ?? 0)]
        );

        $startDateStr = $entry->getStartDate() !== null ? $entry->getStartDate()->format('Y-m-d H:i:s') : null;
        $endDateStr   = $entry->getEndDate()   !== null ? $entry->getEndDate()->format('Y-m-d H:i:s')   : null;

        if ($exists) {
            // UPDATE: 21 SET columns + 1 WHERE id = ? = 22 params total.
            $sql = "UPDATE " . self::TABLE_ENTRIES . " SET
                    title = ?, description = ?, start_date = ?, end_date = ?,
                    all_day = ?, location = ?, online_url = ?, phone_number = ?,
                    send_invites = ?, assigned_to = ?, user_id = ?,
                    customer_id = ?, project_id = ?, task_id = ?, contact_id = ?,
                    status = ?, priority = ?, color = ?, private = ?,
                    reminder = ?, reminder_minutes = ?, updated_at = NOW()
                    WHERE id = ?";

            $params = [
                $entry->getTitle(),
                $entry->getDescription(),
                $startDateStr,
                $endDateStr,
                $entry->getAllDay(),
                $entry->getLocation(),
                $entry->getOnlineUrl(),
                $entry->getPhoneNumber(),
                $entry->getSendInvites() ? 1 : 0,
                $entry->getAssignedTo(),
                $entry->getUserId(),
                $entry->getCustomerId(),
                $entry->getProjectId(),
                $entry->getTaskId(),
                $entry->getContactId(),
                $entry->getStatus(),
                $entry->getPriority(),
                $entry->getColor(),
                $entry->isPrivate() ? 1 : 0,
                $entry->hasReminder() ? 1 : 0,
                $entry->getReminderMinutes(),
                $entry->getId() !== null ? (string) $entry->getId() : null,
            ];
        } else {
            // INSERT: 26 column placeholders (created_at/updated_at use NOW()). 26 params.
            $sql = "INSERT INTO " . self::TABLE_ENTRIES . " (
                    source, source_id, source_type, title, description,
                    start_date, end_date, all_day, timezone, location,
                    online_url, phone_number, send_invites,
                    assigned_to, user_id, customer_id, project_id, task_id, contact_id,
                    status, priority, category, reminder, reminder_minutes, color, private,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $params = [
                $entry->getSource(),
                $entry->getSourceId(),
                $entry->getSourceType(),
                $entry->getTitle(),
                $entry->getDescription(),
                $startDateStr,
                $endDateStr,
                $entry->getAllDay(),
                $entry->getTimezone(),
                $entry->getLocation(),
                $entry->getOnlineUrl(),
                $entry->getPhoneNumber(),
                $entry->getSendInvites() ? 1 : 0,
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
            ];
        }

        $this->db->executeUpdate($sql, $params);
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

    // ---------------------------------------------------------------
    // Invitee methods
    // ---------------------------------------------------------------

    /**
     * Add an invitee (person or resource) to a calendar entry.
     *
     * If contact_type = 'resource' and the resource is available (no
     * conflicting accepted booking), rsvp_status is auto-set to 'accepted'.
     *
     * @param int   $entryId
     * @param array $data  Keys: contact_type, contact_id, name, email, phone,
     *                           is_organizer, rsvp_status (optional)
     * @return CalendarInvitee
     * @throws CalendarException
     * @since 1.1.0
     */
    public function addInvitee(int $entryId, array $data): CalendarInvitee
    {
        // Ensure entry exists.
        $this->getEntry($entryId);

        $invitee = new CalendarInvitee(
            $entryId,
            (string) ($data['contact_type'] ?? CalendarInvitee::TYPE_AD_HOC),
            (string) ($data['name']         ?? ''),
            (string) ($data['email']        ?? ''),
            isset($data['contact_id']) ? (string) $data['contact_id'] : null
        );

        if (isset($data['phone'])) {
            $invitee->setPhone((string) $data['phone']);
        }
        if (isset($data['is_organizer'])) {
            $invitee->setIsOrganizer((bool) $data['is_organizer']);
        }

        // Resources auto-accept when available.
        if ($invitee->isResource()) {
            $status = $this->isResourceAvailable(
                (string) ($data['contact_id'] ?? ''),
                $entryId
            ) ? CalendarInvitee::RSVP_ACCEPTED : CalendarInvitee::RSVP_DECLINED;
            $invitee->setRsvpStatus($status);
        } elseif (isset($data['rsvp_status'])) {
            $invitee->setRsvpStatus((string) $data['rsvp_status']);
        }

        $invitee->setInvitedAt(new \DateTime());

        $this->saveInvitee($invitee);
        return $invitee;
    }

    /**
     * Update the RSVP status for an existing invitee.
     *
     * @param int    $inviteeId
     * @param string $rsvpStatus  One of CalendarInvitee::RSVP_* constants
     * @return CalendarInvitee
     * @throws CalendarException
     * @since 1.1.0
     */
    public function updateRsvp(int $inviteeId, string $rsvpStatus): CalendarInvitee
    {
        $row = $this->db->fetchAssoc(
            "SELECT * FROM " . self::TABLE_INVITEES . " WHERE id = ? AND inactive = 0",
            [(string) $inviteeId]
        );

        if (!$row) {
            throw new CalendarException("Invitee $inviteeId not found");
        }

        $invitee = CalendarInvitee::fromArray($row);
        $invitee->setRsvpStatus($rsvpStatus);

        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_INVITEES . "
             SET rsvp_status = ?, responded_at = NOW()
             WHERE id = ?",
            [$rsvpStatus, (string) $inviteeId]
        );

        return $invitee;
    }

    /**
     * Soft-delete an invitee row.
     *
     * @param int $inviteeId
     * @return void
     * @throws CalendarException
     * @since 1.1.0
     */
    public function removeInvitee(int $inviteeId): void
    {
        $affected = $this->db->executeUpdate(
            "UPDATE " . self::TABLE_INVITEES . " SET inactive = 1 WHERE id = ?",
            [(string) $inviteeId]
        );

        if ($affected === 0) {
            throw new CalendarException("Invitee $inviteeId not found");
        }
    }

    /**
     * Return all active invitees for an entry (people + resources).
     *
     * @param int $entryId
     * @return CalendarInvitee[]
     * @since 1.1.0
     */
    public function getInviteesForEntry(int $entryId): array
    {
        return $this->loadInvitees($entryId);
    }

    /**
     * Return busy time slots for a contact or resource across a date range.
     *
     * Returns an array of ['start' => string, 'end' => string] pairs (ISO 8601).
     * Used to render the free/busy timeline in the invite modal.
     *
     * @param string   $contactType One of CalendarInvitee::TYPE_* constants
     * @param string   $contactId   The contact/resource id
     * @param DateTime $start       Range start (inclusive)
     * @param DateTime $end         Range end (inclusive)
     * @return array<int, array{start: string, end: string}>
     * @since 1.1.0
     */
    public function getFreeBusy(
        string $contactType,
        string $contactId,
        \DateTime $start,
        \DateTime $end
    ): array {
        // Find all invitee rows for this contact in the date range that are not declined.
        $sql = "SELECT e.start_date, e.end_date
                FROM " . self::TABLE_INVITEES . " i
                JOIN " . self::TABLE_ENTRIES . " e ON e.id = i.entry_id
                WHERE i.contact_type = ?
                  AND i.contact_id   = ?
                  AND i.rsvp_status != ?
                  AND i.inactive = 0
                  AND e.inactive = 0
                  AND e.start_date < ?
                  AND (e.end_date > ? OR e.end_date IS NULL)
                ORDER BY e.start_date ASC";

        $rows = $this->db->fetchAll($sql, [
            $contactType,
            $contactId,
            CalendarInvitee::RSVP_DECLINED,
            $end->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
        ]);

        return array_map(function (array $row): array {
            return [
                'start' => $row['start_date'],
                'end'   => $row['end_date'] ?? $row['start_date'],
            ];
        }, $rows);
    }

    /**
     * Search persons by name or email via the FA person registry.
     *
     * Returns one row per "hat" (crm_contacts row) per matching person.
     * For example, if Kevin Fraser has both a 'user' hat and a 'crm_contact'
     * hat, he appears twice — allowing the inviter to choose which role he
     * plays in this calendar event.
     *
     * Resources (fa_resources) are also included when the module is installed.
     *
     * Silently skips the person registry if crm_persons is unavailable.
     * Silently skips resources if fa_resources is unavailable.
     *
     * Result rows: [contact_type, contact_id (= crm_contacts.id), name, email, phone]
     *
     * @param string $query   The search string (minimum 2 characters)
     * @param int    $limit   Maximum rows to return (default 20)
     * @return array<int, array{contact_type: string, contact_id: string, name: string, email: string, phone: string}>
     * @since 1.3.0
     */
    public function searchInvitees(string $query, int $limit = 20): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $results = [];
        $like    = '%' . $query . '%';

        // Person registry: crm_persons JOIN crm_contacts JOIN crm_categories.
        // Returns one row per "hat" per person.
        // contact_id = crm_contacts.id (the row the invitee slot will reference).
        try {
            $personRows = $this->db->fetchAll(
                "SELECT cp.id       AS person_id,"
                . "     cc.id       AS crm_contact_id,"
                . "     cc.type     AS contact_type,"
                . "     cc.entity_id,"
                . "     cp.name,"
                . "     cp.email,"
                . "     cp.phone,"
                . "     cat.name    AS type_label"
                . " FROM crm_persons cp"
                . " JOIN crm_contacts cc  ON cc.person_id = cp.id AND cc.inactive = 0"
                . " JOIN crm_categories cat ON cat.type = cc.type AND cat.action = 'general'"
                . " WHERE (cp.name LIKE ? OR cp.email LIKE ?)"
                . "   AND cp.inactive = 0"
                . " ORDER BY cp.name, cc.type"
                . " LIMIT ?",
                [$like, $like, $limit]
            );

            foreach ($personRows as $row) {
                $results[] = [
                    'contact_type' => (string) ($row['contact_type']   ?? ''),
                    'contact_id'   => (string) ($row['crm_contact_id'] ?? ''),
                    'name'         => (string) ($row['name']           ?? ''),
                    'email'        => (string) ($row['email']          ?? ''),
                    'phone'        => (string) ($row['phone']          ?? ''),
                ];
            }
        } catch (\Exception $e) {
            $this->logger->debug('Person registry search skipped', ['reason' => $e->getMessage()]);
        }

        // Resources (optional; silently skipped if module not installed).
        try {
            $resourceRows = $this->db->fetchAll(
                "SELECT id, name, email, phone"
                . " FROM fa_resources"
                . " WHERE (name LIKE ? OR email LIKE ?)"
                . "   AND inactive = 0"
                . " LIMIT ?",
                [$like, $like, $limit]
            );

            foreach ($resourceRows as $row) {
                $results[] = [
                    'contact_type' => CalendarInvitee::TYPE_RESOURCE,
                    'contact_id'   => (string) ($row['id']    ?? ''),
                    'name'         => (string) ($row['name']  ?? ''),
                    'email'        => (string) ($row['email'] ?? ''),
                    'phone'        => (string) ($row['phone'] ?? ''),
                ];
            }
        } catch (\Exception $e) {
            // Resources module not installed; skip.
            $this->logger->debug('Resource search skipped', ['reason' => $e->getMessage()]);
        }

        return array_slice($results, 0, $limit);
    }

    // ---------------------------------------------------------------
    // Private helpers
    // ---------------------------------------------------------------

    /**
     * Load all active invitee rows for an entry.
     *
     * @param int $entryId
     * @return CalendarInvitee[]
     * @since 1.1.0
     */
    private function loadInvitees(int $entryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_INVITEES . "
             WHERE entry_id = ? AND inactive = 0
             ORDER BY is_organizer DESC, is_resource ASC, id ASC",
            [(string) $entryId]
        );

        return array_map(function (array $row): CalendarInvitee {
            return CalendarInvitee::fromArray($row);
        }, $rows);
    }

    /**
     * INSERT a new invitee row and set its auto-incremented id.
     *
     * @param CalendarInvitee $invitee
     * @return void
     * @since 1.1.0
     */
    private function saveInvitee(CalendarInvitee $invitee): void
    {
        $data = $invitee->toArray();

        $this->db->executeUpdate(
            "INSERT INTO " . self::TABLE_INVITEES . "
             (entry_id, contact_type, contact_id, name, email, phone,
              rsvp_status, is_organizer, is_resource, invited_at, inactive)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
            [
                $data['entry_id'],
                $data['contact_type'],
                $data['contact_id'],
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['rsvp_status'],
                $data['is_organizer'] ? 1 : 0,
                $data['is_resource']  ? 1 : 0,
                $data['invited_at'],
            ]
        );
    }

    /**
     * Check whether a resource has no conflicting accepted booking that
     * overlaps the given entry's time slot.
     *
     * Returns true (available) if the resource has no accepted invitee rows
     * for entries that overlap the target entry's start/end window.
     *
     * @param string $resourceId
     * @param int    $entryId    The entry being booked (excluded from conflict check)
     * @return bool
     * @since 1.1.0
     */
    private function isResourceAvailable(string $resourceId, int $entryId): bool
    {
        if ($resourceId === '') {
            return false;
        }

        // Fetch the target entry's time window.
        $entry = $this->db->fetchAssoc(
            "SELECT start_date, end_date FROM " . self::TABLE_ENTRIES . " WHERE id = ?",
            [(string) $entryId]
        );

        if (!$entry || empty($entry['start_date'])) {
            return true;
        }

        $startDate = $entry['start_date'];
        $endDate   = !empty($entry['end_date']) ? $entry['end_date'] : $startDate;

        $conflict = $this->db->fetchAssoc(
            "SELECT i.id
             FROM " . self::TABLE_INVITEES . " i
             JOIN " . self::TABLE_ENTRIES . " e ON e.id = i.entry_id
             WHERE i.contact_type = ?
               AND i.contact_id   = ?
               AND i.is_resource  = 1
               AND i.rsvp_status  = ?
               AND i.inactive     = 0
               AND e.inactive     = 0
               AND i.entry_id    != ?
               AND e.start_date   < ?
               AND (e.end_date   > ? OR e.end_date IS NULL)
             LIMIT 1",
            [
                CalendarInvitee::TYPE_RESOURCE,
                $resourceId,
                CalendarInvitee::RSVP_ACCEPTED,
                (string) $entryId,
                $endDate,
                $startDate,
            ]
        );

        return $conflict === null;
    }
}