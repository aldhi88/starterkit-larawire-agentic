# Dashcode Template Discovery Atlas

This atlas indexes the 74 DashCode HTML documents available in the owner-provided
archive under `theme-intake/dashcode/`. Vendor HTML and build sources remain
outside the Composer package and runtime archive. The Laravel presentation layer
lives in `resources/themes/dashcode/`; its minimum asset recipe is downloaded to
`public/assets/dashcode/` after size, license, path, and hash validation.

Use [`runtime-map.md`](runtime-map.md) for the components selected for production
and their verification states. This file is the discovery index used to compare
candidates before a runtime pattern is selected.

## Discovery workflow

1. Define the page purpose, data, primary actions, and required states.
2. Search this atlas for the closest page or component family.
3. Inspect one to three candidates in `theme-intake/dashcode/`.
4. Reproduce the DashCode hierarchy, density, classes, and component language
   without copying the customizer, CDN references, or unused dependencies.
5. Implement the pattern in the DashCode theme view and verify it in a browser.

## Layout mechanism

DashCode uses one shell for both supported desktop layouts. The vendor customizer
adds `.horizontalMenu` to `.app-wrapper`; the starter runtime derives that class
from server configuration and does not use customizer local storage.

| Starter layout | DashCode shell | Desktop navigation | Small-screen navigation |
|---|---|---|---|
| `vertical` | `.app-wrapper` | `.sidebar-wrapper` and `.vertical-box` | sidebar drawer |
| `horizontal` | `.app-wrapper.horizontalMenu` | `.horizental-box` and `.main-menu` | sidebar drawer |

Primary references are `blank-page.html`, `assets/js/app.js`, and
`assets/css/app.css`.

## Primary reference routes

| Runtime need | Candidate sources | Patterns to compare |
|---|---|---|
| Shell and navigation | `blank-page.html`, `index.html` | header, sidebar, horizontal menu, content, footer |
| App dashboard and metrics | `index.html`, `crm-dashboard.html`, `ecommerce-dashboard.html`, `banking-dashboard.html`, `project-dashboard.html` | welcome block, statistic card, summary, activity |
| Tables and lists | `basic-table.html`, `advance-table.html`, `invoice.html`, `project.html` | density, status, action, filter, pagination |
| Forms | `input.html`, `input-layout.html`, `form-validation.html`, `select.html`, `date-picker.html` | label, input, validation, grouping, help text |
| Modal and feedback | `modal.html`, `alert.html`, `badges.html`, `progressbar.html` | confirmation, semantic status, destructive action |
| Profile and settings | `profile.html`, `settings.html` | identity, side navigation, section form |
| Authentication | `signin-one.html`, `signin-two.html`, `signin-three.html`, `lock-screen-one.html` | split auth, compact auth, focused form |
| Error and maintenance | `404.html`, `under-maintanance.html`, `comming-soon.html` | illustration, explanation, recovery action |

## Closest component sources

| Component | Reference files | Relevant DashCode pattern |
|---|---|---|
| App shell | `blank-page.html` | `app-wrapper`, `app-header`, `content-wrapper`, `site-footer` |
| Sidebar | `blank-page.html` | `sidebar-wrapper`, `logo-segment`, `sidebar-menu`, `navItem`, `sidebar-submenu` |
| Horizontal menu | `blank-page.html` | `horizontalMenu`, `horizental-box`, `main-menu`, `sub-menu` |
| Card | `card.html`, `basic-widgets.html` | `card`, header, body, footer |
| Button | `buttons.html` | solid, outline, light, and disabled variants |
| Alert | `alert.html` | semantic color, icon, content, dismiss action |
| Badge and status | `badges.html` | solid, light, and outline variants |
| Modal | `modal.html` | dialog, content, close action, body, footer |
| Dropdown | `dropdown.html` | trigger, menu, item, placement |
| Tabs and accordion | `tab-accordion.html` | active state, section navigation, collapsed state |
| Pagination | `pagination.html` | previous, next, active page, disabled state |
| Input and validation | `input.html`, `input-layout.html`, `form-validation.html` | label, control, invalid state, feedback |
| Selection controls | `checkbox.html`, `radio.html`, `switch.html` | selected, unselected, disabled, associated label |
| Select, date, and file | `select.html`, `date-picker.html`, `file-input.html` | control height, picker, upload |
| Data table | `basic-table.html`, `advance-table.html` | header, cell, selection, search, filters, actions, pagination |
| Avatar and profile | `profile.html`, `settings.html` | avatar, identity block, account settings |
| Empty and error | `404.html`, `placeholder.html` | illustration, explanation, recovery action |

## Page inventory

### Dashboards and operations

- `index.html`, `banking-dashboard.html`, `crm-dashboard.html`,
  `ecommerce-dashboard.html`, `project-dashboard.html`.
- `basic-widgets.html`, `statistics-widgets.html`.
- `chat.html`, `email.html`, `kanban.html`, `calander.html`, `todo.html`.
- `project.html`, `project-details.html`.

### Data, charts, and documents

- `basic-table.html`, `advance-table.html`.
- `apex-chart.html`, `chartjs.html`.
- `invoice.html`, `invoive-add.html`, `invoive-preview.html`.
- `map.html`.

### Forms

- `input.html`, `input-group.html`, `input-layout.html`, `textarea.html`.
- `form-validation.html`, `form-repeater.html`, `wizard.html`.
- `input-mask.html`, `file-input.html`, `select.html`, `date-picker.html`.
- `checkbox.html`, `radio.html`, `switch.html`.

### UI components

- `alert.html`, `badges.html`, `buttons.html`, `card.html`.
- `carousel.html`, `dropdown.html`, `image.html`, `modal.html`.
- `pagination.html`, `placeholder.html`, `progressbar.html`.
- `tab-accordion.html`, `tooltip-popover.html`, `typography.html`.
- `colors.html`, `icons.html`, `video.html`.

### Authentication and utility

- `signin-one.html`, `signin-two.html`, `signin-three.html`.
- `signup-one.html`, `signup-two.html`, `signup-three.html`.
- `forget-password-one.html`, `forget-password-two.html`, `forget-password-three.html`.
- `lock-screen-one.html`, `lock-screen-two.html`, `lock-screen-three.html`.
- `profile.html`, `settings.html`, `pricing.html`, `blog.html`, `blog-details.html`.
- `404.html`, `comming-soon.html`, `under-maintanance.html`, `blank-page.html`.

## Implementation boundaries

- `advance-table.html` is the required visual reference for every DashCode
  PowerGrid table. Preserve DashCode table density, filters, actions, selection,
  pagination, and local horizontal scrolling while PowerGrid owns server state.
- Cross-theme parity covers data, actions, authorization, state, accessibility,
  and responsive capability only. Do not copy Tabler or Bootstrap presentation
  markup into the DashCode runtime.
- Do not load Google Fonts, Iconify, unpkg, or another CDN at runtime.
- Do not run `settings.js`, the Theme Customizer, or `rt-plugins.js` globally.
- Use Alpine and the shared runtime for small interactions. Add a local page
  dependency only when a production feature requires it.
- The owner retains the original vendor archive and applicable commercial
  license. The public recipe contains only the authorized minimum runtime subset.
- DashCode uses its dedicated Tailwind PowerGrid adapter, never the Tabler
  Bootstrap adapter.
