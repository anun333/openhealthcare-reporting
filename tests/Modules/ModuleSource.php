<?php

/**
 * The three report modules' checkouts, read and never written: where each is, its version, and an autoloader
 * for its own classes. A module is looked for beside this package (../oe-module-uds-report and so on) unless
 * UDS_DIR, TITLE_X_DIR or CCBHC_DIR names another place.
 *
 * Two rules live inline in a module's page rather than in a class (Title X's and CCBHC's report year).
 * Answers copies those lines and line() checks they still read as copied, so a changed page stops the
 * generator instead of leaving a stale copy.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests\Modules;

final class ModuleSource
{
    /** @var array<string, array{env: string, repo: string, namespace: string}> */
    public const MODULES = [
        'uds' => ['env' => 'UDS_DIR', 'repo' => 'oe-module-uds-report', 'namespace' => 'OpenEMR\\Modules\\UdsReport\\'],
        'title-x' => ['env' => 'TITLE_X_DIR', 'repo' => 'oe-module-title-x-report', 'namespace' => 'OpenHealthcare\\TitleXReport\\'],
        'ccbhc' => ['env' => 'CCBHC_DIR', 'repo' => 'oe-module-ccbhc-report', 'namespace' => 'OpenHealthcare\\CcbhcReport\\'],
    ];

    /** @var array<string, true> */
    private static array $registered = [];

    public static function dir(string $module): string
    {
        $m = self::MODULES[$module] ?? throw new \InvalidArgumentException("no module $module");
        $env = getenv($m['env']);
        return is_string($env) && $env !== '' ? rtrim($env, '/') : dirname(__DIR__, 3) . '/' . $m['repo'];
    }

    public static function present(string $module): bool
    {
        return is_dir(self::dir($module) . '/src');
    }

    /** Lets the module's classes load from its src/, once. */
    public static function load(string $module): void
    {
        if (isset(self::$registered[$module])) {
            return;
        }
        if (!self::present($module)) {
            throw new \RuntimeException("no checkout of $module at " . self::dir($module));
        }
        $namespace = self::MODULES[$module]['namespace'];
        $src = self::dir($module) . '/src/';
        spl_autoload_register(static function (string $class) use ($namespace, $src): void {
            if (str_starts_with($class, $namespace)) {
                $file = $src . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
                if (is_file($file)) {
                    require $file;
                }
            }
        });
        self::$registered[$module] = true;
    }

    /**
     * The module's version (its CHANGELOG's latest heading) and commit, read from files so it works where git
     * isn't installed (the PHP images the tests run in).
     *
     * @return array{module: string, version: string, commit: string}
     */
    public static function identity(string $module): array
    {
        $dir = self::dir($module);
        $version = preg_match('/^## (\d+\.\d+\.\d+)/m', (string)@file_get_contents("$dir/CHANGELOG.md"), $m) === 1 ? $m[1] : 'unknown';
        return ['module' => self::MODULES[$module]['repo'], 'version' => $version, 'commit' => self::head("$dir/.git")];
    }

    private static function head(string $git): string
    {
        $head = trim((string)@file_get_contents("$git/HEAD"));
        if (!str_starts_with($head, 'ref: ')) {
            return $head !== '' ? $head : 'unknown';
        }
        $ref = substr($head, 5);
        $loose = @file_get_contents("$git/$ref");
        if (is_string($loose)) {
            return trim($loose);
        }
        foreach (@file("$git/packed-refs", FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (str_ends_with($line, " $ref")) {
                return strtok($line, ' ');
            }
        }
        return 'unknown';
    }

    /** Stops unless the module's file still has this line, trimmed, exactly as Answers copied it. */
    public static function line(string $module, string $file, string $expected): void
    {
        $path = self::dir($module) . '/' . $file;
        $lines = array_map('trim', file($path, FILE_IGNORE_NEW_LINES) ?: []);
        if (!in_array(trim($expected), $lines, true)) {
            throw new \RuntimeException("$module changed: $file no longer has the line Answers copies:\n  $expected\nRead the page again and update Answers.");
        }
    }
}
