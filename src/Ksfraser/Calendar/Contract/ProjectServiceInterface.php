<?php
/**
 * ProjectServiceInterface for Calendar integration
 *
 * @package Ksfraser\Calendar\Contract
 */

declare(strict_types=1);

namespace Ksfraser\Calendar\Contract;

interface ProjectServiceInterface
{
    public function getTasksByAssignee(string $employeeId): array;
    public function getTask(string $taskId): mixed;
}