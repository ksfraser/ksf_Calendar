<?php
/**
 * CalendarInvitee Entity
 *
 * Represents a single attendee row in fa_cal_invitees.
 * Covers three kinds of participant with a single class:
 *
 *   - Person invitee  (contact_type = user | crm_contact | ad_hoc, is_resource = false)
 *   - Resource booking (contact_type = resource, is_resource = true)
 *
 * Resources auto-accept (rsvp_status = 'accepted') when available; the
 * CalendarService is responsible for setting that before persisting.
 *
 * contact_id stores 0_crm_contacts.id (INT) for person types (user, crm_contact)
 * and fa_resources.id (INT) for resource type; NULL for ad_hoc.
 *
 * @package Ksfraser\Calendar\Entity
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;

class CalendarInvitee
{
    // ---------------------------------------------------------------
    // contact_type constants
    // ---------------------------------------------------------------
    public const TYPE_FA_USER     = 'user';
    public const TYPE_CRM_CONTACT = 'crm_contact';
    public const TYPE_RESOURCE    = 'resource';
    public const TYPE_AD_HOC      = 'ad_hoc';

    // ---------------------------------------------------------------
    // rsvp_status constants
    // ---------------------------------------------------------------
    public const RSVP_PENDING   = 'pending';
    public const RSVP_ACCEPTED  = 'accepted';
    public const RSVP_DECLINED  = 'declined';
    public const RSVP_TENTATIVE = 'tentative';

    /** @var int|null DB primary key; null before first save. */
    private $id;

    /** @var int  FK to fa_cal_entries.id */
    private $entryId;

    /**
     * Discriminator for the kind of contact.
     * One of the TYPE_* constants.
     *
     * @var string
     */
    private $contactType;

    /**
     * Foreign key into the relevant system table via 0_crm_contacts.id:
     *   - user        -> 0_crm_contacts.id (type='user', entity_id = 0_users.id)
     *   - crm_contact -> 0_crm_contacts.id (type='crm_contact')
     *   - resource    -> fa_resources.id  (ksf_FA_Resources)
     *   - ad_hoc      -> NULL
     *
     * @var string|null
     */
    private $contactId;

    /** @var string Display name */
    private $name;

    /** @var string Email address (used for iCal invite delivery) */
    private $email;

    /** @var string Phone number (informational; shown in detail panel) */
    private $phone;

    /**
     * RSVP status.  Resources are auto-set to RSVP_ACCEPTED when available.
     *
     * @var string
     */
    private $rsvpStatus;

    /** @var bool True if this invitee is the meeting/event organiser. */
    private $isOrganizer;

    /**
     * True when contact_type = 'resource'.
     * Drives UI (shown in "Resources" section, not "Attendees") and
     * auto-accept logic in CalendarService.
     *
     * @var bool
     */
    private $isResource;

    /** @var DateTime|null When the invitation was sent. */
    private $invitedAt;

    /** @var DateTime|null When the invitee last changed their RSVP. */
    private $respondedAt;

    /** @var bool Soft-delete flag. */
    private $inactive;

    /**
     * @param int         $entryId     Parent calendar entry id
     * @param string      $contactType One of the TYPE_* constants
     * @param string      $name        Display name
     * @param string      $email       Email address
     * @param string|null $contactId   FK into relevant system table; null for ad-hoc
     * @param string|null $id          DB primary key; null for new records
     *
     * @since 1.1.0
     */
    public function __construct(
        int $entryId,
        string $contactType,
        string $name,
        string $email,
        ?string $contactId = null,
        ?string $id = null
    ) {
        $this->id          = $id !== null ? (int) $id : null;
        $this->entryId     = $entryId;
        $this->contactType = $contactType;
        $this->contactId   = $contactId;
        $this->name        = $name;
        $this->email       = $email;
        $this->phone       = '';
        $this->rsvpStatus  = self::RSVP_PENDING;
        $this->isOrganizer = false;
        $this->isResource  = ($contactType === self::TYPE_RESOURCE);
        $this->invitedAt   = null;
        $this->respondedAt = null;
        $this->inactive    = false;
    }

    // ---------------------------------------------------------------
    // Getters
    // ---------------------------------------------------------------

    /**
     * @return int|null
     * @since 1.1.0
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     * @since 1.1.0
     */
    public function getEntryId(): int
    {
        return $this->entryId;
    }

    /**
     * @return string
     * @since 1.1.0
     */
    public function getContactType(): string
    {
        return $this->contactType;
    }

    /**
     * @return string|null
     * @since 1.1.0
     */
    public function getContactId(): ?string
    {
        return $this->contactId;
    }

    /**
     * @return string
     * @since 1.1.0
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @since 1.1.0
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     * @since 1.1.0
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return string
     * @since 1.1.0
     */
    public function getRsvpStatus(): string
    {
        return $this->rsvpStatus;
    }

    /**
     * @return bool
     * @since 1.1.0
     */
    public function isOrganizer(): bool
    {
        return $this->isOrganizer;
    }

    /**
     * @return bool
     * @since 1.1.0
     */
    public function isResource(): bool
    {
        return $this->isResource;
    }

    /**
     * @return DateTime|null
     * @since 1.1.0
     */
    public function getInvitedAt(): ?DateTime
    {
        return $this->invitedAt;
    }

    /**
     * @return DateTime|null
     * @since 1.1.0
     */
    public function getRespondedAt(): ?DateTime
    {
        return $this->respondedAt;
    }

    /**
     * @return bool
     * @since 1.1.0
     */
    public function isInactive(): bool
    {
        return $this->inactive;
    }

    // ---------------------------------------------------------------
    // Setters (fluent)
    // ---------------------------------------------------------------

    /**
     * @param string $name
     * @return self
     * @since 1.1.0
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $email
     * @return self
     * @since 1.1.0
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $phone
     * @return self
     * @since 1.1.0
     */
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @param string $rsvpStatus One of the RSVP_* constants
     * @return self
     * @since 1.1.0
     */
    public function setRsvpStatus(string $rsvpStatus): self
    {
        $this->rsvpStatus  = $rsvpStatus;
        $this->respondedAt = new DateTime();
        return $this;
    }

    /**
     * @param bool $isOrganizer
     * @return self
     * @since 1.1.0
     */
    public function setIsOrganizer(bool $isOrganizer): self
    {
        $this->isOrganizer = $isOrganizer;
        return $this;
    }

    /**
     * @param DateTime|null $invitedAt
     * @return self
     * @since 1.1.0
     */
    public function setInvitedAt(?DateTime $invitedAt): self
    {
        $this->invitedAt = $invitedAt;
        return $this;
    }

    /**
     * @param bool $inactive
     * @return self
     * @since 1.1.0
     */
    public function setInactive(bool $inactive): self
    {
        $this->inactive = $inactive;
        return $this;
    }

    // ---------------------------------------------------------------
    // Serialisation helpers
    // ---------------------------------------------------------------

    /**
     * Serialise to a plain array suitable for JSON output or DB insert params.
     *
     * @return array
     * @since 1.1.0
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'entry_id'     => $this->entryId,
            'contact_type' => $this->contactType,
            'contact_id'   => $this->contactId,
            'name'         => $this->name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'rsvp_status'  => $this->rsvpStatus,
            'is_organizer' => $this->isOrganizer,
            'is_resource'  => $this->isResource,
            'invited_at'   => ($this->invitedAt   !== null ? $this->invitedAt->format('Y-m-d H:i:s')   : null),
            'responded_at' => ($this->respondedAt !== null ? $this->respondedAt->format('Y-m-d H:i:s') : null),
            'inactive'     => $this->inactive,
        ];
    }

    /**
     * Reconstruct from a DB row or API payload array.
     *
     * @param array $data
     * @return self
     * @since 1.1.0
     */
    public static function fromArray(array $data): self
    {
        $invitee = new self(
            (int) ($data['entry_id']     ?? 0),
            (string) ($data['contact_type'] ?? self::TYPE_AD_HOC),
            (string) ($data['name']         ?? ''),
            (string) ($data['email']        ?? ''),
            isset($data['contact_id']) ? (string) $data['contact_id'] : null,
            isset($data['id'])         ? (string) $data['id']         : null
        );

        $invitee->setPhone((string) ($data['phone'] ?? ''));
        $invitee->rsvpStatus  = (string) ($data['rsvp_status']  ?? self::RSVP_PENDING);
        $invitee->isOrganizer = (bool)   ($data['is_organizer'] ?? false);
        $invitee->isResource  = (bool)   ($data['is_resource']  ?? false);
        $invitee->inactive    = (bool)   ($data['inactive']     ?? false);

        if (!empty($data['invited_at'])) {
            $invitee->invitedAt = new DateTime($data['invited_at']);
        }
        if (!empty($data['responded_at'])) {
            $invitee->respondedAt = new DateTime($data['responded_at']);
        }

        return $invitee;
    }
}
