<?php
/**
 * CalendarDependency Entity
 *
 * Represents a dependency relationship between two calendar entries.
 * Maps to the fa_cal_dependencies DB table.
 *
 * @package Ksfraser\Calendar\Entity
 * @UML Ksfraser\Calendar — CalendarDependency
 * @since 1.4.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class CalendarDependency
{
    /** The dependent entry must start after the prerequisite finishes. */
    public const DEPENDENCY_TYPE_FINISH_TO_START  = 'finish_to_start';

    /** Both entries must start at the same time. */
    public const DEPENDENCY_TYPE_START_TO_START   = 'start_to_start';

    /** Both entries must finish at the same time. */
    public const DEPENDENCY_TYPE_FINISH_TO_FINISH = 'finish_to_finish';

    /** The dependent entry must finish before the prerequisite starts. */
    public const DEPENDENCY_TYPE_START_TO_FINISH  = 'start_to_finish';

    /** @var int|null DB auto-increment PK */
    private ?int $id;

    /** @var int The entry that depends on another entry. */
    private int $entryId;

    /** @var int The entry that must be completed / reached first. */
    private int $dependsOnEntryId;

    /** @var string One of the DEPENDENCY_TYPE_* constants. */
    private string $dependencyType;

    /** @var bool Soft-delete flag. */
    private bool $inactive;

    /** @var DateTime When the relationship was recorded. */
    private DateTime $createdAt;

    /**
     * @param int    $entryId          The dependent entry ID
     * @param int    $dependsOnEntryId The prerequisite entry ID
     * @param string $dependencyType   One of the DEPENDENCY_TYPE_* constants
     *
     * @since 1.4.0
     */
    public function __construct(int $entryId, int $dependsOnEntryId, string $dependencyType)
    {
        $this->id               = null;
        $this->entryId          = $entryId;
        $this->dependsOnEntryId = $dependsOnEntryId;
        $this->dependencyType   = $dependencyType;
        $this->inactive         = false;
        $this->createdAt        = new DateTime();
    }

    // ---------------------------------------------------------------
    // Getters / Setters
    // ---------------------------------------------------------------

    /**
     * @return int|null
     * @since 1.4.0
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return self
     * @since 1.4.0
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     * @since 1.4.0
     */
    public function getEntryId(): int
    {
        return $this->entryId;
    }

    /**
     * @param int $entryId
     * @return self
     * @since 1.4.0
     */
    public function setEntryId(int $entryId): self
    {
        $this->entryId = $entryId;
        return $this;
    }

    /**
     * @return int
     * @since 1.4.0
     */
    public function getDependsOnEntryId(): int
    {
        return $this->dependsOnEntryId;
    }

    /**
     * @param int $dependsOnEntryId
     * @return self
     * @since 1.4.0
     */
    public function setDependsOnEntryId(int $dependsOnEntryId): self
    {
        $this->dependsOnEntryId = $dependsOnEntryId;
        return $this;
    }

    /**
     * @return string
     * @since 1.4.0
     */
    public function getDependencyType(): string
    {
        return $this->dependencyType;
    }

    /**
     * @param string $dependencyType One of the DEPENDENCY_TYPE_* constants
     * @return self
     * @since 1.4.0
     */
    public function setDependencyType(string $dependencyType): self
    {
        $this->dependencyType = $dependencyType;
        return $this;
    }

    /**
     * @return bool
     * @since 1.4.0
     */
    public function isInactive(): bool
    {
        return $this->inactive;
    }

    /**
     * @param bool $inactive
     * @return self
     * @since 1.4.0
     */
    public function setInactive(bool $inactive): self
    {
        $this->inactive = $inactive;
        return $this;
    }

    /**
     * @return DateTime
     * @since 1.4.0
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return self
     * @since 1.4.0
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
     * @since 1.4.0
     */
    public function toArray(): array
    {
        return [
            'id'                  => $this->id,
            'entry_id'            => $this->entryId,
            'depends_on_entry_id' => $this->dependsOnEntryId,
            'dependency_type'     => $this->dependencyType,
            'inactive'            => $this->inactive,
            'created_at'          => $this->createdAt->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * Hydrate a CalendarDependency from a plain array (e.g. DB row).
     *
     * @param array $data
     * @return self
     * @since 1.4.0
     */
    public static function fromArray(array $data): self
    {
        $dep = new self(
            (int) ($data['entry_id']            ?? 0),
            (int) ($data['depends_on_entry_id'] ?? 0),
            (string) ($data['dependency_type']  ?? self::DEPENDENCY_TYPE_FINISH_TO_START)
        );

        if (isset($data['id']) && $data['id'] !== null) {
            $dep->setId((int) $data['id']);
        }

        $dep->setInactive((bool) ($data['inactive'] ?? false));

        if (isset($data['created_at']) && $data['created_at'] !== null) {
            $dep->setCreatedAt(new DateTime($data['created_at']));
        }

        return $dep;
    }
}
