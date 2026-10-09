<?php
/**
 * ProjectServiceInterface for Calendar integration
 *
 * @package ksfraser\Calendar\Contract
 */

declare(strict_types=1);

namespace ksfraser\Calendar\Contract;

interface ProjectServiceInterface
{
    public function getTasksByAssignee(string $employeeId): array;
    public function getTask(string $taskId): mixed;
}