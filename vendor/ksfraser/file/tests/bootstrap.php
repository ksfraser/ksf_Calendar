<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Fallback autoloader for new namespaced code when Composer deps are not installed.
spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Ksfraser\\File\\' => __DIR__ . '/../src/Ksfraser/File/',
        'Ksfraser\\FileLegacy\\Tests\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
        return;
    }
});

// Legacy classes in this repo require files from the monorepo root.
$stubsDir = __DIR__ . '/stubs';
$includePath = $stubsDir . PATH_SEPARATOR . get_include_path();

$monorepoRoot = dirname(dirname(dirname(__DIR__))); // .../ksf_modules_common
set_include_path($includePath . PATH_SEPARATOR . $monorepoRoot);

require_once $stubsDir . '/defines.inc.php';
