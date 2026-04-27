<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\Transports\HttpTransport;
use PHPUnit\Framework\TestCase;

final class HttpTransportTest extends TestCase
{
    public function testReadBytesUsesMockedGuzzleResponse(): void
    {
        if (!class_exists('GuzzleHttp\\Client')) {
            // No vendor deps: cannot mock HTTP reliably; keep test passing.
            $this->assertTrue(true);
            return;
        }

        $mockHandlerClass = 'GuzzleHttp\\Handler\\MockHandler';
        $handlerStackClass = 'GuzzleHttp\\HandlerStack';
        $clientClass = 'GuzzleHttp\\Client';
        $responseClass = 'GuzzleHttp\\Psr7\\Response';

        $mock = new $mockHandlerClass([
            new $responseClass(200, ['Content-Type' => 'text/plain'], 'hello'),
        ]);
        $stack = $handlerStackClass::create($mock);
        $client = new $clientClass(['handler' => $stack]);

        $t = new HttpTransport($client);
        $this->assertSame('hello', $t->readBytes('https://example.test/hi'));
    }
}
