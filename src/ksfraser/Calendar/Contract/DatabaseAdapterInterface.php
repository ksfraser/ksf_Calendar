<?php
/**
 * DatabaseAdapterInterface
 *
 * @package ksfraser\Calendar\Contract
 */

declare(strict_types=1);

namespace ksfraser\Calendar\Contract;

interface DatabaseAdapterInterface
{
    public function fetchAssoc(string $sql, array $params = []): ?array;
    public function fetchAll(string $sql, array $params = []): array;
    public function executeUpdate(string $sql, array $params = []): int;
    public function lastInsertId();
}