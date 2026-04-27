<?php

declare(strict_types=1);

namespace Ksfraser\File;

use Ksfraser\File\Exception\FileException;

class KsfFileCsv extends KsfFile
{
    /** @var int */
    protected $size;

    /** @var string */
    protected $separator;

    /** @var array<int, array<int, string|null>> */
    protected $lines = [];

    /** @var int */
    protected $linecount = 0;

    /** @var bool */
    protected $b_header;

    /** @var bool */
    protected $b_skip_header;

    /** @var bool */
    private $grabbed_header = false;

    /** @var array<int, string|null>|string */
    protected $headerline = '';

    /** @var string */
    protected $enclosure = '"';

    /** @var string */
    protected $escapechar = "\\";

    public function __construct(
        string $filename,
        int $size,
        string $separator,
        bool $b_header = false,
        bool $b_skip_header = false,
        ?string $path = null
    ) {
        parent::__construct($filename, $path);
        $this->size = $size;
        $this->separator = $separator;
        $this->b_header = $b_header;
        $this->b_skip_header = $b_skip_header;
    }

    /**
     * @return array<int, string|null>
     */
    public function readcsv_line(): array
    {
        if (!is_resource($this->fp)) {
            throw new FileException(__CLASS__ . ' required field not set: fp');
        }

        if ($this->size <= 0) {
            throw new FileException(__CLASS__ . ' required field not set: size');
        }

        if ($this->separator === '') {
            throw new FileException(__CLASS__ . ' required field not set: separator');
        }

        if ($this->b_header && !$this->grabbed_header) {
            $this->headerline = fgetcsv($this->fp, $this->size, $this->separator) ?: [];
            $this->grabbed_header = true;

            if ($this->b_skip_header) {
                // Continue through to the next actual data line
            }
        }

        $line = fgetcsv($this->fp, $this->size, $this->separator);
        if ($line === false) {
            return [];
        }

        $this->linecount++;

        /** @var array<int, string|null> $line */
        return $line;
    }

    public function readcsv_entire(): void
    {
        if (!is_resource($this->fp)) {
            $this->open();
        }

        while (true) {
            $line = $this->readcsv_line();
            if ($line === []) {
                break;
            }
            $this->lines[] = $line;
        }
    }

    /**
     * @param array<int, scalar|null> $arr
     */
    public function write_array_to_csv(array $arr): void
    {
        if (!is_resource($this->fp)) {
            throw new FileException('File pointer not set', Defines::KSF_FIELD_NOT_SET);
        }

        fputcsv($this->fp, $arr, $this->separator, $this->enclosure, $this->escapechar);
    }

    /** @return array<int, array<int, string|null>> */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getLineCount(): int
    {
        return $this->linecount;
    }

    /** @return array<int, string|null>|string */
    public function getHeaderLine()
    {
        return $this->headerline;
    }
}
