# New App and Subdomain

## Creation

- The installer asks for the first App code/subdomain and name after database-reset confirmation. Empty input is valid: root landing, auth, profile, settings, users/roles, and logs remain usable; never create a dummy App.
- Create an App with:

```bash
php artisan starter:make-app <subdomain> --name="App Name" --description="Short description" --icon=apps
```

- A subdomain contains lowercase letters, digits, and internal hyphens only; `api` is reserved. The generator creates App config/web/API routes, dashboard Livewire/view, route test, and runs `starter:sync` unless `--no-sync` is required before completing the files.
- Complete config modules/menus and routes named `<subdomain>.<module>.<action>`, dry-run then force sync, assign modules to roles, configure DNS/vhost to `public`, confirm session cookie scope, and test generator, route, authorization, and browser behavior.

## App migration and navigation

- App migrations live in `database/migrations/apps/<subdomain>/`; tables use `{subdomain}_{module}_{entity}`. Generate the model and migration separately with an explicit migration path. The separation is source ownership only: `php artisan migrate` runs all pending App migrations.
- Build root/auth/App URLs with named routes and `StarterNavigation`, never manual hosts or auth URLs. Navigation across origins/subdomains is full-page browser navigation, never `wire:navigate` or CORS.
- Every App switcher link opens its App in a new browser tab with `target="_blank"` and `rel="noopener noreferrer"`; never intercept it with Livewire navigation.
- `APP_URL` points to the root matching `APP_DOMAIN`; use a POST+CSRF logout form and a safe redirect. Test login, session, lock screen, and logout at root and App domains locally and in production.

## API gateway

- APIs are a shared gateway, not Apps: `api.<APP_DOMAIN>`. They are absent by default and register only with `STARTER_API_ENABLED=true`.
- Put each App's endpoints only in `routes/apps/<subdomain>.api.php`. The registrar supplies `api.<APP_DOMAIN>/<subdomain>`, `api` middleware/rate limit, and `api.<subdomain>.*` route names; write only App-relative paths.
- API routes do not enter `starter:sync`, web menu/module metadata, landing selection, or web `starter.authorize`. Each business endpoint declares suitable API authentication and authorization.
- Scramble serves API docs/OpenAPI; production docs require authenticated Superuser. CORS is closed by default; an approved cross-origin need must specify a narrow origin/credential allowlist—never wildcard authenticated endpoints.

Do not manually register Apps in a provider, create config without routes (or vice versa), use mismatched route prefixes, place App web routes in starter global/web routes, or put App APIs in `routes/api.php`/another App's API file.
