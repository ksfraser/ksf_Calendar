<?php

namespace Ksfraser\ModulesDAO\Db;

use Ksfraser\ModulesDAO\Db\DbAdapterInterface;

/**
 * Database adapter for FrontAccounting
 */
class FrontAccountingDbAdapter implements DbAdapterInterface
{
    private $tablePrefix;

    public function __construct(string $tablePrefix = '0_')
    {
        $this->tablePrefix = $tablePrefix;
    }

    public function getDialect(): string
    {
        return 'mysql';
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function query(string $sql, array $params = []): array
    {
        // Replace named parameters with escaped values
        $sql = $this->substituteParams($sql, $params);
        
        // Use FA's db_query function
        $result = db_query($sql, 'could not execute query');

        $rows = [];
        while ($row = db_fetch_assoc($result)) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function execute(string $sql, array $params = []): void
    {
        // Replace named parameters with escaped values
        $sql = $this->substituteParams($sql, $params);
        
        // Use FA's db_query function
        db_query($sql, 'could not execute query');
    }

    private function substituteParams(string $sql, array $params): string
    {
        foreach ($params as $key => $value) {
            // Handle named parameters like :param
            $placeholder = ':' . $key;
            if (is_null($value)) {
                $replacement = 'NULL';
            } elseif (is_numeric($value)) {
                $replacement = (string)$value;
            } elseif (is_bool($value)) {
                $replacement = $value ? '1' : '0';
            } else {
                $replacement = $this->escape((string)$value);
            }
            $sql = str_replace($placeholder, $replacement, $sql);
        }
        return $sql;
    }

    public function lastInsertId(): ?int
    {
        // Use FA's db_insert_id function
        return db_insert_id();
    }

    public function escape(string $value): string
    {
        // Use FA's db_escape function
        return db_escape($value);
    }

    public function beginTransaction(): void
    {
        // FA doesn't have explicit transaction management in the same way
        // This is a no-op for FA
    }

    public function commit(): void
    {
        // FA doesn't have explicit transaction management in the same way
        // This is a no-op for FA
    }

    public function rollback(): void
    {
        // FA doesn't have explicit transaction management in the same way
        // This is a no-op for FA
    }
}