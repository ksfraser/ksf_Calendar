<?php

// Minimal stub of rest_interface for legacy file_download.
class rest_interface
{
    /** @var string */
    protected $baseurl = '';

    /** @var string */
    protected $endpoint = '';

    /** @var string */
    protected $queryval = '';

    /** @var string */
    protected $key = '';

    /** @var string */
    protected $url = '';

    /** @var array<int, mixed> */
    protected $interestedin = [];

    public function __construct(string $host, string $user, string $pass, string $database)
    {
        // no-op
    }

    public function tell_eventloop(object $caller, string $event, $msg = null): void
    {
        // no-op for tests
    }
}
