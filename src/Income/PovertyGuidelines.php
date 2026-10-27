<?php

/**
 * HHS poverty guidelines by year and region, from data/poverty-guidelines.json: a household's guideline is the
 * first person's amount plus each additional person's. A year or region whose figures aren't filled in has no
 * guideline, and a report says so rather than estimating.
 *
 * The file is checked as the UDS Report checks its copy: a malformed row stops the load with its position
 * rather than being skipped, and a year and region listed twice is refused.
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Income;

final readonly class PovertyGuidelines
{
    /** @param array<int, array<string, array{first: int, additional: int, source: string}>> $byYear */
    private function __construct(private array $byYear)
    {
    }

    /** The package's file, or another with the same shape. */
    public static function load(?string $path = null): self
    {
        $path ??= dirname(__DIR__, 2) . '/data/poverty-guidelines.json';
        return self::fromData(json_decode((string)file_get_contents($path), true, flags: JSON_THROW_ON_ERROR), $path);
    }

    public static function fromData(mixed $data, string $label = 'poverty guidelines'): self
    {
        if (!is_array($data) || !is_string($data['about'] ?? null) || !is_array($data['guidelines'] ?? null) || !array_is_list($data['guidelines'])) {
            throw new \UnexpectedValueException("$label: needs about and a list of guidelines");
        }
        $byYear = [];
        $seen = [];
        foreach ($data['guidelines'] as $i => $g) {
            $at = "$label guidelines[$i]";
            $year = is_array($g) ? ($g['year'] ?? null) : null;
            $region = is_array($g) ? PovertyRegion::tryFrom((string)($g['region'] ?? '')) : null;
            if (!is_int($year) || $year < 2000 || $year > 2100 || $region === null) {
                throw new \UnexpectedValueException("$at: needs a year from 2000 to 2100 and a region (contiguous, alaska or hawaii)");
            }
            if (isset($seen[$year][$region->value])) {
                throw new \UnexpectedValueException("$at: $year {$region->value} is listed twice");
            }
            $seen[$year][$region->value] = true;
            if (($g['firstPerson'] ?? null) === null && ($g['eachAdditional'] ?? null) === null) {
                continue; // still to be filled in; listed so the gap is visible
            }
            foreach (['firstPerson', 'eachAdditional'] as $field) {
                if (!is_int($g[$field] ?? null) || $g[$field] < 1 || $g[$field] > 1_000_000) {
                    throw new \UnexpectedValueException("$at.$field: needs a whole number of dollars from 1 to 1,000,000");
                }
            }
            if (!is_string($g['source'] ?? null) || trim($g['source']) === '') {
                throw new \UnexpectedValueException("$at.source: figures need their source");
            }
            $byYear[$year][$region->value] = ['first' => $g['firstPerson'], 'additional' => $g['eachAdditional'], 'source' => $g['source']];
        }
        return new self($byYear);
    }

    public function has(int $year, PovertyRegion $region): bool
    {
        return isset($this->byYear[$year][$region->value]);
    }

    /** The guideline for a household of this size, or null when the year and region aren't loaded. */
    public function guideline(int $year, PovertyRegion $region, int $householdSize): ?int
    {
        $g = $this->byYear[$year][$region->value] ?? null;
        if ($g === null || $householdSize < 1) {
            return null;
        }
        return $g['first'] + ($householdSize - 1) * $g['additional'];
    }

    public function source(int $year, PovertyRegion $region): string
    {
        return $this->byYear[$year][$region->value]['source'] ?? '';
    }
}
