<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The invitee search fell back to `fa_crm_contacts` - a table that exists in
 * neither FA core nor any schema in this estate - so the CRM-contact branch
 * of the search always threw and was silently skipped.
 *
 * A CRM contact is a person in FA core's contact registry (0_crm_persons) and
 * the relation that links the person to an entity (0_crm_contacts). There is
 * no CRM-owned contact table, so the search now goes through the shared
 * contact resolver.
 */
class ContactSearchTest extends TestCase
{
    private const SERVICE = 'src/ksfraser/Calendar/Service/CalendarService.php';

    private string $moduleDir;

    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }

    public function testTheSearchDoesNotAddressTheNonExistentFaCrmContactsTable(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/' . self::SERVICE);

        $this->assertStringNotContainsString(
            'fa_crm_contacts',
            $contents,
            'the search must not address the fa_crm_contacts table: no schema creates it'
        );
    }

    public function testTheSearchUsesTheSharedContactResolver(): void
    {
        $contents = (string) file_get_contents($this->moduleDir . '/' . self::SERVICE);

        $this->assertStringContainsString(
            'contactResolver()',
            $contents,
            'the CRM-contact branch must go through the shared contact resolver'
        );
        $this->assertStringContainsString(
            '->search($query, $limit)',
            $contents,
            'the resolver\'s search() is how a person is matched by name, email or phone'
        );
    }

    public function testTheSharedResolverPackageIsRequired(): void
    {
        $composer = json_decode(
            (string) file_get_contents($this->moduleDir . '/composer.json'),
            true
        );

        $this->assertArrayHasKey(
            'ksfraser/ksf-fa-common',
            $composer['require'] ?? [],
            'the library needs the shared contact resolver package'
        );
    }
}
