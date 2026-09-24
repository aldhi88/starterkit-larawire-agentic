# Changelog

All notable changes to this project will be documented in this file. The format
follows Keep a Changelog and releases follow Semantic Versioning.

## [Unreleased]

## [1.7.5] - 2026-09-24

### Added

- Add a password-confirmed administrator recovery action that resets an
  enrolled non-Superuser authenticator through a theme-consistent confirmation
  modal in Tabler, DashCode, and Vuexy.

### Changed

- Revoke existing sessions when an administrator clears a user authenticator,
  remove its recovery codes, and record actor-bound audit and security events.

## [1.7.4] - 2026-09-24

### Added

- Allow an authorized administrator to set and confirm a policy-compliant
  temporary password while creating a user, including consistent controls and
  guidance across Tabler, DashCode, and Vuexy.

### Changed

- Skip credential email delivery when the administrator explicitly supplies
  the temporary password, while preserving mandatory first-login password
  replacement and clearing the sensitive Livewire state after use.

## [1.7.3] - 2026-09-24

### Added

- Add a default-enabled, server-rendered five-digit human challenge to the
  credential step, with session hashing, expiry, rotation, throttling, and
  security audit events.
- Add opt-in per-user TOTP authentication with QR enrollment, password and code
  confirmation, one-time recovery codes, encrypted storage, login enforcement,
  global environment control, and theme-parity profile/login interfaces.

### Changed

- Chain password, optional email OTP, and enrolled authenticator verification
  before creating the authenticated session, preserving only safe credential
  state when the human challenge rotates after a failed attempt.

## [1.7.2] - 2026-09-19

### Changed

- Dispatch login OTP email through Laravel's configured queue connection using
  encrypted after-commit jobs, preserving inline delivery with `sync` while
  supporting durable asynchronous workers.

## [1.7.1] - 2026-09-19

### Added

- Add optional email OTP verification after password validation, with a
  six-digit code, five-minute expiry, resend throttling, attempt limits,
  security audit events, and consistent login UI across all supported themes.
- Add host-overridable HTML and text OTP mail templates with company branding
  and an active-theme logo fallback.

### Changed

- Require the authenticated session to retain OTP proof for the current login
  while OTP protection is enabled, and disable remember-me for that flow.

## [1.7.0] - 2026-09-17

### Added

- Add code-first `visible` menu metadata so App menus and complete subtrees can
  be hidden from primary navigation without changing module authorization,
  route access, or landing-page selection.

### Changed

- Reconcile `APP_DOMAIN`, `SESSION_DOMAIN`, `SESSION_COOKIE`, and
  `SESSION_SECURE_COOKIE` from `APP_URL` on every production deployment,
  restarting in a fresh process before preflight when values change.
- Finish production deployment with `queue:restart` so long-running workers
  load the current code and configuration.

## [1.6.0] - 2026-09-16

### Added

- Add a profile-security password generator that fills matching 12-character
  mixed-case alphanumeric credentials across all supported themes.
- Queue temporary-password mail through encrypted after-commit jobs, supporting
  both inline `sync` execution and durable asynchronous queue backends.

### Changed

- Align production and account password validation at a six-character minimum
  with uppercase, lowercase, and numeric requirements.
- Require an explicit configured non-null production queue connection and
  report successful queue processing without claiming asynchronous delivery.
- Standardize page-header composition so title and description remain grouped
  while content starts with a distinct theme-native gap.

### Fixed

- Show every password validation reason without overlapping the visibility
  toggle or invalid-state decoration in Tabler, DashCode, and Vuexy.

## [1.5.0] - 2026-09-16

### Added

- Send generated temporary passwords for new users and administrator resets by
  synchronous email, with application-branded HTML and text messages.
- Validate a delivery-capable production mailer and reject `log`, `array`, or
  composite transports that contain either non-delivery transport.

### Changed

- Keep temporary credentials out of Livewire state and rendered theme views,
  and roll back account creation or password reset when email delivery fails.
- Disable modal controls and show targeted progress while password-reset email
  delivery is in progress across Tabler, DashCode, and Vuexy.

### Fixed

- Keep the session-activity heartbeat same-origin and allow it during mandatory
  password changes, preventing profile-security reload loops across root and
  App subdomains.

## [1.4.2] - 2026-09-16

### Fixed

- Redirect users with a temporary password directly to the profile security
  tab after login, avoiding an unnecessary App dashboard transition that could
  cause a reload loop on some session and domain configurations.

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
