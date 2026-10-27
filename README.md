# openhealthcare-reporting

The parts the openhealthcare report modules for [OpenEMR](https://www.open-emr.org) share: UDS Report, Title X Report and CCBHC Report today, and the program reports after them (Ryan White's RSR, public dashboards). Each module has its own copy of these parts, and the copies already differ in small ways: how a year is chosen, how an age is taken, how a CSV cell is guarded. This package will hold one of each, configured per program.

It's a plain Composer library, not an OpenEMR module, and it needs no OpenEMR class: a module hands it data and adapters. **Not released.** While it's 0.x the report modules that use it release together, since a 0.x minor may change an interface (see the design, `docs/products/reporting-engine-2026-10.md` in the openhealthcare workspace).

## What's in it

| Part | Class | From |
|---|---|---|
| Small-cell suppression: counts 1 to 10 hidden, the next smallest hidden with a lone hidden cell, percents of shown counts only | `Suppression\SmallCells` | UDS `Report\SmallCells`, unchanged |
| Reporting years: the earliest year, when the default moves from last year to this, what an out-of-range year does | `Period\ReportYearPolicy`, `Period\ReportingPeriod` | UDS `Report\ReportYear`, Title X and CCBHC `public/index.php` |
| Age on a day of the year (December 31, June 30) or any day, with a rule for a birth after it | `Age\AsOfAge`, `Age\FutureBirth` | UDS `UdsPatient::ageAtYearEnd()`, Title X `TablesBuilder::age()`, CCBHC `Client::ageOn()` |
| HHS poverty guidelines, one file, and both band rules (UDS's exact "100% and below", the FPAR's whole percent) | `Income\PovertyGuidelines`, `Income\IncomeBand` | UDS and Title X, whose files hold the same figures |
| Report tables addressed by cell (`B16`), and last year's big changes | `Table\Table`, `Table\PriorYear` | Title X `Report\Table`, `Report\PriorYear` |
| CSV files: exact for a program's upload, guarded for a spreadsheet | `Output\Csv`, `Output\CsvMode` | Title X `CsvFile`, UDS `CsvRow` |
| What a built report and an audit log look like to the package | `Contract\Report`, `Contract\AuditLog` | new |

Each program's settings, written with these parts, are in `tests/Programs.php`: that's how a module will configure the package when it moves.

## How it's held to the modules

- **Golden files** (`tests/golden/*.json`): each module's own code, run on fixed synthetic inputs (1,651 cases, no patient data), with the module's version and commit. `tools/golden.php` writes them from the modules' checkouts, read and never changed. `GoldenTest` checks the package gives the same answer on every case. Two answers change on purpose when CCBHC moves, both on data no clinic should have; `GoldenTest::changes()` names them by rule, and any other difference fails.
- **Side by side** (`ModuleParityTest`): where the modules are checked out beside this package, it rebuilds the golden files from them and compares, runs UDS's own suppression tests against both implementations, and compares 20,000 random breakdowns, 5,000 random dates of birth and 12,000 random incomes. It skips where the modules aren't checked out, CI included.

```sh
composer install
vendor/bin/phpunit          # with ../oe-module-uds-report etc. beside it, ModuleParityTest runs too
php tools/golden.php        # when a module's answer is meant to change; commit the files with the reason
```

## Licence

GPL-3.0-or-later, as OpenEMR. The poverty guideline figures are HHS's, in the public domain.
