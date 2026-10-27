<?php

/**
 * The golden files: each report module's answers on fixed synthetic inputs (no patient data), written by
 * tools/golden.php from the modules' own code and kept in tests/golden/. GoldenTest holds this package to them
 * without the modules; ModuleParityTest rebuilds them from the modules, where they're checked out, to show the
 * files are still what the modules answer.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests\Modules;

final class Golden
{
    public const DIR = __DIR__ . '/../golden';

    /** The nows the year rules are asked on: either side of UDS's March switch, year ends, a leap year. */
    public const NOWS = ['2024-02-10', '2025-06-15', '2026-01-01', '2026-03-31', '2026-04-01', '2026-10-26', '2026-12-31', '2027-01-15'];

    public const BIRTHS = [
        null, '1925-03-15', '2000-01-01', '2000-02-29', '2000-06-30', '2000-07-01', '2000-12-31', '2004-02-29',
        '2024-12-31', '2025-01-01', '2025-06-30', '2025-07-01', '2025-12-31', '2026-01-01', '2026-06-30',
        '2026-12-31', '2027-06-01', '2030-01-01',
    ];

    public const AGE_YEARS = [2024, 2025, 2026];

    /** Days a CCBHC measure takes an age on: visit days, year ends, February 28 and 29. */
    public const DAYS = ['2024-02-28', '2024-02-29', '2025-02-28', '2025-03-01', '2025-06-30', '2025-12-31', '2026-10-26'];

    /** Years and regions for the income rules: loaded, and not (2025 Alaska is still to be filled in, 2024 isn't listed). */
    public const GUIDELINE_YEARS = [[2026, 'contiguous'], [2026, 'alaska'], [2026, 'hawaii'], [2025, 'contiguous'], [2025, 'alaska'], [2024, 'contiguous']];

    /** @return array<string, array{source: array<string, string>, cases: array<string, list<array{in: list<mixed>, out: mixed}>>}> */
    public static function build(): array
    {
        return ['uds' => self::uds(), 'title-x' => self::titleX(), 'ccbhc' => self::ccbhc()];
    }

    /** @return array{source: array<string, string>, cases: array<string, list<array{in: list<mixed>, out: mixed}>>} */
    private static function uds(): array
    {
        $cases = [];
        foreach ([...range(-1, 25), 99, 100, 250, 1000] as $n) {
            $cases['smallCells.one'][] = ['in' => [$n], 'out' => Answers::udsOne($n)];
        }
        foreach (self::breakdowns() as $cells) {
            $cases['smallCells.breakdown'][] = ['in' => [$cells], 'out' => Answers::udsBreakdown($cells)];
        }
        foreach ([[25, 100], [null, 100], [25, null], [0, 0], [1, 3], [2, 3], [1, 8], [5, 8], [0, 11], [11, 11], [1, 200], [3, 200], [199, 200]] as [$part, $whole]) {
            $cases['smallCells.percent'][] = ['in' => [$part, $whole], 'out' => Answers::udsPercent($part, $whole)];
        }
        foreach (['', '2019', '2023', '2024', '2025', '2026', '2027', '0', '20x6', ' 2025', '2025 ', "2025\n"] as $q) {
            foreach (self::NOWS as $now) {
                $cases['reportYear.fromQuery'][] = ['in' => [$q, $now], 'out' => Answers::udsYear($q, $now)];
            }
        }
        foreach (self::BIRTHS as $dob) {
            foreach (self::AGE_YEARS as $year) {
                $cases['age.atYearEnd'][] = ['in' => [$dob, $year], 'out' => Answers::udsAge($dob, $year)];
            }
        }
        foreach (self::GUIDELINE_YEARS as [$year, $region]) {
            foreach ([null, 0, 1, 2, 3, 4, 8] as $size) {
                foreach ([null, -1, 0, 15960, 15961, 21640, 21641, 32460, 32461, 43280, 43281, 54100, 54101, 1_000_000] as $income) {
                    $cases['income.line'][] = ['in' => [$income, $size, $year, $region], 'out' => Answers::udsIncomeLine($income, $size, $year, $region)];
                }
            }
        }
        $cases['csv.spreadsheet'][] = ['in' => [self::csvLines()], 'out' => Answers::udsCsv(self::csvLines())];
        return ['source' => ModuleSource::identity('uds'), 'cases' => $cases];
    }

    /** @return array{source: array<string, string>, cases: array<string, list<array{in: list<mixed>, out: mixed}>>} */
    private static function titleX(): array
    {
        $cases = [];
        foreach ([null, 0, 2019, 2020, 2023, 2024, 2025, 2026, 2027] as $asked) {
            foreach (self::NOWS as $now) {
                $cases['reportYear.resolve'][] = ['in' => [$asked, $now], 'out' => Answers::titleXYear($asked, $now)];
            }
        }
        // RowBuilder::date() hands the age either '' or a YYYY-MM-DD date that isn't 0000-00-00.
        foreach ([...array_filter(self::BIRTHS), ''] as $dob) {
            foreach (self::AGE_YEARS as $year) {
                $cases['age.juneThirty'][] = ['in' => [$dob, $year], 'out' => Answers::titleXAge($dob, $year)];
            }
        }
        foreach (self::GUIDELINE_YEARS as [$year, $region]) {
            foreach ([0, 1, 2, 4, 8] as $size) {
                $cases['income.guideline'][] = ['in' => [$year, $region, $size], 'out' => Answers::titleXGuideline($year, $region, $size)];
            }
        }
        foreach (self::GUIDELINE_YEARS as [$year, $region]) {
            foreach (self::titleXVisits() as $visits) {
                $cases['income.row'][] = ['in' => [$visits, $year, $region], 'out' => Answers::titleXIncomeRow($visits, $year, $region)];
            }
        }
        [$columns, $records] = self::csvRecords();
        $cases['csv.exact'][] = ['in' => [$columns, $records], 'out' => Answers::titleXCsv($columns, $records)];
        foreach (self::priorYearPairs() as [$now, $prior]) {
            $cases['priorYear.changes'][] = ['in' => [$now, $prior], 'out' => Answers::titleXPriorYear($now, $prior)];
        }
        return ['source' => ModuleSource::identity('title-x'), 'cases' => $cases];
    }

    /** @return array{source: array<string, string>, cases: array<string, list<array{in: list<mixed>, out: mixed}>>} */
    private static function ccbhc(): array
    {
        $cases = [];
        foreach (['', '0', '2019', '2024', '2025', '2026', '2027', '20x6', '2025abc'] as $q) {
            foreach (self::NOWS as $now) {
                $cases['reportYear.resolve'][] = ['in' => [$q, $now], 'out' => Answers::ccbhcYear($q, $now)];
            }
        }
        foreach (self::BIRTHS as $dob) {
            foreach (self::DAYS as $day) {
                $cases['age.on'][] = ['in' => [$dob, $day], 'out' => Answers::ccbhcAge($dob, $day)];
            }
        }
        foreach (['Smith, Ann', 'O\'Neil, Pat', '-Smith', '=HYPERLINK("x")', '+1', '@SUM(1)', '-12', '-12.5', '12', '', ' =x', 'A-1007'] as $v) {
            $cases['csv.safe'][] = ['in' => [$v], 'out' => Answers::ccbhcSafe($v)];
        }
        return ['source' => ModuleSource::identity('ccbhc'), 'cases' => $cases];
    }

    /** @return list<array<array-key, int>> UDS's own four cases first (tests/GrantEvidenceTest.php), then edges */
    public static function breakdowns(): array
    {
        return [
            ['a' => 5, 'b' => 40, 'c' => 75],
            ['a' => 5, 'b' => 3, 'c' => 75],
            ['a' => 0, 'b' => 12, 'c' => 75],
            ['a' => 0, 'b' => 7, 'c' => 75],
            [],
            ['a' => 0],
            ['a' => 5],
            ['a' => 5, 'b' => 0, 'c' => 0],
            [5, 40, 40],
            [40, 5, 12, 12],
            [3, 4, 50],
            [11, 11, 11],
            [0, 0, 0],
            [10, 11, 12, 13],
            ['x' => 1, 'y' => 1000],
            [12, 5, 12],
            [-1, 40, 75],
            ['OTHER_ZIPS' => 4, '96941' => 230, '96942' => 18],
        ];
    }

    /** @return list<list<int|string>> */
    public static function csvLines(): array
    {
        return [
            ['UDS demographic tables', '2025', 'generated 2026-10-26 09:00', 'All patients'],
            [],
            ['Line', 'Description', 'Male', 'Female'],
            ['1', 'Under age 1', 12, 14],
            ['=SUM(A1:A2)', '+1', '-12.50', '-5'],
            ['-x', '@home', 'a,b', 'say "hi"'],
            ['back\\slash', "line\nbreak", ' =lead', ''],
            ["tab\tin", '=1+1', -3, 0],
            ['café', 'naïve', '日本', '-0.5'],
        ];
    }

    /** @return array{list<string>, list<array<string, string>>} */
    public static function csvRecords(): array
    {
        return [
            ['facility_id', 'patient_id', 'visit_date', 'birth_date', 'sex', 'note'],
            [
                ['facility_id' => '3', 'patient_id' => 'P-001', 'visit_date' => '2025-03-04', 'birth_date' => '2001-07-09', 'sex' => '2', 'note' => ''],
                ['facility_id' => '3', 'patient_id' => '=cmd', 'visit_date' => '2025-03-05', 'birth_date' => '', 'sex' => '1'],
                ['facility_id' => '3', 'patient_id' => '-7', 'visit_date' => '2025-03-06', 'birth_date' => '1990-01-01', 'sex' => '2', 'note' => 'a,b "q" back\\slash'],
                ['facility_id' => '4', 'patient_id' => '+1', 'visit_date' => '2025-03-07', 'birth_date' => '1988-12-31', 'sex' => '2', 'note' => "two\nlines"],
            ],
        ];
    }

    /** @return list<list<array{string, string}>> each a user's visits, latest last */
    public static function titleXVisits(): array
    {
        $out = [];
        foreach (['', '0', '15960', '16119', '16119.59', '16119.6', '21640', '21856', '21857', '21640.50', '32460', '43280', '54100', '54101', '1000000'] as $income) {
            foreach (['', '0', '1', '2', '4'] as $size) {
                $out[] = [[$income, $size]];
            }
        }
        return [
            ...$out,
            [['30000', '2'], ['', '']],
            [['', ''], ['30000', '2']],
            [['10000', '1'], ['50000', '1']],
            [['', '3'], ['20000', '']],
            [],
        ];
    }

    /** @return list<array{array<int, array<string, mixed>>, array<int, array<string, mixed>>}> */
    public static function priorYearPairs(): array
    {
        $table = static fn(array $rows, string $title = 'Users by age and sex'): array => ['title' => $title, 'columns' => ['Female', 'Male'], 'rows' => $rows];
        return [
            [
                [1 => $table([['label' => 'Under 15', 'cells' => [10, 0]], ['label' => '15-17', 'cells' => [15, 3]], ['label' => '18-19', 'cells' => [100, null]], ['label' => 'Total', 'cells' => [125, 3]]])],
                [1 => $table([['label' => 'Under 15', 'cells' => [20, 0]], ['label' => '15-17', 'cells' => [10, 0]], ['label' => '18-19', 'cells' => [67, 4]], ['label' => 'Total', 'cells' => [97, 4]]])],
            ],
            [
                [1 => $table([['label' => 'A', 'cells' => [149, 51]]]), 4 => $table([['label' => 'Below 101%', 'cells' => [40, null]]], 'Users by income')],
                [1 => $table([['label' => 'A', 'cells' => [100, 100]]]), 3 => $table([['label' => 'X', 'cells' => [1, 1]]])],
            ],
            [
                [2 => $table([['label' => 'B', 'cells' => [7, 0]], ['label' => 'C', 'cells' => [0, 0]]])],
                [2 => $table([['label' => 'B', 'cells' => [14, 0]], ['label' => 'C', 'cells' => [0, null]]])],
            ],
        ];
    }

    public static function file(string $module): string
    {
        return self::DIR . "/$module.json";
    }

    /** @param array{source: array<string, string>, cases: array<string, list<array{in: list<mixed>, out: mixed}>>} $golden */
    public static function encode(string $module, array $golden): string
    {
        return json_encode([
            'about' => "What the $module module's own code answered on these synthetic inputs, written by tools/golden.php. GoldenTest holds the package to these answers; a module moving onto the package must give the same.",
        ] + $golden, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
    }

    /** @return array{about: string, source: array<string, string>, cases: array<string, list<array{in: list<mixed>, out: mixed}>>} */
    public static function read(string $module): array
    {
        return json_decode((string)file_get_contents(self::file($module)), true, flags: JSON_THROW_ON_ERROR);
    }
}
