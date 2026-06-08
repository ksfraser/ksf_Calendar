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
use Ksfraser\Calendar\Entity\CalendarDependency;
use Ksfraser\Calendar\Entity\CalendarAttachment;
use Ksfraser\Calendar\Entity\CalendarNotification;
use Ksfraser\Calendar\Entity\CalendarOccurrence;
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
    private const TABLE_ENTRIES       = 'fa_cal_entries';
    private const TABLE_SOURCES       = 'fa_cal_sources';
    private const TABLE_INVITEES      = 'fa_cal_invitees';
    private const TABLE_DEPENDENCIES  = 'fa_cal_dependencies';
    private const TABLE_ATTACHMENTS   = 'fa_cal_attachments';
    private const TABLE_NOTIFICATIONS = 'fa_cal_notifications';
    private const TABLE_OCCURRENCES  = 'fa_cal_event_occurrences';

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

        $entry = new CalendarEntry(
            $data['source'] ?? CalendarEntry::SOURCE_USER,
            $data['source_id'] ?? '',
            $data['source_type'] ?? CalendarEntry::TYPE_EVENT,
            $data['title'],
            isset($data['start_date']) ? new DateTime($data['start_date']) : null,
            null
        );

        if (array_key_exists('end_date', $data)) {
            $entry->setEndDate($data['end_date'] ? new DateTime($data['end_date']) : null);
        }
        if (isset($data['description'])) {
            $entry->setDescription($data['description']);
        }
        if (isset($data['assigned_to'])) {
            $entry->setAssignedTo((string) $data['assigned_to']);
        }
        if (isset($data['user_id'])) {
            $entry->setUserId((string) $data['user_id']);
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
        if (isset($data['direction'])) {
            $entry->setDirection($data['direction']);
        }
        if (array_key_exists('meeting_number', $data)) {
            $entry->setMeetingNumber($data['meeting_number']);
        }
        if (array_key_exists('meeting_passcode', $data)) {
            $entry->setMeetingPasscode($data['meeting_passcode']);
        }
        if (isset($data['is_scheduled'])) {
            $entry->setIsScheduled((bool) $data['is_scheduled']);
        }
        if (isset($data['parent_entry_id'])) {
            $entry->setParentEntryId((int) $data['parent_entry_id']);
        }
        if (isset($data['guest_policy'])) {
            $entry->setGuestPolicy($data['guest_policy']);
        }
        if (isset($data['is_billable'])) {
            $entry->setIsBillable((bool) $data['is_billable']);
        }
        if (isset($data['billable_rate'])) {
            $entry->setBillableRate((float) $data['billable_rate']);
        }
        if (isset($data['billable_currency'])) {
            $entry->setBillableCurrency($data['billable_currency']);
        }
        if (isset($data['auto_invoice'])) {
            $entry->setAutoInvoice((bool) $data['auto_invoice']);
        }
        if (array_key_exists('sales_order_id', $data)) {
            $entry->setSalesOrderId($data['sales_order_id']);
        }
        if (isset($data['reminder'])) {
            $entry->setReminder(
                (bool) $data['reminder'],
                isset($data['reminder_minutes']) ? (int) $data['reminder_minutes'] : 15
            );
        }
        if (isset($data['recurrence_end_date'])) {
            $entry->setRecurrenceEndDate(new DateTime($data['recurrence_end_date']));
        }
        if (isset($data['recurrence_count'])) {
            $entry->setRecurrenceCount((int) $data['recurrence_count']);
        }
        if (array_key_exists('recurrence_rule', $data)) {
            $entry->setRecurrenceRule($data['recurrence_rule'] ?: null);
        }

        $this->applyDefaultDuration($entry);

        $this->saveEntry($entry);

        // Pre-generate occurrences for recurring entries
        if ($entry->getRecurrenceRule() !== null && $entry->getRecurrenceRule() !== '') {
            $this->generateOccurrences($entry);
        }

        // Auto-invite the assigned_to user so their free/busy shows in
        // the availability grid when creating events for other users.
        $assignedTo = $entry->getAssignedTo();
        if ($assignedTo !== null && $assignedTo !== '') {
            try {
                $this->addInvitee($entry->getId(), [
                    'contact_type' => CalendarInvitee::TYPE_FA_USER,
                    'contact_id'   => $assignedTo,
                    'name'         => '',
                    'email'        => '',
                    'rsvp_status'  => CalendarInvitee::RSVP_ACCEPTED,
                ]);
            } catch (\Exception $e) {
                $this->logger->warning('Could not auto-add owner invitee', [
                    'entry' => $entry->getId(),
                    'user'  => $assignedTo,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->events->dispatch(new CalendarEntryCreatedEvent($entry));

        $this->logger->info('Calendar entry created', ['id' => $entry->getId()]);
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
            $entry->setAssignedTo((string) $data['assigned_to']);
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
        if (isset($data['direction'])) {
            $entry->setDirection($data['direction']);
        }
        if (array_key_exists('meeting_number', $data)) {
            $entry->setMeetingNumber($data['meeting_number']);
        }
        if (array_key_exists('meeting_passcode', $data)) {
            $entry->setMeetingPasscode($data['meeting_passcode']);
        }
        if (array_key_exists('recurrence_rule', $data)) {
            $entry->setRecurrenceRule($data['recurrence_rule'] ?: null);
        }

        $this->applyDefaultDuration($entry);

        $this->saveEntry($entry);

        // Regenerate occurrences if recurrence_rule present; clean up if removed.
        if ($entry->getRecurrenceRule() !== null && $entry->getRecurrenceRule() !== '') {
            $this->generateOccurrences($entry);
        } else {
            // Occurrences table uses hard DELETE, which is safe because
            // generateOccurrences() also does hard DELETE before regeneration.
            $this->db->executeUpdate(
                "DELETE FROM " . self::TABLE_OCCURRENCES . " WHERE entry_id = ?",
                [(string) $entry->getId()]
            );
        }

        $this->events->dispatch(new CalendarEntryUpdatedEvent($entry));

        $this->logger->info('Calendar entry updated', ['id' => $id]);
        return $entry;
    }

    public function deleteEntry(int $id): void
    {
        $entry = $this->getEntry($id);

        $sql = "UPDATE " . self::TABLE_ENTRIES . " SET inactive = 1 WHERE id = ?";
        $this->db->executeUpdate($sql, [(string) $id]);

        // Soft-delete occurrences
        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_OCCURRENCES . " SET inactive = 1, status = 'cancelled' WHERE entry_id = ?",
            [(string) $id]
        );

        $this->events->dispatch(new CalendarEntryDeletedEvent($entry));
        $this->logger->info('Calendar entry deleted', ['id' => $id]);
    }

    /**
     * Clone an existing calendar entry with new dates.
     *
     * Copies the entry (except recurrence fields) and all invitees.
     * The new entry gets fresh created_at/updated_at timestamps.
     *
     * @param int         $id        Source entry ID
     * @param DateTime    $newStart  New start date/time for the clone
     * @param DateTime    $newEnd    New end date/time for the clone
     * @return CalendarEntry
     * @since 1.8.0
     */
    public function cloneEntry(int $id, \DateTime $newStart, \DateTime $newEnd): CalendarEntry
    {
        $original = $this->getEntry($id);
        $data = $original->toArray();

        // Remove identity and recurrence fields.
        unset($data['id']);
        $data['start_date'] = $newStart->format('Y-m-d H:i:s');
        $data['end_date']   = $newEnd->format('Y-m-d H:i:s');
        $data['recurrence_rule']  = null;
        $data['recurrence_id']    = null;
        $data['recurrence_end_date'] = null;
        $data['recurrence_count']    = null;
        $data['parent_entry_id']     = null;
        $data['source_id'] = 'clone_' . $id;

        $entry = CalendarEntry::fromArray($data);
        $this->saveEntry($entry);

        // Clone invitees (skip inactive).
        $invitees = $this->loadInvitees($id);
        foreach ($invitees as $inv) {
            $invData = $inv->toArray();
            unset($invData['id']);
            $invData['entry_id'] = $entry->getId();
            $this->saveInvitee(CalendarInvitee::fromArray($invData));
        }

        return $this->getEntry($entry->getId());
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
                    OR (recurrence_rule IS NOT NULL AND recurrence_rule != '' AND parent_entry_id IS NULL AND start_date < ?)
                )";

        $params = [
            $start->format('Y-m-d'), $end->format('Y-m-d'),
            $start->format('Y-m-d'), $end->format('Y-m-d'),
            $start->format('Y-m-d'), $end->format('Y-m-d'),
            $end->format('Y-m-d'),
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

        if (!empty($filters['direction'])) {
            $sql .= " AND direction = ?";
            $params[] = $filters['direction'];
        }

        // status_open: true = open items only, false = closed items only.
        if (isset($filters['status_open'])) {
            // Build an IN list of statuses considered open or closed.
            // We rely on CalendarEntry::isOpenStatus() logic but replicate it in SQL
            // by explicitly listing the closed statuses (simpler and DB-portable).
            // Includes v1.3.0 call statuses: call_not_completed, call_vmail_full, call_message_left.
            if ((bool) $filters['status_open']) {
                $sql .= " AND status NOT IN ("
                    . "'completed','cancelled','no_show',"
                    . "'meeting_held','meeting_not_held',"
                    . "'call_held','call_vmail',"
                    . "'call_not_completed','call_vmail_full','call_message_left'"
                    . ")";
            } else {
                $sql .= " AND status IN ("
                    . "'completed','cancelled','no_show',"
                    . "'meeting_held','meeting_not_held',"
                    . "'call_held','call_vmail',"
                    . "'call_not_completed','call_vmail_full','call_message_left'"
                    . ")";
            }
        }

        if (!empty($filters['include_private'])) {
            $sql .= " AND private = 0";
        }

        if (!empty($filters['viewable_by'])) {
            $userId    = (int) $filters['viewable_by'];
            $userIdStr = (string) $userId;

            // Visibility subquery — resolves invitees via direct user match
            // or FA person registry.
            //
            // fa_cal_invitees.contact_id stores:
            //   - user type: the FA numeric user ID (e.g. "1") via TYPE_FA_USER
            //   - crm_contact type: crm_contacts.id (e.g. "42") via TYPE_CRM_CONTACT
            //
            // To find entries visible to $userId:
            //   assigned_to = $userId  (direct ownership)
            //   OR i.contact_type IN ('fa_user', 'user') AND i.contact_id = $userIdStr
            //   (the 'user' fallback is for pre-v1.5 data before ContactTypeRegistry alignment)
            //   OR other contact types: person-registry JOIN to user hat
            $sql .= " AND (assigned_to = ?"
                  . " OR id IN ("
                  .     "SELECT entry_id FROM " . self::TABLE_INVITEES . " i"
                  .     " WHERE i.inactive = 0"
                  .     " AND ("
                .         "((i.contact_type = '" . CalendarInvitee::TYPE_FA_USER . "' OR i.contact_type = 'user') AND i.contact_id = ?)"
                  .         " OR"
                  .         "(i.contact_type NOT IN ('" . CalendarInvitee::TYPE_FA_USER . "', 'user') AND EXISTS ("
                  .             "SELECT 1 FROM crm_contacts ic"
                  .             " JOIN crm_contacts uc"
                  .                " ON uc.person_id = ic.person_id"
                  .               " AND uc.type = 'user'"
                  .               " AND uc.entity_id = ?"
                  .             " WHERE ic.id = i.contact_id"
                  .         "))"
                  .     ")"
                  . "))";

            $params[] = $userId;
            $params[] = $userIdStr;
            $params[] = $userIdStr;
        }

        $sql .= " ORDER BY start_date ASC";

        $rows = $this->db->fetchAll($sql, $params);

        // invitee_user_id: additionally fetch entries in the date range where this
        // contact_id is an accepted invitee.  This is done as a separate query and
        // merged in PHP so that the OR cannot bypass the inactive = 0 guard or the
        // date-range conditions on the owned-entries branch.
        //
        // The separate query applies only inactive = 0 + date range + invitee match;
        // user_id / assigned_to filters intentionally do NOT apply (the invited entry
        // belongs to someone else).  source_type and direction filters ARE applied so
        // per-type views remain correct.
        //
        // fa_cal_invitees.contact_id stores:
        //   - user type: the FA numeric user ID (e.g. "1") via TYPE_FA_USER
        //   - crm_contact type: crm_contacts.id (e.g. "42") via TYPE_CRM_CONTACT
        // The subquery handles both via an OR branch.
        // Backward compat: also match legacy 'user' contact_type (pre-1.5 data).
        if (!empty($filters['invitee_user_id'])) {
            $invStr = (string) $filters['invitee_user_id'];
            $invSql = "SELECT e.* FROM " . self::TABLE_ENTRIES . " e"
                . " WHERE e.inactive = 0"
                . " AND ("
                .     "(e.start_date BETWEEN ? AND ?)"
                .     " OR (e.end_date BETWEEN ? AND ?)"
                .     " OR (e.start_date <= ? AND e.end_date >= ?)"
                . ")"
                . " AND e.id IN ("
                .     "SELECT i.entry_id FROM " . self::TABLE_INVITEES . " i"
                .     " WHERE i.rsvp_status != 'declined'"
                .     " AND i.inactive = 0"
                .     " AND ("
                .         "((i.contact_type = '" . CalendarInvitee::TYPE_FA_USER . "' OR i.contact_type = 'user') AND i.contact_id = ?)"
                  .         " OR"
                  .         "(i.contact_type NOT IN ('" . CalendarInvitee::TYPE_FA_USER . "', 'user') AND EXISTS ("
                  .             "SELECT 1 FROM crm_contacts ic"
                  .             " JOIN crm_contacts uc"
                  .                " ON uc.person_id = ic.person_id"
                  .               " AND uc.type = 'user'"
                  .               " AND uc.entity_id = ?"
                  .             " WHERE ic.id = i.contact_id"
                  .         "))"
                  .     ")"
                  . ")";
            $invParams = [
                $start->format('Y-m-d'), $end->format('Y-m-d'),
                $start->format('Y-m-d'), $end->format('Y-m-d'),
                $start->format('Y-m-d'), $end->format('Y-m-d'),
                $invStr,
                $invStr,
            ];

            if (!empty($filters['source_type'])) {
                if (is_array($filters['source_type'])) {
                    $placeholders = implode(',', array_fill(0, count($filters['source_type']), '?'));
                    $invSql   .= " AND e.source_type IN ($placeholders)";
                    $invParams = array_merge($invParams, $filters['source_type']);
                } else {
                    $invSql    .= " AND e.source_type = ?";
                    $invParams[] = $filters['source_type'];
                }
            }

            if (!empty($filters['direction'])) {
                $invSql    .= " AND e.direction = ?";
                $invParams[] = $filters['direction'];
            }

            $invSql .= " ORDER BY e.start_date ASC";
            $invRows = $this->db->fetchAll($invSql, $invParams);

            // Merge by id, de-duplicate (owned entry may also appear in invitee list).
            $rowsById = [];
            foreach ($rows as $r) {
                $rowsById[(int) $r['id']] = $r;
            }
            foreach ($invRows as $r) {
                $rowsById[(int) $r['id']] = $r;
            }

            usort($rowsById, function (array $a, array $b): int {
                $ta = isset($a['start_date']) ? strtotime((string) $a['start_date']) : 0;
                $tb = isset($b['start_date']) ? strtotime((string) $b['start_date']) : 0;
                return $ta <=> $tb;
            });

            return array_map(function ($row) {
                return CalendarEntry::fromArray($row);
            }, array_values($rowsById));
        }

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
            // UPDATE: 32 SET columns + 1 WHERE id = ? = 33 params total.
            $sql = "UPDATE " . self::TABLE_ENTRIES . " SET
                    title = ?, description = ?, start_date = ?, end_date = ?,
                    all_day = ?, location = ?, online_url = ?, phone_number = ?,
                    send_invites = ?, assigned_to = ?, user_id = ?,
                    customer_id = ?, project_id = ?, task_id = ?, contact_id = ?,
                    status = ?, priority = ?, color = ?, private = ?,
                    reminder = ?, reminder_minutes = ?,
                    direction = ?, meeting_number = ?, meeting_passcode = ?,
                    is_scheduled = ?, parent_entry_id = ?, guest_policy = ?,
                    is_billable = ?, billable_rate = ?, billable_currency = ?,
                     auto_invoice = ?, sales_order_id = ?,
                     recurrence_rule = ?, recurrence_end_date = ?, recurrence_count = ?,
                     delta = ?, needs_review = ?,
                     updated_at = NOW()
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
                $entry->getDirection(),
                $entry->getMeetingNumber(),
                $entry->getMeetingPasscode(),
                $entry->isScheduled() ? 1 : 0,
                $entry->getParentEntryId(),
                $entry->getGuestPolicy(),
                $entry->isBillable() ? 1 : 0,
                $entry->getBillableRate(),
                $entry->getBillableCurrency(),
                $entry->isAutoInvoice() ? 1 : 0,
                $entry->getSalesOrderId(),
                $entry->getRecurrenceRule(),
                $entry->getRecurrenceEndDate() !== null ? $entry->getRecurrenceEndDate()->format('Y-m-d H:i:s') : null,
                $entry->getRecurrenceCount(),
                $entry->getDelta(),
                $entry->getNeedsReview() ? 1 : 0,
                $entry->getId() !== null ? (string) $entry->getId() : null,
            ];
        } else {
            $sql = "INSERT INTO " . self::TABLE_ENTRIES . " (
                    source, source_id, source_type, title, description,
                    start_date, end_date, all_day, timezone, location,
                    online_url, phone_number, send_invites,
                    assigned_to, user_id, customer_id, project_id, task_id, contact_id,
                    status, priority, category, reminder, reminder_minutes, color, private,
                    direction, meeting_number, meeting_passcode,
                    is_scheduled, parent_entry_id, guest_policy,
                    is_billable, billable_rate, billable_currency,
                    auto_invoice, sales_order_id,
                    recurrence_rule, recurrence_end_date, recurrence_count,
                    delta, needs_review,
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

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
                $entry->getDirection(),
                $entry->getMeetingNumber(),
                $entry->getMeetingPasscode(),
                $entry->isScheduled() ? 1 : 0,
                $entry->getParentEntryId(),
                $entry->getGuestPolicy(),
                $entry->isBillable() ? 1 : 0,
                $entry->getBillableRate(),
                $entry->getBillableCurrency(),
                $entry->isAutoInvoice() ? 1 : 0,
                $entry->getSalesOrderId(),
                $entry->getRecurrenceRule(),
                $entry->getRecurrenceEndDate() !== null ? $entry->getRecurrenceEndDate()->format('Y-m-d H:i:s') : null,
                $entry->getRecurrenceCount(),
                $entry->getDelta(),
                $entry->getNeedsReview() ? 1 : 0,
            ];
        }

        $this->db->executeUpdate($sql, $params);

        if (!$exists) {
            // Update the in-memory entry with the real DB-assigned auto-increment id.
            $realId = (int) $this->db->lastInsertId();
            if ($realId > 0) {
                $entry->setId($realId);
            }
        }
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
        if (isset($data['permission'])) {
            $invitee->setPermission((string) $data['permission']);
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
     * Update the permission level for an invitee.
     *
     * @param int    $inviteeId
     * @param string $permission One of CalendarInvitee::PERMISSION_VIEW or PERMISSION_EDIT
     * @return CalendarInvitee
     * @throws CalendarException
     * @since 1.8.0
     */
    public function updateInviteePermission(int $inviteeId, string $permission): CalendarInvitee
    {
        $row = $this->db->fetchAssoc(
            "SELECT * FROM " . self::TABLE_INVITEES . " WHERE id = ? AND inactive = 0",
            [(string) $inviteeId]
        );

        if (!$row) {
            throw new CalendarException("Invitee $inviteeId not found");
        }

        $invitee = CalendarInvitee::fromArray($row);
        $invitee->setPermission($permission);

        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_INVITEES . "
             SET permission = ?
             WHERE id = ?",
            [$permission, (string) $inviteeId]
        );

        return $invitee;
    }

    /**
     * Update the post-event individual attendance status for an invitee.
     *
     * This method does the DB write only.  Business-rule enforcement
     * (who is allowed to set which status) is the responsibility of the
     * calling platform layer (FA_Cal_Module::update_individual_status()).
     *
     * @param int    $inviteeId      Invitee row ID
     * @param string $individualStatus One of CalendarInvitee::INDIVIDUAL_STATUS_* constants
     * @return CalendarInvitee  The updated invitee entity
     * @throws CalendarException  If the invitee row is not found
     * @since 1.3.0
     */
    public function updateIndividualStatus(int $inviteeId, string $individualStatus): CalendarInvitee
    {
        $row = $this->db->fetchAssoc(
            "SELECT * FROM " . self::TABLE_INVITEES . " WHERE id = ? AND inactive = 0",
            [(string) $inviteeId]
        );

        if (!$row) {
            throw new CalendarException("Invitee $inviteeId not found");
        }

        $invitee = CalendarInvitee::fromArray($row);
        $invitee->setIndividualStatus($individualStatus);

        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_INVITEES . "
             SET individual_status = ?, individual_status_updated_at = NOW()
             WHERE id = ?",
            [$individualStatus, (string) $inviteeId]
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
                'start' => str_replace(' ', 'T', $row['start_date']),
                'end'   => str_replace(' ', 'T', $row['end_date'] ?? $row['start_date']),
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
     * When $contactTypes is non-empty, the person query is filtered to only
     * include rows whose cc.type matches the given types. This allows each
     * platform module (CRM, HRM, etc.) to declare which contact types it owns
     * via the 'calendar_invitee_contact_types' hook and have the calendar
     * search only those types rather than querying the full person registry.
     *
     * Result rows: [contact_type, contact_id (= crm_contacts.id), name, email, phone]
     *
     * @param string   $query        The search string (minimum 2 characters)
     * @param int      $limit        Maximum rows to return (default 20)
     * @param string[] $contactTypes Optional list of cc.type values to filter by
     * @return array<int, array{contact_type: string, contact_id: string, name: string, email: string, phone: string}>
     * @since 1.3.0
     */
    public function searchInvitees(string $query, int $limit = 20, array $contactTypes = []): array
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
            $typeCondition = '';
            $personParams  = [$like, $like];
            if (!empty($contactTypes)) {
                $placeholders  = implode(',', array_fill(0, count($contactTypes), '?'));
                $typeCondition = " AND cc.type IN ($placeholders)";
                $personParams  = array_merge($personParams, $contactTypes);
            }
            $personParams[] = $limit;

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
                . " JOIN crm_contacts cc  ON cc.person_id = cp.id"
                . " JOIN crm_categories cat ON cat.type = cc.type AND cat.action = 'general'"
                . " WHERE (cp.name LIKE ? OR cp.email LIKE ?)"
                . "   AND cp.inactive = 0"
                . $typeCondition
                . " ORDER BY cp.name, cc.type"
                . " LIMIT ?",
                $personParams
            );

            foreach ($personRows as $row) {
                $results[] = [
                    'contact_type' => (string) ($row['contact_type']   ?? ''),
                    'contact_id'   => (string) ($row['crm_contact_id'] ?? ''),
                    'name'         => (string) ($row['name']           ?? ''),
                    'email'        => (string) ($row['email']          ?? ''),
                    'phone'        => (string) ($row['phone']          ?? ''),
                    'type_label'   => (string) ($row['type_label']     ?? $row['contact_type'] ?? ''),
                ];
            }
        } catch (\Exception $e) {
            $this->logger->debug('Person registry search skipped', ['reason' => $e->getMessage()]);
        }

        // Fallback: FA users table (when crm person registry not installed).
        // Respects contactTypes filter: only query if 'user' is allowed.
        if (empty($results) || (empty($contactTypes) || in_array(CalendarInvitee::TYPE_FA_USER, $contactTypes))) {
            try {
                $userRows = $this->db->fetchAll(
                    "SELECT id, real_name AS name, email"
                    . " FROM users"
                    . " WHERE inactive = 0"
                    . " AND (real_name LIKE ? OR email LIKE ?)"
                    . " LIMIT ?",
                    [$like, $like, $limit]
                );

                foreach ($userRows as $row) {
                    $results[] = [
                        'contact_type' => CalendarInvitee::TYPE_FA_USER,
                        'contact_id'   => (string) ($row['id'] ?? ''),
                        'name'         => (string) ($row['name'] ?? ''),
                        'email'        => (string) ($row['email'] ?? ''),
                        'phone'        => '',
                        'type_label'   => 'FA User',
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->debug('FA users fallback search skipped', ['reason' => $e->getMessage()]);
            }
        }

        // CRM contacts fallback (optional; silently skipped if module not installed).
        if (empty($results) || (empty($contactTypes) || in_array(CalendarInvitee::TYPE_CRM_CONTACT, $contactTypes))) {
            try {
                $crmRows = $this->db->fetchAll(
                    "SELECT id, CONCAT(first_name, ' ', last_name) AS name, email, phone"
                    . " FROM fa_crm_contacts"
                    . " WHERE inactive = 0"
                    . " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)"
                    . " LIMIT ?",
                    [$like, $like, $like, $like, $limit]
                );

                foreach ($crmRows as $row) {
                    $results[] = [
                        'contact_type' => CalendarInvitee::TYPE_CRM_CONTACT,
                        'contact_id'   => (string) ($row['id'] ?? ''),
                        'name'         => (string) ($row['name'] ?? ''),
                        'email'        => (string) ($row['email'] ?? ''),
                        'phone'        => (string) ($row['phone'] ?? ''),
                        'type_label'   => 'CRM Contact',
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->debug('CRM contacts fallback search skipped', ['reason' => $e->getMessage()]);
            }
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
                    'type_label'   => 'Resource',
                ];
            }
        } catch (\Exception $e) {
            // Resources module not installed; skip.
            $this->logger->debug('Resource search skipped', ['reason' => $e->getMessage()]);
        }

        return array_slice($results, 0, $limit);
    }

    // ---------------------------------------------------------------
    // Dependency methods (v1.4.0)
    // ---------------------------------------------------------------

    /**
     * Create a new dependency link between two calendar entries.
     *
     * @param int    $entryId          The dependent entry ID
     * @param int    $dependsOnEntryId The prerequisite entry ID
     * @param string $type             One of CalendarDependency::DEPENDENCY_TYPE_* constants
     * @return CalendarDependency  The newly created dependency with DB-assigned id
     *
     * @since 1.4.0
     */
    public function addDependency(int $entryId, int $dependsOnEntryId, string $type): CalendarDependency
    {
        $dep = new CalendarDependency($entryId, $dependsOnEntryId, $type);

        $this->db->executeUpdate(
            "INSERT INTO " . self::TABLE_DEPENDENCIES . "
             (entry_id, depends_on_entry_id, dependency_type, inactive, created_at)
             VALUES (?, ?, ?, 0, NOW())",
            [
                (string) $entryId,
                (string) $dependsOnEntryId,
                $type,
            ]
        );

        $newId = (int) $this->db->lastInsertId();
        if ($newId > 0) {
            $dep->setId($newId);
        }

        return $dep;
    }

    /**
     * Soft-delete a dependency row.
     *
     * @param int $dependencyId  The fa_cal_dependencies.id to remove
     * @return void
     * @throws CalendarException  If the row does not exist
     *
     * @since 1.4.0
     */
    public function removeDependency(int $dependencyId): void
    {
        $affected = $this->db->executeUpdate(
            "UPDATE " . self::TABLE_DEPENDENCIES . " SET inactive = 1 WHERE id = ?",
            [(string) $dependencyId]
        );

        if ($affected === 0) {
            throw new CalendarException("Dependency $dependencyId not found");
        }
    }

    /**
     * Return the active dependencies this entry has (i.e. what it depends ON).
     *
     * @param int $entryId
     * @return CalendarDependency[]
     *
     * @since 1.4.0
     */
    public function getDependenciesForEntry(int $entryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_DEPENDENCIES . "
             WHERE entry_id = ? AND inactive = 0
             ORDER BY id ASC",
            [(string) $entryId]
        );

        return array_map(function (array $row): CalendarDependency {
            return CalendarDependency::fromArray($row);
        }, $rows);
    }

    /**
     * Return the active entries that depend ON this entry.
     *
     * @param int $entryId
     * @return CalendarDependency[]
     *
     * @since 1.4.0
     */
    public function getDependentsForEntry(int $entryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_DEPENDENCIES . "
             WHERE depends_on_entry_id = ? AND inactive = 0
             ORDER BY id ASC",
            [(string) $entryId]
        );

        return array_map(function (array $row): CalendarDependency {
            return CalendarDependency::fromArray($row);
        }, $rows);
    }

    /**
     * Return active child entries linked via parent_entry_id.
     *
     * @param int $parentEntryId
     * @return CalendarEntry[]
     *
     * @since 1.4.0
     */
    public function getChildEntries(int $parentEntryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_ENTRIES . "
             WHERE parent_entry_id = ? AND inactive = 0
             ORDER BY start_date ASC",
            [(string) $parentEntryId]
        );

        return array_map(function (array $row): CalendarEntry {
            return CalendarEntry::fromArray($row);
        }, $rows);
    }

    // ---------------------------------------------------------------
    // Attachment methods (v1.5.0)
    // ---------------------------------------------------------------

    /**
     * Add a file attachment to a calendar entry.
     *
     * @param int   $entryId
     * @param array $data  Keys: filename (required), file_path (required),
     *                           file_size, mime_type, uploaded_by
     * @return CalendarAttachment  The newly created attachment with DB-assigned id
     * @throws CalendarException   If the entry does not exist
     *
     * @since 1.5.0
     */
    public function addAttachment(int $entryId, array $data): CalendarAttachment
    {
        // Ensure entry exists.
        $this->getEntry($entryId);

        $att = new CalendarAttachment(
            $entryId,
            (string) ($data['filename']  ?? ''),
            (string) ($data['file_path'] ?? '')
        );

        if (isset($data['file_size']) && $data['file_size'] !== null) {
            $att->setFileSize((int) $data['file_size']);
        }
        if (isset($data['mime_type'])) {
            $att->setMimeType((string) $data['mime_type']);
        }
        if (isset($data['uploaded_by'])) {
            $att->setUploadedBy((string) $data['uploaded_by']);
        }

        $this->db->executeUpdate(
            "INSERT INTO " . self::TABLE_ATTACHMENTS . "
             (entry_id, filename, file_path, file_size, mime_type, uploaded_by, uploaded_at, inactive)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), 0)",
            [
                (string) $entryId,
                $att->getFilename(),
                $att->getFilePath(),
                $att->getFileSize(),
                $att->getMimeType(),
                $att->getUploadedBy(),
            ]
        );

        $newId = (int) $this->db->lastInsertId();
        if ($newId > 0) {
            $att->setId($newId);
        }

        return $att;
    }

    /**
     * Soft-delete an attachment row.
     *
     * @param int $attachmentId
     * @return void
     * @throws CalendarException  If the row does not exist
     *
     * @since 1.5.0
     */
    public function removeAttachment(int $attachmentId): void
    {
        $affected = $this->db->executeUpdate(
            "UPDATE " . self::TABLE_ATTACHMENTS . " SET inactive = 1 WHERE id = ?",
            [(string) $attachmentId]
        );

        if ($affected === 0) {
            throw new CalendarException("Attachment $attachmentId not found");
        }
    }

    /**
     * Return all active attachments for an entry.
     *
     * @param int $entryId
     * @return CalendarAttachment[]
     *
     * @since 1.5.0
     */
    public function getAttachmentsForEntry(int $entryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_ATTACHMENTS . "
             WHERE entry_id = ? AND inactive = 0
             ORDER BY id ASC",
            [(string) $entryId]
        );

        return array_map(function (array $row): CalendarAttachment {
            return CalendarAttachment::fromArray($row);
        }, $rows);
    }

    // ---------------------------------------------------------------
    // Notification methods (v1.5.0)
    // ---------------------------------------------------------------

    /**
     * Add a notification rule to a calendar entry.
     *
     * invitee_id = null → entry-level (applies to all invitees).
     * invitee_id set   → per-invitee override for that specific invitee.
     *
     * @param int   $entryId
     * @param array $data  Keys: notification_type (required), minutes_before (required),
     *                           invitee_id (optional), notify_at (optional)
     * @return CalendarNotification  The newly created notification with DB-assigned id
     * @throws CalendarException     If the entry does not exist
     *
     * @since 1.5.0
     */
    public function addNotification(int $entryId, array $data): CalendarNotification
    {
        // Ensure entry exists.
        $this->getEntry($entryId);

        $notif = new CalendarNotification(
            $entryId,
            (string) ($data['notification_type'] ?? CalendarNotification::TYPE_EMAIL),
            (int) ($data['minutes_before'] ?? 15)
        );

        if (isset($data['invitee_id']) && $data['invitee_id'] !== null) {
            $notif->setInviteeId((int) $data['invitee_id']);
        }
        if (isset($data['notify_at']) && $data['notify_at'] !== null) {
            $notif->setNotifyAt(new \DateTime($data['notify_at']));
        }

        $notifyAtStr = $notif->getNotifyAt() !== null
            ? $notif->getNotifyAt()->format('Y-m-d H:i:s')
            : null;

        $this->db->executeUpdate(
            "INSERT INTO " . self::TABLE_NOTIFICATIONS . "
             (entry_id, invitee_id, notification_type, minutes_before,
              notify_at, sent_at, inactive, created_at)
             VALUES (?, ?, ?, ?, ?, NULL, 0, NOW())",
            [
                (string) $entryId,
                $notif->getInviteeId() !== null ? (string) $notif->getInviteeId() : null,
                $notif->getNotificationType(),
                (string) $notif->getMinutesBefore(),
                $notifyAtStr,
            ]
        );

        $newId = (int) $this->db->lastInsertId();
        if ($newId > 0) {
            $notif->setId($newId);
        }

        return $notif;
    }

    /**
     * Soft-delete a notification row.
     *
     * @param int $notificationId
     * @return void
     * @throws CalendarException  If the row does not exist
     *
     * @since 1.5.0
     */
    public function removeNotification(int $notificationId): void
    {
        $affected = $this->db->executeUpdate(
            "UPDATE " . self::TABLE_NOTIFICATIONS . " SET inactive = 1 WHERE id = ?",
            [(string) $notificationId]
        );

        if ($affected === 0) {
            throw new CalendarException("Notification $notificationId not found");
        }
    }

    /**
     * Return all active notifications for an entry.
     *
     * @param int $entryId
     * @return CalendarNotification[]
     *
     * @since 1.5.0
     */
    public function getNotificationsForEntry(int $entryId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_NOTIFICATIONS . "
             WHERE entry_id = ? AND inactive = 0
             ORDER BY id ASC",
            [(string) $entryId]
        );

        return array_map(function (array $row): CalendarNotification {
            return CalendarNotification::fromArray($row);
        }, $rows);
    }

    /**
     * Update a notification row (minutes_before and/or notification_type).
     *
     * @param int   $notificationId
     * @param array $data  Keys: minutes_before, notification_type, invitee_id, notify_at
     * @return CalendarNotification  The updated entity
     * @throws CalendarException     If the row does not exist
     *
     * @since 1.5.0
     */
    public function updateNotification(int $notificationId, array $data): CalendarNotification
    {
        $row = $this->db->fetchAssoc(
            "SELECT * FROM " . self::TABLE_NOTIFICATIONS . " WHERE id = ? AND inactive = 0",
            [(string) $notificationId]
        );

        if (!$row) {
            throw new CalendarException("Notification $notificationId not found");
        }

        $notif = CalendarNotification::fromArray($row);

        if (isset($data['minutes_before'])) {
            $notif->setMinutesBefore((int) $data['minutes_before']);
        }
        if (isset($data['notification_type'])) {
            $notif->setNotificationType((string) $data['notification_type']);
        }
        if (array_key_exists('invitee_id', $data)) {
            $notif->setInviteeId($data['invitee_id'] !== null ? (int) $data['invitee_id'] : null);
        }
        if (array_key_exists('notify_at', $data)) {
            $notif->setNotifyAt($data['notify_at'] !== null ? new \DateTime($data['notify_at']) : null);
        }

        $notifyAtStr = $notif->getNotifyAt() !== null
            ? $notif->getNotifyAt()->format('Y-m-d H:i:s')
            : null;

        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_NOTIFICATIONS . "
             SET notification_type = ?, minutes_before = ?, invitee_id = ?, notify_at = ?
             WHERE id = ?",
            [
                $notif->getNotificationType(),
                (string) $notif->getMinutesBefore(),
                $notif->getInviteeId() !== null ? (string) $notif->getInviteeId() : null,
                $notifyAtStr,
                (string) $notificationId,
            ]
        );

        return $notif;
    }

    // ---------------------------------------------------------------
    // v1.6.0 — Reminder entry creation
    // ---------------------------------------------------------------

    /**
     * Create a reminder child entry linked to a parent.
     *
     * The reminder entry is derived from the parent's title and start date,
     * with its own start_date set to (parent_start - minutesBefore) so that
     * it appears on the calendar at the appropriate alert time.
     *
     * @param int $parentEntryId The parent calendar entry
     * @param int $minutesBefore How many minutes before the parent to trigger
     * @return CalendarEntry       The newly-created reminder entry
     * @throws CalendarException   If the parent entry does not exist
     *
     * @since 1.6.0
     */
    public function createReminderEntry(int $parentEntryId, int $minutesBefore): CalendarEntry
    {
        $parent = $this->getEntry($parentEntryId);

        $parentStart = $parent->getStartDate();

        $reminderTime = clone $parentStart;
        $reminderTime->modify("-{$minutesBefore} minutes");

        $data = [
            'source'           => $parent->getSource(),
            'source_id'        => $parent->getSourceId(),
            'source_type'      => CalendarEntry::TYPE_REMINDER,
            'title'            => 'Reminder: ' . $parent->getTitle(),
            'description'      => $parent->getDescription(),
            'start_date'       => $reminderTime->format('Y-m-d H:i:s'),
            'end_date'         => $reminderTime->format('Y-m-d H:i:s'),
            'all_day'          => 'no',
            'location'         => $parent->getLocation(),
            'status'           => 'pending',
            'priority'         => $parent->getPriority(),
            'color'            => $parent->getColor(),
            'private'          => $parent->isPrivate(),
            'reminder'         => true,
            'reminder_minutes' => $minutesBefore,
            'parent_entry_id'  => $parentEntryId,
        ];

        return $this->createEntry($data);
    }

    // ---------------------------------------------------------------
    // v1.9.0 — Occurrence management
    // ---------------------------------------------------------------

    /**
     * Generate occurrence rows for a recurring entry.
     *
     * Parses the entry's recurrence_rule (iCal RRULE format) and inserts
     * one row per occurrence into fa_cal_event_occurrences. Existing
     * occurrence rows with the same entry_id are replaced.
     *
     * @param CalendarEntry $entry  The recurring parent entry.
     * @return int                  Number of occurrences generated.
     * @since 1.9.0
     */
    public function generateOccurrences(CalendarEntry $entry): int
    {
        $rrule = $entry->getRecurrenceRule();
        if (empty($rrule)) {
            return 0;
        }

        // Delete existing occurrences for this entry.
        $this->db->executeUpdate(
            "DELETE FROM " . self::TABLE_OCCURRENCES . " WHERE entry_id = ?",
            [(string) $entry->getId()]
        );

        $parts = $this->parseRRule($rrule);
        if (empty($parts)) {
            return 0;
        }

        $startDate  = $entry->getStartDate();
        $endDate    = $entry->getEndDate() ?: $startDate;
        if (!$startDate) {
            return 0;
        }
        $duration   = $endDate->getTimestamp() - $startDate->getTimestamp();

        $freq       = strtoupper($parts['FREQ'] ?? 'DAILY');
        $interval   = max(1, (int) ($parts['INTERVAL'] ?? 1));
        $count      = isset($parts['COUNT']) ? (int) $parts['COUNT'] : null;
        $until      = isset($parts['UNTIL']) ? new DateTime($parts['UNTIL']) : null;

        // Generate up to 1000 occurrences to prevent runaway generation.
        $maxCount  = $count !== null ? $count : 1000;
        if ($maxCount > 1000) {
            $maxCount = 1000;
        }

        $occurrences = [];
        $current     = clone $startDate;
        $idx         = 0;

        while ($idx < $maxCount) {
            $occEnd = (clone $current)->setTimestamp($current->getTimestamp() + $duration);

            $occurrences[] = [
                'entry_id'      => $entry->getId(),
                'recurrence_id' => $idx,
                'start_date'    => $current->format('Y-m-d H:i:s'),
                'end_date'      => $occEnd->format('Y-m-d H:i:s'),
                'status'        => 'active',
                'inactive'      => 0,
            ];

            $idx++;

            // Check termination conditions BEFORE computing next occurrence.
            if ($count !== null && $idx >= $count) {
                break;
            }

            // Advance to next occurrence.
            $this->addInterval($current, $freq, $interval);

            if ($until !== null && $current > $until) {
                break;
            }
        }

        foreach ($occurrences as $occData) {
            $this->db->executeUpdate(
                "INSERT INTO " . self::TABLE_OCCURRENCES . "
                 (entry_id, recurrence_id, start_date, end_date, status, inactive)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    (string) $occData['entry_id'],
                    (string) $occData['recurrence_id'],
                    $occData['start_date'],
                    $occData['end_date'],
                    $occData['status'],
                    '0',
                ]
            );
        }

        return count($occurrences);
    }

    /**
     * Parse an iCal RRULE string into its component parts.
     *
     * @param string $rrule  e.g. "FREQ=WEEKLY;INTERVAL=2;COUNT=10"
     * @return array         e.g. ['FREQ'=>'WEEKLY','INTERVAL'=>'2','COUNT'=>'10']
     * @since 1.9.0
     */
    private function parseRRule(string $rrule): array
    {
        $parts = [];
        $pairs = explode(';', $rrule);
        foreach ($pairs as $pair) {
            $kv = explode('=', $pair, 2);
            if (count($kv) === 2) {
                $parts[strtoupper(trim($kv[0]))] = trim($kv[1]);
            }
        }
        return $parts;
    }

    /**
     * Add a recurrence interval to a DateTime.
     *
     * @param DateTime $dt       The date to advance (modified in place).
     * @param string   $freq     DAILY|WEEKLY|MONTHLY|YEARLY
     * @param int      $interval How many units to add (default 1).
     * @return void
     * @since 1.9.0
     */
    private function addInterval(DateTime $dt, string $freq, int $interval = 1): void
    {
        switch ($freq) {
            case 'DAILY':
                $dt->modify("+{$interval} day");
                break;
            case 'WEEKLY':
                $dt->modify("+{$interval} week");
                break;
            case 'MONTHLY':
                $dt->modify("+{$interval} month");
                break;
            case 'YEARLY':
                $dt->modify("+{$interval} year");
                break;
        }
    }

    /**
     * Get occurrences (active, non-cancelled) for a date range.
     *
     * @param DateTime $start    Range start.
     * @param DateTime $end      Range end.
     * @param array    $filters  Optional filters (entry_id).
     * @return array             Array of CalendarOccurrence arrays.
     * @since 1.9.0
     */
    public function getOccurrencesForDateRange(DateTime $start, DateTime $end, array $filters = []): array
    {
        $sql = "SELECT * FROM " . self::TABLE_OCCURRENCES . "
                WHERE inactive = 0 AND status != 'cancelled'
                  AND start_date < ? AND end_date > ?";
        $params = [
            $end->format('Y-m-d H:i:s'),
            $start->format('Y-m-d H:i:s'),
        ];

        if (!empty($filters['entry_id'])) {
            $sql .= " AND entry_id = ?";
            $params[] = (string) $filters['entry_id'];
        }

        $sql .= " ORDER BY start_date ASC";

        $rows = $this->db->fetchAll($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[] = CalendarOccurrence::fromArray($row);
        }
        return $result;
    }

    /**
     * Get occurrences for specific parent entry IDs in a date range.
     *
     * @param int[]    $parentIds Parent entry IDs.
     * @param DateTime $start     Range start.
     * @param DateTime $end       Range end.
     * @return CalendarOccurrence[]
     * @since 1.9.0
     */
    public function getOccurrencesByParentIds(array $parentIds, DateTime $start, DateTime $end): array
    {
        if (empty($parentIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($parentIds), '?'));
        $sql = "SELECT * FROM " . self::TABLE_OCCURRENCES . "
                WHERE inactive = 0 AND status != 'cancelled'
                  AND entry_id IN ($placeholders)
                  AND start_date < ? AND end_date > ?
                ORDER BY entry_id, recurrence_id ASC";
        $params = array_merge(
            array_map('strval', $parentIds),
            [$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]
        );
        $rows = $this->db->fetchAll($sql, $params);
        $result = [];
        foreach ($rows as $row) {
            $result[] = CalendarOccurrence::fromArray($row);
        }
        return $result;
    }

    /**
     * Cancel (soft-delete) an occurrence.
     *
     * @param int $occurrenceId
     * @return void
     * @since 1.9.0
     */
    public function cancelOccurrence(int $occurrenceId): void
    {
        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_OCCURRENCES . " SET inactive = 1, status = 'cancelled' WHERE id = ?",
            [(string) $occurrenceId]
        );
    }

    /**
     * Cancel all future occurrences for an entry (from a given recurrence_id forward).
     *
     * @param int $entryId       The recurring parent entry ID.
     * @param int $fromRecurrenceId  Cancel from this index onward.
     * @return int               Number of occurrences cancelled.
     * @since 1.9.0
     */
    public function cancelFutureOccurrences(int $entryId, int $fromRecurrenceId): int
    {
        $cancelled = 0;
        $rows = $this->db->fetchAll(
            "SELECT id FROM " . self::TABLE_OCCURRENCES . "
             WHERE entry_id = ? AND recurrence_id >= ? AND inactive = 0",
            [(string) $entryId, (string) $fromRecurrenceId]
        );
        foreach ($rows as $row) {
            $this->cancelOccurrence((int) $row['id']);
            $cancelled++;
        }
        return $cancelled;
    }

    /**
     * Cancel a single occurrence by entry_id and recurrence_id.
     *
     * @param int $entryId       The recurring parent entry ID.
     * @param int $recurrenceId  The 0-based occurrence index to cancel.
     * @return void
     * @since 1.9.0
     */
    public function cancelOccurrenceByRecurrenceId(int $entryId, int $recurrenceId): void
    {
        $this->db->executeUpdate(
            "UPDATE " . self::TABLE_OCCURRENCES . " SET inactive = 1, status = 'cancelled'
             WHERE entry_id = ? AND recurrence_id = ?",
            [(string) $entryId, (string) $recurrenceId]
        );
    }

    /**
     * Truncate a recurring series so only occurrences before the given index
     * remain. The parent's recurrence rule is updated (COUNT set to the given
     * index, UNTIL removed) and occurrences are regenerated.
     *
     * @param int $entryId       The recurring parent entry ID.
     * @param int $recurrenceId  Cutoff index — occurrences at this index and beyond are removed.
     * @return void
     * @since 1.9.0
     */
    public function truncateSeries(int $entryId, int $recurrenceId): void
    {
        $entry = $this->getEntry($entryId);
        $rrule = $entry->getRecurrenceRule();
        if (empty($rrule)) {
            return;
        }

        $parts = $this->parseRRule($rrule);

        $parts['COUNT'] = (string) $recurrenceId;
        unset($parts['UNTIL']);

        $newRrule = [];
        foreach ($parts as $k => $v) {
            $newRrule[] = "{$k}={$v}";
        }
        $entry->setRecurrenceRule(implode(';', $newRrule));
        $this->saveEntry($entry);

        $this->generateOccurrences($entry);
    }

    /**
     * Fork a recurring series at a given occurrence index.
     *
     * 1. Truncates the original parent's recurrence to end before the occurrence.
     * 2. Creates a new entry with the given $newData and a recurrence starting
     *    from the current occurrence.
     *
     * @param int   $parentId      The recurring parent entry ID.
     * @param int   $recurrenceId  The 0-based occurrence index to fork at.
     * @param array $newData       Entry data for the new forked series.
     * @return int                 The new entry's ID.
     * @throws \RuntimeException   If the occurrence is not found.
     * @since 1.9.0
     */
    public function forkSeries(int $parentId, int $recurrenceId, array $newData): int
    {
        $parent = $this->getEntry($parentId);

        $rows = $this->db->fetchAll(
            "SELECT * FROM " . self::TABLE_OCCURRENCES . "
             WHERE entry_id = ? AND recurrence_id = ?",
            [(string) $parentId, (string) $recurrenceId]
        );
        if (empty($rows)) {
            throw new \RuntimeException(
                "Occurrence not found for entry_id={$parentId}, recurrence_id={$recurrenceId}"
            );
        }
        $occurrence = CalendarOccurrence::fromArray($rows[0]);

        $this->truncateSeries($parentId, $recurrenceId);

        $rrule = $parent->getRecurrenceRule();
        $parts = $this->parseRRule($rrule ?? '');
        $originalCount = isset($parts['COUNT']) ? (int) $parts['COUNT'] : null;
        if ($originalCount !== null) {
            $remaining = $originalCount - $recurrenceId;
            $parts['COUNT'] = (string) max(1, $remaining);
        }

        $newRruleParts = [];
        foreach ($parts as $k => $v) {
            $newRruleParts[] = "{$k}={$v}";
        }
        $newRrule = implode(';', $newRruleParts);

        $newData['start_date'] = $occurrence->getStartDate()->format('Y-m-d H:i:s');
        $newData['end_date']   = $occurrence->getEndDate()->format('Y-m-d H:i:s');
        $newData['recurrence_rule'] = $newRrule;
        $newData['source']       = $newData['source']       ?? $parent->getSource();
        $newData['source_type']  = $newData['source_type']  ?? $parent->getSourceType();
        $newData['assigned_to']  = $newData['assigned_to']  ?? $parent->getAssignedTo();
        $newData['user_id']      = $newData['user_id']      ?? $parent->getUserId();

        $newEntry = $this->createEntry($newData);

        return $newEntry->getId();
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
              rsvp_status, permission, is_organizer, is_resource, invited_at,
              individual_status, inactive)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
            [
                $data['entry_id'],
                $data['contact_type'],
                $data['contact_id'],
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['rsvp_status'],
                $data['permission'] ?? CalendarInvitee::PERMISSION_VIEW,
                $data['is_organizer'] ? 1 : 0,
                $data['is_resource']  ? 1 : 0,
                $data['invited_at'],
                $data['individual_status'] ?? '',
            ]
        );

        $realId = $this->db->lastInsertId();
        if ($realId) {
            $invitee->setId((int) $realId);
        }
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