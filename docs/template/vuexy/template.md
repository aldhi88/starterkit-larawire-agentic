# Vuexy 3.0.0 source atlas

The owner supplied 598 HTML references and confirmed a commercial license for
personal/internal use on 2026-09-05. This is not public redistribution permission.
The full intake remains ignored and outside Composer. The integration is
registered as the private `vuexy` installer option after package, fresh-host,
and browser verification on 2026-09-05.

## Reference families

| Family | Location | Runtime decision |
|---|---|---|
| Vertical starter | `html-starter/vertical-menu-template-no-customizer/index.html` | Primary minimal application shell reference |
| Horizontal starter | `html-starter/horizontal-menu-template-no-customizer/index.html` | Horizontal application shell reference |
| Full vertical | `html/vertical-menu-template-no-customizer/` | Component and page reference |
| Full horizontal | `html/horizontal-menu-template-no-customizer/` | Navigation and responsive reference |
| Landing | `html/front-pages-no-customizer/landing-page.html` | Public entry composition reference |
| Customizer variants | Matching directories without `-no-customizer` | Indexed reference; no runtime customizer dependency |

All HTML paths and content hashes are in `source-index.json`, including starter,
front-page, full-page, and vendor support HTML. Source counts must match the full
intake rather than only the selected runtime reference families.

## Native design grammar

Vuexy uses Bootstrap 5.3 components with its own compiled core stylesheet,
`layout-wrapper`, `layout-container`, `layout-page`, `content-wrapper`, and
`container-xxl container-p-y` shell regions. Navigation uses `menu-inner`,
`menu-item`, `menu-link`, and `menu-sub`. Authentication uses the cover layout
with a vendor illustration and a separate form column. Iconify's bundled
`icon-base ti tabler-*` classes are Vuexy vendor icons, not a dependency on the
starter's Tabler implementation. Inspect each chosen icon in the shipped CSS.

Use native cards, labels, validation, dropdowns, tabs, switches, and pagination.
Do not include demo customizer, search, translation, chart, or validation plugins
unless an actual starter runtime capability requires them. The starter uses
Livewire validation and PowerGrid server-side tables.

## Verification record

The owner-prepared runtime archive contains the exact 10-file hashed runtime
closure and is checked against the asset manifest and `VUEXY_SHA256SUMS`. Its
theme-owned integration layer is `css/vuexy.css` plus `js/vuexy.js`. Of the 598
indexed HTML files, 25 are
recorded as direct runtime references and 573 remain discovery-only. The local
`starterkit-larawire-laravel-vuexy` host passed install and idempotent sync,
vertical and horizontal shell checks, landing/auth/error pages, profile and
settings tabs, role and user forms, activity and PowerGrid states, and desktop
plus 390x844 responsive checks. The demo host defaults to the requested
`vertical` layout. The archive and its private distribution notice are
maintained in the separate template repository; no public download URL is
advertised.
