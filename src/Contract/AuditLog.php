<?php

/**
 * Where a report records that someone viewed or downloaded it. A module passes an adapter over OpenEMR's audit
 * log (openhealthcare-common's OpenEmr::audit('other', ...)), so this package needs no OpenEMR class and its
 * tests need no database.
 *
 * The wording of an entry is each module's and stays as it is when the module moves here: sites search their
 * audit logs for it ("Title X Report: FPAR file for 2025 downloaded, 412 rows").
 *
 * @package   OpenHealthcare\Reporting
 * @link      https://github.com/anun333/openhealthcare-reporting
 * @author    anun333 <anun333@posteo.net>
 * @copyright Copyright (c) 2026 anun333 <anun333@posteo.net>
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenHealthcare\Reporting\Contract;

interface AuditLog
{
    public function record(string $comment): void;
}
