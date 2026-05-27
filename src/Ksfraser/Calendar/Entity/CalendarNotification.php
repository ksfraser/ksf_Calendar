<?php
/**
 * CalendarNotification Entity
 *
 * Represents a notification (email or in-app) scheduled for a calendar entry.
 * May apply to the entire entry (invitee_id = NULL) or to a specific invitee
 * as a per-invitee override.
 *
 * Maps to the fa_cal_notifications DB table.
 *
 * @package Ksfraser\Calendar\Entity
 * @since 1.5.0
 *
 * @UML Ksfraser\Calendar — CalendarNotification
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class CalendarNotification
{
    /** Send notification via email. */
    public const TYPE_EMAIL  = 'email';

    /** Send notification as an in-app alert. */
    public const TYPE_IN_APP = 'in_app';

    /** @var int|null DB auto-increment PK */
    private ?int $id;

    /** @var int FK to fa_cal_entries.id */
    private int $entryId;

    /**
     * FK to fa_cal_invitees.id.
     * NULL = entry-level notification (applies to all invitees).
     *
     * @var int|null
     */
    private ?int $inviteeId;

    /** @var string One of the TYPE_* constants */
    private string $notificationType;

    /** @var int Minutes before start_date to notify */
    private int $minutesBefore;

    /**
     * Computed absolute notification time (start_date − minutesBefore).
     * Stored in DB for efficient scheduled-job queries.
     *
     * @var DateTime|null
     */
    private ?DateTime $notifyAt;

    /** @var DateTime|null When the notification was actually sent */
    private ?DateTime $sentAt;

    /** @var bool Soft-delete flag */
    private bool $inactive;

    /** @var DateTime When this row was created */
    private DateTime $createdAt;

    /**
     * @param int    $entryId          FK to fa_cal_entries.id
     * @param string $notificationType One of the TYPE_* constants
     * @param int    $minutesBefore    Minutes before start_date to fire
     *
     * @since 1.5.0
     */
    public function __construct(int $entryId, string $notificationType, int $minutesBefore)
    {
        $this->id               = null;
        $this->entryId          = $entryId;
        $this->inviteeId        = null;
        $this->notificationType = $notificationType;
        $this->minutesBefore    = $minutesBefore;
        $this->notifyAt         = null;
        $this->sentAt           = null;
        $this->inactive         = false;
        $this->createdAt        = new DateTime();
    }

    // ---------------------------------------------------------------
    // Getters / Setters
    // ---------------------------------------------------------------

    /**
     * @return int|null
     * @since 1.5.0
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return self
     * @since 1.5.0
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     * @since 1.5.0
     */
    public function getEntryId(): int
    {
        return $this->entryId;
    }

    /**
     * @param int $entryId
     * @return self
     * @since 1.5.0
     */
    public function setEntryId(int $entryId): self
    {
        $this->entryId = $entryId;
        return $this;
    }

    /**
     * @return int|null  NULL = entry-level
     * @since 1.5.0
     */
    public function getInviteeId(): ?int
    {
        return $this->inviteeId;
    }

    /**
     * @param int|null $inviteeId  NULL = entry-level notification
     * @return self
     * @since 1.5.0
     */
    public function setInviteeId(?int $inviteeId): self
    {
        $this->inviteeId = $inviteeId;
        return $this;
    }

    /**
     * @return string
     * @since 1.5.0
     */
    public function getNotificationType(): string
    {
        return $this->notificationType;
    }

    /**
     * @param string $notificationType One of the TYPE_* constants
     * @return self
     * @since 1.5.0
     */
    public function setNotificationType(string $notificationType): self
    {
        $this->notificationType = $notificationType;
        return $this;
    }

    /**
     * @return int
     * @since 1.5.0
     */
    public function getMinutesBefore(): int
    {
        return $this->minutesBefore;
    }

    /**
     * @param int $minutesBefore
     * @return self
     * @since 1.5.0
     */
    public function setMinutesBefore(int $minutesBefore): self
    {
        $this->minutesBefore = $minutesBefore;
        return $this;
    }

    /**
     * @return DateTime|null
     * @since 1.5.0
     */
    public function getNotifyAt(): ?DateTime
    {
        return $this->notifyAt;
    }

    /**
     * @param DateTime|null $notifyAt
     * @return self
     * @since 1.5.0
     */
    public function setNotifyAt(?DateTime $notifyAt): self
    {
        $this->notifyAt = $notifyAt;
        return $this;
    }

    /**
     * @return DateTime|null
     * @since 1.5.0
     */
    public function getSentAt(): ?DateTime
    {
        return $this->sentAt;
    }

    /**
     * @param DateTime|null $sentAt
     * @return self
     * @since 1.5.0
     */
    public function setSentAt(?DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    /**
     * @return bool
     * @since 1.5.0
     */
    public function isInactive(): bool
    {
        return $this->inactive;
    }

    /**
     * @param bool $inactive
     * @return self
     * @since 1.5.0
     */
    public function setInactive(bool $inactive): self
    {
        $this->inactive = $inactive;
        return $this;
    }

    /**
     * @return DateTime
     * @since 1.5.0
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return self
     * @since 1.5.0
     */
    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    // ---------------------------------------------------------------
    // Array serialisation
    // ---------------------------------------------------------------

    /**
     * Serialise to plain array (suitable for DB INSERT / JSON response).
     *
     * @return array
     * @since 1.5.0
     */
    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'entry_id'          => $this->entryId,
            'invitee_id'        => $this->inviteeId,
            'notification_type' => $this->notificationType,
            'minutes_before'    => $this->minutesBefore,
            'notify_at'         => $this->notifyAt !== null
                                       ? $this->notifyAt->format(DateTimeInterface::ATOM)
                                       : null,
            'sent_at'           => $this->sentAt !== null
                                       ? $this->sentAt->format(DateTimeInterface::ATOM)
                                       : null,
            'inactive'          => $this->inactive,
            'created_at'        => $this->createdAt->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * Hydrate a CalendarNotification from a plain array (e.g. DB row).
     *
     * @param array $data
     * @return self
     * @since 1.5.0
     */
    public static function fromArray(array $data): self
    {
        $n = new self(
            (int) ($data['entry_id']          ?? 0),
            (string) ($data['notification_type'] ?? self::TYPE_EMAIL),
            (int) ($data['minutes_before']     ?? 15)
        );

        if (isset($data['id']) && $data['id'] !== null) {
            $n->setId((int) $data['id']);
        }

        if (isset($data['invitee_id']) && $data['invitee_id'] !== null) {
            $n->setInviteeId((int) $data['invitee_id']);
        }

        if (isset($data['notify_at']) && $data['notify_at'] !== null) {
            $n->setNotifyAt(new DateTime($data['notify_at']));
        }

        if (isset($data['sent_at']) && $data['sent_at'] !== null) {
            $n->setSentAt(new DateTime($data['sent_at']));
        }

        $n->setInactive((bool) ($data['inactive'] ?? false));

        if (isset($data['created_at']) && $data['created_at'] !== null) {
            $n->setCreatedAt(new DateTime($data['created_at']));
        }

        return $n;
    }
}
