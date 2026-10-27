<?php

/**
 * HHS publishes one set of poverty guidelines for the 48 contiguous states and DC, and one each for Alaska and
 * Hawaii.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Income;

enum PovertyRegion: string
{
    case Contiguous = 'contiguous';
    case Alaska = 'alaska';
    case Hawaii = 'hawaii';
}
