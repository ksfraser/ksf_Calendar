<?php

// Minimal stub of UI class used by legacy upload helper.
class ksf_ui_class
{
    public function div_start(string $id): void {}
    public function form_start(bool $multi, bool $multipart, string $action, string $name): void {}
    public function instructions_table(): void {}
    public function table_start($style): void {}
    public function table_header(array $cells): void {}
    public function table_end(int $space = 0): void {}
    public function form_end(): void {}
}

if (!defined('TABLESTYLE')) {
    define('TABLESTYLE', '');
}
