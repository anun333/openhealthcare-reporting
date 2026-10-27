<?php

/**
 * Report years. The first cases are the UDS Report's tests/UdsReportRulesTest.php yearCases (0.17.5), unchanged.
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
use OpenHealthcare\Reporting\Period\OutOfRange;
use OpenHealthcare\Reporting\Period\ReportingPeriod;
use OpenHealthcare\Reporting\Period\ReportYearPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ReportYearPolicyTest extends TestCase
{
    /** @return array<string, array{string, string, int}> */
    public static function udsYearCases(): array
    {
        return [
            'march reports last year' => ['', '2026-03-31', 2025],
            'april reports this year' => ['', '2026-04-01', 2026],
            'asked-for year' => ['2025', '2026-06-01', 2025],
            'future year falls back' => ['2027', '2026-06-01', 2026],
            'too early falls back' => ['2019', '2026-06-01', 2026],
            'not a year falls back' => ['20x6', '2026-06-01', 2026],
        ];
    }

    #[DataProvider('udsYearCases')]
    public function testUdsYears(string $query, string $today, int $expected): void
    {
        self::assertSame($expected, Programs::udsYear()->fromQuery($query, new DateTimeImmutable($today)));
    }

    public function testClampingShowsTheNearestYear(): void
    {
        $now = new DateTimeImmutable('2026-10-26');
        $policy = Programs::titleXYear();
        self::assertSame(2025, $policy->resolve(null, $now));
        self::assertSame(2020, $policy->resolve(2019, $now));
        self::assertSame(2026, $policy->resolve(2031, $now));
        self::assertSame(2023, $policy->resolve(2023, $now));
    }

    public function testTheDefaultMovesAfterTheLastPriorYearMonth(): void
    {
        $never = new ReportYearPolicy(2020, 0, OutOfRange::UseDefault);
        $always = new ReportYearPolicy(2020, 12, OutOfRange::UseDefault);
        self::assertSame(2026, $never->default(new DateTimeImmutable('2026-01-01')));
        self::assertSame(2025, $always->default(new DateTimeImmutable('2026-12-31')));
    }

    public function testFourDigitsAndNothingElse(): void
    {
        $now = new DateTimeImmutable('2026-06-01');
        foreach ([' 2025', '2025 ', "2025\n", '+2025', '2025.0', '02025'] as $raw) {
            self::assertSame(2026, Programs::udsYear()->fromQuery($raw, $now), var_export($raw, true));
        }
    }

    public function testAMonthOutsideTheYearIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ReportYearPolicy(2020, 13, OutOfRange::Clamp);
    }

    public function testThePeriodIsHalfOpen(): void
    {
        $period = new ReportingPeriod(2025);
        self::assertSame(['2025-01-01 00:00:00', '2026-01-01 00:00:00'], $period->binds());
        self::assertTrue($period->contains(new DateTimeImmutable('2025-12-31 23:59:59')));
        self::assertFalse($period->contains(new DateTimeImmutable('2026-01-01 00:00:00')));
        self::assertSame('2025-12-31', $period->lastDay()->format('Y-m-d'));
        self::assertSame('2025-06-30', $period->day(6, 30)->format('Y-m-d'));
        self::assertSame(2025, Programs::ccbhcYear()->period(null, new DateTimeImmutable('2026-10-26'))->year);
    }

    public function testFebruaryTwentyNinthOnlyInALeapYear(): void
    {
        self::assertSame('2024-02-29', (new ReportingPeriod(2024))->day(2, 29)->format('Y-m-d'));
        $this->expectException(\InvalidArgumentException::class);
        (new ReportingPeriod(2025))->day(2, 29);
    }
}
