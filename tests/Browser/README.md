# Navigation regression checks

`login-loader.cjs` checks the shared Livewire login and lock-screen unlock
redirect handoff without credentials or a browser dependency. Successful actions
keep the loader visible and the form busy until navigation, including late
request cleanup while the destination is still loading. Failed password checks
restore the form for a retry; browser Back clears stale loading state. All three
themes use the authentication shell for both pages. Run
`node tests/Browser/login-loader.cjs` after changing the shared runtime, an
authentication layout, or the lock-screen component.

`navigation.cjs` renders the actual Blade shell in the three local per-theme demo
hosts using synthetic context. It performs no authentication or database writes.
Run `starter:sync` in each demo after extracting the current local runtime archive
into its `theme-intake/<theme>/` directory, then run:

```sh
NODE_PATH=<directory-containing-playwright> node tests/Browser/navigation.cjs
```

The default browser is installed Chrome on macOS. Set `CHROME_PATH` for another
Chromium executable. Results and failures go directly to the terminal; the runner
does not save screenshots or JSON reports. `NAVIGATION_EVIDENCE_DIR` is no longer
used. If a specific failure requires a saved diagnostic, follow
`docs/rules/testing.md`: use the workspace `.agent-tmp/<task-id>/` directory,
ignore it when inside Git, and remove the temporary output after use.

The checks cover both layouts, short and long pages, 0/3/8/24 horizontal menu
items, and viewport widths 1920/1280/1024/390. Assertions cover wrapping, document
overflow, menu/content separation, dropdown bounds, mobile menu access, sticky
navigation, and asset/JavaScript errors. The PHP fixture uses the current demo's
view overrides, so it also verifies the shell actually selected by the host.

Run `livewire-dialog-loader.cjs` with the same `NODE_PATH` to check that modal
requests show busy state inside the dialog while ordinary page requests retain
the shared loader.

## CMS editor and modal requests

Run `cms-editor.cjs` with the same `NODE_PATH` to check the pinned Quill editor in
all three themes at desktop and phone widths. It covers image resizing, undo,
saved HTML dimensions, slug/date helpers, picker focus, and navigation cleanup.
Tabler also checks editor expansion, text wrapping, and external scrollbars (drag/keyboard/bounds), plus calendar/time
selection and WIB consistency in Jakarta and Los Angeles browser timezones.

## Application branding runtime

Run `node tests/Browser/application-branding.cjs` to verify that saved logo/favicon
events update their separate DOM targets and resetting either preserves the other.
Livewire upload validation, persistence, authorization, rollback cleanup, and native
form rendering are covered by `StarterApplicationBrandingTest.php`; inspect the
Settings company section in both layouts at desktop and mobile sizes.

Run `node tests/Browser/brand-image-crop.cjs` for crop geometry, transparent
contain/cover behavior, output PNG, bounds, cancellation, upload failure/retry,
and object URL/inert-background cleanup. This deterministic runtime test uses
browser API doubles; real Canvas output and Livewire upload require direct
browser testing in each demo. Select a large raster image, test both crops,
position/zoom and keyboard/drag controls, cancel and reopen after upload, then
verify native modal/control proportions and no overflow in both layouts at
1280×768 and 390×844. Output dimensions are 800×200 (logo) and 256×256 (favicon);
PNG alpha must survive, and cancel must preserve the saved and pending assets.

## Personal notification audio

Run `node tests/Browser/notification-audio.cjs` for the shared runtime contract:
per-account keys, deferred defaults, no automatic playback, local tone generation,
context reuse, navigation cleanup, invalid keys, and browser restrictions. Direct
browser verification in each theme covers preview, save, reload and independent
accounts in both layouts at 1280×768 and 390×844. Ordinary users must still receive
403 from administrative Settings while their own `/settings/notifications` works.

## Post category ordering

`cms-category-order.cjs` checks direct PowerGrid-style row dragging at 1280px
and 390px: drop/autosave, parent/subtree movement, Alt+Up/Down controls,
Livewire row replacement, network/validation retry, cross-parent guards,
and navigation cleanup. Run it with the same `NODE_PATH` and optional
`CHROME_PATH` as the editor checks. Host events, authorization, ordering
persistence, unseen siblings, stale snapshots, and audit are covered by
`CmsCategoryMediaTest.php` and direct browser checks.

`cms-post-actions.cjs` checks keyboard focus, busy-state dismissal, cleanup, and repeated opening of Tabler post quick-action dialogs. Run with the same `NODE_PATH` and `CHROME_PATH` options as the other CMS browser regressions. Persistence, filters, stale selections, rollback, and permissions are covered in `CmsWorkspaceTest.php`.
