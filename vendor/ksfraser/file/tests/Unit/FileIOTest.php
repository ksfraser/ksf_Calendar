<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\FileIO;
use PHPUnit\Framework\TestCase;

final class FileIOTest extends TestCase
{
    public function testFputAndFgetJsonRoundTrip(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_fileio_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $path = $dir . '/data.json';

        $io = new FileIO();
        $io->fput($path, ['a' => 1, 'b' => 'x']);

        $data = $io->fget($path);

        $this->assertIsArray($data);
        $this->assertSame(1, $data['a']);
        $this->assertSame('x', $data['b']);

        unlink($path);
        rmdir($dir);
    }

    public function testFputAndFgetCsvRoundTrip(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_fileio_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $path = $dir . '/data.csv';

        $io = new FileIO();
        $io->fput($path, [
            ['a', 'b'],
            ['1', '2'],
        ]);

        $data = $io->fget($path);

        $this->assertIsArray($data);
        $this->assertSame(['a', 'b'], $data[0]);
        $this->assertSame(['1', '2'], $data[1]);

        unlink($path);
        rmdir($dir);
    }
}
