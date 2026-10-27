<?php

/**
 * Which calendar year a report page shows: the year asked for when the program can report on it, otherwise a
 * default. Programs differ in three ways, each a constructor argument:
 *
 * - the earliest year the page offers (the first year the module's rules cover);
 * - when the default moves from last year to this year: UDS is filed by February 15, so its pages show last
 *   year through March and the year so far after (priorYearThroughMonth 3); the FPAR and the CCBHC measures
 *   always default to the year most recently ended (12);
 * - what an out-of-range year does (OutOfRange).
 *
 * The latest year offered is always this year. Parsing the query string is separate: fromQuery() takes four
 * digits and nothing else, as the UDS Report does; a page that already has an integer calls resolve().
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Period;

use DateTimeImmutable;

final readonly class ReportYearPolicy
{
    /**
     * @param int $priorYearThroughMonth the last month (1-12) in which the default is last year; 0 for never
     */
    public function __construct(
        public int $earliest,
        public int $priorYearThroughMonth,
        public OutOfRange $outOfRange,
    ) {
        if ($priorYearThroughMonth < 0 || $priorYearThroughMonth > 12) {
            throw new \InvalidArgumentException("not a month: $priorYearThroughMonth");
        }
    }

    public function default(DateTimeImmutable $now): int
    {
        $year = (int)$now->format('Y');
        return (int)$now->format('n') <= $this->priorYearThroughMonth ? $year - 1 : $year;
    }

    /** The year to show for the year asked for, null when none was. */
    public function resolve(?int $asked, DateTimeImmutable $now): int
    {
        $latest = (int)$now->format('Y');
        if ($this->outOfRange === OutOfRange::Clamp) {
            return max($this->earliest, min($latest, $asked ?? $this->default($now)));
        }
        return $asked !== null && $asked >= $this->earliest && $asked <= $latest ? $asked : $this->default($now);
    }

    /** The year to show for a query-string value: four digits, or the default. */
    public function fromQuery(string $raw, DateTimeImmutable $now): int
    {
        return $this->resolve(preg_match('/^\d{4}$/D', $raw) === 1 ? (int)$raw : null, $now);
    }

    public function period(?int $asked, DateTimeImmutable $now): ReportingPeriod
    {
        return new ReportingPeriod($this->resolve($asked, $now));
    }
}
