<?php

/**
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests;

use DateTimeImmutable;
use OpenHealthcare\Reporting\Age\AsOfAge;
use OpenHealthcare\Reporting\Age\FutureBirth;
use PHPUnit\Framework\TestCase;

final class AsOfAgeTest extends TestCase
{
    /** Every date of birth from 1996 to 2026 against diff()->y, on the days the programs take ages on. */
    public function testTheAgeIsWhatDiffGivesForEveryBirthOnOrBeforeTheDay(): void
    {
        $age = AsOfAge::yearEnd();
        $checked = 0;
        foreach (['2024-12-31', '2025-06-30', '2025-02-28', '2024-02-29', '2025-03-01', '2024-02-28'] as $on) {
            $day = new DateTimeImmutable($on);
            for ($birth = new DateTimeImmutable('1996-01-01'); $birth <= $day; $birth = $birth->modify('+1 day')) {
                self::assertSame($birth->diff($day)->y, $age->on($birth, $day), $birth->format('Y-m-d') . " on $on");
                self::assertSame($birth->diff($day)->y, $age->on($birth->format('Y-m-d'), $day), $birth->format('Y-m-d') . " as text on $on");
                $checked++;
            }
        }
        self::assertGreaterThan(60_000, $checked);
    }

    public function testTheDayInTheYear(): void
    {
        $june = new AsOfAge(6, 30);
        self::assertSame(25, $june->inYear('2000-06-30', 2025));
        self::assertSame(24, $june->inYear('2000-07-01', 2025));
        self::assertSame(25, AsOfAge::yearEnd()->inYear('2000-07-01', 2025));
        // A February 29 birthday is a year older on March 1 in a common year, not on February 28.
        self::assertSame(0, (new AsOfAge(2, 28))->inYear('2024-02-29', 2025));
        self::assertSame(1, (new AsOfAge(3, 1))->inYear('2024-02-29', 2025));
    }

    public function testABirthAfterTheDayFollowsThePolicy(): void
    {
        self::assertNull(AsOfAge::yearEnd(FutureBirth::Null)->inYear('2026-01-01', 2025));
        self::assertSame(0, AsOfAge::yearEnd(FutureBirth::Zero)->inYear('2030-01-01', 2025));
        self::assertSame(0, AsOfAge::yearEnd(FutureBirth::Null)->inYear('2025-12-31', 2025), 'born on the day is 0, not future');
    }

    public function testNoUsableBirthIsNoAge(): void
    {
        foreach ([null, '', '0000-00-00', '2025-02-30', '2025-13-01', 'yesterday', '25-01-01'] as $birth) {
            self::assertNull(AsOfAge::yearEnd(FutureBirth::Zero)->inYear($birth, 2025), var_export($birth, true));
        }
        self::assertSame(25, AsOfAge::yearEnd()->inYear('2000-03-04 10:15:00', 2025), 'a DATETIME reads as its date');
    }

    public function testFebruaryTwentyNinthIsNoDayToTakeAnAgeOn(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new AsOfAge(2, 29);
    }
}
