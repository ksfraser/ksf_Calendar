<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Exception\FileException;

final class ResourceReader
{
    /** @var TransportResolver */
    private $resolver;

    public function __construct(?TransportResolver $resolver = null)
    {
        $this->resolver = $resolver ?? new TransportResolver();
    }

    public function readBytes(string $uri): string
    {
        return $this->resolver->resolve($uri)->readBytes($uri);
    }
}
