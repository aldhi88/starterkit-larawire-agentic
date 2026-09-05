# Testing and Definition of Done

Every code change adds or updates relevant tests, primarily Pest feature tests. Iterate with the smallest test file/filter, then run affected-area tests, Pint after PHP changes, and the full suite for cross-infrastructure changes. Never add a baseline, ignore rule, or suppression merely to make static analysis green; correct source types and behavior.

```bash
# Composer package repository
composer validate --strict
composer analyse
vendor/bin/pint --test
composer test

# Derived Laravel host
php artisan test --compact tests/Feature/NameTest.php
php artisan test --compact --filter="behavior name"
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

Test relevant behavior: schema/casts/relations/constraints; repository contract, query scope/filter/sort/pagination/eager-loading/budgets; service transactions/audit/rollback; permitted and denied roles/Superuser protection; Livewire render/validation/action/redirect/events; route/module/menu sync dry-run; API disabled/enabled gateway/routes/middleware/auth/validation/OpenAPI/CORS; and security/session/host/redirect/throttle cases.

For UI, test empty/low/high datasets and real browser behavior when JavaScript changes: page assets do not leak, typing in normal forms causes no request, and navigation does not duplicate scripts/listeners/widgets. Cross-origin navigation is full-page. Verify each supported theme independently: capability parity must remain, while visible structure and styling must match that theme's cited vendor example rather than another theme. PowerGrid tests cover Builder source, active theme adapter, live search/sort/pagination, all meaningful column filters, date from/to boundaries, reload-persisted state, selection/by-filter scope, individual/bulk actions, reset selection, and query budget; an excluded filter needs tested justification.

Test mutable lifecycles (create/update/archive/filter archived/restore/permanent delete only from archive/owned-relation protection), bulk selected/filtered/all guards, safe scope/count dialog metadata, summaries, and rollback. Keep `tests/Feature/StarterArchitectureTest.php` strict: controllers/Livewire do not query/load relations/use service locators and services do not build model queries without an explicit infrastructure allowlist and reason. Host integration confirms Composer discovery, version boundary, fresh-source rejection, destructive confirmations, automatic pre-reset security and application tests, rollback behavior, config-cache restart, internal-flag rejection, core autoload/migration/theme/routes/views, dynamic App migration discovery, AGENTS connector, and starter/theme/PowerGrid assets.

Public release CI runs package validation, full `src/` PHPStan, Pint, and package tests on every supported PHP version. A release additionally installs the package through Composer into a clean supported Laravel host and exercises the wizard/install result, `starter:sync`, route listing, tests, and a production-like `starter:deploy` preflight. Claims for database auto-creation are tested per supported driver or clearly marked as environment-dependent.

Theme tests must validate `theme-package-contract.json`: every intake HTML is
accounted for in `source-index.json`; every required capability maps to an
indexed vendor reference, runtime view, and tested state; the asset recipe
rejects missing/tampered/orphan files and produces an exact host directory;
required runtime groups and both layouts exist; license/distribution evidence
is explicit; Composer archive contains no raw vendor HTML/assets; and the
fresh-host browser matrix proves both layouts without cross-theme residue. A
scaffold-only or manifest-only integration is not a passing theme.

Every new or re-audited theme must maintain the fail-closed current-run ledger at
`theme-intake/<theme-key>/.starter-theme-run/verification-evidence.json` and keep
it valid against `theme-verification-evidence.schema.json`. The final ledger may
say `pass` only when all input fingerprints are current, every ordered stage is
`pass`, every automated command exits zero, every required page/state/layout at
`1280x768` and `390x844` has both screenshot and computed-layout evidence, and
the known-defect and skipped-check lists are empty. CSS/media-query inspection is not rendered responsive evidence. A shared component, selector, token, adapter,
or JavaScript change invalidates and reopens every consuming matrix row; an
archive change additionally requires reinstalling that exact archive and
repeating browser smoke from the packaged runtime.
The final mandatory check is
`php tools/validate-theme-evidence.php <theme-key>`; any non-zero exit keeps the
theme incomplete and must be fixed without weakening the validator or evidence.

Done means: acceptance criteria met; no unexplained TODO/TBD; relevant tests and Pint pass; App config/routes were dry-run synced; browser verification was completed for UX changes; no unapproved scope/dependency/config/business decision exists; and the confirmed/approved single issue specification is archived only after verified completion. Core changes must be focused-committed and released canonically before the Laravel host updates its Composer lock.
