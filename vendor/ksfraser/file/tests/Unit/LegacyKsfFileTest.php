<?php

declare(strict_types=1);

namespace Ksfraser\FileLegacy\Tests;

use PHPUnit\Framework\TestCase;

final class LegacyKsfFileTest extends TestCase
{
    public function testOpenForWriteWriteLineAndReadBack(): void
    {
        require_once __DIR__ . '/../../src/Ksfraser/FileLegacy/class.ksf_file.php';

        $dir = sys_get_temp_dir() . '/file_legacy_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $name = 'out.txt';
        $full = $dir . '/' . $name;
        file_put_contents($full, '');

        $file = new \ksf_file($name, $dir);
        $file->open_for_write();
        $file->write_line('abc');
        $file->close();

        $this->assertStringContainsString("abc\r\n", file_get_contents($full));

        unlink($full);
        rmdir($dir);
    }

    public function testWriteArrayToCsvThrowsGuidanceException(): void
    {
        require_once __DIR__ . '/../../src/Ksfraser/FileLegacy/class.ksf_file.php';

        $dir = sys_get_temp_dir() . '/file_legacy_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $name = 'x.txt';
        $full = $dir . '/' . $name;
        file_put_contents($full, '');

        $file = new \ksf_file($name, $dir);
        $file->open_for_write();

        $this->expectException(\Exception::class);
        try {
            $file->write_array_to_csv(['a', 'b']);
        } finally {
            $file->close();
            unlink($full);
            rmdir($dir);
        }
    }
}
