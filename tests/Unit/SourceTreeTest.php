<?php
/**
 * Source-tree conventions.
 *
 * These guards exist because a green test suite does NOT prove the tree is sound:
 * a file nothing references, or one written against a different version of a
 * dependency, is never loaded by the tests and fatals the first time something
 * tries. That is exactly how a 371-line iCalService sat in src/ for so long.
 */

declare(strict_types=1);

namespace ksfraser\Tests\Unit;

use PHPUnit\Framework\TestCase;

class SourceTreeTest extends TestCase
{
    /** @var string */
    private $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    /**
     * @return string[]
     */
    private function sourceFiles(): array
    {
        $out = array();
        $base = $this->root . '/src';

        if (!is_dir($base)) {
            return $out;
        }

        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($rii as $f) {
            if ($f->isFile() && $f->getExtension() === 'php') {
                $out[] = $f->getPathname();
            }
        }

        sort($out);

        return $out;
    }

    /**
     * The namespace must be lowercase ksfraser\ and match its directory.
     *
     * A capital-K namespace would still autoload under Composer's PSR-4 as long
     * as both the prefix and the path matched, but it contradicts the rename
     * policy for the other ksf_* modules and diverges from the lowercase
     * convention the rest of the estate uses.
     *
     * @return void
     */
    public function testNamespaceIsCanonicalAndMatchesItsDirectory(): void
    {
        $offenders = array();

        foreach ($this->sourceFiles() as $path) {
            $src = file_get_contents($path);
            $relative = str_replace($this->root . '/', '', $path);

            if (preg_match('/^namespace\s+([^;]+);/m', $src, $m)) {
                $ns = trim($m[1]);

                if (strpos($ns, 'Ksfraser\\') === 0) {
                    $offenders[] = $relative . ' declares capital-K namespace ' . $ns;
                }

                // The namespace must mirror the path RELATIVE TO src/ksfraser/,
                // because that is what the PSR-4 prefix maps onto.
                $relativeToRoot = str_replace('src/ksfraser/', '', dirname($relative));
                $expected = 'ksfraser\\'
                    . str_replace('/', '\\', trim($relativeToRoot, '/'));

                if ($ns !== $expected) {
                    $offenders[] = sprintf('%s declares %s but its directory implies %s', $relative, $ns, $expected);
                }
            }
        }

        $this->assertSame(
            array(),
            $offenders,
            "namespace must be ksfraser\\ and mirror the path:\n  " . implode("\n  ", $offenders)
        );
    }

    /**
     * One type per file, and the filename must equal the type name.
     *
     * PSR-4 resolves a class to a path derived from its name, so three classes in
     * one file are unreachable unless a classmap entry papers over it — which is
     * exactly how CalendarEntryEvents.php was being carried.
     *
     * @return void
     */
    public function testEachFileDeclaresOneTypeNamedAfterIt(): void
    {
        $offenders = array();

        foreach ($this->sourceFiles() as $path) {
            $src = file_get_contents($path);
            $relative = str_replace($this->root . '/', '', $path);

            if (!preg_match_all(
                '/^(?:final\s+|abstract\s+)?(class|interface|trait|enum)\s+(\w+)/m',
                $src,
                $types,
                PREG_SET_ORDER
            )) {
                continue;
            }

            if (count($types) > 1) {
                $names = array();

                foreach ($types as $t) {
                    $names[] = $t[2];
                }

                $offenders[] = $relative . ' declares ' . count($types) . ' types: ' . implode(', ', $names);

                continue;
            }

            if (basename($path, '.php') !== $types[0][2]) {
                $offenders[] = sprintf(
                    '%s declares %s, so PSR-4 cannot find it',
                    $relative,
                    $types[0][2]
                );
            }
        }

        $this->assertSame(
            array(),
            $offenders,
            "one type per file, filename must match:\n  " . implode("\n  ", $offenders)
        );
    }

    /**
     * Composer must not need a classmap to reach src/.
     *
     * The classmap was a workaround for the three-events-in-one-file problem. Once
     * that is fixed, a classmap entry means a PSR-4 violation has crept back.
     *
     * @return void
     */
    public function testAutoloadIsPsr4OnlyWithNoClassmap(): void
    {
        $composer = json_decode(file_get_contents($this->root . '/composer.json'), true);

        $this->assertArrayNotHasKey(
            'classmap',
            $composer['autoload'],
            'a classmap entry means some type is unreachable by PSR-4'
        );

        $this->assertSame(
            array('ksfraser\\' => 'src/ksfraser/'),
            $composer['autoload']['psr-4'],
            'PSR-4 must be the lowercase namespace pointing at the lowercase directory'
        );

        $this->assertSame(
            array('ksfraser\\Tests\\' => 'tests/'),
            $composer['autoload-dev']['psr-4']
        );
    }

    /**
     * The declared PHP floor must match the code, and the platform pin must match
     * the container.
     *
     * src/ uses typed properties (7.4+ syntax), so `require php` must NOT be
     * lowered to 7.3: that would be a false promise and the module would fatal on
     * 7.3 at parse time. config.platform pins 7.4.33 because that is what the FA
     * container actually runs.
     *
     * @return void
     */
    public function testPhpFloorIsHonestAndPlatformPinMatchesTheContainer(): void
    {
        $composer = json_decode(file_get_contents($this->root . '/composer.json'), true);

        $this->assertSame('>=7.4', $composer['require']['php'], 'src/ uses typed properties, so 7.4 is the floor');
        $this->assertSame(
            '7.4.33',
            $composer['config']['platform']['php'],
            'the platform pin must match the FA container runtime, or the lock resolves for the wrong PHP'
        );
    }

    /**
     * Nothing unreachable may sit in src/ — the check that would have caught the
     * quarantined iCalService.
     *
     * @return void
     */
    public function testEverySourceFileIsActuallyAutoloadable(): void
    {
        $tool = $this->root . '/tools/check_autoload.php';

        if (!file_exists($tool)) {
            $this->fail('tools/check_autoload.php is missing; it is the guard for unloadable source');
        }

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tool) . ' 2>&1';
        $output = array();
        $status = 0;

        exec($command, $output, $status);

        $this->assertSame(
            0,
            $status,
            "every class under src/ must be autoloadable with resolvable dependencies:\n  "
            . implode("\n  ", $output)
        );
    }
}