<?php

namespace Ksfraser\ModulesDAO\Factory;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;
use Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter;
use Ksfraser\ModulesDAO\Db\PdoDbAdapter;
use Ksfraser\ModulesDAO\Db\MysqlDbAdapter;

/**
 * Factory for creating database adapters
 */
class DatabaseAdapterFactory
{
    /**
     * Create a database adapter
     *
     * @param string|null $driver The database driver ('pdo', 'mysql', 'fa')
     * @param string $tablePrefix The table prefix to use
     * @return DbAdapterInterface
     * @throws \InvalidArgumentException
     */
    public static function create(?string $driver = null, string $tablePrefix = '0_'): DbAdapterInterface
    {
        if ($driver === null) {
            $driver = 'fa'; // Default to FrontAccounting
        }

        switch (strtolower($driver)) {
            case 'pdo':
                return new PdoDbAdapter($tablePrefix);
            case 'mysql':
            case 'mysqli':
                return new MysqlDbAdapter($tablePrefix);
            case 'fa':
            case 'frontaccounting':
                return new FrontAccountingDbAdapter($tablePrefix);
            default:
                throw new \InvalidArgumentException("Unknown database driver: {$driver}");
        }
    }
}