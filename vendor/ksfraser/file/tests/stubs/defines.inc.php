<?php

// Minimal constants needed by the legacy FILE classes.
if (!defined('KSF_FILE_OPEN_FAILED')) {
    define('KSF_FILE_OPEN_FAILED', 1001);
    define('KSF_FILE_PTR_NOT_SET', 1002);
    define('KSF_FILE_READONLY', 1003);
    define('KSF_FCN_REFACTORED', 1004);
    define('KSF_FIELD_NOT_SET', 1005);
    // Historical typo used by legacy code.
    define('KSF_FILED_NOT_SET', 1006);
}
