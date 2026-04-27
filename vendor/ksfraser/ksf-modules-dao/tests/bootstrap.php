<?php

$local = __DIR__ . '/../vendor/autoload.php';
if (is_file($local)) {
	require_once $local;
} else {
	$monorepo = __DIR__ . '/../../composer-lib/vendor/autoload.php';
	if (is_file($monorepo)) {
		require_once $monorepo;
	} else {
		throw new RuntimeException('Could not locate Composer autoloader for ModulesDAO tests');
	}
}

// Load FA function mocks for testing
$famockPaths = [
    __DIR__ . '/../vendor/ksfraser/famock/php/FAMock.php',
    __DIR__ . '/../../composer-lib/tests/FAMock.php' // Fallback to local FAMock
];

foreach ($famockPaths as $famockPath) {
    if (file_exists($famockPath)) {
        require_once $famockPath;
        break;
    }
}
