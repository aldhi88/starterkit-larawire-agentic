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
