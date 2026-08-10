# Tabler Runtime Map

This map connects product capabilities to Tabler's original examples and the
Starterkit Larawire runtime views. Use it with `template.md`; do not substitute
another theme's markup or components.

| Capability | Tabler reference | Starter runtime |
|---|---|---|
| Vertical application shell | `layout-vertical.html` | `resources/themes/tabler/views/starter/templates/layouts/navigation/vertical.blade.php` |
| Horizontal sticky shell | `layout-navbar-overlap.html`, `layout-navbar-sticky.html` | `resources/themes/tabler/views/starter/templates/layouts/navigation/horizontal.blade.php` |
| Authentication | Sign-in examples | `resources/themes/tabler/views/starter/auth/` |
| Profile | Profile and form examples | `resources/themes/tabler/views/starter/profile/` |
| Settings | Cards, tabs, forms, switches | `resources/themes/tabler/views/starter/settings/` |
| Roles and users | Form and table examples | `resources/themes/tabler/views/starter/user-management/` |
| Activity log | Advanced table, card, modal | `resources/themes/tabler/views/starter/logs/` |
| PowerGrid | Advanced tables and Bootstrap adapter | `resources/themes/tabler/views/starter/powergrid/` |
| Dialogs and feedback | Modal, alert, toast examples | `resources/themes/tabler/views/starter/templates/components/` |

Both `vertical` and `horizontal` layouts are mandatory. Shared behavior may use
`data-starter-*`, Livewire events, and Alpine state, while visible markup and
styling remain owned by Tabler.
