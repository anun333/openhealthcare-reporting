<?php

/**
 * Age in whole years on a day of the reporting year, as each program counts it: UDS Tables 3A and 4 and the
 * CCBHC measures as of December 31, every FPAR table as of June 30. Dates only: a time of day is ignored.
 *
 * The age is the difference in years, less one when the birthday falls after the as-of day in the calendar,
 * which is what DateTimeImmutable::diff()->y gives for any date of birth on or before that day (a February 29
 * birthday reaches its next year on March 1 in a common year). Integer arithmetic reads a 'YYYY-MM-DD' string
 * without making a date object for every patient; the tests check it against diff() on every day of the
 * as-of years.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Age;

use DateTimeImmutable;

final readonly class AsOfAge
{
    public function __construct(
        public int $month,
        public int $day,
        public FutureBirth $futureBirth = FutureBirth::Null,
    ) {
        // February 29 is refused: a common year has no such day to take an age on.
        if (!checkdate($month, $day, 2001)) {
            throw new \InvalidArgumentException(sprintf('not a day every year has: %02d-%02d', $month, $day));
        }
    }

    public static function yearEnd(FutureBirth $futureBirth = FutureBirth::Null): self
    {
        return new self(12, 31, $futureBirth);
    }

    /** Age on this policy's day of $year; null without a usable date of birth. */
    public function inYear(DateTimeImmutable|string|null $birth, int $year): ?int
    {
        return $this->years(self::parts($birth), [$year, $this->month, $this->day]);
    }

    /** Age on any day (a visit's, say), under this policy's rule for a birth after it. */
    public function on(DateTimeImmutable|string|null $birth, DateTimeImmutable $day): ?int
    {
        return $this->years(self::parts($birth), [(int)$day->format('Y'), (int)$day->format('n'), (int)$day->format('j')]);
    }

    /**
     * @param array{int, int, int}|null $birth
     * @param array{int, int, int}      $on
     */
    private function years(?array $birth, array $on): ?int
    {
        if ($birth === null) {
            return null;
        }
        $years = $on[0] - $birth[0] - (($birth[1] <=> $on[1] ?: $birth[2] <=> $on[2]) > 0 ? 1 : 0);
        if ($years >= 0) {
            return $years;
        }
        return $this->futureBirth === FutureBirth::Zero ? 0 : null;
    }

    /**
     * A date's year, month and day. A string is read as OpenEMR stores a DATE, 'YYYY-MM-DD' (a longer DATETIME's
     * first ten characters), and must be a real date; '0000-00-00', OpenEMR's old empty date, is none.
     *
     * @return array{int, int, int}|null
     */
    public static function parts(DateTimeImmutable|string|null $date): ?array
    {
        if ($date instanceof DateTimeImmutable) {
            return [(int)$date->format('Y'), (int)$date->format('n'), (int)$date->format('j')];
        }
        if ($date === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $date, $m) !== 1) {
            return null;
        }
        [$y, $mo, $d] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        return checkdate($mo, $d, $y) ? [$y, $mo, $d] : null;
    }
}
