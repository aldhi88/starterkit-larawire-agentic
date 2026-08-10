# Derived Project Architecture Context

This starter builds one internal company/client application, not SaaS. One installation has one `starter_clients` company; business tables, users, roles, and logs never use `client_id`. All rules in this directory are architecture boundaries; change core principles only for an explicitly approved project requirement.

- Subdomains separate application groups. A single login can access many Apps via role-owned modules; authorization is module-based, not per-CRUD-button permissions.
- Give materially different user flows/views their own module, route, and view. Every App route belongs to a module; menus navigate but never authorize.
- Superuser is a hidden full-access system account. Settings and Activity Log are global/static menus assignable through role capabilities.
- Public registration, self-service password reset, SaaS tenants/packages/payments are out of scope unless an approved project decision changes the architecture.
- PHP, Laravel, Livewire, and Pest/PHPUnit use the compatible versions locked by project dependencies. Use the active theme proven in source; never treat another registered theme as its visual base. Scramble documents the optional, disabled-by-default `api.<APP_DOMAIN>` gateway. Session/cache drivers are file and queue defaults to `sync` for shared hosting.
- `php artisan starter:setup` creates exactly one company, Superuser role, and Superuser user. `starter:sync` projects App metadata. Never seed production business samples.
