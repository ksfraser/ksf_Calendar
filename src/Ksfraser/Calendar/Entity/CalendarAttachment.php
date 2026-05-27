<?php
/**
 * CalendarAttachment Entity
 *
 * Represents a file attached to a calendar entry.
 * Maps to the fa_cal_attachments DB table.
 *
 * @package Ksfraser\Calendar\Entity
 * @since 1.5.0
 *
 * @UML Ksfraser\Calendar — CalendarAttachment
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Entity;

use DateTime;
use DateTimeInterface;

class CalendarAttachment
{
    /** @var int|null DB auto-increment PK */
    private ?int $id;

    /** @var int FK to fa_cal_entries.id */
    private int $entryId;

    /** @var string Original filename */
    private string $filename;

    /** @var string Server file path or URL */
    private string $filePath;

    /** @var int|null File size in bytes */
    private ?int $fileSize;

    /** @var string|null MIME type (e.g. 'application/pdf') */
    private ?string $mimeType;

    /** @var string|null FA user_id who uploaded the file */
    private ?string $uploadedBy;

    /** @var DateTime When the attachment was uploaded */
    private DateTime $uploadedAt;

    /** @var bool Soft-delete flag */
    private bool $inactive;

    /**
     * @param int    $entryId  FK to fa_cal_entries.id
     * @param string $filename Original filename
     * @param string $filePath Server file path or URL
     *
     * @since 1.5.0
     */
    public function __construct(int $entryId, string $filename, string $filePath)
    {
        $this->id         = null;
        $this->entryId    = $entryId;
        $this->filename   = $filename;
        $this->filePath   = $filePath;
        $this->fileSize   = null;
        $this->mimeType   = null;
        $this->uploadedBy = null;
        $this->uploadedAt = new DateTime();
        $this->inactive   = false;
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
     * @return string
     * @since 1.5.0
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return self
     * @since 1.5.0
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string
     * @since 1.5.0
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return self
     * @since 1.5.0
     */
    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return int|null
     * @since 1.5.0
     */
    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    /**
     * @param int|null $fileSize
     * @return self
     * @since 1.5.0
     */
    public function setFileSize(?int $fileSize): self
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * @return string|null
     * @since 1.5.0
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    /**
     * @param string|null $mimeType
     * @return self
     * @since 1.5.0
     */
    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    /**
     * @return string|null
     * @since 1.5.0
     */
    public function getUploadedBy(): ?string
    {
        return $this->uploadedBy;
    }

    /**
     * @param string|null $uploadedBy
     * @return self
     * @since 1.5.0
     */
    public function setUploadedBy(?string $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    /**
     * @return DateTime
     * @since 1.5.0
     */
    public function getUploadedAt(): DateTime
    {
        return $this->uploadedAt;
    }

    /**
     * @param DateTime $uploadedAt
     * @return self
     * @since 1.5.0
     */
    public function setUploadedAt(DateTime $uploadedAt): self
    {
        $this->uploadedAt = $uploadedAt;
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
            'id'          => $this->id,
            'entry_id'    => $this->entryId,
            'filename'    => $this->filename,
            'file_path'   => $this->filePath,
            'file_size'   => $this->fileSize,
            'mime_type'   => $this->mimeType,
            'uploaded_by' => $this->uploadedBy,
            'uploaded_at' => $this->uploadedAt->format(DateTimeInterface::ATOM),
            'inactive'    => $this->inactive,
        ];
    }

    /**
     * Hydrate a CalendarAttachment from a plain array (e.g. DB row).
     *
     * @param array $data
     * @return self
     * @since 1.5.0
     */
    public static function fromArray(array $data): self
    {
        $att = new self(
            (int) ($data['entry_id'] ?? 0),
            (string) ($data['filename'] ?? ''),
            (string) ($data['file_path'] ?? '')
        );

        if (isset($data['id']) && $data['id'] !== null) {
            $att->setId((int) $data['id']);
        }

        if (isset($data['file_size']) && $data['file_size'] !== null) {
            $att->setFileSize((int) $data['file_size']);
        }

        $att->setMimeType(isset($data['mime_type']) ? (string) $data['mime_type'] : null);
        $att->setUploadedBy(isset($data['uploaded_by']) && $data['uploaded_by'] !== null
            ? (string) $data['uploaded_by']
            : null
        );

        if (isset($data['uploaded_at']) && $data['uploaded_at'] !== null) {
            $att->setUploadedAt(new DateTime($data['uploaded_at']));
        }

        $att->setInactive((bool) ($data['inactive'] ?? false));

        return $att;
    }
}
