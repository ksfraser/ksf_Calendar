<?php
/**
 * CalendarSource Entity Test
 *
 * @package Ksfraser\Calendar\Tests\Unit\Entity
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Tests\Unit\Entity;

use Ksfraser\Calendar\Entity\CalendarEntry;
use Ksfraser\Calendar\Entity\CalendarSource;
use PHPUnit\Framework\TestCase;

class CalendarSourceTest extends TestCase
{
    private CalendarSource $source;

    protected function setUp(): void
    {
        $this->source = new CalendarSource(
            name: 'Test Calendar',
            type: CalendarSource::TYPE_INTERNAL,
            source: CalendarEntry::SOURCE_PM
        );
    }

    public function testConstructorSetsRequiredFields(): void
    {
        $this->assertSame('Test Calendar', $this->source->getName());
        $this->assertSame(CalendarSource::TYPE_INTERNAL, $this->source->getType());
        $this->assertSame(CalendarEntry::SOURCE_PM, $this->source->getSource());
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $this->assertSame('', $this->source->getUrl());
        $this->assertNotEmpty($this->source->getColor());
        $this->assertTrue($this->source->isEnabled());
        $this->assertTrue($this->source->shouldShowEvents());
        $this->assertTrue($this->source->shouldShowTasks());
        $this->assertTrue($this->source->shouldShowCalls());
        $this->assertTrue($this->source->shouldShowMeetings());
        $this->assertFalse($this->source->shouldShowClientDates());
        $this->assertFalse($this->source->shouldShowBirthdays());
        $this->assertFalse($this->source->shouldShowAnniversaries());
        $this->assertFalse($this->source->shouldShowRenewals());
        $this->assertTrue($this->source->shouldShowTimeTracking());
        $this->assertSame(CalendarSource::VISIBILITY_PRIVATE, $this->source->getVisibility());
    }

    public function testSetNameReturnsSelf(): void
    {
        $result = $this->source->setName('New Calendar');
        $this->assertSame($this->source, $result);
        $this->assertSame('New Calendar', $this->source->getName());
    }

    public function testSetUrlReturnsSelf(): void
    {
        $result = $this->source->setUrl('https://calendar.example.com/feed');
        $this->assertSame($this->source, $result);
        $this->assertSame('https://calendar.example.com/feed', $this->source->getUrl());
    }

    public function testSetColorReturnsSelf(): void
    {
        $result = $this->source->setColor('#FF5722');
        $this->assertSame($this->source, $result);
        $this->assertSame('#FF5722', $this->source->getColor());
    }

    public function testSetEnabledReturnsSelf(): void
    {
        $result = $this->source->setEnabled(false);
        $this->assertSame($this->source, $result);
        $this->assertFalse($this->source->isEnabled());
    }

    public function testSetVisibilityReturnsSelf(): void
    {
        $result = $this->source->setVisibility(CalendarSource::VISIBILITY_PUBLIC);
        $this->assertSame($this->source, $result);
        $this->assertSame(CalendarSource::VISIBILITY_PUBLIC, $this->source->getVisibility());
    }

    public function testSetAssignedToReturnsSelf(): void
    {
        $result = $this->source->setAssignedTo('user1');
        $this->assertSame($this->source, $result);
        $this->assertSame('user1', $this->source->getAssignedTo());
    }

    public function testSetUserIdReturnsSelf(): void
    {
        $result = $this->source->setUserId('user-123');
        $this->assertSame($this->source, $result);
        $this->assertSame('user-123', $this->source->getUserId());
    }

    public function testSetApiKeyReturnsSelf(): void
    {
        $result = $this->source->setApiKey('secret-key');
        $this->assertSame($this->source, $result);
        $this->assertSame('secret-key', $this->source->getApiKey());
    }

    public function testSetLastSyncReturnsSelf(): void
    {
        $result = $this->source->setLastSync('2024-01-01 10:00:00');
        $this->assertSame($this->source, $result);
        $this->assertSame('2024-01-01 10:00:00', $this->source->getLastSync());
    }

    public function testSetInactiveReturnsSelf(): void
    {
        $result = $this->source->setInactive(true);
        $this->assertSame($this->source, $result);
        $this->assertTrue($this->source->isInactive());
    }

    public function testSetFiltersUpdatesAllFilterFlags(): void
    {
        $filters = [
            'events' => false,
            'tasks' => true,
            'calls' => false,
            'meetings' => false,
            'client_dates' => true,
            'birthdays' => true,
            'anniversaries' => true,
            'renewals' => false,
            'time_tracking' => true,
        ];

        $result = $this->source->setFilters($filters);

        $this->assertSame($this->source, $result);
        $this->assertFalse($this->source->shouldShowEvents());
        $this->assertTrue($this->source->shouldShowTasks());
        $this->assertFalse($this->source->shouldShowCalls());
        $this->assertFalse($this->source->shouldShowMeetings());
        $this->assertTrue($this->source->shouldShowClientDates());
        $this->assertTrue($this->source->shouldShowBirthdays());
        $this->assertTrue($this->source->shouldShowAnniversaries());
        $this->assertFalse($this->source->shouldShowRenewals());
        $this->assertTrue($this->source->shouldShowTimeTracking());
    }

    public function testGetEnabledSourceTypes(): void
    {
        $types = $this->source->getEnabledSourceTypes();

        $this->assertContains(CalendarEntry::TYPE_EVENT, $types);
        $this->assertContains(CalendarEntry::TYPE_TASK, $types);
        $this->assertContains(CalendarEntry::TYPE_CALL, $types);
        $this->assertContains(CalendarEntry::TYPE_MEETING, $types);
    }

    public function testGetEnabledSourceTypesWithBirthdays(): void
    {
        $source = CalendarSource::createClientDatesCalendar('Client Dates');
        $source->setFilters(['birthdays' => true, 'anniversaries' => false, 'renewals' => false]);

        $types = $source->getEnabledSourceTypes();

        $this->assertContains(CalendarEntry::TYPE_BIRTHDAY, $types);
    }

    public function testGetEnabledSourceTypesWithTimeTracking(): void
    {
        $source = CalendarSource::createHRMCalendar('HRM');

        $types = $source->getEnabledSourceTypes();

        $this->assertContains(CalendarEntry::TYPE_TIMETRACKING, $types);
    }

    public function testToArray(): void
    {
        $this->source->setUrl('https://example.com/cal');
        $this->source->setColor('#FF5722');
        $this->source->setVisibility(CalendarSource::VISIBILITY_SHARED);

        $array = $this->source->toArray();

        $this->assertIsArray($array);
        $this->assertSame('Test Calendar', $array['name']);
        $this->assertSame(CalendarSource::TYPE_INTERNAL, $array['type']);
        $this->assertSame(CalendarEntry::SOURCE_PM, $array['source']);
        $this->assertSame('https://example.com/cal', $array['url']);
        $this->assertSame('#FF5722', $array['color']);
        $this->assertTrue($array['enabled']);
        $this->assertSame(CalendarSource::VISIBILITY_SHARED, $array['visibility']);
    }

    public function testFromArray(): void
    {
        $data = [
            'name' => 'Imported Calendar',
            'type' => CalendarSource::TYPE_EXTERNAL,
            'source' => CalendarEntry::SOURCE_ICAL,
            'url' => 'https://example.com/feed.ics',
            'color' => '#FF5722',
            'enabled' => true,
            'show_events' => true,
            'show_tasks' => false,
            'show_calls' => false,
            'show_meetings' => true,
            'visibility' => CalendarSource::VISIBILITY_PUBLIC,
            'assigned_to' => 'user1',
        ];

        $source = CalendarSource::fromArray($data);

        $this->assertSame('Imported Calendar', $source->getName());
        $this->assertSame(CalendarSource::TYPE_EXTERNAL, $source->getType());
        $this->assertSame(CalendarEntry::SOURCE_ICAL, $source->getSource());
        $this->assertSame('https://example.com/feed.ics', $source->getUrl());
        $this->assertSame('#FF5722', $source->getColor());
        $this->assertTrue($source->isEnabled());
        $this->assertTrue($source->shouldShowEvents());
        $this->assertFalse($source->shouldShowTasks());
        $this->assertTrue($source->shouldShowMeetings());
        $this->assertSame(CalendarSource::VISIBILITY_PUBLIC, $source->getVisibility());
    }

    public function testCreatePMCalendar(): void
    {
        $pmSource = CalendarSource::createPMCalendar('Projects');

        $this->assertSame('Projects', $pmSource->getName());
        $this->assertSame(CalendarEntry::SOURCE_PM, $pmSource->getSource());
        $this->assertTrue($pmSource->shouldShowTasks());
        $this->assertTrue($pmSource->shouldShowTimeTracking());
        $this->assertFalse($pmSource->shouldShowEvents());
    }

    public function testCreateCRMTasksCalendar(): void
    {
        $crmSource = CalendarSource::createCRMTasksCalendar('CRM Tasks');

        $this->assertSame('CRM Tasks', $crmSource->getName());
        $this->assertSame(CalendarEntry::SOURCE_CRM, $crmSource->getSource());
        $this->assertTrue($crmSource->shouldShowTasks());
        $this->assertTrue($crmSource->shouldShowCalls());
        $this->assertTrue($crmSource->shouldShowMeetings());
    }

    public function testCreateClientDatesCalendar(): void
    {
        $clientSource = CalendarSource::createClientDatesCalendar('Client Dates');

        $this->assertSame('Client Dates', $clientSource->getName());
        $this->assertSame(CalendarEntry::SOURCE_CLIENT, $clientSource->getSource());
        $this->assertTrue($clientSource->shouldShowClientDates());
        $this->assertTrue($clientSource->shouldShowBirthdays());
        $this->assertTrue($clientSource->shouldShowAnniversaries());
        $this->assertTrue($clientSource->shouldShowRenewals());
    }

    public function testCreateHRMCalendar(): void
    {
        $hrmSource = CalendarSource::createHRMCalendar('Time Tracking');

        $this->assertSame('Time Tracking', $hrmSource->getName());
        $this->assertSame(CalendarEntry::SOURCE_HRM, $hrmSource->getSource());
        $this->assertTrue($hrmSource->shouldShowTimeTracking());
        $this->assertFalse($hrmSource->shouldShowEvents());
    }

    public function testTypeConstants(): void
    {
        $this->assertSame('internal', CalendarSource::TYPE_INTERNAL);
        $this->assertSame('external', CalendarSource::TYPE_EXTERNAL);
        $this->assertSame('ical', CalendarSource::TYPE_ICAL);
        $this->assertSame('google', CalendarSource::TYPE_GOOGLE);
        $this->assertSame('caldav', CalendarSource::TYPE_CALDAV);
    }

    public function testVisibilityConstants(): void
    {
        $this->assertSame('private', CalendarSource::VISIBILITY_PRIVATE);
        $this->assertSame('shared', CalendarSource::VISIBILITY_SHARED);
        $this->assertSame('public', CalendarSource::VISIBILITY_PUBLIC);
    }
}