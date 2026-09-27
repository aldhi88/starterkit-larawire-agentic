# Navigation regression checks

`navigation.cjs` renders the actual Blade shell in the three local per-theme demo
hosts using synthetic context. It performs no authentication or database writes.
Run `starter:sync` in each demo after extracting the current local runtime archive
into its `theme-intake/<theme>/` directory, then run:

```sh
NODE_PATH=<directory-containing-playwright> node tests/Browser/navigation.cjs
```

The default browser is installed Chrome on macOS. Set `CHROME_PATH` for another
Chromium executable. `NAVIGATION_EVIDENCE_DIR` controls screenshot/JSON output;
the default is `/tmp/larawire-navigation-evidence`.

The checks cover both layouts, short and long pages, 0/3/8/24 horizontal menu
items, and viewport widths 1920/1280/1024/390. Assertions cover wrapping, document
overflow, menu/content separation, dropdown bounds, mobile menu access, sticky
navigation, and asset/JavaScript errors. The PHP fixture uses the current demo's
view overrides, so it also verifies the shell actually selected by the host.
