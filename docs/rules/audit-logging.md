# Universal Audit Log

`starter_logs` records user-initiated create, update, and delete events—not reads. A single multi-table business action uses one `action_id` with ordered sequences.

- Global Eloquent listeners record `created`, `updated`, and `deleted` through `AuditLogService`; use normal Eloquent mutations where appropriate. Audit models are excluded and sensitive attributes (passwords, tokens, secrets, credentials) are filtered.
- Wrap a multi-step action in `AuditLogService::withinAction()` and `DB::transaction()`. Use a stable business key such as `<domain>.<action>` and a user-understandable Indonesian label.
- Query bulk operations, pivot syncs, raw SQL, and external operations must call `recordManual()` when needed with event, table, target type/id/label, changed safe old/new values, and useful metadata. Prefer one safe bulk/chunk operation plus one scoped/count summary to thousands of identical rows.
- Use `recordSecurityEvent()` for authentication/session events. Record success/failure/throttle/lock for login, password confirmation/reset/change, lock/unlock, logout, and session termination, without credentials or password values.
- Archive, restore, and permanent delete use distinct stable keys: `<domain>.archive`, `.restore`, `.delete_permanently`. Record lifecycle state changes; permanent-delete logs contain safe target identity and relation counts, not large/sensitive payloads.
- Selected/filtered/all bulk actions use one action group and summary with safe applied scope/filter, success count, and failed/skipped count. Permanent delete runs only from a service, transaction, and archived target (unless an approved append-only/derived lifecycle says otherwise), and must remove or prove cascade of owned relations.
- Test action key, actor, target/table/event, multi-table grouping, sensitive-data exclusion, success/failure security cases, and bulk scope summaries.
