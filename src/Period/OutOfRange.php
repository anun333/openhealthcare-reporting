<?php

/**
 * What a report page does with a year it can't report on (before its earliest year, or after this one).
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Period;

enum OutOfRange
{
    /** Show the default year instead (UDS, CCBHC). */
    case UseDefault;

    /** Show the nearest year it can (Title X: 2019 shows the earliest, 2031 this year). */
    case Clamp;
}
