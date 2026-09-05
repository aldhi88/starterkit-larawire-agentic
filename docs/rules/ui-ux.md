# Livewire UI/UX and Theme

## Contextual representation and composition

- A table is a tool, not the default representation of a database model. There is no default component for displaying a collection; select the representation from the user's task, the meaning and scale of the data, and the required interaction.
- Be creative in composition, not random in decoration. Creativity means finding a better information and interaction composition, not adding visual complexity.
- Use the simplest representation that communicates the information well, but do not choose the most generic representation merely because it is easiest to implement.
- Professional UI comes from deliberate hierarchy, composition, and interaction decisions—not from the number of cards, charts, badges, colors, shadows, or animations.

Before choosing a page's primary component, perform this internal decision process:

1. Identify the page purpose and primary user task: viewing, scanning, finding, comparing, selecting, editing, processing, bulk processing, monitoring, analysing, understanding hierarchy/status/progress, or reviewing history.
2. Read the data semantically. Determine whether it is primarily entity-oriented, tabular, hierarchical, relational, categorical, chronological, geospatial, numeric, status/workflow-oriented, media-oriented, or activity-oriented. A field's user meaning matters more than its database type: for example, `parent_id` may express hierarchy, timestamps may be primary in an activity context, and coordinates may justify a location-aware view.
3. Classify expected volume as very small, small, medium, or large. Do not give eight relatively static records and eight thousand operational records the same representation.
4. Assess information density per item, comparison needs, search/filter/sort needs, selection and bulk-action needs, interaction frequency, status/workflow importance, available space, progressive-disclosure opportunities, and responsive/touch behavior.
5. Consider at least two or three plausible primary representations and compare their scanning speed, density, comparison support, action discoverability, space cost, cognitive load, and mobile behavior. Choose from that trade-off rather than familiarity. Keep this reasoning internal unless the developer asks for it.

Do not render the database schema directly. Classify available information as primary, secondary, contextual, detail-only, technical, or unnecessary for the current task. Show only what supports the page purpose; move appropriate secondary detail to an expandable region, drawer, modal, or detail page. Establish what users should notice first, what should remain visually quiet, and which action is primary before composing the page.

Use these representation heuristics as candidates, never as automatic mappings:

| Pattern | Strong fit | Poor fit or warning |
|---|---|---|
| Table / data grid | Many records or important attributes; cross-record/column comparison; frequent sorting, complex filtering, dense scanning, selection, bulk actions, or operational processing | Very few simple entities; entity recognition, hierarchy, chronology, or status flow matters more than column comparison |
| Card grid | Few to medium meaningful entities with limited primary information, useful status/metadata/icon/count, and low comparison needs | Cards become mini-tables, create excessive scrolling, or make comparison difficult |
| Compact/rich list or directory | Fast scanning with low-to-medium density where cards are wasteful and a table is too formal or heavy | Dense multi-attribute comparison or complex bulk processing is central |
| Tree, nested list, or accordion | Hierarchy, parent/child relationships, grouping, or progressive disclosure is central | Flat comparable records with no meaningful hierarchy |
| Timeline or activity feed | Events, history, sequence, or change over time is the user's mental model | Ordering in time is incidental metadata |
| Tabs or segmented views | A small set of clear, mutually understandable categories changes the relevant view | Categories overlap, are numerous, or hide information users must compare |
| Kanban, pipeline, stepper, or grouped status | Records genuinely move through meaningful workflow stages or progress | Status is only a label and drag/stage interaction adds no task value |
| Chart or stat/metric | Aggregate comparison, distribution, trend, exception, or a decision-relevant summary is the purpose | Added only to fill space or decorate an index page |
| Map or location-oriented list/card | Spatial relationships or location is essential to the task | Coordinates exist but geography does not affect the user's decision |

Low-density items usually favour cards, lists, or directories; medium density may favour rich lists, structured cards, compact tables, or a justified hybrid; high-density comparable records usually favour tables. These are tendencies, not rules. Never replace table-by-default with card-by-default, dashboard-by-default, or any other universal component. The PowerGrid standard below applies whenever the deliberate choice is a Livewire table.

Page composition follows purpose:

- A directory/master page emphasizes finding, scanning, light management, and entity recognition.
- An operational/transaction page emphasizes processing, filters, status, workflow, selection, and actionability.
- A monitoring page emphasizes current condition, exceptions, urgency, and changes.
- An analysis page emphasizes comparisons, patterns, trends, and insight.
- A detail page emphasizes entity hierarchy, context, related information, relevant actions, and chronology where meaningful.
- A configuration page emphasizes comprehension, clear grouping, safe modification, and consequences.

Do not force every purpose into `title + add button + table + pagination`. Add a contextual header, concise description, summary, primary action, search, filters, grouping, secondary context, detail affordance, or empty state only when it supports the actual task. Build visual hierarchy with typography, spacing, grouping, placement, size, weight, alignment, subtle contrast, iconography, and whitespace; do not give every datum or action equal emphasis.

Distinguish primary, secondary, tertiary, contextual, and destructive actions. Avoid large repeated `Detail / Edit / Delete` button groups when a theme-native item click, text/icon action, contextual dropdown, or accessible overflow menu better preserves hierarchy. Keep destructive actions visually quiet until relevant. Use progressive disclosure to reduce cognitive load, but never hide information or actions needed frequently.

Choose responsive behavior from the start. Decide how information priority changes on narrow screens, whether cards collapse naturally, whether a list is more efficient, whether actions/filters belong in overflow or a collapsible region, and whether touch targets remain usable. Horizontal table scrolling is acceptable for genuinely dense tabular data when preserving columns is clearer than converting each row into a card, but it is not the default answer for every collection.

Before finalizing, review the composition internally:

1. Did the database shape or a familiar CRUD scaffold choose the UI for me?
2. Does the representation match the primary task, semantic data character, expected volume, and comparison need?
3. Did I seriously consider another plausible representation, including a compact list between table and card?
4. Are the first-visible information and primary action correct, with secondary detail and actions quiet or disclosed appropriately?
5. Am I showing unnecessary fields, actions, badges, summaries, charts, or decoration?
6. Does the page remain clear with empty, low, and high data volumes and at mobile width?
7. Can the UI be simpler without losing usability, or more specific without becoming novel for novelty's sake?

Explicitly avoid database-driven field dumping, table-by-default, card-by-default, button or badge explosion, dashboardification, modal-for-everything, decorative complexity, and uniform-page syndrome. Variation is valid when page purpose, data character, user task, or information density differs; consistency remains required where function and interaction are shared.

## UI source and selection

Use the installed active theme. `docs/template/<theme>/` contains the indexed component contract and `template.md` search atlas; raw HTML is resolved by its paths in ignored `theme-intake/<theme>/`, not shipped in Composer. Runtime starter Blade remains in `resources/themes/<theme>/views/starter/`; the exact asset recipe publishes only loaded dependencies from the verified GitHub archive to the host's committed `public/assets/<theme>/`.

1. Complete the contextual representation decision above before searching for a theme component.
2. Search the atlas with the page purpose, chosen representation, data context, and component terms using `rg`; choose 3–5 candidates and open only 1–3 closest HTML sources.
3. Compare hierarchy, information density, action treatment, and responsive behavior, then compose the closest active-theme pattern. Search targeted preview/shared/template documentation only when the atlas is insufficient.
4. Never read the whole atlas/template tree or let the available vendor example override a better-supported representation decision.

When introducing a new theme, verify its license before copying anything. Keep raw vendor HTML/assets outside Composer in the owner archive and ignored intake, then add only the independently implemented views/adapters, indexes, maps, and exact hashed asset recipe to core. Verify source preflight, runtime generation, both layouts, modal, auth/error pages, and PowerGrid. A premium source is never redistributed and may be used only by a valid license holder.

The evidence gate, runtime component map, forbidden compatibility skin, and
acceptance matrix for a new or audited theme are mandatory in
`theme-integration.md`.

The active template is selected by `STARTER_THEME`; the application navigation layout is selected only by `STARTER_LAYOUT`. Every theme owns its layout-to-view mapping and must register existing Blade views for the shared `vertical` and `horizontal` layout contract. Every registered theme must preserve the same shell features and responsive behavior in both layouts. Do not add per-layout environment keys.

Each theme owns its navigation partials, theme CSS, JavaScript adapter, icons, auth/error shell, and PowerGrid adapter. `public/assets/starter/` must remain theme-neutral; never place a vendor selector, utility, token, or variable there. Shared runtime interactions use `data-starter-*` contracts and delegate vendor-specific collapse/dropdown lifecycle work to `window.StarterThemeAdapter` supplied by the active theme.

The page-navigation loader is the deliberate universal visual exception. Every
theme includes `starter-shared::components.navigate-loader` unchanged and uses
only `public/assets/starter/css/starter.css` for its presentation. Theme assets
must not override or duplicate this loader. Livewire action loaders remain a
separate interaction and may follow the active theme.

## Cross-theme contract and visual ownership

- Themes share the complete structural contract: page layout, hierarchy, component type and count, order, grouping, placement, practical proportions, density, information, actions, authorization, validation, loading/empty/error states, accessibility meaning, and responsive behavior must remain equivalent.
- Cosmetic decisions are theme-owned. Select class names and vendor-required wrappers, typography, colors, borders, radii, shadows, icons, and control decoration from the active vendor template without changing the shared structural contract.
- Never use one theme as the cosmetic fallback for another. A new theme must apply its own native visual variants to the shared table, toolbar, dropdown, pagination, form, card, tab, alert, and modal composition rather than importing another theme's style.
- Shared `data-starter-*`, Livewire events, PHP data, and authorization may be reused. Theme Blade and theme assets must translate that shared behavior into their own vendor component patterns.
- Shared semantic Blade structure is valid and preferred when it protects cross-theme layout parity. Each theme may change only the classes and wrappers required to express that structure through its own atlas and runtime.

## Cosmetic selection and color discipline

- For each visible component family, shortlist three to five indexed native variants and inspect one to three closest examples. Choose deliberately from semantic role, hierarchy, density, adjacent surfaces, interaction frequency, and responsive cost; do not use the first available variant or reproduce a screenshot mechanically.
- Use one stable semantic color map throughout a theme: `primary` for the main action/current navigation and neutral product identity, `success` for verified/active/completed states, `info` for non-critical guidance, `warning` for conditions requiring attention, `danger` for destructive/error states, and `secondary` for neutral supporting metadata. Do not assign colors merely to make neighboring widgets different.
- A tinted/label surface must set its matching readable foreground explicitly. Never inherit white text on a pale tint, place a low-opacity icon on a similar tint, or use saturated text and background together when the component is informational rather than urgent. Target WCAG AA text contrast (4.5:1 for normal text, 3:1 for large text) and at least 3:1 for meaningful icons/control boundaries.
- Establish one spacing rhythm and one control-height family per context. Repeated cards, icon medallions, form controls, table toolbars, pagination rows, dividers, and action footers must align by measured bounding boxes; inherited vendor margins are not evidence that the result is proportional.
- In a desktop two-column settings/detail composition, the section-navigation surface must stretch to the measured height of its sibling content surface. On stacked responsive layouts each surface returns to its natural content height. Apply this geometry consistently in every registered theme rather than patching a single theme screenshot.
- A shared account-summary region remains one containing surface and one desktop row in every theme. Theme cosmetics may style its avatar, icon medallions, type, and accents, but must not split the same identity and metadata inventory into separate dashboard cards. The row may wrap or stack only at the responsive breakpoint.
- Password-requirement guidance must occupy its own full-width row after current-password verification and immediately before the new-password and confirmation controls. Never pair that guidance as the visual counterpart of the current-password field; keep the two new-credential controls paired on desktop and stack all controls in reading order on narrow screens.
- Prefer a quiet neutral surface with one clear accent over rainbow decoration. A cluster may use multiple semantic colors only when each color communicates a different state that the user needs to distinguish.

## Base rules

- The starter owns shell layout, account dropdown, auth, lock screen, and error pages. Project features extend only the documented extension paths; never copy or override core views.
- The permitted global extension contracts are `resources/views/extensions/starter/header-actions/index.blade.php`, `profile-menu/index.blade.php`, `layout/head.blade.php`, and `layout/body-end.blade.php`. Extensions add content; they never replace starter layouts/views.
- Use Indonesian UI text, sensible information density, visible status, empty/loading/error states, keyboard-accessible controls, and responsive layouts.
- Use the active vendor's native component variant for visible controls. For example, boolean settings use the vendor switch pattern when it exists; do not fall back to a plain checkbox or another theme's control styling.
- Prefer the existing theme component and a small local Alpine interaction. Add a library only after proving the theme/vanilla alternatives cannot meet the need; keep it local, page-scoped, compatible, idempotent, and cleaned up on navigation.
- Livewire handles server state, validation, authorization, transactions, and audit. Alpine/JavaScript handles presentation-only state (toggle, show/hide, tab, dropdown, copy, local preview). Do not issue Livewire requests for presentation-only work.
- Normal forms use deferred binding and submit validation. Use live server requests only for a demonstrated immediate need (authoritative dependent values, unique checks, autocomplete, upload/preview), with narrow scope, limits, and final submit validation.
- Search/filter requests are live and debounced around 300–500 ms when they query. Ordinary input changes must not show the global action loader. Polling/passive refresh must preserve content, filters, pagination, focus, and scroll.

## PowerGrid table standard

- Every Livewire table uses `power-components/livewire-powergrid` and the active-theme adapter. Data sources, search, filters, sorting, pagination, selection, and bulk/by-filter mutations are server-side.
- The active vendor table is the visual source of truth. Every theme documents its selected vendor table, toolbar, filter, action, and pagination patterns in `runtime-map.md`. Matching columns and behavior never justify matching visual markup.
- Enable a type-appropriate per-column filter by default for every meaningful data column. Free-form or high-cardinality text uses live text filtering. A bounded option set—such as enum, status, boolean, role, relation, App, or another controlled reference—must use select, multi-select, or boolean controls populated from the viewer-authorized source of truth; do not replace it with free-text search. Numeric columns use an appropriate range/value control; date/datetime columns always provide inclusive **from** and **to** filters. Exclude only columns for which filtering is genuinely meaningless (for example actions, derived/audit-only/system metadata), document the reason, and test it.
- Column filters query live—debounced where needed—without an Apply/Search button. Persist filter/sort/search state across reload using the supported PowerGrid/Livewire URL or session mechanism; reset pagination only when filtering/sorting scope changes.
- An above-grid one-row filter card is optional. Use it only for cross-column, composite, or high-value filters that are not represented by a column filter. Never repeat a column filter in the card.
- Every input, select, multi-select trigger, date/datetime control, and other per-column filter control must use `width: 100%` and `box-sizing: border-box` inside a practical intrinsic-width filter wrapper. The wrapper participates in column sizing; the control fills that wrapper, not an arbitrarily stretched table cell. Apply this through the owning active-theme PowerGrid adapter when the behavior is universal for that theme; do not scatter duplicate page-level fixes or let a filter rule widen unrelated no-filter columns.
- Determine a filtered column width from the maximum practical requirement of its intrinsic filter wrapper, header, and representative displayed content. Determine a no-filter column from its header and representative content only. Never use equal-width distribution, screenshot-derived fixed widths, or `table-layout: fixed` to hide a sizing defect. Checkbox and action columns stay compact; short identifiers/statuses may stay compact; medium text may wrap deliberately; long fields such as descriptions may wrap across multiple readable lines and do not need to remain on one line, but must receive a practical minimum width that avoids a tall, word-by-word strip. Use explicit theme-owned `min-width`/width rules only where the native table cannot express the requirement, and prefer inner `table-responsive` horizontal scrolling over compressing controls or content below a usable size.
- Muteable entity tables include select-all, validated bounded bulk actions, by-filter actions, and complete lifecycle controls unless excluded by the domain/audit rules. Dangerous or broad actions require accurate scope/count and explicit confirmation.
- Tables with horizontally long content must be placed in a full-width container (single column layout) to minimize horizontal scrolling. Use the active vendor's native surface/container pattern; its shape, background, border, and shadow are not universal.
- Every PowerGrid table renders pagination both above and below the table. In each position, use a stable three-zone row: the per-page selector followed by `Data per halaman` on the left, page navigation centered independently, and `Menampilkan X sampai Y dari Z data` right-aligned on one line. Always render the current page index—even when the result has only one page—with disabled previous/next controls where appropriate. Show at most five consecutive page numbers nearest the active page so navigation never displaces the left or right zones. Both positions stay synchronized with the same server-side page state; wording and behavior are universal while visual presentation remains owned by the active theme.
- Place `Column::action('Aksi')` first in `columns()` so PowerGrid renders the action column immediately after its automatically prepended checkbox column. Use the label `Aksi`, never `Aksi Massal` or another variation.
- Group row actions through the active vendor's native compact action or dropdown pattern, with an accessible trigger name and clear item labels. The exact border, icon, label, placement, and menu surface are theme-owned. The implementation must remain idempotent through Livewire morph/navigation and must not clip behind table overflow; use a local Alpine/adapter interaction or portal only when the selected vendor pattern needs it. Never import another vendor's data attributes or lifecycle API.
- Date and datetime columns use range filters. Apply `->params(['mode' => 'range'])` to the relevant `Filter::datepicker` or `Filter::datetimepicker`, then preserve and test inclusive from/to boundaries.
- Global PowerGrid styling belongs to each theme's own adapter and theme asset; shared translations remain theme-neutral. Do not put selectors from one theme into another theme's assets or duplicate theme fixes in individual tables.

## Form, modal, and feedback behavior

- Keep action controls near their relevant content. Use the closest theme form, card, modal, alert, badge, empty-state, and pagination patterns.
- Browser-native dialogs are forbidden: do not use `alert`, `confirm`, `prompt`, or their equivalents. Every dialog is an active-template, theme-consistent modal. Every user-action confirmation uses this modal and states the action, affected scope/count, irreversible impact when applicable, and a clear cancel/confirm choice. Destructive, broad, archive/restore, and permanent-delete actions require this confirmation before the server mutation.
- Use a modal for a compact, single-purpose mini form that can be completed without losing page context. Keep it focused, accessible, and short; validation errors remain in the modal and inputs preserve state. A complex, multi-step, large, or page-defining form must use a dedicated page rather than an oversized modal.
- Every modal must be closable by clicking anywhere on the backdrop (outside the modal dialog). For a modal managed manually by Livewire, apply `wire:click.self` to that theme's outer backdrop owner—not to an assumed vendor class—so only outside clicks close it.
- For long forms that require vertical scrolling (exceeding one viewport), the primary action button (Submit/Save) must be easily accessible without scrolling to the bottom. Use either a sticky/floating action bar at the bottom or dual submit buttons (one in the Page Header and one at the bottom of the form).
- Modal state must resolve before a global loader takes focus. Show validation next to the field and preserve submitted state on validation failures.
- Use the existing runtime loader for explicit user actions. Do not add duplicate loaders, global DOM manipulation, or `wire:ignore` except for an isolated third-party widget controlled outside Livewire.
- Livewire navigation is only for guaranteed same-origin URLs. Root/auth/App-subdomain navigation must use normal browser navigation; do not solve cross-origin navigation with CORS.
- Error pages for `400`, `401`, `403`, `404`, `405`, `408`, `419`, `422`, `429`, `500`, `503`, and fallback `4xx`/`5xx` use `resources/themes/<theme>/views/starter/errors/layout.blade.php`, Indonesian text, safe return action, `noindex`, and no internal exception/path/query/credential disclosure.

## Assets

- Core theme/runtime/Livewire/Alpine assets remain local in `public/assets`, loaded once by the layout. No CDN for basic UI or common libraries.
- Keep page CSS/JS at `resources/views/apps/<subdomain>/<module>/assets/<page>.(css|js).blade.php`; include it only from its owner view. Use `@assets` for Livewire dependencies and `@script` for Livewire initialization; non-Livewire pages use the provided stacks.
- Third-party assets remain local and page-scoped in `public/assets/apps/<subdomain>/vendor/`. Use `defer` for non-critical scripts, avoid duplicate bundles, and use version tracking when a changed asset must reload.
- Do not use `data-navigate-once` on page assets/scripts; it is reserved for global singleton runtime. Use `data-navigate-track` for a versioned asset that must force a reload. Keep jQuery exceptional, local, page-scoped, ordered as jQuery → library → initialization, deferred without `async`, and isolate its DOM with `wire:ignore` only when it owns that DOM.
- A non-hostable third-party SDK requires approved feature rationale, a pinned version, `defer`, and a non-user-controlled URL. Production must not require a Node/Vite server unless built source is actually used.

## Browser verification

- Test menu entry, redirects, primary actions, loaders, modal/toast/validation states, empty/low/high data volumes, and relevant desktop/laptop/mobile widths.
- For PowerGrid, test live global and per-column filtering, date from/to boundaries, persisted reload state, sort, pagination/last page, empty result, and role-specific scope. At the `1280x768` safe area, inspect representative text/select/date filters and assert or visually prove that each control fills its intrinsic filter wrapper, filtered columns honor that wrapper/header/content, no-filter columns remain content-driven, long text wraps readably, and horizontal scrolling appears inside the table frame instead of squeezed controls or root-page overflow when the table is wide.
- For CSS changes, verify normal, focus, invalid, and invalid+focus states; inspect DOM/computed style when a screenshot is inconclusive.
- For layout or global CSS changes, test both a short page and a vertically long page in Chromium at the `1280x768` safe area. Verify document/root overflow, horizontal and vertical scrollbars, and sticky navigation. Firefox verification is excluded by default and is required only when the developer explicitly requests it or reports a Firefox-specific bug; default verification must not depend on desktop-control permission.

## Safe Area Resolution

- Dalam mengatur tata letak (layout) komponen UI, selalu mengacu pada resolusi **1280x768** sebagai titik aman (safe area) agar tampilan tetap proporsional dan tidak terpotong pada berbagai layar.
