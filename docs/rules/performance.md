# Performance and Resource Efficiency

This standard applies automatically to the starter and derived projects. Target is one shared-hosting instance with file cache/session and sync queue; no Redis, Octane, daemon, worker, CDN, reverse proxy, or special server tuning may be required. Optimize proven flow/query behavior without weakening validation, authorization, transactions, audits, session security, or data integrity.

## Database queries and PowerGrid

- PowerGrid tables use a Builder data source. All search, meaningful per-column filtering, sort, aggregate, and pagination stay in the database; never turn the Builder into a collection before PowerGrid handles it.
- All growing business lists query/filter/sort/aggregate/page in the database. Never `get()`/`all()` then filter/sort or manually create a paginator. Use sensible allowlisted page sizes, sort columns/directions, and enum filter values.
- Select only displayed columns/relations, eager-load or aggregate to prevent N+1/over-fetching, and do not repeat an identical query in one request. Batch work instead of querying within loops. Local/testing must fail on accidental lazy loading and silently discarded attributes.
- Search is server-length-limited, normalized/bound, and live-debounced. For indexed date filters use half-open datetime ranges (`>= start`, `< next day`) rather than column functions that defeat indexes. Bulk work uses bulk queries, `chunkById()`, or `lazyById()`—never the whole table in memory.
- Index actual `where`/`join`/`order by` combinations after inspecting query/`EXPLAIN`; avoid duplicate/speculative indexes. Schema changes follow `code-style.md` and never assume an empty database or maintenance window.

## Livewire, cache, and assets

- `render()` is read-only/idempotent and loads only current-view data. Public Livewire properties are untrusted/minimal and never retain large collections/models/credentials/sensitive payloads. Layout composers do not make heavy unused partial queries.
- Small presentation interactions stay in Alpine/JavaScript. Stable reference data is loaded once, request-memoized, or safely cached with explicit TTL/invalidation.
- Use the default file cache only for safe derived data. Namespace/scope keys correctly, including authorization user/role scope when relevant; define TTL/invalidation and never let cache alter authorization or indefinitely hide important data. Config cache/optimize is production only.
- Global theme/Livewire/Alpine/runtime assets remain local and load once. Page assets stay beside their owner view and use `@assets`/`@script` (or provided non-Livewire stacks); local third-party vendor files are page-scoped. No unused bundle/build pipeline/CDN; use deferred noncritical scripts and approved/pinned exception SDKs only.

## Audited bulk operations and verification

- Do not trade audit/validation/transactions for query count. Event-bypassing bulk mutation creates one manual audit summary with scope/count. Validate selected IDs and bound/chunk large work.
- By-filter actions rebuild the normalized/allowlisted search/filter/authorization query server-side; never trust browser IDs/count/filter description. Dialog count and mutation use the same scope.
- Archive/restore use safe bulk/chunk mutation. Relational permanent deletion uses chunks and bounded transactions. An empty-filter permanent-delete operation is explicitly all-data in UI/server and needs stronger confirmation.
- Tests prove one-page pagination, query budgets for risky/N+1-prone layouts, search/filter/sort/last-page/empty/role scope, and before/after SQL/query-count evidence for significant query changes.
