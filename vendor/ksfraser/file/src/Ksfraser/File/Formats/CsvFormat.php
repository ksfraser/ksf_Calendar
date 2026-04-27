<?php

declare(strict_types=1);

namespace Ksfraser\File\Formats;

use Ksfraser\File\Contracts\FormatInterface;

final class CsvFormat implements FormatInterface
{
    public function supportsExtension(string $extension): bool
    {
        return $extension === 'csv';
    }

    public function extensions(): array
    {
        return ['csv'];
    }

    public function decode(string $bytes, array $options = [])
    {
        $separator = array_key_exists('separator', $options) ? (string) $options['separator'] : ',';
        $enclosure = array_key_exists('enclosure', $options) ? (string) $options['enclosure'] : '"';
        $escape = array_key_exists('escape', $options) ? (string) $options['escape'] : "\\";
        $hasHeader = array_key_exists('header', $options) ? (bool) $options['header'] : false;

        if (class_exists('League\\Csv\\Reader')) {
            $reader = method_exists('League\\Csv\\Reader', 'fromString')
                ? \League\Csv\Reader::fromString($bytes)
                : \League\Csv\Reader::createFromString($bytes);
            $reader->setDelimiter($separator);
            $reader->setEnclosure($enclosure);
            if (method_exists($reader, 'setEscape')) {
                $reader->setEscape($escape);
            }

            if ($hasHeader) {
                $reader->setHeaderOffset(0);
                return array_values(iterator_to_array($reader->getRecords()));
            }

            return array_values(iterator_to_array($reader->getRecords()));
        }

        $rows = [];
        $lines = preg_split("/(\r\n|\n|\r)/", $bytes);
        if ($lines === false) {
            return [];
        }

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $rows[] = str_getcsv($line, $separator, $enclosure, $escape);
        }

        if (!$hasHeader || $rows === []) {
            return $rows;
        }

        $header = array_shift($rows);
        if ($header === null) {
            return [];
        }

        $assocRows = [];
        foreach ($rows as $row) {
            $assoc = [];
            foreach ($header as $i => $key) {
                $assoc[(string) $key] = $row[$i] ?? null;
            }
            $assocRows[] = $assoc;
        }

        return $assocRows;
    }

    public function encode($data, array $options = []): string
    {
        $separator = array_key_exists('separator', $options) ? (string) $options['separator'] : ',';
        $enclosure = array_key_exists('enclosure', $options) ? (string) $options['enclosure'] : '"';

        if (class_exists('League\\Csv\\Writer')) {
            $writer = method_exists('League\\Csv\\Writer', 'fromString')
                ? \League\Csv\Writer::fromString('')
                : \League\Csv\Writer::createFromString('');
            $writer->setDelimiter($separator);
            $writer->setEnclosure($enclosure);

            if (!is_array($data)) {
                return '';
            }

            foreach ($data as $row) {
                if (!is_array($row)) {
                    $row = [$row];
                }
                $writer->insertOne($row);
            }

            return $writer->toString();
        }

        if (!is_array($data)) {
            return '';
        }

        $fp = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            if (!is_array($row)) {
                $row = [$row];
            }
            fputcsv($fp, $row, $separator, $enclosure);
        }
        rewind($fp);
        $out = stream_get_contents($fp);
        fclose($fp);

        return $out === false ? '' : $out;
    }
}
