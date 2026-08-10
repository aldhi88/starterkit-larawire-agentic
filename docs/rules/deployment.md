# Shared-Hosting Deployment

## Prerequisites and environment

- Deploy the package version locked by the Laravel application's `composer.lock`. PHP must match locked dependencies, `intl` and the selected database driver must exist, document root must be `public`, root/App subdomains point to the same install, and `storage/` plus `bootstrap/cache/` are writable.
- Production baseline: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS `APP_URL`, matching scheme-free `APP_DOMAIN`, `STARTER_API_ENABLED=false` unless needed, active `STARTER_THEME`, `STARTER_LAYOUT=vertical|horizontal`, production DB credentials, file session/cache, sync queue, secure cookie on HTTPS, strong `STARTER_SUPERUSER_PASSWORD`, and `id`/`id`/`id_ID` locales.
- The installer derives `APP_DOMAIN`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, and a domain-specific `SESSION_COOKIE` from `APP_URL`; verify the generated production values and test root/auth/App cookie sharing on a real domain. If API is enabled, point `api.<APP_DOMAIN>` to the same `public`; docs still require Superuser in production.

## Deployment flow

First deployment:

```bash
git clone <repository-laravel> <folder-project>
cd <folder-project>
cp .env.example .env
# configure production values in .env
composer install --no-dev --optimize-autoloader
php artisan starter:setup --company="Company Name"
```

Routine update:

```bash
git status --short
git pull --ff-only origin master
composer install --no-dev --optimize-autoloader
php artisan starter:sync
```

- Stop if production `git status --short` is not empty. Do not reset/stash/merge/rebase automatically. Back up database/uploads before risky migrations.
- Run `composer install` whenever host lock changes; host dependency/lock updates required by a starter release happen before Artisan. Never run `starter:setup --reset-password` during routine deploy.
- `starter:setup`/`starter:sync` coordinate migration, security check, internal asset publication, App registry, best-effort storage link, Livewire assets, and production optimization. Setup creates an APP key only if missing and creates client/Superuser; sync never resets credentials. Do not add cache commands after sync.
- `starter:security-check` is read-only and verifies app key/session encryption/cookies/domain/debug/HTTPS/default password/`intl`/runtime permissions. `optimize` is production only; development uses direct config and `optimize:clear` if stale cache exists.
- Keep the solution shared-hosting compatible: use Laravel middleware/config/static assets and add no daemon, worker, Redis, CDN, reverse proxy, or web-server customization without approved need.

Run `composer audit --locked --no-dev --format=summary --no-interaction` only when requested, in a security review, or after dependency changes; report affected package/advisory and do not update dependencies without approval.

Verify `/up`, root/auth/App domains, enabled API docs/OpenAPI/endpoints, login/remember/lock/logout, password session revocation, synchronized role/module/menu, uploads, and audit logs. Add scheduler/cron only when a feature requires it. Release canonical package changes, update the Laravel application's Composer lock locally, then deploy that lock; never edit package source directly in production.
