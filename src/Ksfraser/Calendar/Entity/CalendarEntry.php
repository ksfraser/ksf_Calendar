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
use Ksfraser\Calendar\Entity\CalendarInvitee;

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
    public const TYPE_CONFERENCE_CALL = 'conference_call';
    public const TYPE_WEBINAR = 'webinar';

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
    public const STATUS_CALL_NOT_COMPLETED = 'call_not_completed';
    public const STATUS_CALL_VMAIL_FULL = 'call_vmail_full';
    public const STATUS_CALL_MESSAGE_LEFT = 'call_message_left';

    // Call direction
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    // Guest policy (v1.4.0)
    public const GUEST_POLICY_OPEN          = 'open';
    public const GUEST_POLICY_INVITEES_ONLY = 'invitees_only';
    public const GUEST_POLICY_OWNER_ONLY    = 'owner_only';

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

    // --- Invitee / type-specific fields (added 1.1.0) ---

    /** Online meeting URL shown for TYPE_MEETING entries. */
    private ?string $onlineUrl;

    /** Dial-in phone number shown for TYPE_CALL entries. */
    private ?string $phoneNumber;

    /** Whether to send iCal email invitations to all invitees on save. */
    private bool $sendInvites;

    /**
     * Call direction: 'inbound' or 'outbound'. Relevant for TYPE_CALL and TYPE_CONFERENCE_CALL.
     *
     * @var string|null
     * @since 1.3.0
     */
    private ?string $direction;

    /**
     * Conference bridge / webinar meeting number. Relevant for TYPE_CONFERENCE_CALL and TYPE_WEBINAR.
     *
     * @var string|null
     * @since 1.3.0
     */
    private ?string $meetingNumber;

    /**
     * Conference bridge / webinar passcode. Relevant for TYPE_CONFERENCE_CALL and TYPE_WEBINAR.
     *
     * @var string|null
     * @since 1.3.0
     */
    private ?string $meetingPasscode;

    /**
     * Whether this entry has been formally placed on the calendar (vs. unscheduled placeholder).
     *
     * @var bool
     * @since 1.4.0
     */
    private bool $isScheduled;

    /**
     * FK to fa_cal_entries.id — links recurring instances, reminders, or follow-ups to a parent.
     *
     * @var int|null
     * @since 1.4.0
     */
    private ?int $parentEntryId;

    /**
     * Controls who may view entry details: 'open' | 'invitees_only' | 'owner_only'.
     *
     * @var string
     * @since 1.4.0
     */
    private string $guestPolicy;

    /**
     * Whether time on this entry is billable to the customer.
     *
     * @var bool
     * @since 1.4.0
     */
    private bool $isBillable;

    /**
     * Hourly billing rate (DECIMAL 10,2).
     *
     * @var float|null
     * @since 1.4.0
     */
    private ?float $billableRate;

    /**
     * ISO 4217 currency code for the billable rate (e.g. 'CAD').
     *
     * @var string|null
     * @since 1.4.0
     */
    private ?string $billableCurrency;

    /**
     * When true, a FA Sales Order is created automatically on entry completion.
     *
     * @var bool
     * @since 1.4.0
     */
    private bool $autoInvoice;

    /**
     * FA Sales Order ID created by the auto-invoice hook.
     *
     * @var string|null
     * @since 1.4.0
     */
    private ?string $salesOrderId;

    /**
     * Optional end date for a recurrence series.
     * When set, the series stops generating instances after this date.
     *
     * @var DateTime|null
     * @since 1.6.0
     */
    private ?DateTime $recurrenceEndDate;

    /**
     * Maximum number of occurrences for a recurrence series.
     * When set, the series stops after this many instances.
     *
     * @var int|null
     * @since 1.6.0
     */
    private ?int $recurrenceCount;

    /**
     * JSON delta of fields changed vs the immediate parent in the override chain.
     * Used by edit-scope logic to detect conflicts during series edits.
     *
     * @var string|null
     * @since 1.9.0
     */
    private ?string $delta;

    /**
     * Flagged for user review when a series edit created a potential conflict
     * with existing overrides.
     *
     * @var bool
     * @since 1.9.0
     */
    private bool $needsReview;

    /**
     * In-memory collection of CalendarInvitee objects.
     * Covers both person attendees (contact_type = fa_user|crm_contact|ad_hoc)
     * and resource bookings (contact_type = resource, is_resource = 1).
     * Populated by CalendarService::getEntry() via a JOIN-load.
     *
     * @var CalendarInvitee[]
     */
    private array $invitees;

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
        $this->recurrenceCount = null;
        $this->delta = null;
        $this->needsReview = false;
        $this->inactive = false;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->onlineUrl = null;
        $this->phoneNumber = null;
        $this->sendInvites = false;
        $this->direction = null;
        $this->meetingNumber = null;
        $this->meetingPasscode = null;
        $this->isScheduled = false;
        $this->parentEntryId = null;
        $this->guestPolicy = self::GUEST_POLICY_OPEN;
        $this->isBillable = false;
        $this->billableRate = null;
        $this->billableCurrency = null;
        $this->autoInvoice = false;
        $this->salesOrderId = null;
        $this->recurrenceEndDate = null;
        $this->recurrenceCount   = null;
        $this->invitees = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the entity id.
     *
     * Called by CalendarService::saveEntry() after INSERT to update the
     * in-memory entity with the real DB-assigned auto-increment id.
     *
     * @param int $id
     * @return self
     * @since 1.3.0
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
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

    // --- Invitee / type-specific getters & setters ---

    /**
     * Get the online meeting URL (relevant for TYPE_MEETING).
     *
     * @return string|null
     * @since 1.1.0
     */
    public function getOnlineUrl(): ?string
    {
        return $this->onlineUrl;
    }

    /**
     * Set the online meeting URL.
     *
     * @param string|null $onlineUrl
     * @return self
     * @since 1.1.0
     */
    public function setOnlineUrl(?string $onlineUrl): self
    {
        $this->onlineUrl = $onlineUrl;
        return $this;
    }

    /**
     * Get the dial-in phone number (relevant for TYPE_CALL).
     *
     * @return string|null
     * @since 1.1.0
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * Set the dial-in phone number.
     *
     * @param string|null $phoneNumber
     * @return self
     * @since 1.1.0
     */
    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * Whether to send iCal email invitations to invitees on save.
     *
     * @return bool
     * @since 1.1.0
     */
    public function getSendInvites(): bool
    {
        return $this->sendInvites;
    }

    /**
     * Set whether to send iCal email invitations.
     *
     * @param bool $sendInvites
     * @return self
     * @since 1.1.0
     */
    public function setSendInvites(bool $sendInvites): self
    {
        $this->sendInvites = $sendInvites;
        return $this;
    }

    /**
     * Get the call direction (inbound/outbound).
     * Relevant for TYPE_CALL and TYPE_CONFERENCE_CALL.
     *
     * @return string|null
     * @since 1.3.0
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }

    /**
     * Set the call direction.
     *
     * @param string|null $direction One of DIRECTION_INBOUND or DIRECTION_OUTBOUND
     * @return self
     * @since 1.3.0
     */
    public function setDirection(?string $direction): self
    {
        $this->direction = $direction;
        return $this;
    }

    /**
     * Get the conference bridge / webinar meeting number.
     * Relevant for TYPE_CONFERENCE_CALL and TYPE_WEBINAR.
     *
     * @return string|null
     * @since 1.3.0
     */
    public function getMeetingNumber(): ?string
    {
        return $this->meetingNumber;
    }

    /**
     * Set the conference bridge / webinar meeting number.
     *
     * @param string|null $meetingNumber
     * @return self
     * @since 1.3.0
     */
    public function setMeetingNumber(?string $meetingNumber): self
    {
        $this->meetingNumber = $meetingNumber;
        return $this;
    }

    /**
     * Get the conference bridge / webinar passcode.
     *
     * @return string|null
     * @since 1.3.0
     */
    public function getMeetingPasscode(): ?string
    {
        return $this->meetingPasscode;
    }

    /**
     * Set the conference bridge / webinar passcode.
     *
     * @param string|null $meetingPasscode
     * @return self
     * @since 1.3.0
     */
    public function setMeetingPasscode(?string $meetingPasscode): self
    {
        $this->meetingPasscode = $meetingPasscode;
        return $this;
    }

    // ---------------------------------------------------------------
    // 1.4.0 Scheduling & Ownership getters / setters
    // ---------------------------------------------------------------

    /**
     * Whether this entry has been formally placed on the calendar.
     *
     * @return bool
     * @since 1.4.0
     */
    public function isScheduled(): bool
    {
        return $this->isScheduled;
    }

    /**
     * Set whether this entry is formally scheduled.
     *
     * @param bool $isScheduled
     * @return self
     * @since 1.4.0
     */
    public function setIsScheduled(bool $isScheduled): self
    {
        $this->isScheduled = $isScheduled;
        return $this;
    }

    /**
     * Get the parent entry ID (FK to fa_cal_entries.id).
     *
     * @return int|null
     * @since 1.4.0
     */
    public function getParentEntryId(): ?int
    {
        return $this->parentEntryId;
    }

    /**
     * Set the parent entry ID.
     *
     * @param int|null $parentEntryId
     * @return self
     * @since 1.4.0
     */
    public function setParentEntryId(?int $parentEntryId): self
    {
        $this->parentEntryId = $parentEntryId;
        return $this;
    }

    /**
     * Get the guest policy: 'open' | 'invitees_only' | 'owner_only'.
     *
     * @return string
     * @since 1.4.0
     */
    public function getGuestPolicy(): string
    {
        return $this->guestPolicy;
    }

    /**
     * Set the guest policy.
     *
     * @param string $guestPolicy One of the GUEST_POLICY_* constants
     * @return self
     * @since 1.4.0
     */
    public function setGuestPolicy(string $guestPolicy): self
    {
        $this->guestPolicy = $guestPolicy;
        return $this;
    }

    /**
     * Whether time on this entry is billable to the customer.
     *
     * @return bool
     * @since 1.4.0
     */
    public function isBillable(): bool
    {
        return $this->isBillable;
    }

    /**
     * Set whether this entry is billable.
     *
     * @param bool $isBillable
     * @return self
     * @since 1.4.0
     */
    public function setIsBillable(bool $isBillable): self
    {
        $this->isBillable = $isBillable;
        return $this;
    }

    /**
     * Get the hourly billing rate.
     *
     * @return float|null
     * @since 1.4.0
     */
    public function getBillableRate(): ?float
    {
        return $this->billableRate;
    }

    /**
     * Set the hourly billing rate.
     *
     * @param float|null $billableRate
     * @return self
     * @since 1.4.0
     */
    public function setBillableRate(?float $billableRate): self
    {
        $this->billableRate = $billableRate;
        return $this;
    }

    /**
     * Get the ISO 4217 currency code for the billing rate.
     *
     * @return string|null
     * @since 1.4.0
     */
    public function getBillableCurrency(): ?string
    {
        return $this->billableCurrency;
    }

    /**
     * Set the ISO 4217 currency code.
     *
     * @param string|null $billableCurrency
     * @return self
     * @since 1.4.0
     */
    public function setBillableCurrency(?string $billableCurrency): self
    {
        $this->billableCurrency = $billableCurrency;
        return $this;
    }

    /**
     * Whether to auto-create a FA Sales Order on entry completion.
     *
     * @return bool
     * @since 1.4.0
     */
    public function isAutoInvoice(): bool
    {
        return $this->autoInvoice;
    }

    /**
     * Set whether auto-invoicing is enabled.
     *
     * @param bool $autoInvoice
     * @return self
     * @since 1.4.0
     */
    public function setAutoInvoice(bool $autoInvoice): self
    {
        $this->autoInvoice = $autoInvoice;
        return $this;
    }

    /**
     * Get the FA Sales Order ID created by the auto-invoice hook.
     *
     * @return string|null
     * @since 1.4.0
     */
    public function getSalesOrderId(): ?string
    {
        return $this->salesOrderId;
    }

    /**
     * Set the FA Sales Order ID.
     *
     * @param string|null $salesOrderId
     * @return self
     * @since 1.4.0
     */
    public function setSalesOrderId(?string $salesOrderId): self
    {
        $this->salesOrderId = $salesOrderId;
        return $this;
    }

    // -------------------------------------------------------------------
    // v1.6.0 — recurrenceEndDate
    // -------------------------------------------------------------------

    /**
     * Get the recurrence series end date.
     *
     * @return DateTime|null
     * @since 1.6.0
     */
    public function getRecurrenceEndDate(): ?DateTime
    {
        return $this->recurrenceEndDate;
    }

    /**
     * Set the recurrence series end date.
     *
     * @param DateTime|null $recurrenceEndDate
     * @return self
     * @since 1.6.0
     */
    public function setRecurrenceEndDate(?DateTime $recurrenceEndDate): self
    {
        $this->recurrenceEndDate = $recurrenceEndDate;
        return $this;
    }

    // -------------------------------------------------------------------
    // v1.6.0 — recurrenceCount
    // -------------------------------------------------------------------

    /**
     * Get the maximum number of occurrences for the recurrence series.
     *
     * @return int|null
     * @since 1.6.0
     */
    public function getRecurrenceCount(): ?int
    {
        return $this->recurrenceCount;
    }

    /**
     * Set the maximum number of occurrences for the recurrence series.
     *
     * @param int|null $recurrenceCount
     * @return self
     * @since 1.6.0
     */
    public function setRecurrenceCount(?int $recurrenceCount): self
    {
        $this->recurrenceCount = $recurrenceCount;
        return $this;
    }

    /**
     * Get the JSON delta string.
     *
     * @return string|null
     * @since 1.9.0
     */
    public function getDelta(): ?string
    {
        return $this->delta;
    }

    /**
     * Set the JSON delta string.
     *
     * @param string|null $delta
     * @return self
     * @since 1.9.0
     */
    public function setDelta(?string $delta): self
    {
        $this->delta = $delta;
        return $this;
    }

    /**
     * Check if this entry is flagged for review.
     *
     * @return bool
     * @since 1.9.0
     */
    public function getNeedsReview(): bool
    {
        return $this->needsReview;
    }

    /**
     * Set the needs-review flag.
     *
     * @param bool $needsReview
     * @return self
     * @since 1.9.0
     */
    public function setNeedsReview(bool $needsReview): self
    {
        $this->needsReview = $needsReview;
        return $this;
    }

    /**
     * Get the in-memory invitee collection.
     *
     * @return CalendarInvitee[]
     * @since 1.1.0
     */
    public function getInvitees(): array
    {
        return $this->invitees;
    }

    /**
     * Replace the entire invitee collection (used by CalendarService after DB load).
     *
     * @param CalendarInvitee[] $invitees
     * @return self
     * @since 1.1.0
     */
    public function setInvitees(array $invitees): self
    {
        $this->invitees = $invitees;
        return $this;
    }

    /**
     * Add a single invitee to the in-memory collection.
     *
     * @param CalendarInvitee $invitee
     * @return self
     * @since 1.1.0
     */
    public function addInvitee(CalendarInvitee $invitee): self
    {
        $this->invitees[] = $invitee;
        return $this;
    }

    /**
     * Return only the resource-booking rows from the invitees collection.
     *
     * @return CalendarInvitee[]
     * @since 1.1.0
     */
    public function getResourceBookings(): array
    {
        return array_values(array_filter(
            $this->invitees,
            function (CalendarInvitee $i) {
                return $i->isResource();
            }
        ));
    }

    /**
     * Return only the person-invitee rows from the invitees collection.
     *
     * @return CalendarInvitee[]
     * @since 1.1.0
     */
    public function getPersonInvitees(): array
    {
        return array_values(array_filter(
            $this->invitees,
            function (CalendarInvitee $i) {
                return !$i->isResource();
            }
        ));
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

    /**
     * Determine whether a given (source_type, status) combination represents
     * an open / still-active item.
     *
     * "Open"  = still requires attention, not yet resolved.
     * "Closed" = resolved, completed, cancelled, or no further action needed.
     *
     * This is the single authoritative mapping for the Open/Closed filter used
     * by list views.  No DB admin table is needed because status values are
     * system-defined constants, not user-configurable labels.
     *
     * @param string $sourceType One of the TYPE_* constants
     * @param string $status     One of the STATUS_* constants
     * @return bool  True = open / active; false = closed / done
     *
     * @since 1.3.0
     */
    public static function isOpenStatus(string $sourceType, string $status): bool
    {
        // Statuses that always mean "closed" regardless of type.
        $alwaysClosed = array(
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW,
            self::STATUS_MEETING_HELD,
            self::STATUS_MEETING_NOT_HELD,
            self::STATUS_CALL_HELD,
        );

        // Statuses that always mean "open" regardless of type.
        $alwaysOpen = array(
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_MEETING_PLANNED,
            self::STATUS_MEETING_RESCHEDULED,
            self::STATUS_CALL_PLANNED,
            self::STATUS_CALL_RNA,           // Ring no answer — needs retry
            self::STATUS_CALL_RNA_FOLLOWUP,  // Waiting to follow up
            self::STATUS_CALL_VMAIL_FOLLOWUP,// Waiting for callback
            self::STATUS_CALL_NOT_COMPLETED, // Needs retry
            self::STATUS_CALL_VMAIL_FULL,    // Needs retry
            self::STATUS_CALL_MESSAGE_LEFT,  // Waiting for callback
        );

        // Voicemail-left: contextually closed (message delivered, no pending action by caller).
        $closedByType = array(
            self::STATUS_CALL_VMAIL => array(self::TYPE_CALL, self::TYPE_CONFERENCE_CALL),
        );

        if (in_array($status, $alwaysClosed, true)) {
            return false;
        }
        if (in_array($status, $alwaysOpen, true)) {
            return true;
        }
        foreach ($closedByType as $closedStatus => $types) {
            if ($status === $closedStatus && in_array($sourceType, $types, true)) {
                return false;
            }
        }

        // Unknown / custom statuses default to open.
        return true;
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
            'online_url' => $this->onlineUrl,
            'phone_number' => $this->phoneNumber,
            'send_invites' => $this->sendInvites,
            'direction' => $this->direction,
            'meeting_number' => $this->meetingNumber,
            'meeting_passcode' => $this->meetingPasscode,
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
            'is_scheduled' => $this->isScheduled,
            'parent_entry_id' => $this->parentEntryId,
            'guest_policy' => $this->guestPolicy,
            'is_billable' => $this->isBillable,
            'billable_rate' => $this->billableRate,
            'billable_currency' => $this->billableCurrency,
            'auto_invoice' => $this->autoInvoice,
            'sales_order_id' => $this->salesOrderId,
            'recurrence_end_date' => $this->recurrenceEndDate !== null ? $this->recurrenceEndDate->format('Y-m-d H:i:s') : null,
            'recurrence_count'    => $this->recurrenceCount,
            'delta'               => $this->delta,
            'needs_review'        => $this->needsReview ? 1 : 0,
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
        $entry->setOnlineUrl($data['online_url'] ?? null);
        $entry->setPhoneNumber($data['phone_number'] ?? null);
        $entry->setSendInvites((bool) ($data['send_invites'] ?? false));
        $entry->setDirection($data['direction'] ?? null);
        $entry->setMeetingNumber($data['meeting_number'] ?? null);
        $entry->setMeetingPasscode($data['meeting_passcode'] ?? null);
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
            $entry->setReminder((bool) $data['reminder'], isset($data['reminder_minutes']) ? (int) $data['reminder_minutes'] : 15);
        }
        $entry->setColor($data['color'] ?? '');
        $entry->setPrivate((bool) ($data['private'] ?? false));
        $entry->setRecurrenceRule($data['recurrence_rule'] ?? null);
        $entry->setRecurrenceId($data['recurrence_id'] ?? null);
        $entry->setInactive((bool) ($data['inactive'] ?? false));
        $entry->setIsScheduled((bool) ($data['is_scheduled'] ?? false));
        if (isset($data['parent_entry_id']) && $data['parent_entry_id'] !== null) {
            $entry->setParentEntryId((int) $data['parent_entry_id']);
        }
        $entry->setGuestPolicy($data['guest_policy'] ?? self::GUEST_POLICY_OPEN);
        $entry->setIsBillable((bool) ($data['is_billable'] ?? false));
        if (isset($data['billable_rate']) && $data['billable_rate'] !== null) {
            $entry->setBillableRate((float) $data['billable_rate']);
        }
        $entry->setBillableCurrency($data['billable_currency'] ?? null);
        $entry->setAutoInvoice((bool) ($data['auto_invoice'] ?? false));
        $entry->setSalesOrderId($data['sales_order_id'] ?? null);

        if (isset($data['recurrence_end_date'])) {
            $entry->setRecurrenceEndDate(new DateTime($data['recurrence_end_date']));
        }
        $entry->setRecurrenceCount(
            isset($data['recurrence_count']) ? (int) $data['recurrence_count'] : null
        );
        $entry->setDelta($data['delta'] ?? null);
        $entry->setNeedsReview((bool) ($data['needs_review'] ?? false));

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