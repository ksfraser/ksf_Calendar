<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Exception\FileException;

/**
 * A small OO wrapper around common PHP file operations.
 *
 * This is intended to preserve the "ksf_file" style interface
 * while being namespaced and testable.
 */
class KsfFile
{
    /** @var resource|null */
    protected $fp;

    /** @var string */
    protected $filename;

    /** @var string|null */
    protected $path;

    /** @var string */
    protected $filepath;

    /** @var int */
    protected $filesize;

    /** @var int */
    protected $linecount = 0;

    /** @var string|null */
    protected $filecontents = null;

    public function __construct(string $filename = 'file.txt', ?string $path = null)
    {
        $this->filename = $filename;
        $this->path = $path;

        $this->filepath = $this->buildFilePath($filename, $path);
        $this->filesize = file_exists($this->filepath) ? (int) filesize($this->filepath) : 0;
    }

    public function __destruct()
    {
        if (is_resource($this->fp)) {
            $this->close();
        }
    }

    public function open(): void
    {
        $this->validateVariables();

        $handle = @fopen($this->filepath, 'r');
        if ($handle === false) {
            throw new FileException('Unable to open file for read: ' . $this->filepath, Defines::KSF_FILE_OPEN_FAILED);
        }

        $this->fp = $handle;
        $this->filesize = file_exists($this->filepath) ? (int) filesize($this->filepath) : 0;
    }

    public function open_for_write(): void
    {
        $this->validateVariables();

        $handle = @fopen($this->filepath, 'w');
        if ($handle === false) {
            throw new FileException('Unable to open file for write: ' . $this->filepath, Defines::KSF_FILE_OPEN_FAILED);
        }

        $this->fp = $handle;
    }

    public function unlink(?string $filename = null): bool
    {
        $target = $filename ?? $this->filepath;
        return @unlink($target);
    }

    public function delete(?string $filename = null): bool
    {
        return $this->unlink($filename);
    }

    public function close(): void
    {
        if (!is_resource($this->fp)) {
            throw new FileException('Trying to close a file pointer that is not set', Defines::KSF_FIELD_NOT_SET);
        }

        fflush($this->fp);
        fclose($this->fp);
        $this->fp = null;
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

    public function make_path(): bool
    {
        $this->validateVariables();

        $path = (string) $this->path;
        if ($path === '') {
            return true;
        }

        if (!is_dir($path)) {
            @mkdir($path, 0777, true);
        }

        return is_dir($path);
    }

    public function pathExists(): bool
    {
        $this->validateVariables();
        return is_dir((string) $this->path);
    }

    public function fileExists(): bool
    {
        $this->validateVariables();
        return is_file($this->filepath) && is_readable($this->filepath);
    }

    public function get_all_contents(): string
    {
        if (!is_resource($this->fp)) {
            throw new FileException('File pointer not set; cannot read', Defines::KSF_FILED_NOT_SET);
        }

        if (!isset($this->filesize)) {
            throw new FileException('File size not set; cannot read', Defines::KSF_FILED_NOT_SET);
        }

        if ($this->filesize <= 0) {
            return '';
        }

        $data = fread($this->fp, $this->filesize);
        if ($data === false) {
            throw new FileException('Unable to read from file pointer', Defines::KSF_FILED_NOT_SET);
        }

        $this->filecontents = $data;
        return $data;
    }

    public function getFileContents(): string
    {
        $this->validateVariables();

        $data = @file_get_contents($this->filepath);
        if ($data === false) {
            throw new FileException('Unable to read file contents: ' . $this->filepath, Defines::KSF_FILED_NOT_SET);
        }

        $this->filecontents = $data;
        return $data;
    }

    public function fread(): string
    {
        $this->assertOpen();

        rewind($this->fp);

        if ($this->filesize > 0) {
            $data = \fread($this->fp, $this->filesize);
            if ($data === false) {
                throw new FileException('Unable to read from file pointer', Defines::KSF_FILED_NOT_SET);
            }
        } else {
            $data = stream_get_contents($this->fp);
            if ($data === false) {
                throw new FileException('Unable to read from file pointer', Defines::KSF_FILED_NOT_SET);
            }
        }

        $this->filecontents = $data;
        return $data;
    }

    public function uploadFileName(int $id = 0): void
    {
        if (!isset($_FILES) || !isset($_FILES['files']) || !isset($_FILES['files']['tmp_name'])) {
            throw new FileException("Can't set a filename when one not passed in", Defines::KSF_FIELD_NOT_SET);
        }

        $tmp = $_FILES['files']['tmp_name'][$id] ?? null;
        if (!is_string($tmp) || $tmp === '') {
            throw new FileException("Can't set a filename when one not passed in", Defines::KSF_FIELD_NOT_SET);
        }

        $this->filename = $tmp;
        $this->path = '';
        $this->filepath = $tmp;
        $this->filesize = file_exists($this->filepath) ? (int) filesize($this->filepath) : 0;
    }

    public function getNumberOfLinesInfile(): int
    {
        $this->assertOpen();

        $count = 0;
        rewind($this->fp);
        while (!feof($this->fp)) {
            if (fgets($this->fp) !== false) {
                $count++;
            }
        }

        rewind($this->fp);
        $this->linecount = $count;
        return $count;
    }

    public function getFilePath(): string
    {
        return $this->filepath;
    }

    /**
     * Exposes the underlying file handle for legacy bridge code.
     *
     * @return resource|null
     */
    public function getHandle()
    {
        return $this->fp;
    }

    protected function validateVariables(): void
    {
        if ($this->path === null) {
            throw new FileException('Path variable not set', Defines::KSF_FIELD_NOT_SET);
        }

        if ($this->filename === '') {
            throw new FileException('Filename variable not set', Defines::KSF_FIELD_NOT_SET);
        }
    }

    protected function buildFilePath(string $filename, ?string $path): string
    {
        if ($path === null || $path === '') {
            return $filename;
        }

        return rtrim($path, '/\\') . '/' . $filename;
    }

    private function assertOpen(): void
    {
        if (!is_resource($this->fp)) {
            throw new FileException('File pointer not set', Defines::KSF_FIELD_NOT_SET);
        }
    }
}
