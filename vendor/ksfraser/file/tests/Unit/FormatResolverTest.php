<?php

declare(strict_types=1);

namespace Ksfraser\File\Tests\Unit;

use Ksfraser\File\FormatResolver;
use Ksfraser\File\Formats\CsvFormat;
use Ksfraser\File\Formats\JsonFormat;
use Ksfraser\File\Formats\RawFormat;
use PHPUnit\Framework\TestCase;

final class FormatResolverTest extends TestCase
{
    public function testResolvesByExtension(): void
    {
        $resolver = new FormatResolver();

        $this->assertInstanceOf(JsonFormat::class, $resolver->resolveForUri('file.json'));
        $this->assertInstanceOf(CsvFormat::class, $resolver->resolveForUri('file.csv'));
        $this->assertInstanceOf(RawFormat::class, $resolver->resolveForUri('file.txt'));
        $this->assertInstanceOf(RawFormat::class, $resolver->resolveForUri('file'));
    }

    public function testResolvesByExplicitFormatOption(): void
    {
        $resolver = new FormatResolver();

        $this->assertInstanceOf(JsonFormat::class, $resolver->resolveForUri('file.unknown', ['format' => 'json']));
        $this->assertInstanceOf(CsvFormat::class, $resolver->resolveForUri('file.unknown', ['format' => 'csv']));
    }
}
