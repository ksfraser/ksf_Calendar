<?php
/**
 * CalendarEntryDTO
 *
 * Data Transfer Object that transforms a CalendarEntry domain entity into the
 * array shapes consumed by FullCalendar (toFullCalendarArray) and plain JSON
 * responses / DB-round-trips (toArray / fromArray).
 *
 * Responsibilities (SRP):
 *   - Shape translation only — no business logic.
 *   - Provide extendedProps for every field that the JavaScript detail panel
 *     or edit modal may read without an additional AJAX round-trip.
 *
 * @package Ksfraser\Calendar\DTO
 * @since   1.0.0
 *
 * @UML class CalendarEntryDTO
 *   +toFullCalendarArray() : array
 *   +toArray() : array
 *   +fromArray(data:array) : self
 *   +fromEntity(entity:CalendarEntry) : self
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\DTO;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarEntry;

class CalendarEntryDTO
{
    /** @var mixed */
    private $id;
    /** @var string */
    private $source;
    /** @var string */
    private $sourceId;
    /** @var string */
    private $sourceType;
    /** @var string */
    private $title;
    /** @var string */
    private $description;
    /** @var string */
    private $startDate;
    /** @var string|null */
    private $endDate;
    /** @var string */
    private $allDay;
    /** @var string */
    private $timezone;
    /** @var string */
    private $location;
    /** @var string */
    private $assignedTo;
    /** @var mixed */
    private $userId;
    /** @var mixed */
    private $customerId;
    /** @var mixed */
    private $projectId;
    /** @var mixed */
    private $taskId;
    /** @var mixed */
    private $contactId;
    /** @var string */
    private $status;
    /** @var string */
    private $priority;
    /** @var string */
    private $category;
    /** @var bool */
    private $reminder;
    /** @var int|null */
    private $reminderMinutes;
    /** @var string */
    private $color;
    /** @var bool */
    private $private;
    /** @var string|null Online meeting / webinar join URL. @since 1.1.0 */
    private $onlineUrl;
    /** @var string|null Dial-in phone number for calls. @since 1.1.0 */
    private $phoneNumber;
    /** @var string|null iCalendar recurrence rule string. */
    private $recurrenceRule;
    /**
     * Call / conference_call direction: 'inbound' or 'outbound'.
     *
     * @var string|null
     * @since 1.3.0
     */
    private $direction;
    /**
     * Conference bridge / webinar meeting number.
     *
     * @var string|null
     * @since 1.3.0
     */
    private $meetingNumber;
    /**
     * Conference bridge / webinar passcode.
     *
     * @var string|null
     * @since 1.3.0
     */
    private $meetingPasscode;
    /** @var bool */
    private $editable;
    /** @var bool */
    private $overdue;
    /** @var bool */
    private $today;
    /** @var string|null */
    private $createdAt;
    /** @var string|null */
    private $updatedAt;
    /**
     * Whether the entry is formally placed on the calendar.
     *
     * @var bool
     * @since 1.4.0
     */
    private $isScheduled;
    /**
     * FK to parent fa_cal_entries.id (recurring instances, reminders, follow-ups).
     *
     * @var int|null
     * @since 1.4.0
     */
    private $parentEntryId;
    /**
     * Guest visibility policy: 'open', 'invitees_only', 'owner_only'.
     *
     * @var string
     * @since 1.4.0
     */
    private $guestPolicy;
    /**
     * Whether time on this entry is billable to the customer.
     *
     * @var bool
     * @since 1.4.0
     */
    private $isBillable;
    /**
     * Hourly billing rate.
     *
     * @var float|null
     * @since 1.4.0
     */
    private $billableRate;
    /**
     * ISO currency code for billing (e.g. 'CAD').
     *
     * @var string|null
     * @since 1.4.0
     */
    private $billableCurrency;
    /**
     * Whether to trigger a FA Sales Order hook on completion.
     *
     * @var bool
     * @since 1.4.0
     */
    private $autoInvoice;
    /**
     * FA Sales Order ID created by auto-invoice.
     *
     * @var string|null
     * @since 1.4.0
     */
    private $salesOrderId;

    /**
     * @param mixed       $id
     * @param string      $source
     * @param string      $sourceId
     * @param string      $sourceType
     * @param string      $title
     * @param string      $description
     * @param string      $startDate
     * @param string|null $endDate
     * @param string      $allDay
     * @param string      $timezone
     * @param string      $location
     * @param string      $assignedTo
     * @param mixed       $userId
     * @param mixed       $customerId
     * @param mixed       $projectId
     * @param mixed       $taskId
     * @param mixed       $contactId
     * @param string      $status
     * @param string      $priority
     * @param string      $category
     * @param bool        $reminder
     * @param int|null    $reminderMinutes
     * @param string      $color
     * @param bool        $private
     * @param string|null $onlineUrl
     * @param string|null $phoneNumber
     * @param string|null $recurrenceRule
     * @param string|null $direction       @since 1.3.0
     * @param string|null $meetingNumber   @since 1.3.0
     * @param string|null $meetingPasscode @since 1.3.0
     * @param bool        $editable
     * @param bool        $overdue
     * @param bool        $today
     * @param string|null $createdAt
     * @param string|null $updatedAt
     * @param bool        $isScheduled      @since 1.4.0
     * @param int|null    $parentEntryId    @since 1.4.0
     * @param string      $guestPolicy      @since 1.4.0
     * @param bool        $isBillable       @since 1.4.0
     * @param float|null  $billableRate     @since 1.4.0
     * @param string|null $billableCurrency @since 1.4.0
     * @param bool        $autoInvoice      @since 1.4.0
     * @param string|null $salesOrderId     @since 1.4.0
     *
     * @since 1.0.0
     */
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
        $onlineUrl = null,
        $phoneNumber = null,
        $recurrenceRule = null,
        $direction = null,
        $meetingNumber = null,
        $meetingPasscode = null,
        $editable = true,
        $overdue = false,
        $today = false,
        $createdAt = null,
        $updatedAt = null,
        $isScheduled = false,
        $parentEntryId = null,
        $guestPolicy = 'open',
        $isBillable = false,
        $billableRate = null,
        $billableCurrency = null,
        $autoInvoice = false,
        $salesOrderId = null
    ) {
        $this->id              = $id;
        $this->source          = $source;
        $this->sourceId        = $sourceId;
        $this->sourceType      = $sourceType;
        $this->title           = $title;
        $this->description     = $description;
        $this->startDate       = $startDate;
        $this->endDate         = $endDate;
        $this->allDay          = $allDay;
        $this->timezone        = $timezone;
        $this->location        = $location;
        $this->assignedTo      = $assignedTo;
        $this->userId          = $userId;
        $this->customerId      = $customerId;
        $this->projectId       = $projectId;
        $this->taskId          = $taskId;
        $this->contactId       = $contactId;
        $this->status          = $status;
        $this->priority        = $priority;
        $this->category        = $category;
        $this->reminder        = $reminder;
        $this->reminderMinutes = $reminderMinutes;
        $this->color           = $color;
        $this->private         = $private;
        $this->onlineUrl       = $onlineUrl;
        $this->phoneNumber     = $phoneNumber;
        $this->recurrenceRule  = $recurrenceRule;
        $this->direction       = $direction;
        $this->meetingNumber   = $meetingNumber;
        $this->meetingPasscode = $meetingPasscode;
        $this->editable        = $editable;
        $this->overdue         = $overdue;
        $this->today           = $today;
        $this->createdAt       = $createdAt;
        $this->updatedAt       = $updatedAt;
        $this->isScheduled     = (bool) $isScheduled;
        $this->parentEntryId   = ($parentEntryId !== null) ? (int) $parentEntryId : null;
        $this->guestPolicy     = $guestPolicy;
        $this->isBillable      = (bool) $isBillable;
        $this->billableRate    = ($billableRate !== null) ? (float) $billableRate : null;
        $this->billableCurrency = $billableCurrency;
        $this->autoInvoice     = (bool) $autoInvoice;
        $this->salesOrderId    = $salesOrderId;
    }

    /**
     * Produce the array shape expected by FullCalendar's events feed.
     *
     * All fields that the JavaScript detail panel or edit modal may read
     * without a follow-up AJAX call are placed in extendedProps so that
     * FullCalendar makes them available on the EventApi object.
     *
     * @return array
     * @since  1.0.0
     */
    public function toFullCalendarArray(): array
    {
        $endStr  = $this->endDate ?: $this->startDate;
        $startTs = $this->startDate ? (int) strtotime($this->startDate) : null;
        $endTs   = $this->endDate   ? (int) strtotime($this->endDate)   : $startTs;

        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'start'      => $this->startDate,
            'end'        => $endStr,
            'start_ts'   => $startTs,
            'end_ts'     => $endTs,
            'allDay'     => $this->allDay === 'yes',
            'color'      => $this->color ?: $this->getDefaultColor(),
            'textColor'  => '#ffffff',
            'source'     => $this->source,
            'sourceType' => $this->sourceType,
            'editable'   => $this->editable,
            'extendedProps' => [
                'source'           => $this->source,
                'source_id'        => $this->sourceId,
                'source_type'      => $this->sourceType,
                'description'      => $this->description,
                'location'         => $this->location,
                'assigned_to'      => $this->assignedTo,
                'customer_id'      => $this->customerId,
                'project_id'       => $this->projectId,
                'task_id'          => $this->taskId,
                'status'           => $this->status,
                'priority'         => $this->priority,
                'overdue'          => $this->overdue,
                'online_url'       => $this->onlineUrl,
                'phone_number'     => $this->phoneNumber,
                'direction'        => $this->direction,
                'meeting_number'   => $this->meetingNumber,
                'meeting_passcode' => $this->meetingPasscode,
                'is_scheduled'     => $this->isScheduled,
                'parent_entry_id'  => $this->parentEntryId,
                'guest_policy'     => $this->guestPolicy,
                'is_billable'      => $this->isBillable,
                'billable_rate'    => $this->billableRate,
                'billable_currency' => $this->billableCurrency,
                'auto_invoice'     => $this->autoInvoice,
                'sales_order_id'   => $this->salesOrderId,
                'recurrence_rule'  => $this->recurrenceRule,
            ],
        ];
    }

    /**
     * Produce a plain key→value array suitable for JSON responses or DB round-trips.
     *
     * @return array
     * @since  1.0.0
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'source'           => $this->source,
            'source_id'        => $this->sourceId,
            'source_type'      => $this->sourceType,
            'title'            => $this->title,
            'description'      => $this->description,
            'start_date'       => $this->startDate,
            'end_date'         => $this->endDate,
            'all_day'          => $this->allDay,
            'timezone'         => $this->timezone,
            'location'         => $this->location,
            'assigned_to'      => $this->assignedTo,
            'user_id'          => $this->userId,
            'customer_id'      => $this->customerId,
            'project_id'       => $this->projectId,
            'task_id'          => $this->taskId,
            'contact_id'       => $this->contactId,
            'status'           => $this->status,
            'priority'         => $this->priority,
            'category'         => $this->category,
            'reminder'         => $this->reminder,
            'reminder_minutes' => $this->reminderMinutes,
            'color'            => $this->color,
            'private'          => $this->private,
            'online_url'       => $this->onlineUrl,
            'phone_number'     => $this->phoneNumber,
            'recurrence_rule'  => $this->recurrenceRule,
            'direction'        => $this->direction,
            'meeting_number'   => $this->meetingNumber,
            'meeting_passcode' => $this->meetingPasscode,
            'editable'         => $this->editable,
            'overdue'          => $this->overdue,
            'today'            => $this->today,
            'created_at'       => $this->createdAt,
            'updated_at'       => $this->updatedAt,
            'is_scheduled'     => $this->isScheduled,
            'parent_entry_id'  => $this->parentEntryId,
            'guest_policy'     => $this->guestPolicy,
            'is_billable'      => $this->isBillable,
            'billable_rate'    => $this->billableRate,
            'billable_currency' => $this->billableCurrency,
            'auto_invoice'     => $this->autoInvoice,
            'sales_order_id'   => $this->salesOrderId,
        ];
    }

    /**
     * Construct from a plain data array (DB row or API payload).
     *
     * @param  array $data
     * @return self
     * @since  1.0.0
     */
    public static function fromArray(array $data): self
    {
        $overdue = false;
        $today   = false;

        if (!empty($data['start_date'])) {
            $today = date('Y-m-d') === substr($data['start_date'], 0, 10);
        }

        if (!empty($data['end_date'])) {
            $end     = new DateTime($data['end_date']);
            $overdue = $end < new DateTime() && ($data['status'] ?? '') !== 'completed';
        }

        return new self(
            isset($data['id']) ? (int) $data['id'] : null,
            $data['source']          ?? '',
            $data['source_id']       ?? '',
            $data['source_type']     ?? 'event',
            $data['title']           ?? '',
            $data['description']     ?? '',
            $data['start_date']      ?? '',
            $data['end_date']        ?? null,
            $data['all_day']         ?? 'no',
            $data['timezone']        ?? date_default_timezone_get(),
            $data['location']        ?? '',
            $data['assigned_to']     ?? '',
            $data['user_id']         ?? null,
            $data['customer_id']     ?? null,
            $data['project_id']      ?? null,
            $data['task_id']         ?? null,
            $data['contact_id']      ?? null,
            $data['status']          ?? 'pending',
            $data['priority']        ?? 'medium',
            $data['category']        ?? '',
            (bool) ($data['reminder']         ?? false),
            isset($data['reminder_minutes'])   ? (int) $data['reminder_minutes'] : null,
            $data['color']           ?? '',
            (bool) ($data['private']          ?? false),
            $data['online_url']      ?? null,
            $data['phone_number']    ?? null,
            $data['recurrence_rule'] ?? null,
            $data['direction']        ?? null,
            $data['meeting_number']   ?? null,
            $data['meeting_passcode'] ?? null,
            !($data['private']       ?? false),
            $overdue,
            $today,
            $data['created_at']      ?? null,
            $data['updated_at']      ?? null,
            (bool) ($data['is_scheduled']    ?? false),
            isset($data['parent_entry_id'])  ? (int) $data['parent_entry_id'] : null,
            $data['guest_policy']            ?? 'open',
            (bool) ($data['is_billable']     ?? false),
            isset($data['billable_rate'])     ? (float) $data['billable_rate'] : null,
            $data['billable_currency']       ?? null,
            (bool) ($data['auto_invoice']    ?? false),
            $data['sales_order_id']          ?? null
        );
    }

    /**
     * Construct from a CalendarEntry domain entity.
     *
     * @param  CalendarEntry $entity
     * @return self
     * @since  1.0.0
     */
    public static function fromEntity(CalendarEntry $entity): self
    {
        $allDay = $entity->getAllDay();
        // Use date-only format for all-day events; use naive (no TZ offset) ISO
        // datetime for timed events.  Emitting format('c') with a UTC offset causes
        // FullCalendar to convert to the browser's local timezone, producing wrong
        // display times (e.g. +2 hours on a UTC+2 client connecting to a UTC server).
        // Naive strings are treated as "wall-clock local" by both FullCalendar and JS
        // Date(), which is the correct behaviour for a single-timezone deployment.
        $dateFormat  = 'Y-m-d';
        $dtFormat    = 'Y-m-d\TH:i:s';
        $startFormat = ($allDay === 'yes') ? $dateFormat : $dtFormat;
        $endFormat   = ($allDay === 'yes') ? $dateFormat : $dtFormat;

        return new self(
            $entity->getId(),
            $entity->getSource(),
            $entity->getSourceId(),
            $entity->getSourceType(),
            $entity->getTitle(),
            $entity->getDescription(),
            ($entity->getStartDate() !== null ? $entity->getStartDate()->format($startFormat) : ''),
            ($entity->getEndDate()   !== null ? $entity->getEndDate()->format($endFormat)     : ''),
            $allDay,
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
            $entity->getOnlineUrl(),
            $entity->getPhoneNumber(),
            $entity->getRecurrenceRule(),
            $entity->getDirection(),
            $entity->getMeetingNumber(),
            $entity->getMeetingPasscode(),
            !$entity->isPrivate(),
            $entity->isOverdue(),
            $entity->isToday(),
            ($entity->getCreatedAt() !== null ? $entity->getCreatedAt()->format('c') : null),
            ($entity->getUpdatedAt() !== null ? $entity->getUpdatedAt()->format('c') : null),
            $entity->isScheduled(),
            $entity->getParentEntryId(),
            $entity->getGuestPolicy(),
            $entity->isBillable(),
            $entity->getBillableRate(),
            $entity->getBillableCurrency(),
            $entity->isAutoInvoice(),
            $entity->getSalesOrderId()
        );
    }

    /**
     * Return a source-based default hex colour when no explicit colour is set.
     *
     * @return string Hex colour string.
     * @since  1.0.0
     */
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
