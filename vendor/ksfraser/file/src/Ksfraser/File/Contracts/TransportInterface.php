<?php

declare(strict_types=1);

namespace Ksfraser\File\Contracts;

interface TransportInterface
{
    /**
     * @param string|null $scheme Lowercase URI scheme, or null for plain local paths.
     */
    public function supportsScheme(?string $scheme): bool;

    public function readBytes(string $uri): string;

    public function writeBytes(string $uri, string $bytes): void;

    /**
     * @return mixed A stream-like object (preferably a PSR-7 stream) or a PHP resource.
     */
    public function openReadStream(string $uri);

    /**
     * @return mixed A stream-like object (preferably a PSR-7 stream) or a PHP resource.
     */
    public function openWriteStream(string $uri);
}
