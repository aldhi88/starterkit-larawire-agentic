# Starterkit Larawire Agentic

[Bahasa Indonesia](README.md) · **English**

[Official website](https://starterkit-larawire.altekno.id/en) ·
[Documentation](https://starterkit-larawire.altekno.id/en/docs) ·
[Packagist](https://packagist.org/packages/aldhi88/starterkit-larawire-agentic)

Starterkit Larawire is built with and appreciates the work of
[Laravel](https://laravel.com/), [Livewire](https://livewire.laravel.com/),
[Livewire PowerGrid](https://livewire-powergrid.com/),
[Tabler](https://tabler.io/),
[Laravel Lang](https://laravel-lang.com/),
[Scramble](https://scramble.dedoc.co/), and [Pest](https://pestphp.com/).

> Minimum Laravel version: **13.8**. Each release's Composer constraint defines
> the versions already tested; support for a new Laravel major is added only
> after compatibility testing passes.

## A. About

### What is Starterkit Larawire Agentic?

Think of Starterkit Larawire as a larger version of Laravel's authentication
starter kits. It starts a new private company application with authentication
and the common foundations normally rebuilt in every internal project:

- login with username or email;
- Superuser, users, roles, and module authorization;
- company profile and security settings;
- activity logs;
- App and subdomain separation;
- code-first route, module, and menu synchronization;
- Livewire PowerGrid tables;
- optional API gateway and OpenAPI documentation;
- vertical and horizontal layouts; and
- an AGENTS AI development contract.

It is designed for one company/client installation, not a multi-tenant SaaS.

### How Apps and subdomains work

Suppose you are building an internal ERP with Sales, HR, Warehouse, and
Employee areas. They can be separated into Apps:

| App | Example address | Responsibility |
|---|---|---|
| Sales | `sales.company.test` | Leads, quotations, and sales orders |
| HR | `hr.company.test` | Recruitment and HR processes |
| Warehouse | `warehouse.company.test` | Stock and goods movement |
| Employee | `employee.company.test` | Employee self-service |

All Apps belong to the same Laravel project, database, company profile, and
login session. The subdomain separates the work area; it does not create a
separate Laravel installation.

In production, point the main domain and every App subdomain to the same
Laravel `public/` directory. A wildcard DNS entry such as `*.company.com` may
be used when supported by the hosting provider.

### Files developers normally edit

Each App owns its configuration and feature source:

```text
config/apps/<app>.php                    App, module, and menu definitions
routes/apps/<app>.php                   Web routes for the App subdomain
routes/apps/<app>.api.php               Optional App API routes
app/Livewire/Apps/<App>/                Livewire components
resources/views/apps/<app>/             App views
database/migrations/apps/<app>/         App database migrations
tests/Feature/Apps/<App>/               App tests
```

Create the initial structure with:

```bash
php artisan starter:app
```

Routes define accessible pages. `config/apps/<app>.php` connects those routes
to modules and menus. Menus are navigation only; module ownership is the actual
authorization boundary.

### Dynamic role access

Administrators assign modules to a role from the Settings page. A role may open
one or many Apps, and each allowed App has a selected landing page. Superuser
always has full access and is protected as a hidden system account.

After route, module, or menu definitions change, synchronize code metadata:

```bash
php artisan starter:sync
```

### Themes and layouts

Starterkit includes two themes with `vertical` and `horizontal` layouts:

| Theme | Source license | Notes |
|---|---|---|
| Tabler | MIT | Use under the Tabler license |
| DashCode | Commercial | License managed by the owner for internal team projects |

The selection is stored in the environment:

```dotenv
STARTER_THEME=tabler
STARTER_LAYOUT=vertical
```

The `starter:install` wizard asks for Tabler or DashCode and a layout. The
installer automatically downloads a pinned GitHub archive, verifies its size
and checksum, and publishes the minimal runtime to `public/assets/<theme>/`.
No manual template download or copy step is required. DashCode vendor sources
and demo pages are not distributed. Its runtime is intended for internal
projects covered by the repository owner's vendor license.
Archives are maintained separately in
[`starterkit-larawire-agentic-template`](https://github.com/aldhi88/starterkit-larawire-agentic-template).

Commit generated `public/assets/<theme>/` with the Laravel application.
Production reuses these files and never downloads a theme archive.

To add a theme, place its vendor HTML/assets distribution in `theme-intake/<theme-key>/`, then instruct the AI agent:

```text
Integrate theme <theme-key> from theme-intake/<theme-key> until it is ready for installer selection.
```

The agent must complete license review, full HTML indexing, asset filtering, every starter page/component, both layouts, PowerGrid, and verification. Vendor demo source/assets stay only in local intake or the owner archive; the core package keeps the indexes, recipes, implementation, and reference map.

### AGENTS AI mode

Installation adds a managed block to the Laravel project's `AGENTS.md`. It
points AI coding agents to the architecture, security, testing, UI, theme, and
feature-development rules inside this Composer package. Application features
remain in the Laravel project; `vendor/aldhi88/starterkit-larawire-agentic` is read-only.

## B. Installation

### First local installation

Prepare:

1. a fresh Laravel project;
2. the correct database connection in `.env`; and
3. the local main domain in `APP_URL`.

From the Laravel project root:

```bash
composer require aldhi88/starterkit-larawire-agentic
```

No manual template download or copy is needed. After theme selection, the
installer downloads Tabler assets from GitHub and verifies them before changing
the project's source or database.

Example `.env` values:

```dotenv
APP_URL=http://company.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=company_erp
DB_USERNAME=root
DB_PASSWORD=
```

Run the installer:

```bash
php artisan starter:install
```

The wizard asks for the theme (Tabler or DashCode), the vertical/horizontal
layout, then these five identities:

| Question | Spaces allowed? | Example input |
|---|---:|---|
| Company/client name | Yes | `Maju Bersama Ltd` |
| First App name | Yes | `Human Resources` |
| App domain/subdomain | No | `hr` |
| Superuser email | No | `admin@company.test` |
| Superuser password | No | entered through a hidden prompt |

For the App domain, enter only the subdomain part without dots or the main
domain. For example, `hr` becomes `hr.company.test` when `APP_URL` uses
`company.test`.

The installer clearly shows the destructive changes, confirms `APP_URL`, runs
the identity wizard, confirms the database connection, then checks the Laravel
source. If the confirmed database does not exist, the installer creates it
automatically when the database account has `CREATE DATABASE` permission. A
missing SQLite database file is also created automatically. If source code is no
longer fresh, installation stops before changing files or data. If fresh source
already ran migrations, one additional confirmation is required.

The Superuser username is always `superuser`. Its password is read only through
a hidden prompt, immediately stored as a hash, and never written to `.env`,
command arguments, logs, or temporary files.

Local passwords may be simple for practical development, but they are still
required and confirmed. When `starter:deploy` creates the first Superuser in an
empty production database, the password must contain at least 10 characters,
uppercase and lowercase letters, and a number.

The installer automatically runs internal security validation and the complete
application test suite before resetting the target database. No extra validation
command is required. Failed verification stops installation, restores
the changed project files, and leaves the target database untouched. A failure
after database reset restores either the empty database state or Laravel's
default fresh migration structure.

The installation runs `migrate:fresh`; every table and row in the selected
database is deleted.

### Reset and reinstall locally

Use this only when the entire old application should be discarded and rebuilt:

```bash
php artisan starter:reset
```

This command shows a hard warning and then runs the same installation wizard.
When confirmed, it deletes the entire database, App-boundary source, App assets,
starterkit logo/profile uploads, roles, users, settings, menus, and activity
logs. Do not use it for routine updates or in production.

### Using a new empty database after installation

Do not run the fresh installer again. Update `.env`, then rebuild required
starter data with:

```bash
php artisan starter:sync
```

When the new database is empty, sync runs migrations and securely prompts for
the new Superuser email and password.

### Synchronize local metadata

Run this after changing App configuration, routes, modules, menus, migrations,
or the selected theme/layout:

```bash
php artisan starter:sync
```

Sync also verifies the theme recipe and publishes package, PowerGrid, and
Livewire runtime assets. There is no separate asset-publish command. Commit the
generated `public/assets/<theme>/`. Sync can redownload the verified GitHub
archive when the local asset cache is unavailable.

### Update the package locally

```bash
composer update aldhi88/starterkit-larawire-agentic
php artisan starter:sync
```

Review and commit both `composer.json` and `composer.lock` in the Laravel
application repository.

### First production deployment

```bash
git clone <laravel-repository> <project-folder>
cd <project-folder>
cp .env.example .env
# configure production APP_URL, database, and secrets; preserve local STARTER_THEME and STARTER_LAYOUT
composer install --no-dev --optimize-autoloader
php artisan starter:deploy
```

Point the main domain and App subdomains to `<project-folder>/public`.
`STARTER_THEME` and `STARTER_LAYOUT` must match the local selection. Both values
remain in local and production `.env` files, avoiding an additional configuration
file. Production never downloads the GitHub archive or prepares
`theme-intake/`: it uses the selected theme runtime generated by `starter:sync` and
committed locally into `public/assets/<theme>/`.

### Routine production update

```bash
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan starter:deploy
```

Production uses the package version locked by the Laravel project's
`composer.lock`. `starter:deploy` is production-only. It validates the complete
environment—including explicit theme/layout values and committed theme runtime
assets—before mutation, then applies migrations, App registry synchronization,
and production caches. For the first empty production database,
it securely prompts for Superuser credentials. `starter:sync` and
`starter:reset` are rejected in production.

### Starterkit commands

Before installation, `starter:install` is available. After installation:

| Command | Purpose |
|---|---|
| `starter:reset` | Delete the old installation and rerun the wizard; local only |
| `starter:sync` | Synchronize developer source into the local database |
| `starter:app` | Create a new App/subdomain through a wizard |
| `starter:deploy` | Deploy to production with complete preflight validation |

## C. Optional API Gateway

The API gateway is disabled by default. Enable it in `.env`:

```dotenv
STARTER_API_ENABLED=true
```

Then run:

```bash
php artisan starter:sync
```

App API routes live in `routes/apps/<app>.api.php` and are served from
`api.<APP_DOMAIN>/<app>` without an additional `/api` prefix. Every endpoint
still requires explicit authentication, authorization, validation, and rate
limits. Production API documentation is restricted to Superuser.

## Documentation

This README is the concise GitHub guide. Installation, App architecture,
authorization, local workflow, Agentic AI, production deployment, API Gateway,
and troubleshooting guides are available on the
[official documentation website](https://starterkit-larawire.altekno.id/en/docs).
Technical rules for agents and contributors remain available in the package's
`docs/` directory.

## License

The source may be installed, modified, and deployed for internal applications
under [LICENSE](LICENSE). Selling, sublicensing, or republishing this package or
a competing starter is not permitted. Third-party components remain under
their respective licenses; see
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md).
