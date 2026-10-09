<?php
/**
 * Verify every class under src/ is autoloadable via PSR-4 and has resolvable
 * dependencies. Run from the module root:
 *
 *   php tools/check_autoload.php
 *
 * Exists because a green test suite does NOT prove the source tree is sound: a
 * file nothing references, or one written against a different version of a
 * dependency, will never be loaded by the tests and will fatal the first time
 * something tries.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$base = realpath(__DIR__ . '/../src');

if ($base === false) {
    fwrite(STDERR, "src/ not found\n");
    exit(2);
}

$files = array();
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));

foreach ($rii as $f) {
    if ($f->isFile() && $f->getExtension() === 'php') {
        $files[] = $f->getPathname();
    }
}

sort($files);

$ok = 0;
$problems = array();

/**
 * Resolve a short type name to a fully-qualified name using the file's use map.
 *
 * @param array<string,string> $alias
 * @param string               $namespace
 * @param string               $name
 * @return string
 */
function resolve_name(array $alias, string $namespace, string $name): string
{
    if (strpos($name, '\\') !== false) {
        return ltrim($name, '\\');
    }

    if (isset($alias[$name])) {
        return $alias[$name];
    }

    return $namespace . '\\' . $name;
}

foreach ($files as $path) {
    $src = file_get_contents($path);
    $rel = substr($path, strlen($base) + 1);

    if (!preg_match('/^namespace\s+([^;]+);/m', $src, $nsMatch)) {
        continue;
    }

    $namespace = trim($nsMatch[1]);

    if (!preg_match_all('/^(?:final\s+|abstract\s+)?(class|interface|trait|enum)\s+(\w+)/m', $src, $types, PREG_SET_ORDER)) {
        continue;
    }

    // ── PSR-4: one file per type, filename must equal the type name ──────────
    if (count($types) > 1) {
        $names = array();

        foreach ($types as $t) {
            $names[] = $t[2];
        }

        $problems[] = sprintf(
            '%-46s holds %d types (%s): PSR-4 needs one file each',
            $rel,
            count($types),
            implode(', ', $names)
        );

        continue;
    }

    $type = $types[0][2];

    if (basename($rel, '.php') !== $type) {
        $problems[] = sprintf('%s declares %s (filename must match)', $rel, $type);
        continue;
    }

    // ── alias map from use statements ────────────────────────────────────────
    $alias = array();

    if (preg_match_all('/^use\s+([A-Za-z_][A-Za-z0-9_\\\\]*)(?:\s+as\s+(\w+))?;/m', $src, $uses, PREG_SET_ORDER)) {
        foreach ($uses as $u) {
            $short = (!empty($u[2])) ? $u[2] : substr(strrchr('\\' . $u[1], '\\'), 1);
            $alias[$short] = $u[1];
        }
    }

    // ── every referenced type must already be resolvable ──────────────────────
    // Checked BEFORE including this file, because an unresolvable trait or parent
    // is a COMPILE-TIME fatal that no try/catch can intercept.
    $missing = array();

    foreach ($uses as $u) {
        if (!empty($u[2])) {
            continue;
        }

        $fq = ltrim($u[1], '\\');

        if (class_exists($fq) || interface_exists($fq) || trait_exists($fq)) {
            continue;
        }

        $missing[] = $u[1];
    }

    if (preg_match('/^(?:final\s+|abstract\s+)?(?:class|interface|trait|enum)\s+\w+\s+extends\s+([\w\\\\]+)/m', $src, $ext)) {
        $fq = resolve_name($alias, $namespace, $ext[1]);

        if (!class_exists($fq) && !interface_exists($fq)) {
            $missing[] = 'extends ' . $ext[1] . ' -> ' . $fq;
        }
    }

    if (preg_match('/^(?:final\s+|abstract\s+)?(?:class|interface)\s+\w+(?:\s+extends\s+[\w\\\\]+)?\s+implements\s+([\w\\\\,\s\\\\]+)/m', $src, $impl)) {
        foreach (explode(',', $impl[1]) as $piece) {
            $piece = trim($piece);

            if ($piece === '') {
                continue;
            }

            $fq = resolve_name($alias, $namespace, $piece);

            if (!class_exists($fq) && !interface_exists($fq) && !trait_exists($fq)) {
                $missing[] = 'implements ' . $piece . ' -> ' . $fq;
            }
        }
    }

    if ($missing !== array()) {
        $problems[] = sprintf('%s -> %s', $rel, implode(', ', array_unique($missing)));
        continue;
    }

    // ── safe to load now ─────────────────────────────────────────────────────
    $fq = $namespace . '\\' . $type;

    if (class_exists($fq) || interface_exists($fq) || trait_exists($fq)) {
        $ok++;
    } else {
        $problems[] = $fq . ' did not load';
    }
}

printf("%d classes autoloadable, %d problem(s)\n", $ok, count($problems));

foreach ($problems as $p) {
    echo '  - ' . $p . "\n";
}

exit($problems === array() ? 0 : 1);