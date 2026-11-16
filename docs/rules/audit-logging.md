# Universal Audit Log

`starter_logs` records every meaningful server-side application action initiated by an authenticated login, including reads and mutations. A single multi-table business action uses one `action_id` with ordered sequences.

## Viewer visibility

- Only a Superuser may read activity performed by a Superuser or targeting a Superuser account/system role. For every other log viewer, exclude the complete protected action in the repository before grouping, filtering, sorting, pagination, counts, filter options, or detail lookup; do not replace the actor with a visible system placeholder. Direct action-ID requests must return 404.
- Use the immutable `actor_is_superuser` snapshot as well as protected actor/target IDs, including archived accounts and roles. Historical Superuser activity remains hidden even when its actor no longer exists. Apply the same scope to every theme and invalidate cached filter options when the visibility policy changes.

## Row ownership

- Every application-owned database table must physically define both `created_by` and `updated_by`, including starter core, App business, pivot, configuration, registry, append-only, and audit tables. Both columns use nullable foreign keys to `starter_client_logins.id` with `nullOnDelete()` so historical rows remain valid after an actor is permanently removed.
- Laravel/framework runtime tables written directly by the framework—migration history, cache, cache locks, jobs, job batches, failed jobs, sessions, and password-reset tokens—are the only standing exception. Any other exception requires an explicit developer decision documented in the owning rule and covered by a regression test.
- Nullability exists only for bootstrap, migration, import, integration, console, or system work that genuinely has no authenticated login. Every row created by an authenticated action sets both columns to that login ID. Every later mutation, including archive, restore, relationship/pivot sync, and soft delete, preserves `created_by` and sets `updated_by` to the current login ID.
- `created_by` is immutable after insertion. Permanent deletion preserves the actor and target snapshot in `starter_logs` before the row disappears. Append-only rows set `updated_by` equal to `created_by` at insertion and never change it.
- Never accept either actor column from request, form, import payload, API payload, or mass assignment. The authenticated action boundary passes the actor explicitly; the service/repository or a tested shared model mechanism stamps the columns. Raw SQL, upsert, bulk, and event-bypassing writes must set the actor columns explicitly.
- Models expose typed `creator` and `updater` `belongsTo` relations when the records are consumed as business data. Migrations index both actor columns. Existing tables are brought into compliance through forward-only expand/backfill migrations; never edit an already-applied migration or invent a historical actor when evidence is unavailable.

## Action coverage and actor identity

- An audit action is one meaningful server-side user intent, not a browser-only toggle, DOM event, Livewire hydration/render cycle, internal retry, or individual keystroke. Log page/list/detail access, completed search/filter/sort/pagination state, export/download/print, create/update, relationship changes, status/workflow transitions, archive/restore/permanent delete, bulk/by-filter operations, approvals, settings, and privileged/security operations.
- Debounce or coalesce high-frequency browse/search/filter input into one audit entry for the completed server request/state; this reduces duplicate noise but must not omit the authenticated action. Background work retains the initiating login ID and action ID when it continues a user action; genuinely scheduled/system work uses a stable system action key and a null actor.
- Every audit entry for an authenticated action has a non-null `client_login_id` and immutable actor snapshots (`actor_name`, `actor_username`, `actor_role`, and Superuser state). Pre-authentication success/failure/throttle events may have a null actor but must record a safe target identity and outcome without credentials.
- Record validation failures, denied authorization, failed business actions, and successful actions with a stable key and safe outcome metadata when the request reached the application action boundary. Never log secrets, raw credentials, session identifiers, tokens, sensitive query text, or unrestricted request payloads.
- A state-changing action and its success audit are atomic: keep both in the same database transaction and fail the mutation if the required audit cannot be persisted. Record a failed attempt outside the rolled-back business transaction when safe and technically possible.

## Recording mechanics and verification

- Global Eloquent listeners record `created`, `updated`, and `deleted` through `AuditLogService`; use normal Eloquent mutations where appropriate. Audit models are excluded and sensitive attributes (passwords, tokens, secrets, credentials) are filtered.
- Wrap a multi-step action in `AuditLogService::withinAction()` and `DB::transaction()`. Use a stable business key such as `<domain>.<action>` and a user-understandable Indonesian label.
- Query bulk operations, pivot syncs, raw SQL, and external operations must call `recordManual()` when needed with event, table, target type/id/label, changed safe old/new values, and useful metadata. Prefer one safe bulk/chunk operation plus one scoped/count summary to thousands of identical rows.
- Use `recordSecurityEvent()` for authentication/session events. Record success/failure/throttle/lock for login, password confirmation/reset/change, lock/unlock, logout, and session termination, without credentials or password values.
- Archive, restore, and permanent delete use distinct stable keys: `<domain>.archive`, `.restore`, `.delete_permanently`. Record lifecycle state changes; permanent-delete logs contain safe target identity and relation counts, not large/sensitive payloads.
- Selected/filtered/all bulk actions use one action group and summary with safe applied scope/filter, success count, and failed/skipped count. Permanent delete runs only from a service, transaction, and archived target (unless an approved append-only/derived lifecycle says otherwise), and must remove or prove cascade of owned relations.
- Test the physical actor columns, foreign keys/indexes, create/update stamping, immutable `created_by`, unauthenticated/system nullability, actor removal, Eloquent/raw/bulk/pivot paths, and forward-migration backfill behavior.
- Test action key, authenticated actor and snapshots, target/table/event, read and mutation coverage, denied/failed outcomes, multi-table grouping, transaction rollback, sensitive-data exclusion, success/failure security cases, and bulk scope summaries. A feature is incomplete if any exposed server-side action has no asserted audit path.

## Noise control and integration

- Configure `starter.audit.models` by exact model class. `ignore_updated` removes only explicitly named technical attributes from update diffs; it never suppresses an entire model. Sensitive and ownership/lifecycle attributes cannot be excluded. Creation/deletion evidence remains intact. Use `label` and safe `label_attributes` to give domain names and identifiers; `event_labels` maps event verbs. Never use secret-bearing attributes as labels.
- `withinAction()` now opens a default-connection database transaction. Existing surrounding transactions remain supported. Both business writes and audit must use that connection. Global post-write Eloquent listeners alone cannot make an unwrapped autocommit write atomic. External side effects require an application outbox/after-commit design; the package cannot roll them back.
- Use `withinAutomatedAction(key, label, callback, initiator)` for scheduled/worker work. Pass the initiating login explicitly (null only for genuine system work). Nested work retains the enclosing action and ordered sequences; independent jobs start their own group. For a separately dispatched continuation, pass the optional `actionId` with the same original key, label and initiating actor, after the original transaction commits. The service locks the original entry and appends ordered sequences in the same action; a missing action or mismatched actor/key/label is rejected. Distinct job actions may instead use safe correlation metadata. Do not rely on a queue worker's ambient authentication.
- The grid groups before pagination, uses target snapshots without target-model queries, adds a target only for a proven single target, and marks multiple objects/tables. Category derives from security events, explicit automated/console source, and known read events; remaining legacy/custom events retain the business/user fallback. Mixed groups remain accessible. These are presentation classifications, not invented historical business outcomes. Known metadata outcomes and explicit event outcomes are shown; unknown outcomes stay unspecified.
- `recordAccess()` is only for a completed, successful read. It has no mutation, security, or failure event parameter. Opt in with both `access_window_seconds` (1–3600; default 0) and `access_actions` (exact action keys). Do not route mutations, approvals, rejected/failed reads, security events, or exports needing individual evidence through this API. Existing `recordManual()`/`recordSecurityEvent()` behavior does not coalesce.
- Pass normalized search/filter/sort/page/page-size state and a server-derived session scope (defaults to the request session). Missing session, disabled configuration, an unlisted action, an automated boundary, or an enclosing action disables coalescing. State is compared with a keyed SHA-256 digest covering actor, role/protected status, session, host/path/route, action, target, labels, interval and state. Object/map keys are canonicalized; list order is preserved. Do not supply credentials or tokens even though state is not stored. Never copy raw state into labels or metadata. Application-key rotation starts new groups.
- A fixed time bucket merges identical reads with one atomic indexed upsert, retaining first actor/target snapshots, first timestamp/action ID/sequence, occurrence count and last timestamp. Nearby reads on opposite bucket boundaries remain separate. The summary means repeated identical successful reads, not suppressed failures or changed objects. It intentionally does not retain every request ID/IP/user-agent; the initial request snapshot is retained. Disable aggregation where each occurrence needs that evidence. Counts do not measure unique people or records read.
- The application still owns invocation placement, push-registration idempotency, completed/debounced access boundaries, normalized state, safe domain labels, explicit outcomes (`success`, `failed`, `denied`/`rejected`, `queued`, `skipped`) and meaningful bulk summaries. Coalescing is not a repair for a logging call in render/hydration or a repeated business mutation.

### Configuration example

Merge these keys into the host's `config/starter.php`; do not edit vendor:

```php
'audit' => [
    'models' => [
        App\Models\Apps\Notifications\PushSubscription::class => [
            'ignore_updated' => ['last_seen_at'],
            'label' => 'Langganan notifikasi browser',
            'label_attributes' => ['device_name'],
        ],
    ],
    'event_labels' => ['approved' => 'Menyetujui'],
    'access_window_seconds' => 60,
    'access_actions' => ['requests.browse'],
],
```

Model/field names above are illustrative, not package-owned classes. After a successful authorized read:

```php
$audit->recordAccess(
    key: 'requests.browse',
    label: 'Menelusuri daftar request',
    auditableType: RequestRecord::class,
    auditableId: 'list',
    state: ['search' => $normalizedSearch, 'filters' => $filters,
        'sort' => $sort, 'page' => $page, 'per_page' => $perPage],
);
```

### Upgrade

Apply forward migration `2026_11_02_000000_add_access_aggregation_to_starter_logs` before serving the updated grid. It adds three nullable columns and a unique nullable fingerprint index; existing rows are neither rewritten nor merged. MySQL/MariaDB allow multiple nulls in this index. Large tables may require an index-build maintenance window according to the database version and operational policy. Rolling the migration back discards aggregation counters, so retain these columns if reverting code while preserving evidence.

Use `starter:sync` for authorized local demos and the established deployment procedure for production. Review published view overrides with `starter:views-status`; update custom detail views to display `access_count` and first/last timestamps. Existing recording signatures remain valid and aggregation stays disabled by default. The existing `action_label` grid field/filter key remains compatible with persisted filters.
