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

use OpenHealthcare\Reporting\Output\Csv;
use OpenHealthcare\Reporting\Output\CsvMode;
use OpenHealthcare\Reporting\Table\PriorYear;
use OpenHealthcare\Reporting\Table\Table;
use PHPUnit\Framework\TestCase;

final class TablesAndFilesTest extends TestCase
{
    private static function table(): Table
    {
        return new Table('3A', 'Patients by age and sex', ['Male', 'Female'], [
            ['label' => 'Under age 1', 'cells' => [12, 14]],
            ['label' => 'Age 1', 'cells' => [null, 9]],
            ['label' => 'Total', 'cells' => [12, 23]],
        ]);
    }

    public function testCellsByReference(): void
    {
        self::assertSame(23, self::table()->cell('B3'));
        self::assertNull(self::table()->cell('A2'));
        self::assertSame(['A1' => 12, 'B1' => 14, 'B2' => 9, 'A3' => 12, 'B3' => 23], self::table()->cells());
    }

    public function testABadReferenceIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::table()->cell('B4');
    }

    public function testPriorYearByStringIds(): void
    {
        $prior = new Table('3A', 'Patients by age and sex', ['Male', 'Female'], [
            ['label' => 'Under age 1', 'cells' => [6, 14]],
            ['label' => 'Age 1', 'cells' => [3, 0]],
            ['label' => 'Total', 'cells' => [9, 14]],
        ]);
        self::assertSame([
            ['table' => '3A', 'cell' => 'T3A_A1', 'row' => 'Under age 1', 'column' => 'Male', 'prior' => 6, 'now' => 12, 'change' => 100],
            ['table' => '3A', 'cell' => 'T3A_B2', 'row' => 'Age 1', 'column' => 'Female', 'prior' => 0, 'now' => 9, 'change' => null],
            ['table' => '3A', 'cell' => 'T3A_B3', 'row' => 'Total', 'column' => 'Female', 'prior' => 14, 'now' => 23, 'change' => 64],
        ], PriorYear::changes(['3A' => self::table()], ['3A' => $prior]));
    }

    public function testExactFilesKeepEveryCell(): void
    {
        self::assertSame("a,b\n=1+1,-x\n", Csv::records(['a', 'b'], [['a' => '=1+1', 'b' => '-x']], CsvMode::Exact));
    }

    public function testSpreadsheetFilesCantRunAFormula(): void
    {
        self::assertSame("'=1+1,'-x,-12.50,'+1,'@a,\" =x\"\n", Csv::lines([['=1+1', '-x', '-12.50', '+1', '@a', ' =x']], CsvMode::Spreadsheet));
    }

    public function testABackslashIsAnOrdinaryCharacter(): void
    {
        self::assertSame("\"say \\\"\"\"\n", Csv::lines([['say \\"']], CsvMode::Exact));
    }
}
