# Livewire UI/UX and Theme

## UI source and selection

Use the installed active theme. `docs/template/<theme>/` is the complete upstream vendor bundle: paste its `assets/` and HTML there unchanged. Runtime starter Blade remains in `resources/themes/<theme>/views/starter/`; runtime assets remain in `public/themes/<theme>/assets/`. The bundle's `template.md` is a non-binding search atlas, not a design checklist.

1. Identify the page goal, data type/volume, statuses, primary action, and required interaction.
2. Search the atlas with context plus component terms using `rg`; choose 3–5 candidates and open only 1–3 closest HTML sources.
3. Compare hierarchy and information density, then compose the closest active-theme pattern. Search targeted preview/shared/template documentation only when the atlas is insufficient.
4. Never read the whole atlas/template tree or design from personal taste.

When introducing a new theme, first verify that its license permits Composer redistribution. If permitted, place its vendor distribution intact in `docs/template/<theme>/`; then generate the starter Blade contract in `resources/themes/<theme>/views/starter/`, copy required runtime assets to `public/themes/<theme>/assets/`, register paths, PowerGrid adapter, and the layout-to-view map in `config/starter.php`, and verify internal asset sync, every registered layout, modal, auth/error pages, and PowerGrid. Keep non-redistributable premium source outside the public repository and package dist.

The evidence gate, runtime component map, forbidden compatibility skin, and
acceptance matrix for a new or audited theme are mandatory in
`theme-integration.md`.

The active template is selected by `STARTER_THEME`; the application navigation layout is selected only by `STARTER_LAYOUT`. Every theme owns its layout-to-view mapping and must register existing Blade views for the shared `vertical` and `horizontal` layout contract. Every registered theme must preserve the same shell features and responsive behavior in both layouts. Do not add per-layout environment keys.

Each theme owns its navigation partials, theme CSS, JavaScript adapter, icons, auth/error shell, and PowerGrid adapter. `public/assets/starter/` must remain theme-neutral; never place Bootstrap, Tabler, Vuexy, or another vendor selector/variable there. Shared runtime interactions use `data-starter-*` contracts and delegate vendor-specific collapse/dropdown lifecycle work to `window.StarterThemeAdapter` supplied by the active theme.

The page-navigation loader is the deliberate universal visual exception. Every
theme includes `starter-shared::components.navigate-loader` unchanged and uses
only `public/assets/starter/css/starter.css` for its presentation. Theme assets
must not override or duplicate this loader. Livewire action loaders remain a
separate interaction and may follow the active theme.

## Cross-theme contract and visual ownership

- Themes share only the product contract: the same information, actions, authorization, validation, loading/empty/error states, accessibility meaning, and responsive capability must remain available.
- Every visual decision is theme-owned. Select the component, hierarchy, markup, class names, density, typography, spacing, color, icon treatment, and interaction presentation from the active vendor template.
- Never use one theme as the visual fallback for another. A new theme must use its own table, toolbar, dropdown, pagination, form, card, tab, alert, and modal presentation rather than restyling another theme.
- Shared `data-starter-*`, Livewire events, PHP data, and authorization may be reused. Theme Blade and theme assets must translate that shared behavior into their own vendor component patterns.
- Identical theme view files are acceptable only when the file has no visual hierarchy of its own, such as a thin status/error forwarder. A rendered component or page with visible structure must be composed independently from its theme atlas.

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
- The active vendor table is the visual source of truth. Tabler PowerGrid follows the closest Tabler advanced-table example and Bootstrap adapter; each future theme must document its own table source in `runtime-map.md`. Matching columns and behavior never justify matching visual markup.
- Enable a type-appropriate per-column filter by default for every meaningful data column. Free-form or high-cardinality text uses live text filtering. A bounded option set—such as enum, status, boolean, role, relation, App, or another controlled reference—must use select, multi-select, or boolean controls populated from the viewer-authorized source of truth; do not replace it with free-text search. Numeric columns use an appropriate range/value control; date/datetime columns always provide inclusive **from** and **to** filters. Exclude only columns for which filtering is genuinely meaningless (for example actions, derived/audit-only/system metadata), document the reason, and test it.
- Column filters query live—debounced where needed—without an Apply/Search button. Persist filter/sort/search state across reload using the supported PowerGrid/Livewire URL or session mechanism; reset pagination only when filtering/sorting scope changes.
- An above-grid one-row filter card is optional. Use it only for cross-column, composite, or high-value filters that are not represented by a column filter. Never repeat a column filter in the card.
- Size columns for the filter control and expected content, not arbitrary equal widths. Keep headers/values readable and consistent at desktop and mobile sizes; wrap deliberately where useful and use `table-responsive`/horizontal scrolling for wide tables.
- Muteable entity tables include select-all, validated bounded bulk actions, by-filter actions, and complete lifecycle controls unless excluded by the domain/audit rules. Dangerous or broad actions require accurate scope/count and explicit confirmation.
- Tables with horizontally long content must be placed in a full-width container (single column layout) to minimize horizontal scrolling. Always wrap tables inside a white background card.
- Every PowerGrid table renders pagination both above and below the table. In each position, use a stable three-zone row: the per-page selector followed by `Data per halaman` on the left, page navigation centered independently, and `Menampilkan X sampai Y dari Z data` right-aligned on one line. Always render the current page index—even when the result has only one page—with disabled previous/next controls where appropriate. Show at most five consecutive page numbers nearest the active page so navigation never displaces the left or right zones. Both positions stay synchronized with the same server-side page state; wording and behavior are universal while visual presentation remains owned by the active theme.
- Place `Column::action('Aksi')` first in `columns()` so PowerGrid renders the action column immediately after its automatically prepended checkbox column. Use the label `Aksi`, never `Aksi Massal` or another variation.
- Group every row action in one borderless, icon-only trigger with `aria-label="Aksi"`; dropdown items retain clear text labels. Use Alpine (`x-data`, `@click`, and related state) instead of Bootstrap `data-bs-toggle="dropdown"`, and render the menu through `x-teleport="body"` to avoid clipping and table-overflow/z-index problems during Livewire DOM morphing.
- Date and datetime columns use range filters. Apply `->params(['mode' => 'range'])` to the relevant `Filter::datepicker` or `Filter::datetimepicker`, then preserve and test inclusive from/to boundaries.
- Global PowerGrid styling belongs to each theme's own adapter and theme asset; shared translations remain theme-neutral. Do not put selectors from one theme into another theme's assets or duplicate theme fixes in individual tables.

## Form, modal, and feedback behavior

- Keep action controls near their relevant content. Use the closest theme form, card, modal, alert, badge, empty-state, and pagination patterns.
- Browser-native dialogs are forbidden: do not use `alert`, `confirm`, `prompt`, or their equivalents. Every dialog is an active-template, theme-consistent modal. Every user-action confirmation uses this modal and states the action, affected scope/count, irreversible impact when applicable, and a clear cancel/confirm choice. Destructive, broad, archive/restore, and permanent-delete actions require this confirmation before the server mutation.
- Use a modal for a compact, single-purpose mini form that can be completed without losing page context. Keep it focused, accessible, and short; validation errors remain in the modal and inputs preserve state. A complex, multi-step, large, or page-defining form must use a dedicated page rather than an oversized modal.
- Every modal must be closable by clicking anywhere on the backdrop (outside the modal dialog). For modals managed manually via Livewire (e.g., using a boolean property and `d-block`), ensure `wire:click.self` is applied to the outermost `.modal` element to capture backdrop clicks.
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
- For PowerGrid, test live global and per-column filtering, date from/to boundaries, persisted reload state, sort, pagination/last page, empty result, and role-specific scope.
- For CSS changes, verify normal, focus, invalid, and invalid+focus states; inspect DOM/computed style when a screenshot is inconclusive.
- For layout or global CSS changes, test both a short page and a vertically long page in Chromium at the `1280x768` safe area. Verify document/root overflow, horizontal and vertical scrollbars, and sticky navigation. Firefox verification is excluded by default and is required only when the developer explicitly requests it or reports a Firefox-specific bug; default verification must not depend on desktop-control permission.

## Safe Area Resolution

- Dalam mengatur tata letak (layout) komponen UI, selalu mengacu pada resolusi **1280x768** sebagai titik aman (safe area) agar tampilan tetap proporsional dan tidak terpotong pada berbagai layar.
