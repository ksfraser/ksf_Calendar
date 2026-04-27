<?php

declare(strict_types=1);

namespace Ksfraser\FileLegacy\Tests;

use PHPUnit\Framework\TestCase;

final class LegacyKsfFileCsvTest extends TestCase
{
    public function testReadCsvEntireWithHeader(): void
    {
        require_once __DIR__ . '/../../src/Ksfraser/FILE/class.ksf_file.php';
        require_once __DIR__ . '/../../src/Ksfraser/FileLegacy/class.ksf_file_csv.php';

        $dir = sys_get_temp_dir() . '/file_legacy_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $name = 'in.csv';
        $full = $dir . '/' . $name;
        file_put_contents($full, "h1,h2\n1,2\n");

        $csv = new \ksf_file_csv($name, 1024, ',', true, false, $dir);
        $csv->open();
        $csv->readcsv_entire();
        $csv->close();

        $ref = new \ReflectionObject($csv);
        $header = $ref->getProperty('headerline');
        $header->setAccessible(true);
        $linesProp = $ref->getProperty('lines');
        $linesProp->setAccessible(true);

        $this->assertSame(['h1', 'h2'], $header->getValue($csv));
        $this->assertSame([['1', '2']], $linesProp->getValue($csv));

        unlink($full);
        rmdir($dir);
    }
}
