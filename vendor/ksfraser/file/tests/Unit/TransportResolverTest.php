<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\FileIO;
use Ksfraser\File\TransportResolver;
use Ksfraser\File\Transports\HttpTransport;
use Ksfraser\File\Transports\LocalFileTransport;
use PHPUnit\Framework\TestCase;

final class TransportResolverTest extends TestCase
{
    public function testResolvesLocalPathAndFileSchemeToLocalTransport(): void
    {
        $resolver = new TransportResolver([
            new LocalFileTransport(),
        ]);

        $this->assertInstanceOf(LocalFileTransport::class, $resolver->resolve('C:/tmp/x.txt'));
        $this->assertInstanceOf(LocalFileTransport::class, $resolver->resolve('file:///C:/tmp/x.txt'));
    }

    public function testResolvesHttpToHttpTransportWhenProvided(): void
    {
        $resolver = new TransportResolver([
            new HttpTransport(),
            new LocalFileTransport(),
        ]);

        $this->assertInstanceOf(HttpTransport::class, $resolver->resolve('http://example.test/a.json'));
        $this->assertInstanceOf(HttpTransport::class, $resolver->resolve('https://example.test/a.json'));
    }

    public function testFileIORefusesRemoteWrite(): void
    {
        $io = new FileIO(null, new TransportResolver([
            new HttpTransport(),
            new LocalFileTransport(),
        ]));

        $this->expectException(\Throwable::class);
        $io->writeBytes('https://example.test/x.txt', 'nope');
    }
}
