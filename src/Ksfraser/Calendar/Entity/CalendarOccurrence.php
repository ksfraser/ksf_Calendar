<?php

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class CalendarOccurrence
{
    private $id;
    private $entryId;
    private $recurrenceId = 0;
    private $startDate;
    private $endDate;
    private $title;
    private $location;
    private $status = 'active';
    private $needsReview = false;
    private $inactive = false;
    private $createdAt;

    public function __construct(
        int $entryId,
        DateTime $startDate,
        DateTime $endDate
    ) {
        $this->entryId   = $entryId;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getEntryId(): int
    {
        return $this->entryId;
    }

    public function getRecurrenceId(): int
    {
        return $this->recurrenceId;
    }

    public function setRecurrenceId(int $recurrenceId): self
    {
        $this->recurrenceId = $recurrenceId;
        return $this;
    }

    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(DateTime $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(DateTime $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = ['active', 'cancelled', 'modified'];
        $this->status = in_array($status, $allowed, true) ? $status : 'active';
        return $this;
    }

    public function getNeedsReview(): bool
    {
        return $this->needsReview;
    }

    public function setNeedsReview(bool $needsReview): self
    {
        $this->needsReview = $needsReview;
        return $this;
    }

    public function getInactive(): bool
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

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'entry_id'       => $this->entryId,
            'recurrence_id'  => $this->recurrenceId,
            'start_date'     => $this->startDate->format('Y-m-d H:i:s'),
            'end_date'       => $this->endDate->format('Y-m-d H:i:s'),
            'title'          => $this->title,
            'location'       => $this->location,
            'status'         => $this->status,
            'needs_review'   => $this->needsReview ? 1 : 0,
            'inactive'       => $this->inactive ? 1 : 0,
            'created_at'     => $this->createdAt !== null
                ? $this->createdAt->format(DateTimeInterface::ATOM)
                : null,
        ];
    }

    public static function fromArray(array $data): self
    {
        $startDate = $data['start_date'] instanceof DateTime
            ? $data['start_date']
            : new DateTime($data['start_date']);
        $endDate = $data['end_date'] instanceof DateTime
            ? $data['end_date']
            : new DateTime($data['end_date']);

        $occ = new self(
            (int) $data['entry_id'],
            $startDate,
            $endDate
        );

        if (isset($data['id'])) {
            $occ->setId((int) $data['id']);
        }
        if (isset($data['recurrence_id'])) {
            $occ->setRecurrenceId((int) $data['recurrence_id']);
        }
        if (isset($data['title'])) {
            $occ->setTitle($data['title'] !== null ? (string) $data['title'] : null);
        }
        if (isset($data['location'])) {
            $occ->setLocation($data['location'] !== null ? (string) $data['location'] : null);
        }
        if (isset($data['status'])) {
            $occ->setStatus((string) $data['status']);
        }
        if (isset($data['needs_review'])) {
            $occ->setNeedsReview((bool) $data['needs_review']);
        }
        if (isset($data['inactive'])) {
            $occ->setInactive((bool) $data['inactive']);
        }
        if (isset($data['created_at'])) {
            $occ->createdAt = $data['created_at'] instanceof DateTime
                ? $data['created_at']
                : new DateTime($data['created_at']);
        }

        return $occ;
    }
}
