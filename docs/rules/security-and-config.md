# Security and Dynamic Configuration

## Configuration

- Admin-changeable runtime configuration lives in `starter_configs` and is accessed only through `StarterConfigService`, which supplies typed safe fallbacks before table/migration availability. Changes invalidate their cache and are audited.
- Secrets and environment/infrastructure settings stay in `.env`/`config/*.php`, never the database. Any local environment-key change updates `.env` and `.env.example` together; the example contains safe placeholders/defaults and required guidance, never secrets.
- Do not make developers manually add a key that can be changed in the local checkout. Document production value/pattern in `.env.example` and deployment rules.
- `STARTER_LAYOUT` is infrastructure UI configuration validated against the active theme's registered layout-to-view map. Every current theme must register `vertical` and `horizontal`; installer default is `vertical` and no additional per-layout environment key is allowed.
- Local/testing Superuser credentials must be explicit in `.env` and `.env.example`; `STARTER_SUPERUSER_PASSWORD=superuser123` is local/development/testing only. Production needs a strong replacement and `starter:security-check --production` must reject the default.
- Existing dynamic configuration includes API gateway enablement (`STARTER_API_ENABLED`, default false), remember-me, lock-screen enablement/timeout, login attempts/decay, and maximum image upload. New config requires an idempotent migration/seed, typed fallback/accessor/clamp, optional Settings UI, all consumers, validation, audit, invalidation, and tests.

## Sessions, lock screen, and uploads

- Use file session/cache. The installer derives `APP_DOMAIN`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, and a domain-specific `SESSION_COOKIE` from `APP_URL`; developers edit only `APP_URL` before installation and verify the generated values for production. The unique cookie name prevents a pre-install Laravel session cookie from shadowing the shared root/subdomain login session. Session config derives `.<APP_DOMAIN>` outside localhost. Session `starter.auth_version` must match `starter_client_logins.auth_version`; an old session may be adopted only when database version is initial `1`. Password change/reset increments auth version, rotates `remember_token`, keeps only the freshly confirmed actor session, and invalidates other sessions server-side.
- Remember-me follows dynamic config. `starter.lock` is server-side protection; browser runtime throttles activity and rechecks timeout on visibility/focus/sleep/back-forward restoration. Expired sessions go to lock screen through full-page navigation. Timeout is 60 seconds to 24 hours; unlock requires a rate-limited password and safe return URL.
- Server-validate upload MIME/type/size; temporary upload ceiling is 10 MB and profile/logo images are at most 4096×4096. Generate filenames, never trust user filenames/paths/URL fields, preserve legacy values without rendering arbitrary external paths, delete only verified owned-storage paths, and use `object-fit: contain` for previews.

## Security baseline

- Require CSRF for web mutations; apply simple global security headers (no CSP); send `Cache-Control: no-store` for authenticated/login/confirmation/lock responses. Trusted hosts derive from matching `APP_URL`/`APP_DOMAIN`; HSTS is production HTTPS only.
- Cross-subdomain redirects allow only HTTP(S), trusted root/subdomain hosts, no userinfo or unexpected port, and the `APP_URL` scheme. Rate-limit login per username/IP and aggregate IP without raw usernames in cache keys; unknown accounts still do password-hash work to reduce enumeration.
- Sensitive settings require recent `password.confirm`. Log security events per `audit-logging.md`. Production requires debug off, HTTPS, secure cookies, minimal storage permissions, and passing `starter:security-check`.
- Never log passwords/tokens/secrets/credentials/file contents. Bound and clear password/credential Livewire state after both success and failure. Treat input as untrusted: validate type/length/enum, authorize mutations, allowlist assignment, escape output, and bind queries; raw SQL/HTML only accepts proven safe internal values.
- Disabled API registers neither endpoints nor docs. Enabled API has explicit authentication/authorization/rate limits and production documentation is Superuser-only. The destructive installer is fresh-project only; existing applications must be rejected before mutation and routine setup/sync must never reset application data.
