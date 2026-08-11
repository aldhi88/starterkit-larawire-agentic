# Testing and Definition of Done

Every code change adds or updates relevant tests, primarily Pest feature tests. Iterate with the smallest test file/filter, then run affected-area tests, Pint after PHP changes, and the full suite for cross-infrastructure changes.

```bash
php artisan test --compact tests/Feature/NameTest.php
php artisan test --compact --filter="behavior name"
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

Test relevant behavior: schema/casts/relations/constraints; repository contract, query scope/filter/sort/pagination/eager-loading/budgets; service transactions/audit/rollback; permitted and denied roles/Superuser protection; Livewire render/validation/action/redirect/events; route/module/menu sync dry-run; API disabled/enabled gateway/routes/middleware/auth/validation/OpenAPI/CORS; and security/session/host/redirect/throttle cases.

For UI, test empty/low/high datasets and real browser behavior when JavaScript changes: page assets do not leak, typing in normal forms causes no request, and navigation does not duplicate scripts/listeners/widgets. Cross-origin navigation is full-page. Verify each supported theme independently: capability parity must remain, while visible structure and styling must match that theme's cited vendor example rather than another theme. PowerGrid tests cover Builder source, active theme adapter, live search/sort/pagination, all meaningful column filters, date from/to boundaries, reload-persisted state, selection/by-filter scope, individual/bulk actions, reset selection, and query budget; an excluded filter needs tested justification.

Test mutable lifecycles (create/update/archive/filter archived/restore/permanent delete only from archive/owned-relation protection), bulk selected/filtered/all guards, safe scope/count dialog metadata, summaries, and rollback. Keep `StarterArchitectureTest` strict: controllers/Livewire do not query/load relations/use service locators and services do not build model queries without approved, justified deviation. Host integration confirms Composer discovery, fresh-source rejection, destructive confirmations, automatic pre-reset security and application tests, rollback behavior, core autoload/migration/theme/routes/views, dynamic App migration discovery, AGENTS connector, and starter/theme/PowerGrid assets.

Done means: acceptance criteria met; no unexplained TODO/TBD; relevant tests and Pint pass; App config/routes were dry-run synced; browser verification was completed for UX changes; no unapproved scope/dependency/config/business decision exists; and the confirmed/approved single issue specification is archived only after verified completion. Core changes must be focused-committed and released canonically before the Laravel host updates its Composer lock.
