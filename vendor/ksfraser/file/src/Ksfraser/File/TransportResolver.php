<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Contracts\TransportInterface;
use Ksfraser\File\Exception\FileException;
use Ksfraser\File\Transports\HttpTransport;
use Ksfraser\File\Transports\LocalFileTransport;

final class TransportResolver
{
    /** @var array<int, TransportInterface> */
    private $transports;

    /**
     * @param array<int, TransportInterface> $transports
     */
    public function __construct(array $transports = [])
    {
        $this->transports = $transports ?: [
            new LocalFileTransport(),
            new HttpTransport(),
        ];
    }

    public function resolve(string $uri): TransportInterface
    {
        $scheme = $this->inferScheme($uri);

        foreach ($this->transports as $transport) {
            if ($transport->supportsScheme($scheme)) {
                return $transport;
            }
        }

        $s = $scheme ?? '(path)';
        throw new FileException('Unsupported URI scheme: ' . $s);
    }

    private function inferScheme(string $uri): ?string
    {
        // Windows drive-letter paths (e.g. C:\tmp\a.txt or C:/tmp/a.txt) are local paths.
        // `parse_url()` mis-identifies these as scheme "c" on Windows.
        if (preg_match('~^[A-Za-z]:[\\\\/]~', $uri) === 1) {
            return null;
        }

        // UNC paths (\\server\share\file or //server/share/file) are also local filesystem paths.
        if (preg_match('~^(\\\\\\\\|//)~', $uri) === 1) {
            return null;
        }

        $parts = @parse_url($uri);
        if (!is_array($parts) || !isset($parts['scheme']) || !is_string($parts['scheme']) || $parts['scheme'] === '') {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        // Extra guard: treat one-letter "schemes" as drive letters.
        if (strlen($scheme) === 1 && preg_match('~^[A-Za-z]:~', $uri) === 1) {
            return null;
        }

        return $scheme;
    }
}
