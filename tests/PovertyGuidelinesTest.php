<?php

/**
 * The poverty guidelines. tests/cases/poverty-guidelines.json is the UDS Report's data/cases file (0.17.5),
 * copied unchanged: "Any program that uses poverty-guidelines.json must give these results."
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests;

use OpenHealthcare\Reporting\Income\IncomeBand;
use OpenHealthcare\Reporting\Income\PovertyGuidelines;
use OpenHealthcare\Reporting\Income\PovertyRegion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PovertyGuidelinesTest extends TestCase
{
    /** @return array<string, array<string, mixed>> */
    private static function caseFile(string $list): array
    {
        $data = json_decode((string)file_get_contents(__DIR__ . '/cases/poverty-guidelines.json'), true, flags: JSON_THROW_ON_ERROR);
        return array_column(array_map(fn(array $c): array => ['name' => $c['name'], 'case' => $c], $data[$list]), 'case', 'name');
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function thresholds(): array
    {
        return array_map(fn(array $c): array => [$c], self::caseFile('thresholds'));
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function incomeLines(): array
    {
        return array_map(fn(array $c): array => [$c], self::caseFile('incomeLines'));
    }

    /** @param array<string, mixed> $c */
    #[DataProvider('thresholds')]
    public function testThresholds(array $c): void
    {
        self::assertSame($c['threshold'], Programs::guidelines()->guideline($c['year'], PovertyRegion::from($c['region']), $c['familySize']));
    }

    /** @param array<string, mixed> $c */
    #[DataProvider('incomeLines')]
    public function testUdsIncomeLines(array $c): void
    {
        self::assertSame($c['line'], Programs::udsIncomeLine($c['annualIncome'], $c['familySize'], $c['year'], $c['region']));
    }

    public function testTheTwoBandRulesDifferJustOverOneHundredPercent(): void
    {
        // Family of 2, 2026: 21,640. One dollar over is "101-150%" for UDS, still "Below 101%" for the FPAR.
        self::assertFalse(IncomeBand::atOrBelow(21641, 21640, 100));
        self::assertSame(100, IncomeBand::wholePercent(21641, 21640));
        self::assertSame(100, IncomeBand::wholePercent('21856.39', 21640));
        self::assertSame(101, IncomeBand::wholePercent('21857', 21640));
    }

    public function testSourcesAndGaps(): void
    {
        $g = Programs::guidelines();
        self::assertTrue($g->has(2026, PovertyRegion::Hawaii));
        self::assertFalse($g->has(2025, PovertyRegion::Alaska), 'listed with null figures: still to be filled in');
        self::assertStringContainsString('Federal Register', $g->source(2026, PovertyRegion::Contiguous));
        self::assertSame('', $g->source(2024, PovertyRegion::Contiguous));
    }

    /** @return array<string, array{mixed}> */
    public static function badFiles(): array
    {
        $row = ['year' => 2026, 'region' => 'contiguous', 'firstPerson' => 15960, 'eachAdditional' => 5680, 'source' => 'HHS'];
        return [
            'no about' => [['guidelines' => [$row]]],
            'listed twice' => [['about' => 'x', 'guidelines' => [$row, $row]]],
            'one figure missing' => [['about' => 'x', 'guidelines' => [['eachAdditional' => null] + $row]]],
            'no source' => [['about' => 'x', 'guidelines' => [['source' => ' '] + $row]]],
            'unknown region' => [['about' => 'x', 'guidelines' => [['region' => 'guam'] + $row]]],
            'figure as text' => [['about' => 'x', 'guidelines' => [['firstPerson' => '15960'] + $row]]],
        ];
    }

    #[DataProvider('badFiles')]
    public function testAMalformedFileStopsTheLoad(mixed $data): void
    {
        $this->expectException(\UnexpectedValueException::class);
        PovertyGuidelines::fromData($data);
    }
}
