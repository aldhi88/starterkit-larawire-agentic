# Vuexy runtime ownership

The runtime is composed from Vuexy 3.0.0 no-customizer HTML. Full and starter
variants remain indexed for discovery; demo customizer is not a dependency.
Application behavior is shared through Livewire and `data-starter-*` contracts.
Only theme-neutral error forwarders and the session redirect are reused.

| Runtime | Native reference |
|---|---|
| Application shell and navigation | `html-starter/vertical-menu-template-no-customizer/index.html`, corresponding horizontal starter |
| Authentication | `html/vertical-menu-template-no-customizer/auth-login-cover.html` |
| Landing | `html/front-pages-no-customizer/landing-page.html` |
| Forms and switches | `forms-basic-inputs.html`, `forms-switches.html` |
| Profile and settings | `pages-account-settings-account.html`, `pages-account-settings-security.html` |
| Role/module selection | `app-access-roles.html`, `ui-accordion.html`, native checkboxes/radios |
| PowerGrid | `tables-basic.html`, `tables-datatables-basic.html`, `ui-pagination-breadcrumbs.html` |
| Feedback | `ui-alerts.html`, `ui-modals.html`, `ui-toasts.html` |

Relative single-file references above belong to
`html/vertical-menu-template-no-customizer/`.

## Minimal presentation bridges

`vuexy.css` is the theme-named custom integration layer. It bridges
Starter-specific runtime mechanisms: shared
action-loader visibility, practical PowerGrid column widths, independently
centered pagination, portaled row-action positioning, and the measured content
offset for Vuexy's fixed horizontal menu. Native Vuexy dropdown markup is used;
menu coordinates are measured dynamically to avoid viewport and scroll-frame
clipping. Colors, spacing, surfaces, and controls remain native Vuexy. Cosmetic
selection uses Vuexy's native label badges, line tabs, bordered controls, soft
alerts, and three-zone pagination after comparing the nearest vendor variants.
The palette is semantic: primary for current navigation and primary actions,
success for verified/completed state, info for non-critical guidance, warning
for attention, danger for destructive/error state, and secondary for neutral
metadata. Tinted surfaces always carry their matching foreground color.
`vuexy.js` owns the idempotent navigation, dropdown, accordion, password, and
modal behavior needed beside the vendor runtime. The application layout uses
Vuexy's separate native vertical and horizontal shell markup while preserving
the same content hierarchy, components, grouping, placement, and responsive
composition as the Tabler/DashCode baseline.

## Distribution and verification

The owner confirmed personal/internal licensed use. The runtime archive is
private/local; no public URL or redistribution permission is inferred.
Integration and browser verification completed on 2026-09-05. The fresh
`starterkit-larawire-laravel-vuexy` host passed install and idempotent sync,
both native layout shells, landing/auth/error pages, settings and profile line
tabs, role and user forms, activity and PowerGrid states, action spacing, and a
390x844 overflow check. A second cosmetic audit covered centered avatar/icon
content, readable badges, profile-card alignment, subdued password guidance,
sidebar spacing, mobile tab containment, and balanced PowerGrid pagination.
Its final demo setting is `STARTER_LAYOUT=vertical`.
The 10-file private archive is intentionally not advertised through a public
download URL.
