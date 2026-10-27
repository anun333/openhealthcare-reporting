<?php

/**
 * What an age is when the date of birth is after the day it's taken on, which only a data-entry error gives.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Age;

enum FutureBirth
{
    /** No age, as for a missing date of birth (UDS: the patient is a data gap). */
    case Null;

    /** Age 0 (the FPAR preview, which counts every user in an age band). */
    case Zero;
}
