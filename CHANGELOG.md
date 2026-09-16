# Changelog

All notable changes to this project will be documented in this file. The format
follows Keep a Changelog and releases follow Semantic Versioning.

## [Unreleased]

## [1.4.1] - 2026-09-16

### Changed

- Stop requiring the synchronous queue driver during security and production
  deployment validation; keep `sync` as the installer default while allowing a
  database queue on hosting with reliable cron-driven processing.

## [1.4.0] - 2026-09-08

### Added

- Add theme-specific, project-owned starter view overrides with checksum-based
  status reporting, conflict detection, dry-run replacement, path validation,
  and automatic pre-replacement backups.
- Add `starter:views-publish`, `starter:views-status`, and
  `starter:views-replace` while keeping Composer updates non-destructive.

### Changed

- Use the configured company logo consistently across application, landing,
  favicon, and authentication surfaces for all supported themes.

### Fixed

- Restore native-scale company logos above the authentication form in Tabler,
  DashCode, and Vuexy, including centered desktop and mobile placement.
- Remove only the recognized fresh-Laravel feature-test scaffold before
  domain-aware starter verification so current Laravel 13 hosts install cleanly.

## [1.3.0] - 2026-09-07

### Added

- Synchronize browser activity across tabs for the same authenticated session,
  reconcile idle expiry with the server, and audit automatic, manual, and
  direct screen-lock events.
- Require an explicit Composition Decision for page-defining surfaces and bind
  theme color choices to complete native token, contrast, and interaction-state
  pairs.

### Changed

- Refresh session activity at a timeout-aware interval and cache-bust the
  shared starter runtime consistently across all supported themes.

## [1.2.3] - 2026-09-07

### Added

- Require indexed `created_by` and `updated_by` actor ownership on every
  application-owned table and actor-bound audit coverage for every meaningful
  authenticated server-side action.
- Treat developer-designated local per-theme demo projects as standing
  synchronization and host-verification targets for affected canonical changes.

### Fixed

- Enlarge horizontal DashCode and Vuexy company wordmarks, remove duplicated
  Vuexy brand text, restore Vuexy's native fixed-menu content spacing, and add
  consistent profile-page descriptions.
- Normalize card, alert, standalone settings, and confirmation typography plus
  section-to-control spacing across all three themes.
- Remove decorative page pretitles from every theme and restore a proportional,
  vendor-anchored typography hierarchy for page, modal, card, subsection,
  label, and supporting text—including activity-detail dialogs.
- Keep DashCode role-form header actions aligned to the page end and establish
  a tested header overlay layer in DashCode, Tabler, and Vuexy so account and
  App dropdowns remain above sticky content cards.
- Adopt DashCode's native three-dot row-action menu with semantic icons and
  explicit hover, focus, and disabled states across starter PowerGrid tables.

## [1.2.2] - 2026-09-06

### Added

- Add canonical live vendor-demo references to each theme atlas while keeping
  versioned local evidence as the implementation source of truth.
- Add a bounded native-composition exercise and a shared PowerGrid toolbar
  structure to the UI/UX contract.

### Fixed

- Keep Role form columns within their grid bounds and center summary-avatar
  icons consistently across Tabler, DashCode, and Vuexy.

## [1.2.1] - 2026-09-06

### Changed

- Normalize icon-to-label spacing in horizontal navigation across Tabler,
  DashCode, and Vuexy.
- Keep the active App identity visible in every horizontal layout on desktop
  and mobile without introducing horizontal overflow.
- Increase the Tabler horizontal brand logo from 32px to 36px while preserving
  the vertical-sidebar proportion.

## [1.2.0] - 2026-09-05

### Added

- Add the licensed-private Vuexy 3.0.0 theme with independent vertical and
  horizontal starter views, PowerGrid adapter, indexed source atlas, and a
  checksum-verified local runtime archive workflow.
- Require every theme addition to update the canonical package, documentation
  site, and template-repository documentation and distribution notices.

### Changed

- Tighten the cross-theme contract so layout and component composition remain
  identical while each theme deliberately selects its native cosmetic variants.
- Refine Vuexy spacing, profile proportions, icon alignment, responsive tabs,
  semantic color hierarchy, and PowerGrid pagination density.
- Make the profile section-navigation surface match the active content height
  on desktop across Tabler, DashCode, and Vuexy while retaining natural height
  in stacked responsive layouts.
- Replace DashCode's four-card account summary with the shared single white row
  while retaining native DashCode typography, medallions, and responsive styling.
- Place password requirements on a dedicated full-width row before the paired
  new-password controls in every theme.
- Restore Vuexy settings-section spacing and a visible, proportionate company
  logo preview surface.
- Rebalance Vuexy activity-summary cards with compact metric typography,
  consistently sized icon medallions, and a tighter vertical rhythm.
- Make new-theme templating fail closed for lower-cost LLMs through an ordered
  state machine, fingerprinted run ledger, full browser-state matrix, objective
  geometry tolerances, evidence invalidation, and a machine-readable schema.

## [1.1.1] - 2026-08-25

### Changed

- Make technical English a hard pre-review gate for every persisted internal
  plan and issue specification, regardless of the developer's chat language.
- Require a whole-file language compliance review with narrow exceptions only
  for implementation-relevant literal UI copy, business names, identifiers,
  and marked verbatim requirements.

## [1.1.0] - 2026-08-25

### Changed

- Add a contextual UI decision framework that selects collection representations
  from page purpose, user task, data semantics, volume, density, interaction,
  hierarchy, and responsive trade-offs instead of defaulting to tables or cards.
- Require candidate comparison, deliberate page and action hierarchy, progressive
  disclosure, and an internal review against CRUD-style and decorative UI
  anti-patterns before selecting active-theme components.

## [1.0.3] - 2026-08-25

### Changed

- Require a complete, numbered chat confirmation of every requested outcome
  before converting the approved request into a traceable technical issue file.
- Add breathing room between the Tabler vertical-navigation heading and its
  first menu item.

## [1.0.2] - 2026-08-25

### Changed

- Link Composer, Packagist, and both README languages directly to the official
  landing page and documentation website.
- Require token-efficient English issue specifications that fully constrain
  lower-cost LLM implementation, semantic menu icons, and full-width PowerGrid
  column filters with content-aware column sizing.

## [1.0.1] - 2026-08-23

### Changed

- Prefix derived-host issue specification filenames with their creation timestamp
  and preserve that prefix when archiving completed work, so directory listings
  remain chronological.

## [1.0.0] - 2026-08-15

### Added

- Composer-native fresh Laravel installer with guarded install, reset, sync,
  App creation, and production deploy workflows.
- Agentic AI development contract and routed architecture/security/UI/testing
  rules for derived Laravel projects.
- Modular optional-theme registry with a minimal redistributable Tabler theme.

### Security

- Fresh-source compatibility gate, secret-safe file rollback, protected
  internal command contexts, and production preflight validation.
