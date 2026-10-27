<?php

/**
 * A calendar reporting year, as every report module so far counts one: January 1 to December 31. Queries take it
 * as a half-open range (on or after the start, before the next year's start), so a visit at 23:59:59 on
 * December 31 is in and one at midnight on January 1 is not, whatever the column's precision.
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

final readonly class ReportingPeriod
{
    public function __construct(public int $year)
    {
        if ($year < 1900 || $year > 9998) {
            throw new \InvalidArgumentException("not a reporting year: $year");
        }
    }

    /** January 1. */
    public function start(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-01-01', $this->year));
    }

    /** December 31, the last day in the period (the period ends when this day does). */
    public function lastDay(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-12-31', $this->year));
    }

    /** January 1 of the next year, the first moment outside the period. */
    public function endExclusive(): DateTimeImmutable
    {
        return new DateTimeImmutable(sprintf('%04d-01-01', $this->year + 1));
    }

    /** A day of the year, such as June 30 for an age the FPAR counts. */
    public function day(int $month, int $day): DateTimeImmutable
    {
        if (!checkdate($month, $day, $this->year)) {
            throw new \InvalidArgumentException(sprintf('no %02d-%02d in %d', $month, $day, $this->year));
        }
        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $this->year, $month, $day));
    }

    /**
     * The binds for `col >= ? AND col < ?`, as 'Y-m-d H:i:s' strings, which compare correctly against DATE,
     * DATETIME and TIMESTAMP columns alike.
     *
     * @return array{string, string}
     */
    public function binds(): array
    {
        return [$this->start()->format('Y-m-d H:i:s'), $this->endExclusive()->format('Y-m-d H:i:s')];
    }

    public function contains(DateTimeImmutable $moment): bool
    {
        return $moment >= $this->start() && $moment < $this->endExclusive();
    }
}
