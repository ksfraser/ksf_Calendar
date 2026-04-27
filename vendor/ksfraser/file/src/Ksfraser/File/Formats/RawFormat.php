<?php

declare(strict_types=1);

namespace Ksfraser\File\Formats;

use Ksfraser\File\Contracts\FormatInterface;

final class RawFormat implements FormatInterface
{
    public function supportsExtension(string $extension): bool
    {
        return $extension === '' || $extension === 'txt' || $extension === 'bin';
    }

    public function extensions(): array
    {
        return ['', 'txt', 'bin'];
    }

    public function decode(string $bytes, array $options = [])
    {
        return $bytes;
    }

    public function encode($data, array $options = []): string
    {
        if (is_string($data)) {
            return $data;
        }

        return (string) $data;
    }
}
