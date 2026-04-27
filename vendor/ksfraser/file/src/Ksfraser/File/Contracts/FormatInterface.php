<?php

declare(strict_types=1);

namespace Ksfraser\File\Contracts;

interface FormatInterface
{
    /**
     * File extension without dot (e.g. "csv", "json").
     */
    public function supportsExtension(string $extension): bool;

    /**
     * @return array<int, string>
     */
    public function extensions(): array;

    /**
     * @return mixed
     */
    public function decode(string $bytes, array $options = []);

    public function encode($data, array $options = []): string;
}
