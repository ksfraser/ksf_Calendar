<?php

declare(strict_types=1);

namespace Ksfraser\File\Legacy;

/**
 * @deprecated Legacy compatibility shim.
 */
abstract class RestInterface extends Origin
{
    /** @var string */
    protected $baseurl;

    /** @var string */
    protected $endpoint;

    /** @var string */
    protected $key;

    /** @var string */
    protected $url;

    /** @var string */
    protected $queryval;

    public function __construct(string $baseurl = '', string $endpoint = '', string $key = '', string $queryval = '')
    {
        parent::__construct();
        $this->baseurl = $baseurl;
        $this->endpoint = $endpoint;
        $this->key = $key;
        $this->queryval = $queryval;
        $this->url = '';
    }

    protected function tell_eventloop($sender, string $event, string $message): void
    {
        // Stub implementation
    }
}
