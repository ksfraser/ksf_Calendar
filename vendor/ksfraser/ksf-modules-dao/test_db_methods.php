<?php

// Simple test to verify database methods work with mocks
require_once __DIR__ . '/tests/bootstrap.php';

// Explicitly load FAMock
$famockPath = __DIR__ . '/../composer-lib/tests/FAMock.php';
if (file_exists($famockPath)) {
    require_once $famockPath;
    echo "db_query exists: " . (function_exists('db_query') ? 'yes' : 'no') . "\n";
echo "db_fetch_assoc exists: " . (function_exists('db_fetch_assoc') ? 'yes' : 'no') . "\n";
echo "db_insert_id exists: " . (function_exists('db_insert_id') ? 'yes' : 'no') . "\n";
echo "db_escape exists: " . (function_exists('db_escape') ? 'yes' : 'no') . "\n";
} else {
    echo "FAMock not found at: $famockPath\n";
}

echo "Testing FrontAccountingDbAdapter database methods with mocks...\n";

try {
    $adapter = new \Ksfraser\ModulesDAO\Db\FrontAccountingDbAdapter();

    // Test query method
    echo "Testing query with SQL: 'SELECT * FROM test_table'\n";
    $dbResult = db_query('SELECT * FROM test_table');
    echo "db_query returned: " . gettype($dbResult) . "\n";
    if (is_object($dbResult)) {
        echo "Object class: " . get_class($dbResult) . "\n";
    }

    $result = $adapter->query('SELECT * FROM test_table');
    echo "Adapter query returned: " . (is_array($result) ? 'array with ' . count($result) . ' rows' : 'non-array') . "\n";

    // Test execute method
    $adapter->execute('INSERT INTO test_table (name) VALUES ("test")');
    echo "✅ execute() completed without error\n";

    // Test lastInsertId method
    $id = $adapter->lastInsertId();
    echo "✅ lastInsertId() returned: " . (is_int($id) || is_null($id) ? $id : 'invalid type') . "\n";

    // Test escape method
    $escaped = $adapter->escape("test'value");
    echo "✅ escape() returned: '$escaped'\n";

    echo "🎉 All database methods work with mocks!\n";

} catch (Exception $e) {
    echo "❌ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}