<?php

// Minimal stub of fa_origin so legacy FILE classes can be instantiated.
class fa_origin
{
    public function __construct()
    {
    }

    public function set(string $field, $value = null, bool $enforce = true)
    {
        $this->{$field} = $value;
        return true;
    }

    public function get(string $field)
    {
        return $this->{$field} ?? null;
    }
}

if (!function_exists('company_path')) {
    function company_path(): string
    {
        return '';
    }
}
