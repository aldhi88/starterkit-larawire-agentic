# Testing and Definition of Done

Every code change adds or updates relevant tests, primarily Pest feature tests. Iterate with the smallest test file/filter, then run affected-area tests, Pint after PHP changes, and the full suite for cross-infrastructure changes. Never add a baseline, ignore rule, or suppression merely to make static analysis green; correct source types and behavior.

## Direct testing and temporary files

- Use existing test commands and direct browser inspection by default, including the in-app browser or an available host browser such as Chrome or Firefox. Do not create screenshots, videos, traces, JSON/HTML reports, dumps, generated sample-data files, or one-off helper scripts merely to prove that testing happened. Prefer terminal output and in-memory data; do not duplicate existing checks or persist their output automatically.
- A screenshot/capture is allowed only when needed to inspect or diagnose a concrete visual issue, required by the available inspection tool, or explicitly requested by the developer. Prefer transient inspection without saving a file. No saved screenshot or evidence attachment is required for an ordinary task. Describe executed checks, results, and limitations briefly in chat; never claim an unexecuted check passed.
- If a file is technically necessary, use exactly one scratch root: `<workspace-root>/.agent-tmp/`, with a unique `<task-id>/` child. Resolve the outermost project workspace from the active chat context, not from the shell's incidental working directory or the filesystem root. For `workspace/dashboard/` and `workspace/mobile/`, use `workspace/.agent-tmp/<task-id>/`; for a workspace that is itself the Laravel project, use `<laravel-root>/.agent-tmp/<task-id>/`. Never write scratch files into `app/`, `resources/`, `public/`, `tests/`, `vendor/`, or `theme-intake/`.
- Inspect actual Git ownership before writing. If the scratch root is outside every Git repository, do not add an ignore rule. If it is inside a worktree (including a monorepo or single Laravel repository), add its exact root-relative directory to that repository's root `.gitignore`: `/.agent-tmp/` when workspace and repository roots coincide. Multiple child projects do not by themselves prove that the workspace is outside Git. Never initialize a repository for temporary output.
- Create the scratch directory only when needed. Configure tool output paths there; remove unavoidable tool-managed temporary output after use instead of copying it as proof. Delete only files created for this task and its empty directories when they are no longer needed, including on failed checks. Keep a resumable task's necessary state only while the task remains active; clean it after verification/handoff consumes it. Do not delete another task's files, user references, or requested deliverables. Never commit scratch artifacts. Report cleanup that could not be completed.
- This policy covers agent scratch output, not meaningful maintained regression test source, approved specifications, required runtime/build artifacts, or requested exports. Those use their established ownership paths. Existing test-runner/framework caches follow their tool lifecycle and must remain ignored; do not invent additional evidence files or folders. The one-shot theme workflow's minimal resumable verification ledger is a necessary temporary tool input, follows this scratch policy, and does not authorize screenshot bundles or ordinary-task reports.

## Verification commands and coverage

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

Test relevant behavior: schema/casts/relations/constraints; repository contract, query scope/filter/sort/pagination/eager-loading/budgets; service transactions/audit/rollback; permitted and denied roles/Superuser protection; Livewire render/validation/action/redirect/events; route/module/menu sync dry-run; API disabled/enabled gateway/routes/middleware/auth/validation/OpenAPI/CORS; security/session/host/redirect/throttle cases; and view-override publish/preserve/status/conflict/dry-run/backup/replace/path-traversal behavior.

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
`<workspace-root>/.agent-tmp/<task-id>/theme-<theme-key>/verification-evidence.json` and keep
it valid against `theme-verification-evidence.schema.json`. The final ledger may
say `pass` only when all input fingerprints are current, every ordered stage is
`pass`, every automated command exits zero, every required page/state/layout at
`1280x768` and `390x844` has direct browser observations and computed-layout evidence, and
the known-defect and skipped-check lists are empty. CSS/media-query inspection is not rendered responsive evidence. A shared component, selector, token, adapter,
or JavaScript change invalidates and reopens every consuming matrix row; an
archive change additionally requires reinstalling that exact archive and
repeating browser smoke from the packaged runtime.
The final mandatory check is
`php tools/validate-theme-evidence.php <theme-key> <ledger-path>`; any non-zero exit keeps the
theme incomplete and must be fixed without weakening functional checks. Supply the
resolved scratch ledger path explicitly (or `-` to validate JSON from stdin).
After final validation and any active handoff have consumed the ledger, remove
the task scratch files; summarize the result in chat without a saved proof bundle.

Done means: acceptance criteria met; no unexplained TODO/TBD; relevant tests and Pint pass; App config/routes were dry-run synced; browser verification was completed for UX changes; and no unapproved scope/dependency/config/business decision exists. In technical specification mode, archive the confirmed/approved single issue only after verified completion. Direct-execution work requires no issue file or archive. Core changes must be focused-committed and released canonically before the Laravel host updates its Composer lock.
