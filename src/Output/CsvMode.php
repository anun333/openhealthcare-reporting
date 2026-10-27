<?php

/**
 * Who a CSV file is for, which decides whether a cell may be changed on the way out.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Output;

enum CsvMode
{
    /**
     * A file a program's system reads (OPA's FPAR upload): every cell exactly as given, since a changed cell is a
     * wrong submission. Its cells come from coded fields, not free text.
     */
    case Exact;

    /**
     * A file a person opens in a spreadsheet (UDS downloads, CCBHC client lists): a text cell starting with =, +,
     * - or @ gets an apostrophe in front so opening the file can't run a formula; a negative number such as
     * -12.50 is a number and is left alone.
     */
    case Spreadsheet;
}
