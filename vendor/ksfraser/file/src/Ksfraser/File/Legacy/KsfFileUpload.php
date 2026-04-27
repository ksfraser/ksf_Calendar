<?php

declare(strict_types=1);

namespace Ksfraser\File\Legacy;

use Exception;
use Ksfraser\File\KsfFileCsv;

/**
 * @deprecated Legacy compatibility shim; file upload processing is framework-specific.
 */
class KsfFileUpload extends \Ksfraser\File\KsfFile
{
    /** @var bool */
    protected $upload_ok = false;

    /** @var array<int, mixed> */
    protected $files_array = [];

    /** @var array<int, mixed> */
    protected $filepaths_array = [];

    /** @var object|null */
    protected $ui_class;

    /** @var string */
    protected $upload_file_field_name;

    /** @var bool */
    protected $b_upload_single_file;

    /** @var array<string, mixed> */
    protected $a_data = [];

    public function __construct(string $filename, $ui_c = null, string $upload_file_field_name = 'import_files', bool $b_upload_single_file = true)
    {
        parent::__construct($filename);
        $this->ui_class = $ui_c ?? new KsfUi();
        $this->upload_file_field_name = $upload_file_field_name;
        $this->b_upload_single_file = $b_upload_single_file;
    }

    public function process_single_file(string $filename, int $size, string $separator = ',', string $type = 'csv'): array
    {
        if ($type !== 'csv') {
            return [];
        }

        $fc = new KsfFileCsv($filename, $size, $separator);
        $fc->readcsv_entire();

        return [
            'count' => $fc->getLineCount(),
            'header' => $fc->getHeaderLine(),
            'data' => $fc->getLines(),
        ];
    }

    public function upload_form(bool $b_multi = false, string $action = '', string $name = ''): void
    {
        if ($this->ui_class === null) {
            throw new Exception('UI Class not set');
        }

        echo '<form method="post" enctype="multipart/form-data">';
        $multi = $this->b_upload_single_file ? '' : ' multiple';
        echo '<input type="file" name="' . htmlspecialchars($this->upload_file_field_name, ENT_QUOTES) . ($this->b_upload_single_file ? '' : '[]') . '"' . $multi . ' />';
        echo '<input type="submit" value="Upload" />';
        echo '</form>';
    }
}
