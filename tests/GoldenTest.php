<?php

/**
 * The package against the golden files: on every synthetic input, configured as each module will configure it,
 * it gives what that module's own code gave. No answer changes when a module moves: the two the design named,
 * both CCBHC's, went out in CCBHC 0.1.6 (a birth after the day is age 0; every text cell of the client list is
 * guarded). An answer meant to change is named by a rule in changes(), and the test fails if any other differs.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests;

use DateTimeImmutable;
use OpenHealthcare\Reporting\Income\PovertyRegion;
use OpenHealthcare\Reporting\Output\Csv;
use OpenHealthcare\Reporting\Output\CsvMode;
use OpenHealthcare\Reporting\Suppression\SmallCells;
use OpenHealthcare\Reporting\Table\PriorYear;
use OpenHealthcare\Reporting\Tests\Modules\Golden;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GoldenTest extends TestCase
{
    /** @return array<string, array<string, \Closure>> the package's answer for each kind of case */
    private static function package(): array
    {
        return [
            'uds' => [
                'smallCells.one' => fn(int $n): ?int => SmallCells::one($n),
                'smallCells.breakdown' => fn(array $cells): array => SmallCells::breakdown($cells),
                'smallCells.percent' => fn(?int $part, ?int $whole): ?int => SmallCells::percent($part, $whole),
                'reportYear.fromQuery' => fn(string $q, string $now): int => Programs::udsYear()->fromQuery($q, new DateTimeImmutable($now)),
                'age.atYearEnd' => fn(?string $dob, int $year): ?int => Programs::udsAge()->inYear($dob, $year),
                'income.line' => Programs::udsIncomeLine(...),
                'csv.spreadsheet' => fn(array $lines): string => Csv::lines($lines, CsvMode::Spreadsheet),
            ],
            'title-x' => [
                'reportYear.resolve' => fn(?int $asked, string $now): int => Programs::titleXYear()->resolve($asked, new DateTimeImmutable($now)),
                'age.juneThirty' => fn(string $dob, int $year): int => Programs::titleXAge()->inYear($dob, $year) ?? 0,
                'income.guideline' => fn(int $year, string $region, int $size): ?int => Programs::guidelines()->guideline($year, PovertyRegion::from($region), $size),
                'income.row' => Programs::titleXIncomeRow(...),
                'csv.exact' => fn(array $columns, array $records): string => Csv::records($columns, $records, CsvMode::Exact),
                'priorYear.changes' => fn(array $now, array $prior): array => PriorYear::changes(Programs::tables($now), Programs::tables($prior)),
            ],
            'ccbhc' => [
                'reportYear.resolve' => fn(string $q, string $now): int => Programs::ccbhcYear()->resolve((int)$q, new DateTimeImmutable($now)),
                'age.on' => fn(?string $dob, string $day): ?int => Programs::ccbhcAge()->on($dob, new DateTimeImmutable($day)),
                'csv.safe' => fn(string $v): string => (string)Csv::cell($v),
                'csv.spreadsheet' => fn(array $lines): string => Csv::lines($lines, CsvMode::Spreadsheet),
            ],
        ];
    }

    /**
     * The answers that change on purpose, by rule: [module, kind, applies(in), the package's answer(in)].
     *
     * @return list<array{string, string, \Closure, \Closure, string}>
     */
    private static function changes(): array
    {
        // None now. CCBHC 0.1.6 (2026-10-26) took the two the design named: Client::ageOn() gives 0 for a birth
        // after the day, and the client list guards every text cell with UDS's rule, so a chart number like -12
        // keeps no apostrophe.
        return [];
    }

    /** @return iterable<string, array{string, string, list<mixed>, mixed}> */
    public static function cases(): iterable
    {
        foreach (['uds', 'title-x', 'ccbhc'] as $module) {
            foreach (Golden::read($module)['cases'] as $kind => $list) {
                foreach ($list as $i => $case) {
                    yield "$module $kind #$i" => [$module, $kind, $case['in'], $case['out']];
                }
            }
        }
    }

    /** @param list<mixed> $in */
    #[DataProvider('cases')]
    public function testThePackageGivesWhatTheModuleGave(string $module, string $kind, array $in, mixed $out): void
    {
        $answer = self::package()[$module][$kind] ?? null;
        self::assertNotNull($answer, "no package answer for $module $kind");
        $expected = $out;
        foreach (self::changes() as [$m, $k, $applies, $instead]) {
            if ($m === $module && $k === $kind && $applies(...$in)) {
                $expected = $instead(...$in);
            }
        }
        self::assertSame($expected, $answer(...$in));
    }

    /** Each change named above happens on at least one golden input, so the design's account of them stays true. */
    public function testEachNamedChangeIsExercised(): void
    {
        if (self::changes() === []) {
            $this->expectNotToPerformAssertions();
            return;
        }
        foreach (self::changes() as [$module, $kind, $applies, $instead, $what]) {
            $differs = 0;
            foreach (Golden::read($module)['cases'][$kind] as $case) {
                if ($applies(...$case['in']) && $instead(...$case['in']) !== $case['out']) {
                    $differs++;
                }
            }
            self::assertGreaterThan(0, $differs, "$module $kind: $what");
        }
    }

    public function testEveryGoldenKindHasAPackageAnswer(): void
    {
        foreach (['uds', 'title-x', 'ccbhc'] as $module) {
            $golden = Golden::read($module);
            self::assertSame(array_keys($golden['cases']), array_keys(self::package()[$module]), $module);
            self::assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $golden['source']['commit'], "$module golden files come from a commit, not a working tree");
        }
    }
}
