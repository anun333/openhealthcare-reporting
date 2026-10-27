<?php

/**
 * Each report module's rules written with this package, as the module will write them when it moves here. The
 * golden tests hold these to what the modules answer today.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests;

use OpenHealthcare\Reporting\Age\AsOfAge;
use OpenHealthcare\Reporting\Age\FutureBirth;
use OpenHealthcare\Reporting\Income\IncomeBand;
use OpenHealthcare\Reporting\Income\PovertyGuidelines;
use OpenHealthcare\Reporting\Income\PovertyRegion;
use OpenHealthcare\Reporting\Period\OutOfRange;
use OpenHealthcare\Reporting\Period\ReportYearPolicy;
use OpenHealthcare\Reporting\Table\Table;

final class Programs
{
    /** UDS: from 2024, last year through March (filed by February 15), any other year shows the default. */
    public static function udsYear(): ReportYearPolicy
    {
        return new ReportYearPolicy(2024, 3, OutOfRange::UseDefault);
    }

    /** FPAR: from 2020, always last year by default, an out-of-range year clamped. */
    public static function titleXYear(): ReportYearPolicy
    {
        return new ReportYearPolicy(2020, 12, OutOfRange::Clamp);
    }

    /** CCBHC: from 2025, the year most recently ended by default (reports are due nine months after it). */
    public static function ccbhcYear(): ReportYearPolicy
    {
        return new ReportYearPolicy(2025, 12, OutOfRange::UseDefault);
    }

    /** UDS Tables 3A and 4: December 31; a birth after it is a data gap, not an age. */
    public static function udsAge(): AsOfAge
    {
        return AsOfAge::yearEnd(FutureBirth::Null);
    }

    /** FPAR: June 30, every user in a band, so no age is 0. */
    public static function titleXAge(): AsOfAge
    {
        return new AsOfAge(6, 30, FutureBirth::Zero);
    }

    /** CCBHC: on the day a measure names (a visit's, or December 31); a birth after it is 0. */
    public static function ccbhcAge(): AsOfAge
    {
        return AsOfAge::yearEnd(FutureBirth::Zero);
    }

    public static function guidelines(): PovertyGuidelines
    {
        static $guidelines = null;
        return $guidelines ??= PovertyGuidelines::load();
    }

    /** UDS Table 4's income line: '1' 100% and below, '2' 101-150%, '3' 151-200%, '4' over 200%, '5' unknown. */
    public static function udsIncomeLine(?int $income, ?int $size, int $year, string $region): string
    {
        $guideline = $size === null ? null : self::guidelines()->guideline($year, PovertyRegion::from($region), $size);
        if ($income === null || $income < 0 || $guideline === null) {
            return '5';
        }
        foreach (['1' => 100, '2' => 150, '3' => 200] as $line => $percent) {
            if (IncomeBand::atOrBelow($income, $guideline, $percent)) {
                return (string)$line;
            }
        }
        return '4';
    }

    /**
     * FPAR Table 4's band index for a user's visits, latest last: the latest with both income and household size,
     * by whole percent (up to 100, 150, 200, 250, above), else unknown (5).
     *
     * @param list<array{string, string}> $visits
     */
    public static function titleXIncomeRow(array $visits, int $year, string $region): int
    {
        $upTo = [100, 150, 200, 250];
        $unknown = count($upTo) + 1;
        foreach (array_reverse($visits) as [$income, $size]) {
            if ($income === '' || $size === '') {
                continue;
            }
            $guideline = self::guidelines()->guideline($year, PovertyRegion::from($region), (int)$size);
            if ($guideline === null) {
                return $unknown;
            }
            $percent = IncomeBand::wholePercent($income, $guideline);
            foreach ($upTo as $i => $limit) {
                if ($percent <= $limit) {
                    return $i;
                }
            }
            return $unknown - 1;
        }
        return $unknown;
    }

    /**
     * @param array<int|string, array{title: string, columns: list<string>, rows: list<array{label: string, cells: list<?int>}>}> $spec
     * @return array<int|string, Table>
     */
    public static function tables(array $spec): array
    {
        $out = [];
        foreach ($spec as $id => $t) {
            $out[$id] = new Table((string)$id, $t['title'], $t['columns'], $t['rows']);
        }
        return $out;
    }
}
