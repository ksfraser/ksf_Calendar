<?php
/**
 * CalendarService Attachment & Notification Tests (v1.5.0)
 *
 * Covers:
 *   addAttachment, removeAttachment, getAttachmentsForEntry,
 *   addNotification, removeNotification, getNotificationsForEntry,
 *   updateNotification
 *
 * PHP 7.4+ compatible — no PHP 8+ syntax.
 *
 * @package Ksfraser\Calendar\Tests\Unit
 * @since   1.5.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ksfraser\Calendar\Service\CalendarService;
use Ksfraser\Calendar\Entity\CalendarAttachment;
use Ksfraser\Calendar\Entity\CalendarNotification;
use Ksfraser\Calendar\Contract\DatabaseAdapterInterface;
use Ksfraser\Calendar\Exception\CalendarException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * @covers \Ksfraser\Calendar\Service\CalendarService
 */
class CalendarServiceAttachmentNotificationTest extends TestCase
{
    /** @var DatabaseAdapterInterface|MockObject */
    private $db;

    /** @var EventDispatcherInterface|MockObject */
    private $events;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var CalendarService */
    private $service;

    protected function setUp(): void
    {
        $this->db     = $this->createMock(DatabaseAdapterInterface::class);
        $this->events = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new CalendarService($this->db, $this->events, $this->logger);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /** Minimal entry row so getEntry() succeeds. */
    private function makeEntryRow(array $override = []): array
    {
        return array_merge([
            'id'          => '5',
            'source'      => 'user',
            'source_id'   => '5',
            'source_type' => 'event',
            'title'       => 'Entry',
            'description' => '',
            'start_date'  => '2026-06-01 10:00:00',
            'end_date'    => '2026-06-01 11:00:00',
            'all_day'     => 'no',
            'timezone'    => 'UTC',
            'location'    => '',
            'status'      => 'pending',
            'priority'    => 'medium',
            'color'       => '',
            'private'     => '0',
            'inactive'    => '0',
        ], $override);
    }

    /** Minimal attachment row as returned by fetchAssoc / fetchAll. */
    private function makeAttachmentRow(array $override = []): array
    {
        return array_merge([
            'id'          => '1',
            'entry_id'    => '5',
            'filename'    => 'doc.pdf',
            'file_path'   => '/uploads/doc.pdf',
            'file_size'   => '1024',
            'mime_type'   => 'application/pdf',
            'uploaded_by' => 'admin',
            'uploaded_at' => '2026-06-01 09:00:00',
            'inactive'    => '0',
        ], $override);
    }

    /** Minimal notification row as returned by fetchAssoc / fetchAll. */
    private function makeNotificationRow(array $override = []): array
    {
        return array_merge([
            'id'                => '1',
            'entry_id'          => '5',
            'invitee_id'        => null,
            'notification_type' => 'email',
            'minutes_before'    => '15',
            'notify_at'         => null,
            'sent_at'           => null,
            'inactive'          => '0',
            'created_at'        => '2026-06-01 08:00:00',
        ], $override);
    }

    // ---------------------------------------------------------------
    // addAttachment
    // ---------------------------------------------------------------

    /**
     * addAttachment() returns a CalendarAttachment with DB-assigned id.
     *
     * @since 1.5.0
     */
    public function testAddAttachmentReturnsEntityWithId(): void
    {
        $this->db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn($this->makeEntryRow());

        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('77');

        $att = $this->service->addAttachment(5, [
            'filename'  => 'report.pdf',
            'file_path' => '/uploads/report.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->assertInstanceOf(CalendarAttachment::class, $att);
        $this->assertSame(77, $att->getId());
        $this->assertSame(5,  $att->getEntryId());
    }

    /**
     * addAttachment() throws CalendarException when the entry does not exist.
     *
     * @since 1.5.0
     */
    public function testAddAttachmentThrowsWhenEntryNotFound(): void
    {
        $this->db->method('fetchAssoc')->willReturn(null);

        $this->expectException(CalendarException::class);

        $this->service->addAttachment(999, ['filename' => 'x.pdf', 'file_path' => '/x.pdf']);
    }

    // ---------------------------------------------------------------
    // removeAttachment
    // ---------------------------------------------------------------

    /**
     * removeAttachment() soft-deletes the row without exception.
     *
     * @since 1.5.0
     */
    public function testRemoveAttachmentSucceeds(): void
    {
        $this->db->method('executeUpdate')->willReturn(1);

        // No exception = pass
        $this->service->removeAttachment(1);
        $this->addToAssertionCount(1);
    }

    /**
     * removeAttachment() throws CalendarException when the row does not exist.
     *
     * @since 1.5.0
     */
    public function testRemoveAttachmentThrowsWhenNotFound(): void
    {
        $this->db->method('executeUpdate')->willReturn(0);

        $this->expectException(CalendarException::class);

        $this->service->removeAttachment(999);
    }

    // ---------------------------------------------------------------
    // getAttachmentsForEntry
    // ---------------------------------------------------------------

    /**
     * getAttachmentsForEntry() returns an array of CalendarAttachment objects.
     *
     * @since 1.5.0
     */
    public function testGetAttachmentsForEntryReturnsArray(): void
    {
        $this->db->method('fetchAll')
            ->willReturn([$this->makeAttachmentRow(), $this->makeAttachmentRow(['id' => '2'])]);

        $result = $this->service->getAttachmentsForEntry(5);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(CalendarAttachment::class, $result);
    }

    /**
     * getAttachmentsForEntry() returns empty array when there are no attachments.
     *
     * @since 1.5.0
     */
    public function testGetAttachmentsForEntryEmpty(): void
    {
        $this->db->method('fetchAll')->willReturn([]);

        $result = $this->service->getAttachmentsForEntry(99);

        $this->assertSame([], $result);
    }

    // ---------------------------------------------------------------
    // addNotification
    // ---------------------------------------------------------------

    /**
     * addNotification() returns a CalendarNotification with DB-assigned id.
     *
     * @since 1.5.0
     */
    public function testAddNotificationReturnsEntityWithId(): void
    {
        $this->db->expects($this->once())
            ->method('fetchAssoc')
            ->willReturn($this->makeEntryRow());

        $this->db->method('executeUpdate')->willReturn(1);
        $this->db->method('lastInsertId')->willReturn('88');

        $notif = $this->service->addNotification(5, [
            'notification_type' => 'email',
            'minutes_before'    => 30,
        ]);

        $this->assertInstanceOf(CalendarNotification::class, $notif);
        $this->assertSame(88, $notif->getId());
        $this->assertSame(5,  $notif->getEntryId());
    }

    /**
     * addNotification() throws CalendarException when the entry does not exist.
     *
     * @since 1.5.0
     */
    public function testAddNotificationThrowsWhenEntryNotFound(): void
    {
        $this->db->method('fetchAssoc')->willReturn(null);

        $this->expectException(CalendarException::class);

        $this->service->addNotification(999, [
            'notification_type' => 'email',
            'minutes_before'    => 15,
        ]);
    }

    // ---------------------------------------------------------------
    // removeNotification
    // ---------------------------------------------------------------

    /**
     * removeNotification() soft-deletes the row without exception.
     *
     * @since 1.5.0
     */
    public function testRemoveNotificationSucceeds(): void
    {
        $this->db->method('executeUpdate')->willReturn(1);

        $this->service->removeNotification(1);
        $this->addToAssertionCount(1);
    }

    /**
     * removeNotification() throws CalendarException when the row does not exist.
     *
     * @since 1.5.0
     */
    public function testRemoveNotificationThrowsWhenNotFound(): void
    {
        $this->db->method('executeUpdate')->willReturn(0);

        $this->expectException(CalendarException::class);

        $this->service->removeNotification(999);
    }

    // ---------------------------------------------------------------
    // getNotificationsForEntry
    // ---------------------------------------------------------------

    /**
     * getNotificationsForEntry() returns an array of CalendarNotification objects.
     *
     * @since 1.5.0
     */
    public function testGetNotificationsForEntryReturnsArray(): void
    {
        $this->db->method('fetchAll')
            ->willReturn([
                $this->makeNotificationRow(),
                $this->makeNotificationRow(['id' => '2', 'notification_type' => 'in_app']),
            ]);

        $result = $this->service->getNotificationsForEntry(5);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(CalendarNotification::class, $result);
    }

    /**
     * getNotificationsForEntry() returns empty array when there are none.
     *
     * @since 1.5.0
     */
    public function testGetNotificationsForEntryEmpty(): void
    {
        $this->db->method('fetchAll')->willReturn([]);

        $result = $this->service->getNotificationsForEntry(99);

        $this->assertSame([], $result);
    }

    // ---------------------------------------------------------------
    // updateNotification
    // ---------------------------------------------------------------

    /**
     * updateNotification() returns the updated CalendarNotification entity.
     *
     * @since 1.5.0
     */
    public function testUpdateNotificationReturnsUpdatedEntity(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturn($this->makeNotificationRow());

        $this->db->method('executeUpdate')->willReturn(1);

        $notif = $this->service->updateNotification(1, ['minutes_before' => 60]);

        $this->assertInstanceOf(CalendarNotification::class, $notif);
        $this->assertSame(60, $notif->getMinutesBefore());
    }

    /**
     * updateNotification() throws CalendarException when the row does not exist.
     *
     * @since 1.5.0
     */
    public function testUpdateNotificationThrowsWhenNotFound(): void
    {
        $this->db->method('fetchAssoc')->willReturn(null);

        $this->expectException(CalendarException::class);

        $this->service->updateNotification(999, ['minutes_before' => 30]);
    }

    /**
     * updateNotification() accepts notification_type change.
     *
     * @since 1.5.0
     */
    public function testUpdateNotificationChangesType(): void
    {
        $this->db->method('fetchAssoc')
            ->willReturn($this->makeNotificationRow(['notification_type' => 'email']));

        $this->db->method('executeUpdate')->willReturn(1);

        $notif = $this->service->updateNotification(1, [
            'notification_type' => CalendarNotification::TYPE_IN_APP,
        ]);

        $this->assertSame(CalendarNotification::TYPE_IN_APP, $notif->getNotificationType());
    }
}
