<?php

/**
 * Cells that changed a lot since last year, the way OPA's data quality checks flag them: by half or more of last
 * year's figure, or up from 0. A cell computed this year but not last (no poverty guidelines that year, say) isn't
 * compared, and 0 both years isn't a change.
 *
 * Ported from the Title X Report's Report\PriorYear (0.2.2), over any keyed set of tables.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Table;

final class PriorYear
{
    public const THRESHOLD = 0.5;

    /**
     * @param array<int|string, Table> $now   this year's tables by id
     * @param array<int|string, Table> $prior last year's, keyed the same way
     * @return list<array{table: int|string, cell: string, row: string, column: string, prior: int, now: int, change: ?int}> change in
     *         whole percent, null when last year's figure was 0
     */
    public static function changes(array $now, array $prior, float $threshold = self::THRESHOLD): array
    {
        $out = [];
        foreach ($now as $id => $table) {
            $before = isset($prior[$id]) ? $prior[$id]->cells() : [];
            foreach ($table->cells() as $ref => $value) {
                if (!array_key_exists($ref, $before)) {
                    continue;
                }
                $was = $before[$ref];
                if ($was === 0 && $value === 0) {
                    continue;
                }
                if ($was !== 0 && abs($value - $was) < $threshold * $was) {
                    continue;
                }
                $r = (int)substr($ref, 1) - 1;
                $c = ord($ref[0]) - ord('A');
                $out[] = ['table' => $id, 'cell' => "T{$id}_$ref", 'row' => $table->rows[$r]['label'], 'column' => $table->columns[$c] ?? '',
                    'prior' => $was, 'now' => $value, 'change' => $was === 0 ? null : (int)round(($value - $was) * 100 / $was)];
            }
        }
        return $out;
    }
}
