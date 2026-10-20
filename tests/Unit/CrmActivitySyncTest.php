<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\Calendar;

use PHPUnit\Framework\TestCase;

/**
 * syncCRMActivities() read `TB_PREF . "fa_crm_communications"`. The CRM
 * module's table is 0_ksf_crm_communications - there is no 0_fa_crm_*
 * table anywhere - so the query failed with MySQL error 1146 and no CRM
 * activity was ever synced into the calendar.
 *
 * The query also filtered on `inactive`, a column the CRM's communications
 * table does not have (its status column is `status`), which would have
 * failed with "Unknown column 'inactive'" even after the table name was
 * corrected.
 */
class CrmActivitySyncTest extends TestCase
{
    private const CRM_TABLE = 'ksf_crm_communications';

    private string $moduleDir;

    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }

    public function testSyncDoesNotAddressTheNonExistentFaCrmCommunicationsTable(): void
    {
        $contents = (string) file_get_contents(
            $this->moduleDir . '/src/ksfraser/Calendar/Service/CalendarService.php'
        );

        $this->assertStringNotContainsString(
            'TB_PREF . "fa_crm_communications',
            $contents,
            'the CRM activity sync must not address 0_fa_crm_communications: that table is created by no schema'
        );
    }

    public function testSyncReadsTheCrmModulesOwnCommunicationsTable(): void
    {
        $contents = (string) file_get_contents(
            $this->moduleDir . '/src/ksfraser/Calendar/Service/CalendarService.php'
        );

        $this->assertStringContainsString(
            'TB_PREF . "' . self::CRM_TABLE,
            $contents,
            'a CRM communication is a CRM-domain record and lives in the CRM module\'s own table'
        );
    }

    public function testSyncDoesNotFilterOnTheNonExistentInactiveColumn(): void
    {
        $contents = (string) file_get_contents(
            $this->moduleDir . '/src/ksfraser/Calendar/Service/CalendarService.php'
        );

        $method = $this->syncCRMActivitiesBody($contents);

        $this->assertStringNotContainsString(
            'inactive = 0',
            $method,
            '0_ksf_crm_communications has no inactive column; the query would fail with "Unknown column"'
        );
    }

    /**
     * The body of syncCRMActivities(), so the assertions stay scoped to it.
     *
     * @param string $contents File contents
     * @return string
     */
    private function syncCRMActivitiesBody(string $contents): string
    {
        $start = strpos($contents, 'public function syncCRMActivities');
        $this->assertNotFalse($start, 'CalendarService must have syncCRMActivities()');

        $end = strpos($contents, 'public function ', $start + 10);
        if ($end === false) {
            $end = strlen($contents);
        }

        return substr($contents, $start, $end - $start);
    }
}
