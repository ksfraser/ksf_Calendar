<?php

declare(strict_types=1);

namespace Ksfraser\File\Transports;

use Ksfraser\File\Contracts\TransportInterface;
use Ksfraser\File\Exception\FileException;

final class LocalFileTransport implements TransportInterface
{
    public function supportsScheme(?string $scheme): bool
    {
        return $scheme === null || $scheme === '' || $scheme === 'file';
    }

    public function readBytes(string $uri): string
    {
        $path = $this->toLocalPath($uri);

        $data = @file_get_contents($path);
        if ($data === false) {
            throw new FileException('Unable to read local file: ' . $path);
        }

        return $data;
    }

    public function writeBytes(string $uri, string $bytes): void
    {
        $path = $this->toLocalPath($uri);

        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            if (class_exists('Symfony\\Component\\Filesystem\\Filesystem')) {
                $fs = new \Symfony\Component\Filesystem\Filesystem();
                $fs->mkdir($dir);
            } else {
                @mkdir($dir, 0777, true);
            }
        }

        $written = @file_put_contents($path, $bytes);
        if ($written === false) {
            throw new FileException('Unable to write local file: ' . $path);
        }
    }

    public function openReadStream(string $uri)
    {
        $path = $this->toLocalPath($uri);

        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new FileException('Unable to open local file for read: ' . $path);
        }

        return $this->streamFor($handle);
    }

    public function openWriteStream(string $uri)
    {
        $path = $this->toLocalPath($uri);

        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && !is_dir($dir)) {
            if (class_exists('Symfony\\Component\\Filesystem\\Filesystem')) {
                $fs = new \Symfony\Component\Filesystem\Filesystem();
                $fs->mkdir($dir);
            } else {
                @mkdir($dir, 0777, true);
            }
        }

        $handle = @fopen($path, 'wb');
        if ($handle === false) {
            throw new FileException('Unable to open local file for write: ' . $path);
        }

        return $this->streamFor($handle);
    }

    private function toLocalPath(string $uri): string
    {
        $parts = @parse_url($uri);
        if (is_array($parts) && isset($parts['scheme']) && is_string($parts['scheme']) && strtolower($parts['scheme']) === 'file') {
            $path = $parts['path'] ?? '';
            if (!is_string($path)) {
                $path = '';
            }
            $path = rawurldecode($path);

            // Windows-style file URLs often look like file:///C:/path
            if (preg_match('~^/[A-Za-z]:/~', $path) === 1) {
                $path = ltrim($path, '/');
            }

            return $path;
        }

        return $uri;
    }

    /**
     * @param resource $handle
     */
    private function streamFor($handle)
    {
        if (class_exists('GuzzleHttp\\Psr7\\Utils')) {
            return \GuzzleHttp\Psr7\Utils::streamFor($handle);
        }

        // Minimal fallback: provide a tiny StreamInterface wrapper.
        return new class($handle) {
            /** @var resource */
            private $h;
            public function __construct($h) { $this->h = $h; }
            public function __toString() { $this->seek(0); return (string) stream_get_contents($this->h); }
            public function close(): void { if (is_resource($this->h)) { fclose($this->h); } }
            public function detach() { $h = $this->h; $this->h = null; return $h; }
            public function getSize(): ?int { $s = fstat($this->h); return is_array($s) && isset($s['size']) ? (int) $s['size'] : null; }
            public function tell(): int { $p = ftell($this->h); if ($p === false) { throw new \RuntimeException('tell failed'); } return $p; }
            public function eof(): bool { return feof($this->h); }
            public function isSeekable(): bool { $m = $this->getMetadata(); return (bool) ($m['seekable'] ?? false); }
            public function seek($offset, $whence = SEEK_SET): void { if (fseek($this->h, (int) $offset, (int) $whence) !== 0) { throw new \RuntimeException('seek failed'); } }
            public function rewind(): void { $this->seek(0); }
            public function isWritable(): bool { $m = $this->getMetadata('mode'); return is_string($m) && (strpos($m, 'w') !== false || strpos($m, '+') !== false || strpos($m, 'a') !== false); }
            public function write($string): int { $n = fwrite($this->h, (string) $string); if ($n === false) { throw new \RuntimeException('write failed'); } return $n; }
            public function isReadable(): bool { $m = $this->getMetadata('mode'); return is_string($m) && (strpos($m, 'r') !== false || strpos($m, '+') !== false); }
            public function read($length): string { $d = fread($this->h, (int) $length); if ($d === false) { throw new \RuntimeException('read failed'); } return $d; }
            public function getContents(): string { $d = stream_get_contents($this->h); if ($d === false) { throw new \RuntimeException('getContents failed'); } return $d; }
            public function getMetadata($key = null) { $m = stream_get_meta_data($this->h); if ($key === null) { return $m; } return $m[$key] ?? null; }
        };
    }
}
