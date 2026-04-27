<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Contracts\FormatInterface;
use Ksfraser\File\Formats\CsvFormat;
use Ksfraser\File\Formats\JsonFormat;
use Ksfraser\File\Formats\RawFormat;

final class FormatResolver
{
    /** @var array<int, FormatInterface> */
    private $formats;

    /**
     * @param array<int, FormatInterface> $formats
     */
    public function __construct(array $formats = [])
    {
        $this->formats = $formats ?: [
            new JsonFormat(),
            new CsvFormat(),
            new RawFormat(),
        ];
    }

    public function resolveForUri(string $uri, array $options = []): FormatInterface
    {
        if (isset($options['format']) && is_string($options['format']) && $options['format'] !== '') {
            return $this->resolveByExtension($options['format']);
        }

        $ext = $this->inferExtension($uri);
        return $this->resolveByExtension($ext);
    }

    private function resolveByExtension(string $extension): FormatInterface
    {
        $extension = strtolower(ltrim($extension, '.'));

        foreach ($this->formats as $format) {
            if ($format->supportsExtension($extension)) {
                return $format;
            }
        }

        // Fallback to raw
        return new RawFormat();
    }

    private function inferExtension(string $uri): string
    {
        $parts = parse_url($uri);
        $path = $parts['path'] ?? $uri;

        $basename = basename($path);
        $dotPos = strrpos($basename, '.');
        if ($dotPos === false) {
            return '';
        }

        return substr($basename, $dotPos + 1);
    }
}
