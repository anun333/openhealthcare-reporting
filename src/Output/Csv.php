<?php

/**
 * CSV files as the report modules write them: UTF-8, comma-separated, double quotes only where a cell needs them,
 * no escape character (so a backslash is an ordinary character, as RFC 4180 has it), lines ending in \n.
 *
 * Exact mode is the Title X Report's Report\CsvFile::records() (0.2.2); spreadsheet mode adds the UDS Report's
 * Report\CsvRow::cell() guard (0.17.5).
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Output;

final class Csv
{
    /**
     * A file with a heading row of column names and one line per record, a missing column written empty.
     *
     * @param list<string>                    $columns
     * @param list<array<string, int|string>> $records
     */
    public static function records(array $columns, array $records, CsvMode $mode): string
    {
        return self::lines([$columns, ...array_map(fn(array $record): array => array_map(fn(string $c): int|string => $record[$c] ?? '', $columns), $records)], $mode);
    }

    /** @param list<list<int|string>> $lines */
    public static function lines(array $lines, CsvMode $mode): string
    {
        $out = fopen('php://temp', 'w+');
        if ($out === false) {
            throw new \RuntimeException('no temporary stream for the CSV');
        }
        foreach ($lines as $line) {
            self::write($out, $line, $mode);
        }
        rewind($out);
        $csv = (string)stream_get_contents($out);
        fclose($out);
        return $csv;
    }

    /**
     * One line to an open stream, for a download written as it's read.
     *
     * @param resource              $out
     * @param array<int|string> $cells
     */
    public static function write($out, array $cells, CsvMode $mode): void
    {
        $cells = array_values($cells);
        if ($mode === CsvMode::Spreadsheet) {
            $cells = array_map(self::cell(...), $cells);
        }
        fputcsv($out, $cells, ',', '"', '');
    }

    /** A cell as spreadsheet mode writes it. */
    public static function cell(int|string $value): int|string
    {
        if (!is_string($value) || preg_match('/^-\d+(?:\.\d+)?$/D', $value) === 1) {
            return $value;
        }
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'" . $value : $value;
    }
}
