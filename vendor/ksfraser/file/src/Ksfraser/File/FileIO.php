<?php

declare(strict_types=1);

namespace Ksfraser\File;

final class FileIO
{
    /** @var FormatResolver */
    private $resolver;

    /** @var TransportResolver */
    private $transportResolver;

    public function __construct(
        ?FormatResolver $resolver = null,
        ?TransportResolver $transportResolver = null
    ) {
        $this->resolver = $resolver ?? new FormatResolver();
        $this->transportResolver = $transportResolver ?? new TransportResolver();
    }

    public function readBytes(string $uri): string
    {
        return $this->transportResolver->resolve($uri)->readBytes($uri);
    }

    public function writeBytes(string $uri, string $bytes): void
    {
        $this->transportResolver->resolve($uri)->writeBytes($uri, $bytes);
    }

    /**
     * @return mixed A stream-like object (preferably a PSR-7 stream) or a PHP resource.
     */
    public function streamRead(string $uri)
    {
        return $this->transportResolver->resolve($uri)->openReadStream($uri);
    }

    /**
     * @return mixed A stream-like object (preferably a PSR-7 stream) or a PHP resource.
     */
    public function streamWrite(string $uri)
    {
        return $this->transportResolver->resolve($uri)->openWriteStream($uri);
    }

    /**
     * Format-aware read (csv/json/raw by extension or $options['format']).
     *
     * @return mixed
     */
    public function fget(string $uri, array $options = [])
    {
        $bytes = $this->readBytes($uri);
        $format = $this->resolver->resolveForUri($uri, $options);

        return $format->decode($bytes, $options);
    }

    /**
     * Format-aware write (csv/json/raw by extension or $options['format']).
     */
    public function fput(string $uri, $data, array $options = []): void
    {
        $format = $this->resolver->resolveForUri($uri, $options);
        $bytes = $format->encode($data, $options);

        $this->writeBytes($uri, $bytes);
    }
}
