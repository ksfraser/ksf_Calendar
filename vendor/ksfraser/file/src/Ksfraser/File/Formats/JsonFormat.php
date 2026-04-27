<?php

declare(strict_types=1);

namespace Ksfraser\File\Formats;

use Ksfraser\File\Contracts\FormatInterface;
use Ksfraser\File\Exception\FileException;

final class JsonFormat implements FormatInterface
{
    public function supportsExtension(string $extension): bool
    {
        return $extension === 'json';
    }

    public function extensions(): array
    {
        return ['json'];
    }

    public function decode(string $bytes, array $options = [])
    {
        $assoc = array_key_exists('assoc', $options) ? (bool) $options['assoc'] : true;
        $depth = array_key_exists('depth', $options) ? (int) $options['depth'] : 512;

        $decoded = json_decode($bytes, $assoc, $depth);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new FileException('Invalid JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    public function encode($data, array $options = []): string
    {
        $flags = array_key_exists('flags', $options) ? (int) $options['flags'] : 0;
        $depth = array_key_exists('depth', $options) ? (int) $options['depth'] : 512;

        $encoded = json_encode($data, $flags, $depth);
        if ($encoded === false) {
            throw new FileException('Unable to encode JSON: ' . json_last_error_msg());
        }

        return $encoded;
    }
}
