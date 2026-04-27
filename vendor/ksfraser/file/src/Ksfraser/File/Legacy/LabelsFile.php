<?php

declare(strict_types=1);

namespace Ksfraser\File\Legacy;

/**
 * @deprecated Legacy compatibility shim; depends on application DB functions.
 */
class LabelsFile extends KsfGenerateCatalogue
{
    /** @var string */
    protected $hline = '"stock_id", "Title", "barcode", "category", "price"';

    /** @var string */
    protected $query = '';

    public function __construct($pref_tablename)
    {
        parent::__construct(null, null, null, null, $pref_tablename);
        set_time_limit(300);

        $this->filename = 'labels.csv';
        $this->set_var('include_header', true);

        if (!defined('TB_PREF')) {
            define('TB_PREF', '');
        }

        $this->query = 'select s.stock_id as stock_id, s.description as description, q.instock as instock, c.description as category, 0 as price from ' . TB_PREF . 'stock_master s, ' . TB_PREF . 'ksf_qoh q, ' . TB_PREF . 'stock_category c where s.inactive=0 and s.stock_id=q.stock_id and s.category_id = c.category_id order by c.description, s.description';
    }

    public function create_file(): int
    {
        $this->prep_write_file();

        if (!isset($this->write_file)) {
            return 0;
        }

        $this->write_file->write_line($this->hline);

        if (!function_exists('db_query')) {
            return 0;
        }

        // Framework signature varies; keep it simple for compatibility.
        $result = db_query($this->query);

        $rowcount = 0;
        while ($row = db_fetch($result)) {
            $num = $row['instock'];
            for ($num; $num > 0; $num--) {
                $this->write_sku_labels_line($row['stock_id'], $row['category'], $row['description'], $row['price']);
                $rowcount++;
            }
        }

        $this->write_file->close();
        if ($rowcount > 0) {
            $this->email_file('Labels File');
        }

        return $rowcount;
    }

    protected function write_sku_labels_line($stock_id, $category, $description, $price): void
    {
        if (!isset($this->write_file)) {
            return;
        }

        $line = '"' . $stock_id . '", "' . $description . '", "", "' . $category . '", "' . $price . '"';
        $this->write_file->write_line($line);
    }
}
