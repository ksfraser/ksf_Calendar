<?php
/**
 * CalendarDependency Entity Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\Entity
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Entity;

use DateTime;
use Ksfraser\Calendar\Entity\CalendarDependency;
use PHPUnit\Framework\TestCase;

class CalendarDependencyTest extends TestCase
{
    // ---------------------------------------------------------------
    // Dependency type constants
    // ---------------------------------------------------------------

    /**
     * All four dependency type constants exist with correct values.
     *
     * @since 1.4.0
     */
    public function testDependencyTypeConstants(): void
    {
        $this->assertSame('finish_to_start',   CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $this->assertSame('start_to_start',    CalendarDependency::DEPENDENCY_TYPE_START_TO_START);
        $this->assertSame('finish_to_finish',  CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_FINISH);
        $this->assertSame('start_to_finish',   CalendarDependency::DEPENDENCY_TYPE_START_TO_FINISH);
    }

    // ---------------------------------------------------------------
    // Constructor / defaults
    // ---------------------------------------------------------------

    /**
     * Constructor sets entryId, dependsOnEntryId, dependencyType and
     * applies safe defaults for the remaining fields.
     *
     * @since 1.4.0
     */
    public function testConstructorSetsRequiredFields(): void
    {
        $dep = new CalendarDependency(
            10,
            20,
            CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START
        );

        $this->assertSame(10, $dep->getEntryId());
        $this->assertSame(20, $dep->getDependsOnEntryId());
        $this->assertSame(CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START, $dep->getDependencyType());
    }

    /**
     * Constructor defaults: id = null, inactive = false, createdAt = DateTime instance.
     *
     * @since 1.4.0
     */
    public function testConstructorDefaults(): void
    {
        $dep = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_START_TO_START);

        $this->assertNull($dep->getId());
        $this->assertFalse($dep->isInactive());
        $this->assertInstanceOf(DateTime::class, $dep->getCreatedAt());
    }

    // ---------------------------------------------------------------
    // Setters / getters (fluent)
    // ---------------------------------------------------------------

    /**
     * setId() is fluent and stores the value.
     *
     * @since 1.4.0
     */
    public function testSetIdReturnsSelf(): void
    {
        $dep    = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $result = $dep->setId(99);
        $this->assertSame($dep, $result);
        $this->assertSame(99, $dep->getId());
    }

    /**
     * setEntryId() is fluent.
     *
     * @since 1.4.0
     */
    public function testSetEntryIdReturnsSelf(): void
    {
        $dep    = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $result = $dep->setEntryId(55);
        $this->assertSame($dep, $result);
        $this->assertSame(55, $dep->getEntryId());
    }

    /**
     * setDependsOnEntryId() is fluent.
     *
     * @since 1.4.0
     */
    public function testSetDependsOnEntryIdReturnsSelf(): void
    {
        $dep    = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $result = $dep->setDependsOnEntryId(77);
        $this->assertSame($dep, $result);
        $this->assertSame(77, $dep->getDependsOnEntryId());
    }

    /**
     * setDependencyType() is fluent.
     *
     * @since 1.4.0
     */
    public function testSetDependencyTypeReturnsSelf(): void
    {
        $dep    = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $result = $dep->setDependencyType(CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_FINISH);
        $this->assertSame($dep, $result);
        $this->assertSame(CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_FINISH, $dep->getDependencyType());
    }

    /**
     * setInactive() is fluent.
     *
     * @since 1.4.0
     */
    public function testSetInactiveReturnsSelf(): void
    {
        $dep    = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $result = $dep->setInactive(true);
        $this->assertSame($dep, $result);
        $this->assertTrue($dep->isInactive());
    }

    /**
     * setCreatedAt() is fluent and stores a DateTime.
     *
     * @since 1.4.0
     */
    public function testSetCreatedAtReturnsSelf(): void
    {
        $dep = new CalendarDependency(1, 2, CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START);
        $dt  = new DateTime('2026-01-15 10:00:00');
        $result = $dep->setCreatedAt($dt);
        $this->assertSame($dep, $result);
        $this->assertSame($dt, $dep->getCreatedAt());
    }

    // ---------------------------------------------------------------
    // toArray()
    // ---------------------------------------------------------------

    /**
     * toArray() returns all fields with correct keys.
     *
     * @since 1.4.0
     */
    public function testToArray(): void
    {
        $dep = new CalendarDependency(
            10,
            20,
            CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START
        );
        $dep->setId(5);
        $dep->setInactive(false);

        $arr = $dep->toArray();

        $this->assertIsArray($arr);
        $this->assertSame(5,  $arr['id']);
        $this->assertSame(10, $arr['entry_id']);
        $this->assertSame(20, $arr['depends_on_entry_id']);
        $this->assertSame(CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_START, $arr['dependency_type']);
        $this->assertFalse($arr['inactive']);
        $this->assertArrayHasKey('created_at', $arr);
    }

    // ---------------------------------------------------------------
    // fromArray()
    // ---------------------------------------------------------------

    /**
     * fromArray() constructs a CalendarDependency with all fields set.
     *
     * @since 1.4.0
     */
    public function testFromArray(): void
    {
        $data = [
            'id'                   => 7,
            'entry_id'             => 100,
            'depends_on_entry_id'  => 200,
            'dependency_type'      => CalendarDependency::DEPENDENCY_TYPE_START_TO_START,
            'inactive'             => 0,
            'created_at'           => '2026-03-01 08:00:00',
        ];

        $dep = CalendarDependency::fromArray($data);

        $this->assertInstanceOf(CalendarDependency::class, $dep);
        $this->assertSame(7,   $dep->getId());
        $this->assertSame(100, $dep->getEntryId());
        $this->assertSame(200, $dep->getDependsOnEntryId());
        $this->assertSame(CalendarDependency::DEPENDENCY_TYPE_START_TO_START, $dep->getDependencyType());
        $this->assertFalse($dep->isInactive());
        $this->assertSame('2026-03-01 08:00:00', $dep->getCreatedAt()->format('Y-m-d H:i:s'));
    }

    /**
     * fromArray() round-trip: toArray() output feeds back into fromArray() correctly.
     *
     * @since 1.4.0
     */
    public function testFromArrayRoundTrip(): void
    {
        $original = new CalendarDependency(
            30,
            40,
            CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_FINISH
        );
        $original->setId(3);
        $original->setInactive(true);

        $restored = CalendarDependency::fromArray($original->toArray());

        $this->assertSame(3,  $restored->getId());
        $this->assertSame(30, $restored->getEntryId());
        $this->assertSame(40, $restored->getDependsOnEntryId());
        $this->assertSame(CalendarDependency::DEPENDENCY_TYPE_FINISH_TO_FINISH, $restored->getDependencyType());
        $this->assertTrue($restored->isInactive());
    }
}
