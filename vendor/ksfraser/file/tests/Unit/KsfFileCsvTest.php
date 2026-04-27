<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\KsfFileCsv;
use PHPUnit\Framework\TestCase;

final class KsfFileCsvTest extends TestCase
{
    public function testWriteArrayToCsvAndReadBack(): void
    {
        $dir = sys_get_temp_dir() . '/ksf_file_csv_' . bin2hex(random_bytes(6));
        mkdir($dir);

        $csv = new KsfFileCsv('t.csv', 4096, ',', false, false, $dir);
        $csv->open_for_write();
        $csv->write_array_to_csv(['a', 'b']);
        $csv->close();

        $contents = file_get_contents($dir . '/t.csv');
        $this->assertIsString($contents);
        $this->assertSame('a,b', trim($contents));

        $reader = new KsfFileCsv('t.csv', 4096, ',', false, false, $dir);
        $reader->open();
        $reader->readcsv_entire();
        $reader->close();

        $this->assertSame([['a', 'b']], $reader->getLines());

        unlink($dir . '/t.csv');
        rmdir($dir);
    }
}
