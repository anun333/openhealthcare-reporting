<?php

/**
 * What each report module answers today, by calling its own code: a public method where there is one, a private
 * one through reflection (Title X's age and income band), and for the two rules a page writes inline, a copy of
 * those lines that ModuleSource::line() checks against the page first.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Tests\Modules;

use DateTimeImmutable;
use OpenEMR\Modules\UdsReport\Enum\PovertyRegion as UdsRegion;
use OpenEMR\Modules\UdsReport\Model\UdsPatient;
use OpenEMR\Modules\UdsReport\Report\CsvRow;
use OpenEMR\Modules\UdsReport\Report\ReportYear;
use OpenEMR\Modules\UdsReport\Report\SmallCells as UdsSmallCells;
use OpenEMR\Modules\UdsReport\ReferenceData\PovertyGuidelines as UdsGuidelines;
use OpenHealthcare\CcbhcReport\Domain\Client;
use OpenHealthcare\CcbhcReport\Report\CsvRow as CcbhcCsvRow;
use OpenHealthcare\TitleXReport\Reference\PovertyGuidelines as TitleXGuidelines;
use OpenHealthcare\TitleXReport\Report\CsvFile;
use OpenHealthcare\TitleXReport\Report\PriorYear as TitleXPriorYear;
use OpenHealthcare\TitleXReport\Report\Row;
use OpenHealthcare\TitleXReport\Report\Table as TitleXTable;
use OpenHealthcare\TitleXReport\Report\Tables;
use OpenHealthcare\TitleXReport\Report\TablesBuilder;

final class Answers
{
    // UDS Report (src/Report/SmallCells.php, ReportYear.php, Model/UdsPatient.php, ReferenceData/PovertyGuidelines.php, Report/CsvRow.php)

    public static function udsOne(int $count): ?int
    {
        ModuleSource::load('uds');
        return UdsSmallCells::one($count);
    }

    /**
     * @param array<array-key, int> $cells
     * @return array<array-key, ?int>
     */
    public static function udsBreakdown(array $cells): array
    {
        ModuleSource::load('uds');
        return UdsSmallCells::breakdown($cells);
    }

    public static function udsPercent(?int $part, ?int $whole): ?int
    {
        ModuleSource::load('uds');
        return UdsSmallCells::percent($part, $whole);
    }

    public static function udsYear(string $query, string $now): int
    {
        ModuleSource::load('uds');
        return ReportYear::fromQuery($query, new DateTimeImmutable($now));
    }

    public static function udsAge(?string $dob, int $year): ?int
    {
        ModuleSource::load('uds');
        $patient = (new \ReflectionClass(UdsPatient::class))->newInstanceWithoutConstructor();
        $value = $dob === null ? null : new DateTimeImmutable($dob);
        \Closure::bind(function () use ($value): void {
            $this->dob = $value;
        }, $patient, UdsPatient::class)();
        return $patient->ageAtYearEnd($year);
    }

    public static function udsIncomeLine(?int $income, ?int $size, int $year, string $region): string
    {
        ModuleSource::load('uds');
        static $guidelines = null;
        $guidelines ??= UdsGuidelines::fromData(json_decode((string)file_get_contents(ModuleSource::dir('uds') . '/data/poverty-guidelines.json'), true, flags: JSON_THROW_ON_ERROR));
        return $guidelines->incomeLine($income, $size, $year, UdsRegion::from($region));
    }

    /** @param list<list<int|string>> $lines */
    public static function udsCsv(array $lines): string
    {
        ModuleSource::load('uds');
        $out = fopen('php://temp', 'w+');
        foreach ($lines as $line) {
            CsvRow::write($out, $line);
        }
        rewind($out);
        return (string)stream_get_contents($out);
    }

    // Title X Report (src/Report/TablesBuilder.php, Reference/PovertyGuidelines.php, Report/CsvFile.php, Report/PriorYear.php, public/index.php)

    public static function titleXYear(?int $asked, string $now): int
    {
        ModuleSource::line('title-x', 'public/index.php', '$year = $request->query->getInt(\'year\', $thisYear - 1); // FPAR reports the prior calendar year');
        ModuleSource::line('title-x', 'public/index.php', '$year = max(2020, min($thisYear, $year));');
        $thisYear = (int)(new DateTimeImmutable($now))->format('Y');
        $year = $asked ?? $thisYear - 1; // getInt('year', default): the default when the query has no year
        $year = max(2020, min($thisYear, $year));
        return $year;
    }

    public static function titleXAge(string $birthDate, int $year): int
    {
        return self::titleXBuilder($year)->age($birthDate);
    }

    /**
     * The income band index of a user's visits, latest last, as Table 4 counts it.
     *
     * @param list<array{string, string}> $visits annual_household_income, household_size per visit
     */
    public static function titleXIncomeRow(array $visits, int $year, string $region): int
    {
        $builder = self::titleXBuilder($year, $region);
        $rows = array_map(fn(array $v, int $i): Row => new Row($i + 1, 1, ['annual_household_income' => $v[0], 'household_size' => $v[1]], [], []), $visits, array_keys($visits));
        return $builder->incomeRow($rows);
    }

    public static function titleXGuideline(int $year, string $region, int $size): ?int
    {
        return self::titleXGuidelines()->guideline($year, $region, $size);
    }

    /**
     * @param list<string>                $columns
     * @param list<array<string, string>> $records
     */
    public static function titleXCsv(array $columns, array $records): string
    {
        ModuleSource::load('title-x');
        return CsvFile::records($columns, $records);
    }

    /**
     * @param array<int, array{title: string, columns: list<string>, rows: list<array{label: string, cells: list<?int>}>}> $now
     * @param array<int, array{title: string, columns: list<string>, rows: list<array{label: string, cells: list<?int>}>}> $prior
     * @return list<array<string, mixed>>
     */
    public static function titleXPriorYear(array $now, array $prior): array
    {
        ModuleSource::load('title-x');
        $tables = static function (int $year, array $spec): Tables {
            $built = array_map(fn(array $t, int $n): TitleXTable => new TitleXTable($n, $t['title'], $t['columns'], $t['rows']), $spec, array_keys($spec));
            $tables = (new \ReflectionClass(Tables::class))->newInstanceWithoutConstructor();
            \Closure::bind(function () use ($year, $built, $spec): void {
                $this->year = $year;
                $this->tables = array_combine(array_keys($spec), $built);
            }, $tables, Tables::class)();
            return $tables;
        };
        return TitleXPriorYear::changes($tables(2025, $now), $tables(2024, $prior));
    }

    private static function titleXGuidelines(): TitleXGuidelines
    {
        ModuleSource::load('title-x');
        static $guidelines = null;
        return $guidelines ??= TitleXGuidelines::load(ModuleSource::dir('title-x') . '/data/poverty-guidelines.json');
    }

    private static function titleXBuilder(int $year, string $region = 'contiguous'): object
    {
        $guidelines = self::titleXGuidelines();
        $map = json_decode((string)file_get_contents(ModuleSource::dir('title-x') . '/data/fpar-tables.json'), true, flags: JSON_THROW_ON_ERROR);
        $builder = (new \ReflectionClass(TablesBuilder::class))->newInstanceWithoutConstructor();
        \Closure::bind(function () use ($year, $map, $guidelines, $region): void {
            $this->year = $year;
            $this->map = $map;
            $this->guidelines = $guidelines;
            $this->region = $region;
        }, $builder, TablesBuilder::class)();
        return new class ($builder) {
            public function __construct(private readonly object $builder)
            {
            }

            public function age(string $birthDate): int
            {
                return (new \ReflectionMethod(TablesBuilder::class, 'age'))->invoke($this->builder, $birthDate);
            }

            /** @param list<Row> $rows */
            public function incomeRow(array $rows): int
            {
                return (new \ReflectionMethod(TablesBuilder::class, 'incomeRow'))->invoke($this->builder, $rows);
            }
        };
    }

    // CCBHC Report (src/Domain/Client.php, public/index.php, src/Report/CsvRow.php)

    public static function ccbhcYear(string $query, string $now): int
    {
        ModuleSource::line('ccbhc', 'public/index.php', '$year = (int)($_GET[\'year\'] ?? 0);');
        ModuleSource::line('ccbhc', 'public/index.php', '$year = $year >= 2025 && $year <= $thisYear ? $year : $thisYear - 1;');
        $thisYear = (int)(new DateTimeImmutable($now))->format('Y');
        $year = (int)$query; // $_GET['year'] ?? 0: a missing year is '' here, which casts to 0 the same way
        $year = $year >= 2025 && $year <= $thisYear ? $year : $thisYear - 1;
        return $year;
    }

    public static function ccbhcAge(?string $dob, string $day): ?int
    {
        ModuleSource::load('ccbhc');
        $client = new Client(1, $dob === null ? null : new DateTimeImmutable($dob), 'female', Client::UNKNOWN, Client::UNKNOWN);
        return $client->ageOn(new DateTimeImmutable($day));
    }

    /** The client list's guard on a cell (every column, from 0.1.6). */
    public static function ccbhcSafe(string $value): string
    {
        ModuleSource::load('ccbhc');
        return (string)CcbhcCsvRow::cell($value);
    }

    /** @param list<list<int|string>> $lines */
    public static function ccbhcCsv(array $lines): string
    {
        ModuleSource::load('ccbhc');
        $out = fopen('php://temp', 'w+');
        foreach ($lines as $line) {
            CcbhcCsvRow::write($out, $line);
        }
        rewind($out);
        return (string)stream_get_contents($out);
    }
}
