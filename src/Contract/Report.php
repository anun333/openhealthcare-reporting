<?php

/**
 * A built report: the program it's for, the year it covers and its tables. What the package does with any
 * report (compare with last year, write a file, suppress small cells for a public page) goes through this; how a
 * program selects visits and counts them stays in its module.
 *
 * Unstable while the package is 0.x: the report modules that use it release together.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Contract;

use OpenHealthcare\Reporting\Period\ReportingPeriod;
use OpenHealthcare\Reporting\Table\Table;

interface Report
{
    /** A short name for files and audit entries: uds, fpar, ccbhc. */
    public function program(): string;

    public function period(): ReportingPeriod;

    /** @return array<string, Table> by table id, in the order the program's form lists them */
    public function tables(): array;
}
