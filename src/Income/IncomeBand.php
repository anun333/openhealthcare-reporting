<?php

/**
 * Income against a poverty guideline, the two ways the programs draw their bands. Which one a report uses is the
 * program's rule, so both stay:
 *
 * - UDS Table 4 says "100% and below": an income one dollar over the guideline is in the 101-150% line.
 *   atOrBelow() compares exactly, in whole dollars, with no rounding.
 * - The FPAR's Table 4 says "Below 101%": its bands go by the whole percent, rounded down, so 100.9 percent is
 *   still in the first band. wholePercent() gives that percent.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Income;

final class IncomeBand
{
    /** Whether the income is at or below $percent of the guideline (UDS: 100, 150, 200). */
    public static function atOrBelow(int $annualIncome, int $guideline, int $percent): bool
    {
        return $annualIncome * 100 <= $guideline * $percent;
    }

    /** The income as a whole percent of the guideline, rounded down (the FPAR's bands). */
    public static function wholePercent(int|float|string $annualIncome, int $guideline): int
    {
        if ($guideline <= 0) {
            throw new \InvalidArgumentException("not a guideline: $guideline");
        }
        return (int)floor((float)$annualIncome * 100 / $guideline);
    }
}
