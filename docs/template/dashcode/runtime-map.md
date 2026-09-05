# Dashcode Runtime Component Map

This map records the source of every selected DashCode runtime pattern. Themes
may share data and actions, but they must not share presentation structure.

| Runtime need | Vendor source | Selected DashCode pattern | Primary runtime | Verification states |
|---|---|---|---|---|
| Vertical and horizontal shell | `blank-page.html`, `assets/js/app.js` | app wrapper, fixed light sidebar, native horizontal menu | `templates/layouts/*` | desktop, drawer, sticky, short and long page |
| Landing | `blank-page.html`, `basic-widgets.html` | branded navigation, hero, application preview, capability cards | `templates/landing.blade.php` | no apps, apps available, desktop, mobile |
| App dashboard | `index.html`, `project-dashboard.html` | welcome panel, statistic cards, module list, next steps | `templates/app-dashboard.blade.php` | modules, empty module set, wrapped content |
| Authentication | `signin-one.html`, `signin-two.html` | split layout, focused form, responsive mobile brand | `auth/*`, `templates/layouts/auth.blade.php` | default, invalid, loading, mobile |
| Navigation account menu | `blank-page.html`, `profile.html` | identity trigger and account dropdown | `templates/layouts/account-menu.blade.php` | closed, open, keyboard, navigation |
| Card and statistic | `card.html`, `basic-widgets.html`, `statistics-widgets.html`, `settings.html` | white surface, pastel summary card, semantic border, and solid icon medallion | dashboard, profile, settings, activity log | 1280x768, mobile, long text, nested color contrast |
| Form and validation | `input-layout.html`, `select.html`, `file-input.html`, `form-validation.html` | label, control, help text, invalid feedback, responsive grid | user, role, profile, settings | valid, invalid, disabled, upload |
| Profile | `profile.html`, `settings.html`, `input-layout.html` | identity overview, side navigation, two-column form, action footer | `profile/edit-my-profile.blade.php` | account, security, required password, photo |
| Selection controls | `checkbox.html`, `radio.html`, `switch.html` | native DashCode control with associated label | role and security forms | checked, unchecked, disabled |
| Tabs and accordion | `tab-accordion.html`, `settings.html` | responsive section navigation and access accordion | settings, profile, role form | active, inactive, open, closed |
| Alert, toast, and modal | `alert.html`, `modal.html` | semantic feedback, overlay dialog, focus trap, recovery action | `templates/components/*` | success, info, warning, danger, validation |
| Badge and empty state | `badges.html`, `placeholder.html`, `404.html` | soft status badge and explained empty state | tables, details, errors | semantic status, long text, safe return |
| PowerGrid table | `advance-table.html` | DashCode header/cell density, toolbar, filters, action dropdown, local overflow, pagination | `powergrid/*`, all PowerGrid views | search, sort, filters, selection, bulk action, overflow |

Any new runtime component must be added here before the theme is complete. Use
`template.md` as the discovery index and inspect the cited vendor HTML before
selecting or changing a presentation pattern.
