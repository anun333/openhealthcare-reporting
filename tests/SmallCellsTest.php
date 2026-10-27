<?php

/**
 * The UDS Report's tests/GrantEvidenceTest.php suppression cases (0.17.5), unchanged, against the port.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests;

use OpenHealthcare\Reporting\Suppression\SmallCells;
use PHPUnit\Framework\TestCase;

final class SmallCellsTest extends TestCase
{
    public function testCountsFromOneToTenAreHiddenAndZeroIsShown(): void
    {
        self::assertSame([0, null, null, 11, 250], array_map(SmallCells::one(...), [0, 1, 10, 11, 250]));
    }

    public function testAOneCellGapHidesTheNextSmallestToo(): void
    {
        // Total 120: with only the 5 hidden, 120 - 40 - 75 would give it away, so the 40 goes as well.
        self::assertSame(['a' => null, 'b' => null, 'c' => 75], SmallCells::breakdown(['a' => 5, 'b' => 40, 'c' => 75]));
        // Two hidden already: nothing more to hide.
        self::assertSame(['a' => null, 'b' => null, 'c' => 75], SmallCells::breakdown(['a' => 5, 'b' => 3, 'c' => 75]));
        // Nothing hidden: nothing changes. A zero never counts as the next smallest.
        self::assertSame(['a' => 0, 'b' => 12, 'c' => 75], SmallCells::breakdown(['a' => 0, 'b' => 12, 'c' => 75]));
        self::assertSame(['a' => 0, 'b' => null, 'c' => null], SmallCells::breakdown(['a' => 0, 'b' => 7, 'c' => 75]));
    }

    public function testAPercentNeedsBothCountsShown(): void
    {
        self::assertSame(25, SmallCells::percent(25, 100));
        self::assertNull(SmallCells::percent(null, 100));
        self::assertNull(SmallCells::percent(25, null));
        self::assertNull(SmallCells::percent(0, 0));
    }

    public function testATieForNextSmallestHidesTheFirst(): void
    {
        self::assertSame([40, null, null, 12], SmallCells::breakdown([40, 5, 12, 12]));
    }

    public function testOneHiddenAmongZerosHasNothingToHideWithIt(): void
    {
        // The other cells are 0 and shown; a 0 is never hidden to protect a neighbour.
        self::assertSame(['a' => null, 'b' => 0, 'c' => 0], SmallCells::breakdown(['a' => 5, 'b' => 0, 'c' => 0]));
    }
}
