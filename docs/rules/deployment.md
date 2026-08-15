# Shared-Hosting Deployment

## Prerequisites and environment

- Deploy the package version locked by the Laravel application's `composer.lock`. PHP must match locked dependencies, `intl` and the selected database driver must exist, document root must be `public`, root/App subdomains point to the same install, and `storage/` plus `bootstrap/cache/` are writable.
- Production baseline: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, matching scheme-free `APP_DOMAIN`, `STARTER_API_ENABLED=false` unless needed, explicit `STARTER_THEME` and `STARTER_LAYOUT` matching local, production DB credentials, database session/cache, sync queue, secure cookie on HTTPS, and `id`/`id`/`id_ID` locales. Superuser credentials never belong in environment files.
- The installer derives `APP_DOMAIN`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, and a domain-specific `SESSION_COOKIE` from `APP_URL`; verify the generated production values and test root/auth/App cookie sharing on a real domain. If API is enabled, point `api.<APP_DOMAIN>` to the same `public`; docs still require Superuser in production.

## Deployment flow

First deployment:

```bash
git clone <repository-laravel> <folder-project>
cd <folder-project>
cp .env.example .env
# configure production values in .env
composer install --no-dev --optimize-autoloader
php artisan starter:deploy
```

Routine update:

```bash
git status --short
git pull --ff-only origin master
composer install --no-dev --optimize-autoloader
php artisan starter:deploy
```

- Stop if production `git status --short` is not empty. Do not reset/stash/merge/rebase automatically. Back up database/uploads before risky migrations.
- Run `composer install` whenever host lock changes; host dependency/lock updates required by a starter release happen before Artisan.
- `starter:deploy` owns production preflight, database creation/connection, migration, verification/reuse of committed theme runtime assets, internal package asset publication, App registry, best-effort storage link, Livewire assets, final validation, and production optimization. It creates company/Superuser only when the production database is empty and never resets existing credentials. Do not add cache commands afterward.
- Production never downloads theme archives or stores vendor source and never needs `theme-intake/`. The local environment runs `starter:sync`, commits `public/assets/<theme>/`, and pushes it with the application. Deployment must fail before database mutation when explicit `STARTER_THEME`/`STARTER_LAYOUT` are absent or the selected committed runtime is missing/tampered; remediation happens in local development, followed by a new commit and pull.
- If configuration or route cache exists when deployment starts, `starter:deploy` clears it and restarts itself in a fresh PHP process before reading preflight values. Never validate production with configuration booted from stale cache, and never expose or invoke internal installer/deployer flags manually.
- Preflight runs before mutation and verifies app key, environment, debug, HTTPS, session encryption/cookies, domain, active theme/layout, `intl`, and runtime directory permissions. Direct `starter:sync` and `starter:reset` are blocked in production.
- Keep the solution shared-hosting compatible: use Laravel middleware/config/static assets and add no daemon, worker, Redis, CDN, reverse proxy, or web-server customization without approved need.

Run `composer audit --locked --no-dev --format=summary --no-interaction` only when requested, in a security review, or after dependency changes; report affected package/advisory and do not update dependencies without approval.

Verify `/up`, root/auth/App domains, enabled API docs/OpenAPI/endpoints, login/remember/lock/logout, password session revocation, synchronized role/module/menu, uploads, and audit logs. Add scheduler/cron only when a feature requires it. Release canonical package changes, update the Laravel application's Composer lock locally, then deploy that lock; never edit package source directly in production.
