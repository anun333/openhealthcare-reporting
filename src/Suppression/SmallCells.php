<?php

/**
 * Small-cell suppression for counts shown outside the clinic (grant applications, public dashboards). CMS's cell
 * size policy hides any count from 1 to 10; 0 and 11 or more are shown. In a breakdown that sums to a shown
 * total, one hidden cell could be worked out by subtraction, so when exactly one cell is hidden the next
 * smallest positive cell is hidden with it (complementary suppression).
 *
 * Ported unchanged from the UDS Report's Report\SmallCells (0.17.5, src/Report/SmallCells.php), where the grant
 * evidence page uses it; tests/ModuleParityTest.php runs both on the same cases.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Suppression;

final class SmallCells
{
    /** The smallest count shown. */
    public const MIN = 11;

    /** A count as shown: itself when 0 or MIN and over, otherwise null (hidden). */
    public static function one(int $count): ?int
    {
        return $count === 0 || $count >= self::MIN ? $count : null;
    }

    /**
     * Each count as shown, with complementary suppression when exactly one is hidden. Keys are kept; with two
     * cells tied for next smallest, the first in key order is hidden.
     *
     * @template K of array-key
     * @param array<K, int> $cells
     * @return array<K, ?int>
     */
    public static function breakdown(array $cells): array
    {
        $out = array_map(self::one(...), $cells);
        $hidden = array_keys(array_filter($out, static fn(?int $v): bool => $v === null));
        if (count($hidden) === 1) {
            $smallest = null;
            foreach ($out as $key => $value) {
                if ($value !== null && $value > 0 && ($smallest === null || $value < $out[$smallest])) {
                    $smallest = $key;
                }
            }
            if ($smallest !== null) {
                $out[$smallest] = null;
            }
        }
        return $out;
    }

    /** A whole percent of shown counts, or null when either is hidden or the whole is 0. */
    public static function percent(?int $part, ?int $whole): ?int
    {
        if ($part === null || $whole === null || $whole === 0) {
            return null;
        }
        return (int)round(100 * $part / $whole);
    }
}
