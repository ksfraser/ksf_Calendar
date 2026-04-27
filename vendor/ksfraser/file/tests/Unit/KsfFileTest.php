<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\KsfFile;
use PHPUnit\Framework\TestCase;

final class KsfFileTest extends TestCase
{
    public function testOpenReadAndGetAllContents(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $fileName = 'a.txt';
        $full = $dir . '/' . $fileName;
        file_put_contents($full, "hello\n");

        $file = new KsfFile($fileName, $dir);
        $file->open();

        $this->assertSame("hello\n", $file->get_all_contents());

        $file->close();
        unlink($full);
        rmdir($dir);
    }

    public function testCloseWithoutOpenThrows(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $file = new KsfFile('x.txt', $dir);

        $this->expectException(\Throwable::class);
        try {
            $file->close();
        } finally {
            rmdir($dir);
        }
    }

    public function testGetAllContentsWithoutPointerThrows(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $full = $dir . '/b.txt';
        file_put_contents($full, 'data');

        $file = new KsfFile('b.txt', $dir);

        $this->expectException(\Throwable::class);
        try {
            $file->get_all_contents();
        } finally {
            unlink($full);
            rmdir($dir);
        }
    }

    public function testMakePathCreatesDirectory(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        $nested = $dir . '/nested';

        $file = new KsfFile('x.txt', $nested);
        $this->assertFalse(is_dir($nested));

        $this->assertTrue($file->make_path());
        $this->assertTrue(is_dir($nested));

        rmdir($nested);
        rmdir($dir);
    }

    public function testOpenForWriteWriteLineAndReadBack(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $file = new KsfFile('w.txt', $dir);
        $file->open_for_write();
        $file->write_line('hi');
        $file->close();

        $reader = new KsfFile('w.txt', $dir);
        $reader->open();
        $this->assertSame("hi\r\n", $reader->get_all_contents());
        $reader->close();

        unlink($dir . '/w.txt');
        rmdir($dir);
    }

    public function testGetFileContentsReadsWithoutOpen(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir . '/c.txt', 'abc');

        $file = new KsfFile('c.txt', $dir);
        $this->assertSame('abc', $file->getFileContents());

        unlink($dir . '/c.txt');
        rmdir($dir);
    }

    public function testDeleteUnlinksFile(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir . '/d.txt', 'x');

        $file = new KsfFile('d.txt', $dir);
        $this->assertTrue($file->delete());
        $this->assertFalse(file_exists($dir . '/d.txt'));

        rmdir($dir);
    }

    public function testFreadAndGetNumberOfLinesInfile(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_' . bin2hex(random_bytes(6));
        mkdir($dir);
        file_put_contents($dir . '/lines.txt', "a\n" . "b\n" . "c\n");

        $file = new KsfFile('lines.txt', $dir);
        $file->open();

        $this->assertSame(3, $file->getNumberOfLinesInfile());
        $this->assertSame("a\n" . "b\n" . "c\n", $file->fread());

        $file->close();
        unlink($dir . '/lines.txt');
        rmdir($dir);
    }
}
