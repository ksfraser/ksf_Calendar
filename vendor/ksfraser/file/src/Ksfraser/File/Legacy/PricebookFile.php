<?php

declare(strict_types=1);

namespace Ksfraser\File\Legacy;

/**
 * @deprecated Legacy compatibility shim; depends on application DB functions.
 */
class PricebookFile extends LabelsFile
{
    public function __construct($pref_tablename)
    {
        parent::__construct($pref_tablename);

        $this->filename = 'pricebook.csv';

        if (!defined('TB_PREF')) {
            define('TB_PREF', '');
        }

        $this->query = 'select s.stock_id as stock_id, s.description as description, q.instock as instock, c.description as category, p.price as price from ' . TB_PREF . 'stock_master s, ' . TB_PREF . 'ksf_qoh q, ' . TB_PREF . 'stock_category c, ' . TB_PREF . 'prices p where s.inactive=0 and s.stock_id=q.stock_id and s.category_id = c.category_id and s.stock_id=p.stock_id and p.curr_abrev=\'CAD\' and p.sales_type_id=1 order by c.description, s.description';
    }
}
