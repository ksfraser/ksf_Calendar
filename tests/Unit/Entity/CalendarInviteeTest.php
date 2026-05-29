<?php
/**
 * CalendarInviteeTest
 *
 * @package Ksfraser\Calendar\Tests\Unit\Entity
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Ksfraser\Calendar\Entity\CalendarInvitee;

class CalendarInviteeTest extends TestCase
{
    // ---------------------------------------------------------------
    // Construction
    // ---------------------------------------------------------------

    public function testConstructorSetsRequiredFields(): void
    {
        $invitee = new CalendarInvitee(42, CalendarInvitee::TYPE_FA_USER, 'Alice', 'alice@example.com', '7');

        $this->assertSame(42, $invitee->getEntryId());
        $this->assertSame(CalendarInvitee::TYPE_FA_USER, $invitee->getContactType());
        $this->assertSame('Alice', $invitee->getName());
        $this->assertSame('alice@example.com', $invitee->getEmail());
        $this->assertSame('7', $invitee->getContactId());
        $this->assertNull($invitee->getId());
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $invitee = new CalendarInvitee(1, CalendarInvitee::TYPE_AD_HOC, 'Bob', 'bob@example.com');

        $this->assertSame('', $invitee->getPhone());
        $this->assertSame(CalendarInvitee::RSVP_PENDING, $invitee->getRsvpStatus());
        $this->assertFalse($invitee->isOrganizer());
        $this->assertFalse($invitee->isResource());
        $this->assertFalse($invitee->isInactive());
        $this->assertNull($invitee->getInvitedAt());
        $this->assertNull($invitee->getRespondedAt());
        $this->assertNull($invitee->getContactId());
    }

    // ---------------------------------------------------------------
    // isResource auto-detection
    // ---------------------------------------------------------------

    public function testResourceContactTypeAutoSetsIsResource(): void
    {
        $invitee = new CalendarInvitee(1, CalendarInvitee::TYPE_RESOURCE, 'Boardroom A', '', '5');

        $this->assertTrue($invitee->isResource());
    }

    public function testNonResourceContactTypeDoesNotSetIsResource(): void
    {
        foreach ([
            CalendarInvitee::TYPE_FA_USER,
            CalendarInvitee::TYPE_CRM_CONTACT,
            CalendarInvitee::TYPE_AD_HOC,
        ] as $type) {
            $invitee = new CalendarInvitee(1, $type, 'Name', 'e@example.com');
            $this->assertFalse($invitee->isResource(), "Expected isResource=false for type '$type'");
        }
    }

    // ---------------------------------------------------------------
    // Fluent setters
    // ---------------------------------------------------------------

    public function testSetNameReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $result  = $invitee->setName('Charlie');

        $this->assertSame($invitee, $result);
        $this->assertSame('Charlie', $invitee->getName());
    }

    public function testSetEmailReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $result  = $invitee->setEmail('charlie@example.com');

        $this->assertSame($invitee, $result);
        $this->assertSame('charlie@example.com', $invitee->getEmail());
    }

    public function testSetPhoneReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $result  = $invitee->setPhone('+1-555-1234');

        $this->assertSame($invitee, $result);
        $this->assertSame('+1-555-1234', $invitee->getPhone());
    }

    public function testSetIsOrganizerReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $result  = $invitee->setIsOrganizer(true);

        $this->assertSame($invitee, $result);
        $this->assertTrue($invitee->isOrganizer());
    }

    public function testSetInactiveReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $result  = $invitee->setInactive(true);

        $this->assertSame($invitee, $result);
        $this->assertTrue($invitee->isInactive());
    }

    // ---------------------------------------------------------------
    // RSVP status
    // ---------------------------------------------------------------

    public function testSetRsvpStatusUpdatesStatusAndRespondedAt(): void
    {
        $invitee = $this->makeInvitee();
        $before  = new \DateTime();

        $invitee->setRsvpStatus(CalendarInvitee::RSVP_ACCEPTED);

        $this->assertSame(CalendarInvitee::RSVP_ACCEPTED, $invitee->getRsvpStatus());
        $this->assertNotNull($invitee->getRespondedAt());
        $this->assertGreaterThanOrEqual($before, $invitee->getRespondedAt());
    }

    public function testRsvpConstants(): void
    {
        $this->assertSame('pending',   CalendarInvitee::RSVP_PENDING);
        $this->assertSame('accepted',  CalendarInvitee::RSVP_ACCEPTED);
        $this->assertSame('declined',  CalendarInvitee::RSVP_DECLINED);
        $this->assertSame('tentative', CalendarInvitee::RSVP_TENTATIVE);
    }

    public function testContactTypeConstants(): void
    {
        $this->assertSame('user',         CalendarInvitee::TYPE_FA_USER);
        $this->assertSame('crm_contact', CalendarInvitee::TYPE_CRM_CONTACT);
        $this->assertSame('resource',    CalendarInvitee::TYPE_RESOURCE);
        $this->assertSame('ad_hoc',      CalendarInvitee::TYPE_AD_HOC);
    }

    // ---------------------------------------------------------------
    // invitedAt
    // ---------------------------------------------------------------

    public function testSetInvitedAtReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $dt      = new \DateTime('2026-01-15 10:00:00');
        $result  = $invitee->setInvitedAt($dt);

        $this->assertSame($invitee, $result);
        $this->assertSame($dt, $invitee->getInvitedAt());
    }

    public function testSetInvitedAtAcceptsNull(): void
    {
        $invitee = $this->makeInvitee();
        $invitee->setInvitedAt(new \DateTime());
        $invitee->setInvitedAt(null);

        $this->assertNull($invitee->getInvitedAt());
    }

    // ---------------------------------------------------------------
    // toArray / fromArray round-trip
    // ---------------------------------------------------------------

    public function testToArrayContainsAllFields(): void
    {
        $invitee = new CalendarInvitee(5, CalendarInvitee::TYPE_CRM_CONTACT, 'Diana', 'diana@example.com', '99', '12');
        $invitee->setPhone('555-9999');
        $invitee->setIsOrganizer(true);
        $invitee->setRsvpStatus(CalendarInvitee::RSVP_TENTATIVE);

        $arr = $invitee->toArray();

        $this->assertSame(12,   $arr['id']);
        $this->assertSame(5,    $arr['entry_id']);
        $this->assertSame(CalendarInvitee::TYPE_CRM_CONTACT, $arr['contact_type']);
        $this->assertSame('99', $arr['contact_id']);
        $this->assertSame('Diana', $arr['name']);
        $this->assertSame('diana@example.com', $arr['email']);
        $this->assertSame('555-9999', $arr['phone']);
        $this->assertSame(CalendarInvitee::RSVP_TENTATIVE, $arr['rsvp_status']);
        $this->assertTrue($arr['is_organizer']);
        $this->assertFalse($arr['is_resource']);
        $this->assertFalse($arr['inactive']);
    }

    public function testFromArrayRoundTrip(): void
    {
        $original = new CalendarInvitee(3, CalendarInvitee::TYPE_RESOURCE, 'Room A', 'room@example.com', '77', '8');
        $original->setPhone('n/a');
        $original->setRsvpStatus(CalendarInvitee::RSVP_ACCEPTED);
        $original->setInvitedAt(new \DateTime('2026-03-01 09:00:00'));

        $restored = CalendarInvitee::fromArray($original->toArray());

        $this->assertSame($original->getId(),          $restored->getId());
        $this->assertSame($original->getEntryId(),     $restored->getEntryId());
        $this->assertSame($original->getContactType(), $restored->getContactType());
        $this->assertSame($original->getContactId(),   $restored->getContactId());
        $this->assertSame($original->getName(),        $restored->getName());
        $this->assertSame($original->getEmail(),       $restored->getEmail());
        $this->assertSame($original->getPhone(),       $restored->getPhone());
        $this->assertSame($original->getRsvpStatus(),  $restored->getRsvpStatus());
        $this->assertTrue($restored->isResource());
    }

    public function testFromArraySetsIsResourceFromFlag(): void
    {
        // is_resource flag in DB row should override contact_type inference.
        $invitee = CalendarInvitee::fromArray([
            'entry_id'     => 1,
            'contact_type' => CalendarInvitee::TYPE_AD_HOC,
            'name'         => 'Projector',
            'email'        => '',
            'is_resource'  => 1,
        ]);

        $this->assertTrue($invitee->isResource());
    }

    public function testFromArrayHandlesNullContactId(): void
    {
        $invitee = CalendarInvitee::fromArray([
            'entry_id'     => 1,
            'contact_type' => CalendarInvitee::TYPE_AD_HOC,
            'name'         => 'External Guest',
            'email'        => 'guest@example.com',
        ]);

        $this->assertNull($invitee->getContactId());
    }

    // ---------------------------------------------------------------
    // 1.3.0 — individual_status
    // ---------------------------------------------------------------

    /**
     * @since 1.3.0
     */
    public function testIndividualStatusConstants(): void
    {
        $this->assertSame('planned',      CalendarInvitee::INDIVIDUAL_STATUS_PLANNED);
        $this->assertSame('attended',     CalendarInvitee::INDIVIDUAL_STATUS_ATTENDED);
        $this->assertSame('not_attended', CalendarInvitee::INDIVIDUAL_STATUS_NOT_ATTENDED);
        $this->assertSame('declined',     CalendarInvitee::INDIVIDUAL_STATUS_DECLINED);
    }

    /**
     * individual_status defaults to null on construction.
     *
     * @since 1.3.0
     */
    public function testIndividualStatusDefaultsToNull(): void
    {
        $invitee = $this->makeInvitee();
        $this->assertNull($invitee->getIndividualStatus());
        $this->assertNull($invitee->getIndividualStatusUpdatedAt());
    }

    /**
     * setIndividualStatus returns $this (fluent interface).
     *
     * @since 1.3.0
     */
    public function testSetIndividualStatusReturnsSelf(): void
    {
        $invitee = $this->makeInvitee();
        $result  = $invitee->setIndividualStatus(CalendarInvitee::INDIVIDUAL_STATUS_ATTENDED);

        $this->assertSame($invitee, $result);
        $this->assertSame(CalendarInvitee::INDIVIDUAL_STATUS_ATTENDED, $invitee->getIndividualStatus());
    }

    /**
     * Setting individual_status stamps individual_status_updated_at.
     *
     * @since 1.3.0
     */
    public function testSetIndividualStatusStampsUpdatedAt(): void
    {
        $invitee = $this->makeInvitee();
        $before  = new \DateTime();
        $invitee->setIndividualStatus(CalendarInvitee::INDIVIDUAL_STATUS_PLANNED);

        $this->assertNotNull($invitee->getIndividualStatusUpdatedAt());
        $this->assertGreaterThanOrEqual($before, $invitee->getIndividualStatusUpdatedAt());
    }

    /**
     * Setting individual_status to null clears the timestamp too.
     *
     * @since 1.3.0
     */
    public function testSetIndividualStatusNullClearsTimestamp(): void
    {
        $invitee = $this->makeInvitee();
        $invitee->setIndividualStatus(CalendarInvitee::INDIVIDUAL_STATUS_ATTENDED);
        $invitee->setIndividualStatus(null);

        $this->assertNull($invitee->getIndividualStatus());
        $this->assertNull($invitee->getIndividualStatusUpdatedAt());
    }

    /**
     * toArray() must include individual_status and individual_status_updated_at.
     *
     * @since 1.3.0
     */
    public function testToArrayIncludesIndividualStatus(): void
    {
        $invitee = $this->makeInvitee();
        $invitee->setIndividualStatus(CalendarInvitee::INDIVIDUAL_STATUS_NOT_ATTENDED);

        $arr = $invitee->toArray();

        $this->assertArrayHasKey('individual_status', $arr);
        $this->assertArrayHasKey('individual_status_updated_at', $arr);
        $this->assertSame(CalendarInvitee::INDIVIDUAL_STATUS_NOT_ATTENDED, $arr['individual_status']);
        $this->assertNotNull($arr['individual_status_updated_at']);
    }

    /**
     * toArray() individual_status_updated_at is null when status is null.
     *
     * @since 1.3.0
     */
    public function testToArrayIndividualStatusNullWhenNotSet(): void
    {
        $arr = $this->makeInvitee()->toArray();

        $this->assertNull($arr['individual_status']);
        $this->assertNull($arr['individual_status_updated_at']);
    }

    /**
     * fromArray() round-trip preserves individual_status.
     *
     * @since 1.3.0
     */
    public function testFromArrayRestoresIndividualStatus(): void
    {
        $original = $this->makeInvitee();
        $original->setIndividualStatus(CalendarInvitee::INDIVIDUAL_STATUS_DECLINED);

        $restored = CalendarInvitee::fromArray($original->toArray());

        $this->assertSame(
            CalendarInvitee::INDIVIDUAL_STATUS_DECLINED,
            $restored->getIndividualStatus()
        );
        $this->assertNotNull($restored->getIndividualStatusUpdatedAt());
    }

    /**
     * fromArray() with no individual_status key leaves it null.
     *
     * @since 1.3.0
     */
    public function testFromArrayMissingIndividualStatusIsNull(): void
    {
        $invitee = CalendarInvitee::fromArray([
            'entry_id'     => 1,
            'contact_type' => CalendarInvitee::TYPE_FA_USER,
            'name'         => 'No Status',
            'email'        => 'nostatus@example.com',
        ]);

        $this->assertNull($invitee->getIndividualStatus());
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    private function makeInvitee(): CalendarInvitee
    {
        return new CalendarInvitee(1, CalendarInvitee::TYPE_FA_USER, 'Test User', 'test@example.com', '1');
    }
}
