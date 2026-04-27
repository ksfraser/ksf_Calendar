<?php

declare(strict_types=1);

namespace Ksfraser\FileLegacy\Tests;

use PHPUnit\Framework\TestCase;

final class LegacyKsfFileUploadTest extends TestCase
{
    public function testFilePutContentsWritesFileAndTracksPaths(): void
    {
        require_once __DIR__ . '/../stubs/class.ksf_ui.php';
        require_once __DIR__ . '/../../src/Ksfraser/FILE/class.ksf_file.php';
        require_once __DIR__ . '/../../src/Ksfraser/FileLegacy/class.ksf_file_upload.php';

        $dir = sys_get_temp_dir() . '/file_legacy_' . bin2hex(random_bytes(6));
        mkdir($dir);
        $name = 'up.txt';
        $full = $dir . '/' . $name;
        file_put_contents($full, '');

        $ui = new \ksf_ui_class();
        $up = new \ksf_file_upload($name, $ui);

        // Force path to our temp dir.
        $ref = new \ReflectionObject($up);
        $pathProp = $ref->getProperty('path');
        $pathProp->setAccessible(true);
        $pathProp->setValue($up, $dir);

        $up->file_put_contents('payload');

        $this->assertSame('payload', file_get_contents($full));

        $filesProp = $ref->getProperty('files_array');
        $filesProp->setAccessible(true);
        $pathsProp = $ref->getProperty('filepaths_array');
        $pathsProp->setAccessible(true);

        $this->assertSame([$name], $filesProp->getValue($up));
        $this->assertSame([$full], $pathsProp->getValue($up));

        unlink($full);
        rmdir($dir);
    }
}
