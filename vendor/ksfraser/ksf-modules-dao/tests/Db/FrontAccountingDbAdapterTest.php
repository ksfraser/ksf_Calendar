<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;

/**
 * Test for FrontAccountingDbAdapter
 */
class FrontAccountingDbAdapterTest extends DbAdapterTestCase
{
    protected function createAdapter(): \Ksfraser\ModulesDAO\Db\DbAdapterInterface
    {
        return new FrontAccountingDbAdapter();
    }

    public function testConstructorWithPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
    }

    public function testGetDialect(): void
    {
        $adapter = $this->createAdapter();
        $this->assertEquals('mysql', $adapter->getDialect());
    }

    public function testGetTablePrefix(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    public function testGetTablePrefixWithCustomPrefix(): void
    {
        $adapter = new FrontAccountingDbAdapter('custom_');
        $this->assertEquals('custom_', $adapter->getTablePrefix());
    }

    public function testQueryReturnsArray(): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->query('SELECT * FROM test_table');
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // Mock returns 2 rows
        $this->assertEquals('Test Item', $result[0]['name']);
        $this->assertEquals('Another Item', $result[1]['name']);
    }

    public function testExecuteDoesNotThrow(): void
    {
        $adapter = $this->createAdapter();
        // Should not throw an exception
        $adapter->execute('INSERT INTO test_table (name) VALUES ("test")');
        $this->assertTrue(true); // If we get here, no exception was thrown
    }
}