<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Exception\FileException;

final class ResourceWriter
{
    /** @var TransportResolver */
    private $resolver;

    public function __construct(?TransportResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new TransportResolver();
    }

    public function writeBytes(string $uri, string $bytes): void
    {
        $this->resolver->resolve($uri)->writeBytes($uri, $bytes);
    }
}
