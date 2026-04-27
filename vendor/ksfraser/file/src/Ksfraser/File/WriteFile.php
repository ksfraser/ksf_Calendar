<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Exception\FileException;

class WriteFile
{
    /** @var resource|null */
    protected $fp;

    /** @var string */
    protected $tmp_dir;

    /** @var string */
    protected $filename;

    /** @var string */
    protected $deliminater = ',';

    /** @var string */
    protected $enclosure = '"';

    /** @var string */
    protected $escape_char = "\\";

    public function __construct(string $tmp_dir = '../../tmp/', string $filename = 'file.txt')
    {
        $this->tmp_dir = rtrim($tmp_dir, '/\\');
        $this->filename = $filename;

        if (!is_dir($this->tmp_dir)) {
            @mkdir($this->tmp_dir, 0777, true);
        }

        $path = $this->tmp_dir . '/' . $this->filename;
        $handle = @fopen($path, 'w');
        if ($handle === false) {
            throw new FileException('Unable to open file for writing: ' . $path);
        }

        $this->fp = $handle;
    }

    public function __destruct()
    {
        if (is_resource($this->fp)) {
            $this->close();
        }
    }

    public function write_chunk(string $line): void
    {
        $this->assertOpen();
        fwrite($this->fp, $line);
        fflush($this->fp);
    }

    public function write_line(string $line): void
    {
        $this->assertOpen();
        fwrite($this->fp, $line . "\r\n");
        fflush($this->fp);
    }

    /**
     * @param array<int, scalar|null> $arr
     */
    public function write_array_to_csv(array $arr): void
    {
        $this->assertOpen();
        fputcsv($this->fp, $arr, $this->deliminater, $this->enclosure);
    }

    public function close(): void
    {
        if (!is_resource($this->fp)) {
            throw new FileException("Trying to close a file pointer that isn't set");
        }

        fflush($this->fp);
        fclose($this->fp);
        $this->fp = null;
    }

    private function assertOpen(): void
    {
        if (!is_resource($this->fp)) {
            throw new FileException('File pointer not set');
        }
    }
}
