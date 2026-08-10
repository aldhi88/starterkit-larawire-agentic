# Modules, Routes, Menus, and Roles

## Access model

- A role may own many modules. A module authorizes every route whose name belongs to that module; menus are navigation only; each App landing selects the role's initial page. Superuser bypasses module access.
- If a read-only operator needs a materially different page from an administrator, create a dedicated module, route, and view (for example `employee_view`); do not stack role conditionals onto a full CRUD page.
- Global static capabilities are `can_manage_settings` (Settings, Roles, Users, Company Profile, Security) and `can_view_logs` (Activity Log). They are configured in the role form and stored in `starter_client_roles`; Superuser has both.

## Mandatory protection

- Every App route uses `auth:web`, `starter.active`, `starter.password-change`, and `starter.lock`; module pages also use `starter.authorize`.
- Every Livewire action validates and authorizes the manipulated data. Hidden menus/buttons are not security.
- Non-superusers cannot see, edit, move, or delete the Superuser role/user. Superuser password cannot be reset through User Management—even by Superuser—and can be changed only through My Profile. Enforce system-account restrictions in both UI and server/service code.

## Synchronization

`starter:sync` maps a module from the second route-name segment: `hr.employee.index` matches module `employee`; `hr.employee-data.index` does not. Always dry-run before a database-writing sync.
