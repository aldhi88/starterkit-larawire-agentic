# Starterkit Larawire Private

**English** · [Bahasa Indonesia](README.id.md)

Starterkit Larawire is built with and appreciates the work of
[Laravel](https://laravel.com/), [Livewire](https://livewire.laravel.com/),
[Livewire PowerGrid](https://livewire-powergrid.com/),
[Tabler](https://tabler.io/), [Laravel Lang](https://laravel-lang.com/),
[Scramble](https://scramble.dedoc.co/), and [Pest](https://pestphp.com/).

> Minimum Laravel version: **13.8**. Later Laravel versions may be used when
> their fresh application structure remains compatible with the installed
> Starterkit Larawire release.

## A. About

### What is Starterkit Larawire?

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
php artisan starter:make-app sales --name="Sales"
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

The public package currently provides the redistributable **Tabler** theme with
two layouts:

```dotenv
STARTER_THEME=tabler
STARTER_LAYOUT=vertical
```

`STARTER_LAYOUT` accepts `vertical` or `horizontal`. Theme registration is
modular: a future theme supplies its own views, assets, JavaScript handler,
PowerGrid adapter, component reference, and both layouts. One theme's visual
components are never forced onto another theme.

### AGENTS AI mode

Installation adds a managed block to the Laravel project's `AGENTS.md`. It
points AI coding agents to the architecture, security, testing, UI, theme, and
feature-development rules inside this Composer package. Application features
remain in the Laravel project; `vendor/aldhi88/starterkit-larawire` is read-only.

## B. Installation

### First local installation

Prepare:

1. a fresh Laravel project;
2. the correct database connection in `.env`; and
3. the local main domain in `APP_URL`.

From the Laravel project root:

```bash
composer require aldhi88/starterkit-larawire
```

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
php artisan starterkit:install --company="Company Name"
```

The installer clearly shows the destructive changes, confirms `APP_URL`, tests
and confirms the selected database, then checks the Laravel source. If source
code is no longer fresh, installation stops before changing files or data. If
fresh source already ran migrations, one additional confirmation is required.

The installation runs `migrate:fresh`; every table and row in the selected
database is deleted.

### Using a new empty database after installation

Do not run the fresh installer again. Update `.env`, then rebuild required
starter data with:

```bash
php artisan starter:setup --company="Company Name"
```

### Synchronize local metadata

Run this after changing App configuration, routes, modules, menus, migrations,
or the selected theme/layout:

```bash
php artisan starter:sync
```

Sync also publishes package, PowerGrid, and Livewire runtime assets. There is
no separate asset-publish command.

### Update the package locally

```bash
composer update aldhi88/starterkit-larawire
php artisan starter:sync
```

Review and commit both `composer.json` and `composer.lock` in the Laravel
application repository.

### First production deployment

```bash
git clone <laravel-repository> <project-folder>
cd <project-folder>
cp .env.example .env
# configure production APP_URL, database, and secrets
composer install --no-dev --optimize-autoloader
php artisan starter:setup --company="Company Name"
```

Point the main domain and App subdomains to `<project-folder>/public`.

### Routine production update

```bash
git pull --ff-only
composer install --no-dev --optimize-autoloader
php artisan starter:sync
```

Production uses the package version locked by the Laravel project's
`composer.lock`. It does not run the destructive installer.

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

This README is the concise GitHub guide. Detailed rules and architecture are
available in `docs/`. A separate official documentation website is planned;
the public URL will be added when it is ready.
