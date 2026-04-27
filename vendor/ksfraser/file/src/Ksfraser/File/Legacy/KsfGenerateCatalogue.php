<?php

declare(strict_types=1);

namespace Ksfraser\File\Legacy;

/**
 * @deprecated Legacy compatibility shim.
 */
abstract class KsfGenerateCatalogue extends Origin
{
    protected ?string $tmp_dir = null;
    protected ?string $filename = null;

    /** @var mixed */
    protected $write_file;

    public function __construct($param1 = null, $param2 = null, $param3 = null, $param4 = null, $pref_tablename = null)
    {
        parent::__construct();
    }

    public function set_var($key, $value): void
    {
    }

    protected function prep_write_file(): void
    {
    }

    protected function email_file(string $subject = 'File'): bool
    {
        return true;
    }

    protected function display_notification(string $message): void
    {
    }
}
