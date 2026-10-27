<?php

/**
 * The package against the report modules' own code, run side by side, where the modules are checked out beside
 * this package (skipped elsewhere, CI included; GoldenTest holds the package to their recorded answers there).
 *
 * - The golden files are still what the modules answer.
 * - The suppression port gives UDS's answers on UDS's own test cases, and on 20,000 random breakdowns.
 * - Ages and income bands agree on random dates and incomes.
 * - The poverty figures are the same in all three files.
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
use OpenHealthcare\Reporting\Suppression\SmallCells;
use OpenHealthcare\Reporting\Tests\Modules\Answers;
use OpenHealthcare\Reporting\Tests\Modules\Golden;
use OpenHealthcare\Reporting\Tests\Modules\ModuleSource;
use PHPUnit\Framework\TestCase;

final class ModuleParityTest extends TestCase
{
    private const SEED = 20261026;

    private static function need(string ...$modules): void
    {
        foreach ($modules as $module) {
            if (!ModuleSource::present($module)) {
                self::markTestSkipped("no checkout of $module at " . ModuleSource::dir($module));
            }
        }
    }

    public function testTheGoldenFilesAreWhatTheModulesAnswerToday(): void
    {
        self::need('uds', 'title-x', 'ccbhc');
        foreach (Golden::build() as $module => $golden) {
            self::assertSame(
                (string)file_get_contents(Golden::file($module)),
                Golden::encode($module, $golden),
                "$module answers differently from tests/golden/$module.json: run tools/golden.php if that's meant"
            );
        }
    }

    /** UDS's tests/GrantEvidenceTest.php, every assertion, against both implementations. */
    public function testSuppressionGivesUdsAnswersOnUdsOwnCases(): void
    {
        self::need('uds');
        $one = [0, 1, 10, 11, 250];
        self::assertSame([0, null, null, 11, 250], array_map(Answers::udsOne(...), $one));
        self::assertSame([0, null, null, 11, 250], array_map(SmallCells::one(...), $one));
        $breakdowns = [
            [['a' => 5, 'b' => 40, 'c' => 75], ['a' => null, 'b' => null, 'c' => 75]],
            [['a' => 5, 'b' => 3, 'c' => 75], ['a' => null, 'b' => null, 'c' => 75]],
            [['a' => 0, 'b' => 12, 'c' => 75], ['a' => 0, 'b' => 12, 'c' => 75]],
            [['a' => 0, 'b' => 7, 'c' => 75], ['a' => 0, 'b' => null, 'c' => null]],
        ];
        foreach ($breakdowns as [$cells, $expected]) {
            self::assertSame($expected, Answers::udsBreakdown($cells));
            self::assertSame($expected, SmallCells::breakdown($cells));
        }
        foreach ([[25, 100, 25], [null, 100, null], [25, null, null], [0, 0, null]] as [$part, $whole, $expected]) {
            self::assertSame($expected, Answers::udsPercent($part, $whole));
            self::assertSame($expected, SmallCells::percent($part, $whole));
        }
    }

    public function testSuppressionGivesUdsAnswersOnRandomBreakdowns(): void
    {
        self::need('uds');
        mt_srand(self::SEED);
        for ($n = -5; $n <= 2000; $n++) {
            self::assertSame(Answers::udsOne($n), SmallCells::one($n), "one($n)");
        }
        for ($i = 0; $i < 20_000; $i++) {
            $cells = [];
            $size = mt_rand(1, 8);
            $named = mt_rand(0, 1) === 1;
            for ($c = 0; $c < $size; $c++) {
                // Mostly small counts, so one-hidden and tie cases come up often.
                $value = mt_rand(0, 3) === 0 ? mt_rand(0, 500) : mt_rand(0, 15);
                $named ? $cells['k' . mt_rand(0, 20)] = $value : $cells[] = $value;
            }
            self::assertSame(Answers::udsBreakdown($cells), SmallCells::breakdown($cells), json_encode($cells, JSON_THROW_ON_ERROR));
            $part = mt_rand(0, 9) === 0 ? null : mt_rand(0, 300);
            $whole = mt_rand(0, 9) === 0 ? null : mt_rand(0, 300);
            self::assertSame(Answers::udsPercent($part, $whole), SmallCells::percent($part, $whole), "percent($part, $whole)");
        }
    }

    public function testAgesAgreeOnRandomDates(): void
    {
        self::need('uds', 'title-x', 'ccbhc');
        mt_srand(self::SEED);
        $day = static fn(int $from, int $to): string => date('Y-m-d', mt_rand(strtotime("$from-01-01 12:00 UTC"), strtotime("$to-12-31 12:00 UTC")));
        for ($i = 0; $i < 5_000; $i++) {
            $dob = $day(1900, 2030);
            $year = mt_rand(2020, 2027);
            self::assertSame(Answers::udsAge($dob, $year), Programs::udsAge()->inYear($dob, $year), "UDS $dob in $year");
            self::assertSame(Answers::titleXAge($dob, $year), Programs::titleXAge()->inYear($dob, $year) ?? 0, "Title X $dob in $year");
            $on = $day(2020, 2027);
            $ccbhc = Answers::ccbhcAge($dob, $on);
            // The one named change (GoldenTest::changes()): a birth after the day is 0 in the package.
            self::assertSame($dob > $on ? 0 : $ccbhc, Programs::ccbhcAge()->on($dob, new DateTimeImmutable($on)), "CCBHC $dob on $on");
        }
    }

    public function testIncomeBandsAgreeOnRandomIncomes(): void
    {
        self::need('uds', 'title-x');
        mt_srand(self::SEED);
        foreach (Golden::GUIDELINE_YEARS as [$year, $region]) {
            for ($i = 0; $i < 2_000; $i++) {
                $size = mt_rand(0, 12);
                $income = mt_rand(0, 150_000);
                self::assertSame(Answers::udsIncomeLine($income, $size, $year, $region), Programs::udsIncomeLine($income, $size, $year, $region), "UDS $income, $size, $year $region");
                $cents = mt_rand(0, 1) === 1 ? sprintf('%d.%02d', $income, mt_rand(0, 99)) : (string)$income;
                $visits = [[$cents, (string)$size]];
                self::assertSame(Answers::titleXIncomeRow($visits, $year, $region), Programs::titleXIncomeRow($visits, $year, $region), "Title X $cents, $size, $year $region");
            }
        }
    }

    public function testThePovertyFiguresAreTheSameInAllThreeFiles(): void
    {
        self::need('uds', 'title-x');
        $figures = static fn(string $path): array => json_decode((string)file_get_contents($path), true, flags: JSON_THROW_ON_ERROR)['guidelines'];
        $ours = $figures(dirname(__DIR__) . '/data/poverty-guidelines.json');
        self::assertSame($figures(ModuleSource::dir('uds') . '/data/poverty-guidelines.json'), $ours);
        self::assertSame($figures(ModuleSource::dir('title-x') . '/data/poverty-guidelines.json'), $ours);
    }
}
