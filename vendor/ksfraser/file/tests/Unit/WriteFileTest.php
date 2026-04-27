<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\WriteFile;
use PHPUnit\Framework\TestCase;

final class WriteFileTest extends TestCase
{
    public function testWriteLineAndChunkCreateFile(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_write_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $wf = new WriteFile($dir, 'out.txt');
        $wf->write_chunk('a');
        $wf->write_line('b');
        $wf->close();

        $contents = file_get_contents($dir . '/out.txt');
        $this->assertIsString($contents);
        $this->assertStringContainsString('a', $contents);
        $this->assertStringContainsString("b\r\n", $contents);

        unlink($dir . '/out.txt');
        rmdir($dir);
    }

    public function testWriteArrayToCsvWritesCsvLine(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_write_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $wf = new WriteFile($dir, 'csv.txt');
        $wf->write_array_to_csv(['x', 'y']);
        $wf->close();

        $contents = file_get_contents($dir . '/csv.txt');
        $this->assertIsString($contents);
        $this->assertStringContainsString('x', $contents);
        $this->assertStringContainsString('y', $contents);

        unlink($dir . '/csv.txt');
        rmdir($dir);
    }
}
