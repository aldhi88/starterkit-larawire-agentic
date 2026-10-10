# Feature Development Workflow

## Choose the execution mode

For a feature, behavioral change, bug fix, maintenance behavior change, or security patch in a derived Laravel host, use the developer's intended mode:

- **Direct execution:** A request to implement, fix, add, or work now authorizes source discovery, code changes, and proportionate verification. Do not create an `issues/*.md` file or demand checklist, specification, file, or execution approval merely because code will change. Ask only for genuinely missing material decisions; do not turn that clarification into an automatic specification workflow.
- **Technical specification:** An explicit request to prepare an issue, technical document, implementation specification, review artifact, or handoff uses the steps below. Do not implement production code until the developer later authorizes execution.
- **Unclear mode:** If the wording and prior context genuinely leave both modes plausible, ask in the developer's language: "Do you want me to implement this now, or prepare a technical specification in `issues/` for review first?" Continue only read-only discovery while awaiting the answer. Do not infer specification mode from the existence of this rule, and do not ask when an implementation or documentation request is already clear.

Read-only diagnosis/status/consultation and ordinary documentation-only work do not create an issue automatically. The chosen mode never relaxes authorization, security, architecture, data-integrity, or testing requirements.

## Technical specification workflow

1. Perform evidence-based read-only discovery, then give a concise chat confirmation that acts as a complete request-receipt checklist. Restate every requested outcome, user-visible behavior, constraint, explicit exclusion, affected business flow/data/role, and expected result—including small, clear, or low-risk items. Keep this at requirement level, not technical implementation-plan level. Give each independently verifiable request its own numbered checklist item; group items only when traceability is preserved. Separate source-proven current behavior from requested changes and list ambiguities/material decisions separately. End by asking the developer to confirm that the checklist covers the complete request.
2. Do not create a planning/issue file from the initial request alone. Only after the developer explicitly confirms the chat checklist, convert every numbered item into one detailed technical specification: `issues/<YYYY_MM_DD_HHMMSS>_feature_<slug>.md` or `issues/<YYYY_MM_DD_HHMMSS>_bug_<slug>.md`. Preserve one-to-one traceability from each confirmed request item to technical requirements and acceptance criteria; do not silently omit, merge, weaken, expand, or reinterpret any item. The timestamp must be the filename prefix so lexicographic filename order is chronological, uses developer/project local time, and is never changed during the issue lifecycle. Do not overwrite an issue.
3. Tell the developer it is ready for review and wait for explicit file approval before changing implementation code.
4. After the developer explicitly authorizes execution, implement only the approved scope. If a material conflict/decision appears, stop that part, update confirmation/specification after approval, then continue.
5. After every criterion and verification passes, move—not copy—the same file to `issues/archives/<YYYY_MM_DD_HHMMSS>_done_feature_<slug>.md` or `issues/archives/<YYYY_MM_DD_HHMMSS>_done_bug_<slug>.md` in the implementation commit. Preserve the issue's original timestamp prefix; insert only `done_` between the timestamp and type. Never archive partial, failed, canceled, or undecided work.

Do not make duplicate planning files. If the developer switches from specification to direct execution, follow the latest explicit instruction; do not treat an unapproved draft as an approved contract or create/archive an issue solely to permit implementation.

## Artifact language gate

This is a hard pre-review gate for every persisted internal plan, implementation plan, technical specification, and `issues/*.md` file:

- The complete artifact uses concise technical English regardless of the developer's chat language, the project's UI language, or the language used to discuss and confirm the request. This includes its title, headings, prose, table labels, requirements, acceptance criteria, implementation steps, test plan, verification, rollout, rollback, and review notes.
- The user-facing request-receipt confirmation follows the developer's language so its coverage can be reviewed accurately. Translate its confirmed meaning into technical English for the artifact; do not copy Indonesian chat prose into the plan/specification. The developer's chat language never changes the artifact language.
- Preserve non-English text only when fidelity is implementation-relevant: exact Indonesian UI copy, an approved business/domain name, a source identifier, or a short verbatim requirement that is clearly marked as a literal. Surrounding explanation and all normative instructions remain English.
- Immediately before saving or presenting the artifact for approval, inspect the entire file for language compliance. Any Indonesian or mixed-language narrative outside the allowed literals makes the artifact invalid; rewrite it before reporting that the file is ready. Never ask the developer to translate it or approve it as an exception merely because the discussion used Indonesian.
- When materially revising an existing artifact, the complete resulting file—not only the new section—must pass this gate. English planning output optimizes tokens through compact structure and non-repetition, never through missing technical constraints.

In technical specification mode, use this confirmation shape before creating an issue; populate it with evidence, label unknowns, and do not write an issue while material answers are missing:

```text
Type: Feature | Behavior change | Bug
Repository/application:
App/subdomain:
Module/page/flow:
Related consumer: Web | API | Android | iOS | other

Source-proven current behavior:
Complete requested-outcome checklist:
1. [one independently verifiable request]
2. [one independently verifiable request]
Explicit scope exclusions:
Authorization and data impact:
Expected result:
Ambiguities / open material decisions:
Confirmation request: Confirm that every requested item is represented above before the issue file is created.
```

The specification is a deterministic execution contract for a lower-cost coding LLM, not a human-oriented tutorial. It must be self-contained enough that the implementing model does not infer architecture, invent source facts, or choose among material alternatives. Distinguish confirmed requirements, source-proven findings, approved decisions, and explicitly rejected scope. Use direct normative statements and compact tables/lists; remove greetings, narrative repetition, teaching prose, duplicated general rules, and speculative commentary.

Before approval, resolve every material open decision; no `TBD`, ambiguous option, "as appropriate", "follow best practices", or instruction to decide during implementation may remain. Bind every existing route/table/class/method/config key/view/component/icon to inspected source evidence. For each touched behavior, specify when relevant: exact owning path and symbol; files to create/modify; method signatures and layer ownership; schema types/nullability/defaults/relations/constraints/indexes/backfill/compatibility; route names/middleware/domain; authorization matrix; input normalization and validation; transactions/concurrency/idempotency/audit; exact UI labels, active-theme reference, exact approved icon key, states and responsive behavior; PowerGrid columns/filter types/full-width controls/column sizing/state persistence and the mandatory first-column `Aksi` three-dot row-action dropdown; API request/response/error contract; query/performance limits; ordered implementation steps; focused and regression tests with exact assertions; verification commands; objective acceptance criteria; rollout/rollback; and forbidden out-of-scope changes. Include literal code only when a signature, payload, schema, or algorithm cannot otherwise be made deterministic. No production implementation appears during specification stage.

## New-module boundary

Before specifying or implementing a new module, the developer must state: owning App/subdomain, module name/code, single vs parent/child menu structure, every menu label in order, and landing page. If API is needed, they must also state consumer, authentication, authorization, required endpoints/operations, and browser CORS need. Do not infer these. Reply briefly with the missing data and a correct prompt pattern.

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
- Add module/menu source config, select and verify active-theme icons whose meanings match every rendered menu label/destination, validate menu route ownership and landing, then inspect `starter:sync <app> --dry-run` before `--force`.
- Verify business flow, roles/403, Livewire action/validation, audit, scalable server-side lists/query budget, Pint/tests, and browser behavior for UI including empty/low/high data.
