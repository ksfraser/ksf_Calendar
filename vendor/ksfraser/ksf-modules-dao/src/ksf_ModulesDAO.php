<?php

/**
 * Global DAO wrapper for FA db functions
 * Part of ksf_ModulesDAO library
 */
class ksf_ModulesDAO
{
    public function query($sql, $params = [])
    {
        if (!empty($params)) {
            // Simple parameter replacement, assuming ? placeholders
            foreach ($params as $param) {
                $sql = preg_replace('/\?/', "'" . addslashes($param) . "'", $sql, 1);
            }
        }
        return db_query($sql, "DAO query failed");
    }

    public function affectedRows()
    {
        return db_num_affected_rows();
    }

    public function beginTransaction()
    {
        db_query("START TRANSACTION");
    }

    public function commit()
    {
        db_query("COMMIT");
    }

    public function rollback()
    {
        db_query("ROLLBACK");
    }
}