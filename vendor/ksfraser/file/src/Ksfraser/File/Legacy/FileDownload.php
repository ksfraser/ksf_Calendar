<?php

declare(strict_types=1);

namespace Ksfraser\File\Legacy;

/**
 * @deprecated Prefer using Ksfraser\\File\\ResourceReader / FileIO for URL reads.
 */
class FileDownload extends RestInterface
{
    /** @var string */
    protected $filename = '';

    /** @var string */
    protected $tmpdir = '.';

    /** @var string */
    protected $saveto = '';

    public function __construct()
    {
        parent::__construct('', '', '', '');
    }

    public function run(): bool
    {
        if (strlen($this->filename) <= 2) {
            return false;
        }

        $this->saveto = '';
        if (strlen($this->tmpdir) > 2) {
            $this->saveto = rtrim($this->tmpdir, '/\\') . '/';
        }
        $this->saveto .= $this->filename;

        $this->build_url();

        $bytes = @file_get_contents($this->url);
        if ($bytes === false) {
            return false;
        }

        return file_put_contents($this->saveto, $bytes) !== false;
    }

    public function build_url(): void
    {
        $this->url = $this->baseurl;
        if (strlen($this->endpoint) > 0) {
            $this->url .= '/' . $this->endpoint;
        }
        if (strlen($this->queryval) > 0) {
            $this->url .= '?' . $this->queryval;
        }
        if (isset($this->key) && strlen($this->key) > 2) {
            if (strlen($this->queryval) > 0) {
                $this->url .= '&';
            }
            $this->url .= 'key=' . $this->key;
        }

        $this->tell_eventloop($this, 'NOTIFY_LOG_DEBUG', 'URL ' . $this->url);
    }

    public function download_url($caller, $msg): void
    {
        if (is_string($msg)) {
            $this->tell_eventloop($this, 'NOTIFY_LOG_DEBUG', 'Setting BaseURL ' . $msg);
            $this->baseurl = $msg;
        }
    }

    public function download_endpoint($caller, $msg): void
    {
        if (is_string($msg)) {
            $this->tell_eventloop($this, 'NOTIFY_LOG_DEBUG', 'Setting endpoint ' . $msg);
            $this->endpoint = $msg;
        }
    }

    public function download_query($caller, $msg): void
    {
        if (is_string($msg)) {
            $this->tell_eventloop($this, 'NOTIFY_LOG_DEBUG', 'Setting query ' . $msg);
            $this->queryval = $msg;
        }
    }

    public function download_filename($caller, $msg): void
    {
        if (is_string($msg)) {
            $this->tell_eventloop($this, 'NOTIFY_LOG_DEBUG', 'Setting filename ' . $msg);
            $this->filename = $msg;
        }
    }

    public function download_tmpdir($caller, $msg): void
    {
        if (is_string($msg)) {
            $this->tell_eventloop($this, 'NOTIFY_LOG_DEBUG', 'Setting tmpdir ' . $msg);
            $this->tmpdir = $msg;
        }
    }
}
