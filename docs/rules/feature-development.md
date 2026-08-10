# Feature Development Workflow

## Required gates

For every feature, behavioral change, bug fix, maintenance behavior change, or security patch that changes code:

1. Perform evidence-based read-only discovery and give a concise chat confirmation of understood business flow, data, roles, scope, existing evidence, and material open decisions.
2. After the developer confirms that understanding, create exactly one detailed technical specification: `issues/feature_<slug>_<YYYY_MM_DD_HHMMSS>.md` or `issues/bug_<slug>_<YYYY_MM_DD_HHMMSS>.md`. Do not overwrite an issue; timestamp uses developer/project local time.
3. Tell the developer it is ready for review and wait for explicit file approval before changing implementation code.
4. Implement only the approved scope. If a material conflict/decision appears, stop that part, update confirmation/specification after approval, then continue.
5. After every criterion and verification passes, move—not copy—the same file to `issues/archives/done_<original-name>.md` in the implementation commit. Never archive partial, failed, canceled, or undecided work.

Read-only diagnosis/status/consultation and documentation-only work do not create an issue automatically. Do not make duplicate planning files.

Use this confirmation shape before creating an issue; populate it with evidence, label unknowns, and do not write an issue while material answers are missing:

```text
Type: Feature | Behavior change | Bug
Repository/application:
App/subdomain:
Module/page/flow:
Related consumer: Web | API | Android | iOS | other

Confirmed need / proven current behavior / expected outcome:
Scope in / scope out:
Authorization and data impact:
Open material decisions:
```

The specification must be junior-implementable and distinguish confirmed requirements from proven existing findings, proposals, and decisions. Include, when relevant: metadata/status; business goal; in/out scope; actors/authorization; main/alternative/failure flows; source ownership/architecture; changed files; schema/relations/constraints/indexes/backfill/compatibility; API contract/idempotency; validation/security/transactions/concurrency/audit; UI/state/loading/empty/error/responsive/accessibility; PowerGrid filter/state behavior; performance/query budget/cache; integration/deploy impacts; ordered implementation steps; tests; objective acceptance criteria; verification/manual checks/rollout/rollback/risks/open decisions. Do not invent existing names/routes/tables/classes/endpoints/files; label proposals. Reference applicable rules without copying them. No migration/model/service/repository/Livewire/route/view implementation appears during specification stage.

## New-module boundary

Before confirmation/specification/code, the developer must state: owning App/subdomain, module name/code, single vs parent/child menu structure, every menu label in order, and landing page. If API is needed, they must also state consumer, authentication, authorization, required endpoints/operations, and browser CORS need. Do not infer these. Reply briefly with the missing data and a correct prompt pattern.

Example single menu:

```text
Create a reporting module in the finance App.
Single menu: Financial Reports.
Landing: Financial Reports.
[business need, data, flow, roles]
```

Example parent/children:

```text
Create a transaction module in the finance App.
Parent: Transactions.
Children: Transaction List, Add Transaction.
Landing: Transaction List.
[business need, data, flow, roles]
```

## Implementation rules

- Trace route/menu/module → Livewire/controller → service → interface/repository → model/migration → config/tests, inspect schema meaning, and define scope/acceptance/authorization/data effects/audit/performance/rollback before implementation.
- Select owning App/module and a separate module for genuinely different audience/flow/UI. Create App model/migration separately; migrations live in `database/migrations/apps/<app-key>/` and follow production-safe expand/backfill/contract rules.
- Follow `architecture.md`: action boundary validates/authorizes, repositories own persistence/query, services own logic/transactions/orchestration, and audit is designed before UI.
- App Livewire classes/views/assets follow the ownership paths in `architecture.md`; full pages use `#[Layout('layouts::app')]`. Use deferred normal forms and the closest active-theme HTML example. Preserve feature parity across themes without copying visual markup or component styling between themes. Tables use PowerGrid with the active-theme adapter and vendor table pattern, live nonredundant per-column filters, date from/to filters, persisted state, and server-side query/pagination.
- Add protected App web routes with standard auth/active/password-change/lock plus `starter.authorize`, named `<app>.<module>.<action>`. Put APIs only in the App API route file with explicit API auth/authorization/validation/pagination/rate limit.
- Add module/menu source config, validate menu route ownership and landing, then inspect `starter:sync <app> --dry-run` before `--force`.
- Verify business flow, roles/403, Livewire action/validation, audit, scalable server-side lists/query budget, Pint/tests, and browser behavior for UI including empty/low/high data.
