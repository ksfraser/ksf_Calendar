<?php

namespace Ksfraser\ModulesDAO\Test\Db;

use Ksfraser\ModulesDAO\Factory\DatabaseAdapterFactory;
use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\ModulesDAO\Db\PdoDbAdapter;
use Ksfraser\ModulesDAO\Db\MysqlDbAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Test for DatabaseAdapterFactory
 */
class DatabaseAdapterFactoryTest extends TestCase
{
    public function testCreateFaAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreateFaAdapterWithPrefix(): void
    {
        $adapter = DatabaseAdapterFactory::create('fa', 'custom_');
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreatePdoAdapter(): void
    {
        $this->markTestSkipped('PDO adapter requires database connection not available in test environment');
    }

    public function testCreateMysqlAdapter(): void
    {
        $this->markTestSkipped('MySQL adapter requires database connection not available in test environment');
    }

    public function testCreateDefaultAdapter(): void
    {
        $adapter = DatabaseAdapterFactory::create();
        $this->assertInstanceOf(FrontAccountingDbAdapter::class, $adapter);
        $this->assertInstanceOf(DbAdapterInterface::class, $adapter);
    }

    public function testCreateUnknownDriverThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown database driver: unknown');
        DatabaseAdapterFactory::create('unknown');
    }
}