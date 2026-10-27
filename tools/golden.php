<?php

/**
 * Writes tests/golden/*.json from the report modules' own code, read from their checkouts beside this package
 * (or UDS_DIR, TITLE_X_DIR, CCBHC_DIR). The modules aren't changed. Run it when a module's answer is meant to
 * change, and commit the new files with the reason.
 *
 *     php tools/golden.php
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

use OpenHealthcare\Reporting\Tests\Modules\Golden;

require dirname(__DIR__) . '/vendor/autoload.php';

foreach (Golden::build() as $module => $golden) {
    file_put_contents(Golden::file($module), Golden::encode($module, $golden));
    $count = array_sum(array_map('count', $golden['cases']));
    printf("%s: %d cases from %s %s (%s)\n", $module, $count, $golden['source']['module'], $golden['source']['version'], substr($golden['source']['commit'], 0, 12));
}
