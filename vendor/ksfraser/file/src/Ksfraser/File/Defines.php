<?php

declare(strict_types=1);

namespace Ksfraser\File;

/**
 * Legacy-ish error codes used across older KSF file utilities.
 */
final class Defines
{
    public const KSF_FILE_OPEN_FAILED = 1001;
    public const KSF_FIELD_NOT_SET = 1002;
    public const KSF_FILED_NOT_SET = 1003; // Intentionally kept typo for compatibility

    public const KSF_FILE_PTR_NOT_SET = 1004;
    public const KSF_VAR_NOT_SET = 1005;

    private function __construct()
    {
    }
}
