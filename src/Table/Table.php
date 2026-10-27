<?php

/**
 * One table of a report as a page shows it and a file carries it: its rows and columns in the program's order,
 * totals included, and a cell left null where the form says "not applicable" or the figures can't be computed.
 * Cells are addressed as data quality checks name them, a column letter and a row number: the FPAR's T7_B16 is
 * cell('B16') of table 7.
 *
 * Generalised from the Title X Report's Report\Table (0.2.2), with a string id so UDS's 3A and 6A fit.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Table;

final readonly class Table
{
    /**
     * @param list<string>                                    $columns column headings, A first (26 at most)
     * @param list<array{label: string, cells: list<?int>}> $rows    rows 1 to n, any total row last
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $columns,
        public array $rows,
        public string $note = '',
    ) {
        if (count($columns) > 26) {
            throw new \InvalidArgumentException("table {$id} has more columns than letters");
        }
    }

    /** A cell by reference, such as 'J21'; null when it's not applicable or not computed. */
    public function cell(string $ref): ?int
    {
        if (preg_match('/^([A-Z])(\d+)$/', $ref, $m) !== 1) {
            throw new \InvalidArgumentException("not a cell reference: $ref");
        }
        $row = $this->rows[(int)$m[2] - 1] ?? throw new \InvalidArgumentException("no row {$m[2]} in table {$this->id}");
        return $row['cells'][ord($m[1]) - ord('A')] ?? null;
    }

    /** @return array<string, int> every computed cell by reference, for comparing years */
    public function cells(): array
    {
        $out = [];
        foreach ($this->rows as $r => $row) {
            foreach ($row['cells'] as $c => $value) {
                if ($value !== null) {
                    $out[chr(ord('A') + $c) . ($r + 1)] = $value;
                }
            }
        }
        return $out;
    }
}
