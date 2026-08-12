# Code Style and Database Rules

## PHP and Livewire

- Follow the closest sibling and existing structure. Use descriptive names, strict parameter/return types, constructor promotion, braces, and useful PHPDoc array shapes/generics; do not comment what code already says.
- Use named `route()` calls, Form Requests for complex HTTP controllers, local Livewire validation, Eloquent/factories in tests, and no `env()` outside config.
- Follow `architecture.md`: repositories own reusable query/persistence, interfaces expose only needed typed business contracts, and services own real business flow. No empty pass-through services, generic CRUD repositories, mirrored Eloquent interfaces, service locators, or unnecessary packages.
- Full-page Livewire components use `#[Layout('layouts::app')]`; actions have business names and authorize/validate mutations. Large lists use database queries/pagination, and public state never retains large objects or sensitive data.

## Database and migrations

- New business tables use lower-snake `{subdomain}_{module}_{entity}`; normalize hyphens to underscores and explicitly set each model `$table`. Use clear Indonesian-domain collection names; do not force English pluralization.
- Pivots use `{subdomain}_{module}_{entity_a}_{entity_b}` with consistent/alphabetical entities and clear singular foreign keys. Keep table, foreign key, unique, and index identifiers within MySQL's 64-character limit; use explicit clear index names when necessary.
- `starter_` is starter infrastructure only; `x_` is Laravel/helper infrastructure only (`x_migrations`, cache, jobs, sessions, password resets, etc.). Never rename existing tables merely for style; existing helper-table renames require production-safe migration/backfill.
- Core migrations live in `vendor/aldhi88/starterkit-larawire-agentic/database/migrations/starter`; host App migrations live only in `database/migrations/apps/<subdomain>/`. Generate App model/migration separately with `--no-interaction` and explicit `--path`; never use `make:model -m` for an App table.
- Migrations are reversible and production-safe: assume large existing tables, add compatible nullable/default columns, backfill in safe stages, add constraints later, avoid unsafe rewrites/large transactions, and use Schema/query builder rather than mutable models. Analyze compatibility, backup/rollback, and data preservation before rename/drop/type change; index actual query paths.
- Bind and allowlist every sort/filter/raw-expression input. Do not add `client_id` in this single-company architecture. Use transactions for atomic actions.
- Mutable CRUD entities use `SoftDeletes` with indexed `deleted_at` by default. State list scope explicitly (`active`, `withTrashed`, `onlyTrashed`); archive with `delete()`, restore with `restore()`, and permanently delete only archived data through a validated/audited transactional service. Make owned-relation deletion/cascade explicit and test it.

## Formatting

Run `php artisan make:<type> ... --no-interaction` and `vendor/bin/pint --dirty --format agent`. Use Pest instead of temporary verification scripts. Never run `config:cache`/`optimize` during normal development; clear stale cache with `php artisan optimize:clear` when needed.
