<?php
/**
 * CalendarNotification Entity Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\Entity
 * @since   1.5.0
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Entity;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarNotification;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ksfraser\Calendar\Entity\CalendarNotification
 */
class CalendarNotificationTest extends TestCase
{
    // ---------------------------------------------------------------
    // Type constants
    // ---------------------------------------------------------------

    /**
     * Notification type constants exist with correct values.
     *
     * @since 1.5.0
     */
    public function testTypeConstants(): void
    {
        $this->assertSame('email',   CalendarNotification::TYPE_EMAIL);
        $this->assertSame('in_app',  CalendarNotification::TYPE_IN_APP);
    }

    // ---------------------------------------------------------------
    // Constructor / defaults
    // ---------------------------------------------------------------

    /**
     * Constructor sets entryId, notificationType, minutesBefore and defaults.
     *
     * @since 1.5.0
     */
    public function testConstructorSetsRequiredFields(): void
    {
        $n = new CalendarNotification(10, CalendarNotification::TYPE_EMAIL, 15);

        $this->assertSame(10,                             $n->getEntryId());
        $this->assertSame(CalendarNotification::TYPE_EMAIL, $n->getNotificationType());
        $this->assertSame(15,                             $n->getMinutesBefore());
    }

    /**
     * Constructor defaults: id = null, inviteeId = null,
     * notifyAt = null, sentAt = null, inactive = false, createdAt = DateTime.
     *
     * @since 1.5.0
     */
    public function testConstructorDefaults(): void
    {
        $n = new CalendarNotification(1, CalendarNotification::TYPE_IN_APP, 30);

        $this->assertNull($n->getId());
        $this->assertNull($n->getInviteeId());
        $this->assertNull($n->getNotifyAt());
        $this->assertNull($n->getSentAt());
        $this->assertFalse($n->isInactive());
        $this->assertInstanceOf(DateTime::class, $n->getCreatedAt());
    }

    // ---------------------------------------------------------------
    // Setters are fluent
    // ---------------------------------------------------------------

    /**
     * Fluent setters return the same instance.
     *
     * @since 1.5.0
     */
    public function testFluentSettersReturnSelf(): void
    {
        $n = new CalendarNotification(1, CalendarNotification::TYPE_EMAIL, 10);

        $this->assertSame($n, $n->setId(5));
        $this->assertSame($n, $n->setEntryId(2));
        $this->assertSame($n, $n->setInviteeId(3));
        $this->assertSame($n, $n->setNotificationType(CalendarNotification::TYPE_IN_APP));
        $this->assertSame($n, $n->setMinutesBefore(60));
        $this->assertSame($n, $n->setNotifyAt(new DateTime()));
        $this->assertSame($n, $n->setSentAt(new DateTime()));
        $this->assertSame($n, $n->setInactive(true));
        $this->assertSame($n, $n->setCreatedAt(new DateTime()));
    }

    /**
     * Setter round-trips.
     *
     * @since 1.5.0
     */
    public function testSetterGetterRoundTrip(): void
    {
        $notify = new DateTime('2026-06-01 09:00:00');
        $sent   = new DateTime('2026-06-01 09:00:01');

        $n = new CalendarNotification(5, CalendarNotification::TYPE_EMAIL, 15);
        $n->setId(88)
          ->setEntryId(5)
          ->setInviteeId(7)
          ->setNotificationType(CalendarNotification::TYPE_IN_APP)
          ->setMinutesBefore(30)
          ->setNotifyAt($notify)
          ->setSentAt($sent)
          ->setInactive(false);

        $this->assertSame(88,                              $n->getId());
        $this->assertSame(5,                               $n->getEntryId());
        $this->assertSame(7,                               $n->getInviteeId());
        $this->assertSame(CalendarNotification::TYPE_IN_APP, $n->getNotificationType());
        $this->assertSame(30,                              $n->getMinutesBefore());
        $this->assertSame($notify,                         $n->getNotifyAt());
        $this->assertSame($sent,                           $n->getSentAt());
        $this->assertFalse($n->isInactive());
    }

    // ---------------------------------------------------------------
    // Entry-level vs invitee-level
    // ---------------------------------------------------------------

    /**
     * inviteeId = null means the notification applies entry-wide.
     *
     * @since 1.5.0
     */
    public function testNullInviteeIdIsEntryLevel(): void
    {
        $n = new CalendarNotification(1, CalendarNotification::TYPE_EMAIL, 15);
        $this->assertNull($n->getInviteeId());

        $n->setInviteeId(3);
        $this->assertSame(3, $n->getInviteeId());

        $n->setInviteeId(null);
        $this->assertNull($n->getInviteeId());
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
        $n    = new CalendarNotification(1, CalendarNotification::TYPE_EMAIL, 15);
        $data = $n->toArray();

        $expectedKeys = [
            'id', 'entry_id', 'invitee_id', 'notification_type',
            'minutes_before', 'notify_at', 'sent_at', 'inactive', 'created_at',
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
        $n = new CalendarNotification(9, CalendarNotification::TYPE_IN_APP, 60);
        $n->setId(77)->setInviteeId(4)->setInactive(false);

        $data = $n->toArray();

        $this->assertSame(77,                              $data['id']);
        $this->assertSame(9,                               $data['entry_id']);
        $this->assertSame(4,                               $data['invitee_id']);
        $this->assertSame(CalendarNotification::TYPE_IN_APP, $data['notification_type']);
        $this->assertSame(60,                              $data['minutes_before']);
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
        $original = new CalendarNotification(11, CalendarNotification::TYPE_EMAIL, 20);
        $original->setId(33)->setInviteeId(5)->setInactive(false);

        $restored = CalendarNotification::fromArray($original->toArray());

        $this->assertSame($original->getId(),               $restored->getId());
        $this->assertSame($original->getEntryId(),          $restored->getEntryId());
        $this->assertSame($original->getInviteeId(),        $restored->getInviteeId());
        $this->assertSame($original->getNotificationType(), $restored->getNotificationType());
        $this->assertSame($original->getMinutesBefore(),    $restored->getMinutesBefore());
    }

    /**
     * fromArray() with minimal data uses safe defaults.
     *
     * @since 1.5.0
     */
    public function testFromArrayMinimalData(): void
    {
        $n = CalendarNotification::fromArray([
            'entry_id'          => '4',
            'notification_type' => 'email',
            'minutes_before'    => '10',
        ]);

        $this->assertNull($n->getId());
        $this->assertSame(4,       $n->getEntryId());
        $this->assertSame('email', $n->getNotificationType());
        $this->assertSame(10,      $n->getMinutesBefore());
        $this->assertNull($n->getInviteeId());
        $this->assertFalse($n->isInactive());
    }
}
