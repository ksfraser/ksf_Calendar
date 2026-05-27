<?php
/**
 * CalendarAttachment Entity Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\Entity
 * @since   1.5.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Entity;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarAttachment;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\Calendar\Entity\CalendarAttachment
 */
class CalendarAttachmentTest extends TestCase
{
    // ---------------------------------------------------------------
    // Constructor / defaults
    // ---------------------------------------------------------------

    /**
     * Constructor sets entryId, filename, filePath and applies safe defaults.
     *
     * @since 1.5.0
     */
    public function testConstructorSetsRequiredFields(): void
    {
        $att = new CalendarAttachment(5, 'report.pdf', '/uploads/2026/report.pdf');

        $this->assertSame(5, $att->getEntryId());
        $this->assertSame('report.pdf', $att->getFilename());
        $this->assertSame('/uploads/2026/report.pdf', $att->getFilePath());
    }

    /**
     * Constructor defaults: id = null, fileSize = null, mimeType = null,
     * uploadedBy = null, inactive = false, uploadedAt = DateTime instance.
     *
     * @since 1.5.0
     */
    public function testConstructorDefaults(): void
    {
        $att = new CalendarAttachment(1, 'test.txt', '/tmp/test.txt');

        $this->assertNull($att->getId());
        $this->assertNull($att->getFileSize());
        $this->assertNull($att->getMimeType());
        $this->assertNull($att->getUploadedBy());
        $this->assertFalse($att->isInactive());
        $this->assertInstanceOf(DateTime::class, $att->getUploadedAt());
    }

    // ---------------------------------------------------------------
    // Setters are fluent (return self)
    // ---------------------------------------------------------------

    /**
     * All fluent setters return the same instance.
     *
     * @since 1.5.0
     */
    public function testFluentSettersReturnSelf(): void
    {
        $att = new CalendarAttachment(1, 'a.png', '/a.png');

        $this->assertSame($att, $att->setId(7));
        $this->assertSame($att, $att->setEntryId(2));
        $this->assertSame($att, $att->setFilename('b.png'));
        $this->assertSame($att, $att->setFilePath('/b.png'));
        $this->assertSame($att, $att->setFileSize(1024));
        $this->assertSame($att, $att->setMimeType('image/png'));
        $this->assertSame($att, $att->setUploadedBy('admin'));
        $this->assertSame($att, $att->setUploadedAt(new DateTime()));
        $this->assertSame($att, $att->setInactive(true));
    }

    /**
     * Setter round-trips: values survive set → get.
     *
     * @since 1.5.0
     */
    public function testSetterGetterRoundTrip(): void
    {
        $att = new CalendarAttachment(1, 'x.pdf', '/x.pdf');
        $att->setId(99)
            ->setEntryId(42)
            ->setFilename('doc.pdf')
            ->setFilePath('/docs/doc.pdf')
            ->setFileSize(2048)
            ->setMimeType('application/pdf')
            ->setUploadedBy('kevin')
            ->setInactive(true);

        $this->assertSame(99,                $att->getId());
        $this->assertSame(42,                $att->getEntryId());
        $this->assertSame('doc.pdf',         $att->getFilename());
        $this->assertSame('/docs/doc.pdf',   $att->getFilePath());
        $this->assertSame(2048,              $att->getFileSize());
        $this->assertSame('application/pdf', $att->getMimeType());
        $this->assertSame('kevin',           $att->getUploadedBy());
        $this->assertTrue($att->isInactive());
    }

    // ---------------------------------------------------------------
    // toArray
    // ---------------------------------------------------------------

    /**
     * toArray() contains all expected keys.
     *
     * @since 1.5.0
     */
    public function testToArrayHasAllKeys(): void
    {
        $att  = new CalendarAttachment(3, 'img.jpg', '/img.jpg');
        $data = $att->toArray();

        $expectedKeys = [
            'id', 'entry_id', 'filename', 'file_path',
            'file_size', 'mime_type', 'uploaded_by', 'uploaded_at', 'inactive',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data, "Missing key: $key");
        }
    }

    /**
     * toArray() returns correct values.
     *
     * @since 1.5.0
     */
    public function testToArrayValues(): void
    {
        $att = new CalendarAttachment(7, 'slide.pptx', '/slides/slide.pptx');
        $att->setId(55)
            ->setFileSize(4096)
            ->setMimeType('application/vnd.ms-powerpoint')
            ->setUploadedBy('user1')
            ->setInactive(false);

        $data = $att->toArray();

        $this->assertSame(55,                                    $data['id']);
        $this->assertSame(7,                                     $data['entry_id']);
        $this->assertSame('slide.pptx',                         $data['filename']);
        $this->assertSame('/slides/slide.pptx',                 $data['file_path']);
        $this->assertSame(4096,                                  $data['file_size']);
        $this->assertSame('application/vnd.ms-powerpoint',      $data['mime_type']);
        $this->assertSame('user1',                               $data['uploaded_by']);
        $this->assertFalse($data['inactive']);
    }

    // ---------------------------------------------------------------
    // fromArray
    // ---------------------------------------------------------------

    /**
     * fromArray() round-trips through toArray().
     *
     * @since 1.5.0
     */
    public function testFromArrayRoundTrip(): void
    {
        $original = new CalendarAttachment(8, 'archive.zip', '/zips/archive.zip');
        $original->setId(12)
                 ->setFileSize(102400)
                 ->setMimeType('application/zip')
                 ->setUploadedBy('sysadmin');

        $restored = CalendarAttachment::fromArray($original->toArray());

        $this->assertSame($original->getId(),       $restored->getId());
        $this->assertSame($original->getEntryId(),  $restored->getEntryId());
        $this->assertSame($original->getFilename(), $restored->getFilename());
        $this->assertSame($original->getFilePath(), $restored->getFilePath());
        $this->assertSame($original->getFileSize(), $restored->getFileSize());
        $this->assertSame($original->getMimeType(), $restored->getMimeType());
        $this->assertSame($original->getUploadedBy(), $restored->getUploadedBy());
    }

    /**
     * fromArray() with minimal data uses safe defaults.
     *
     * @since 1.5.0
     */
    public function testFromArrayMinimalData(): void
    {
        $att = CalendarAttachment::fromArray([
            'entry_id' => '3',
            'filename' => 'min.txt',
            'file_path' => '/min.txt',
        ]);

        $this->assertNull($att->getId());
        $this->assertSame(3,        $att->getEntryId());
        $this->assertSame('min.txt',$att->getFilename());
        $this->assertNull($att->getFileSize());
        $this->assertNull($att->getMimeType());
        $this->assertFalse($att->isInactive());
    }
}
