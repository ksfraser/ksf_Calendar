# Ksfraser File

Format-aware file IO helpers (CSV/JSON/raw) with legacy compatibility.

## Installation

```bash
composer require ksfraser/file
```

## Core usage (format-aware)

```php
use Ksfraser\File\FileIO;

$io = new FileIO();

// JSON by extension
$io->fput('/tmp/data.json', ['a' => 1]);
$data = $io->fget('/tmp/data.json');

// CSV by extension
$io->fput('/tmp/data.csv', [
    ['a', 'b'],
    ['1', '2'],
]);
$rows = $io->fget('/tmp/data.csv');
```

## URI sanity + transports

`FileIO` selects a transport by URI scheme:

- Local files: plain paths like `C:/tmp/a.json` or `file:///C:/tmp/a.json`
- Remote reads: `http://...` and `https://...`

Remote writes are rejected.

## Bytes + streams

```php
use Ksfraser\File\FileIO;

$io = new FileIO();

$bytes = $io->readBytes('file:///C:/tmp/a.txt');
$io->writeBytes('file:///C:/tmp/b.txt', $bytes);

$stream = $io->streamRead('https://example.com/data.json');
// $stream is typically a PSR-7 stream when guzzle is installed
```

## OO wrappers (legacy-style)

```php
use Ksfraser\File\KsfFile;
use Ksfraser\File\WriteFile;

$f = new KsfFile('a.txt', '/tmp');
$f->open();
$contents = $f->get_all_contents();
$f->close();

$w = new WriteFile('/tmp', 'out.txt');
$w->write_line('hello');
$w->close();
```

## Legacy classes

Legacy `class.*` implementations remain available under `src/Ksfraser/FileLegacy/` and are marked `@deprecated`. They are kept to avoid breaking older apps, but new development should use `Ksfraser\File\*`.
