<?php

declare(strict_types=1);

namespace Ksfraser\File\Transports;

use Ksfraser\File\Contracts\TransportInterface;
use Ksfraser\File\Exception\FileException;

final class HttpTransport implements TransportInterface
{
    /** @var object|null */
    private $client;

    /**
     * @param object|null $client Optional Guzzle client (ClientInterface) for DI/testing.
     */
    public function __construct($client = null)
    {
        $this->client = $client;
    }

    public function supportsScheme(?string $scheme): bool
    {
        return $scheme === 'http' || $scheme === 'https';
    }

    public function readBytes(string $uri): string
    {
        // Prefer Guzzle when present.
        if (interface_exists('GuzzleHttp\\ClientInterface') || class_exists('GuzzleHttp\\Client')) {
            $client = $this->client;
            if ($client === null) {
                $clientClass = '\\GuzzleHttp\\Client';
                $client = new $clientClass();
            }

            try {
                $resp = $client->request('GET', $uri);
            } catch (\Throwable $e) {
                throw new FileException('HTTP GET failed: ' . $e->getMessage());
            }

            return (string) $resp->getBody();
        }

        // Fallback: allow basic reads without vendor deps.
        $data = @file_get_contents($uri);
        if ($data === false) {
            throw new FileException('Unable to read remote resource: ' . $uri);
        }

        return $data;
    }

    public function writeBytes(string $uri, string $bytes): void
    {
        throw new FileException('Refusing to write to remote HTTP URI: ' . $uri);
    }

    public function openReadStream(string $uri)
    {
        if (interface_exists('GuzzleHttp\\ClientInterface') || class_exists('GuzzleHttp\\Client')) {
            $client = $this->client;
            if ($client === null) {
                $clientClass = '\\GuzzleHttp\\Client';
                $client = new $clientClass();
            }

            try {
                $resp = $client->request('GET', $uri, ['stream' => true]);
            } catch (\Throwable $e) {
                throw new FileException('HTTP stream GET failed: ' . $e->getMessage());
            }

            return $resp->getBody();
        }

        // No clean StreamInterface without vendor; emulate with a temp stream.
        $bytes = $this->readBytes($uri);
        $h = fopen('php://temp', 'r+');
        fwrite($h, $bytes);
        rewind($h);

        if (class_exists('GuzzleHttp\\Psr7\\Utils')) {
            return \GuzzleHttp\Psr7\Utils::streamFor($h);
        }

        throw new FileException('Streaming requires guzzlehttp/psr7 (install vendor deps)');
    }

    public function openWriteStream(string $uri)
    {
        throw new FileException('Refusing to open write stream for remote HTTP URI: ' . $uri);
    }
}
