<?php

declare(strict_types=1);

namespace Ksfraser\FileLegacy\Tests;

use PHPUnit\Framework\TestCase;

final class LegacyFileDownloadTest extends TestCase
{
    public function testRunReturnsFalseWhenFilenameMissing(): void
    {
        require_once __DIR__ . '/../stubs/class.rest_interface.php';
        require_once __DIR__ . '/../../src/Ksfraser/FileLegacy/class.file_download.php';

        $dl = new \file_download();
        $this->assertFalse($dl->run());
    }
}
