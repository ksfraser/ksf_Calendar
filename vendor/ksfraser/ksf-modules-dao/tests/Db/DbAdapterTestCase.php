<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test for DbAdapterInterface implementations
 */
abstract class DbAdapterTestCase extends TestCase
{
    abstract protected function createAdapter(): DbAdapterInterface;

    public function testImplementsInterface(): void
    {
        $adapter = $this->createAdapter();
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testGetDialectReturnsString(): void
    {
        $adapter = $this->createAdapter();
        $dialect = $adapter->getDialect();
        $this->assertIsString($dialect);
        $this->assertNotEmpty($dialect);
    }

    public function testGetTablePrefixReturnsString(): void
    {
        $adapter = $this->createAdapter();
        $prefix = $adapter->getTablePrefix();
        $this->assertIsString($prefix);
    }

    public function testEscapeReturnsString(): void
    {
        $adapter = $this->createAdapter();
        $escaped = $adapter->escape("test'value");
        $this->assertIsString($escaped);
    }

    public function testQueryReturnsArray(): void
    {
        $adapter = $this->createAdapter();
        // This will be overridden in concrete tests that can provide actual DB functionality
        $this->markTestSkipped('Query method test must be implemented in concrete test class');
    }

    public function testExecuteDoesNotThrow(): void
    {
        $adapter = $this->createAdapter();
        // This will be overridden in concrete tests that can provide actual DB functionality
        $this->markTestSkipped('Execute method test must be implemented in concrete test class');
    }

    public function testLastInsertIdReturnsIntOrNull(): void
    {
        $adapter = $this->createAdapter();
        $result = $adapter->lastInsertId();
        $this->assertTrue(is_int($result) || is_null($result));
    }
}