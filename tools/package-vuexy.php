<?php

declare(strict_types=1);

// Owner-only maintenance: no downloads, no publication, no vendor source in Composer.
$root = dirname(__DIR__);
$runtime = $root.'/theme-intake/vuexy/runtime';
$docs = $root.'/docs/template/vuexy';
$views = 'resources/themes/vuexy/views/starter/';
$repository = dirname($root).'/starterkit-larawire-agentic-template';
$writeJson = static fn (string $path, array $value) => file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
$contract = json_decode(file_get_contents($root.'/docs/rules/theme-package-contract.json'), true, flags: JSON_THROW_ON_ERROR);
$sourceIndex = json_decode(file_get_contents($docs.'/source-index.json'), true, flags: JSON_THROW_ON_ERROR);
$indexed = array_column($sourceIndex['files'], null, 'path');
$components = [];
foreach ($contract['required_components'] as $id) {
    [$reference, $view, $structure, $classes, $states, $reason] = match (true) {
        $id === 'layout.vertical' => ['html-starter/vertical-menu-template-no-customizer/index.html', 'templates/layouts/navigation/vertical', 'fixed aside plus detached navbar and constrained content', ['layout-wrapper', 'layout-container', 'layout-menu', 'layout-page', 'container-xxl'], ['normal', 'expanded', 'mobile-open', 'mobile-closed', 'responsive', 'keyboard'], 'Closest no-customizer starter shell with the semi-dark menu requested by the owner.'],
        $id === 'layout.horizontal' => ['html-starter/horizontal-menu-template-no-customizer/index.html', 'templates/layouts/navigation/horizontal', 'full-width navbar followed by horizontal menu container', ['layout-navbar-full', 'layout-horizontal', 'menu-horizontal', 'container-xxl'], ['normal', 'submenu-open', 'mobile-open', 'mobile-closed', 'responsive', 'keyboard'], 'Native no-customizer horizontal counterpart preserves the same application capabilities.'],
        $id === 'navigation.primary' => ['html-starter/vertical-menu-template-no-customizer/index.html', 'templates/layouts/menu-item', 'recursive list item with link or submenu trigger', ['menu-item', 'menu-link', 'menu-sub', 'menu-icon'], ['normal', 'active', 'expanded', 'collapsed', 'disabled', 'responsive', 'keyboard'], 'Vuexy menu hierarchy is the native representation for authorization-filtered App navigation.'],
        $id === 'navigation.account-menu' => ['html/vertical-menu-template-no-customizer/index.html', 'templates/layouts/account-menu', 'avatar trigger and end-aligned dropdown menu', ['dropdown-user', 'dropdown-menu-end', 'avatar', 'dropdown-item'], ['normal', 'open', 'closed', 'responsive', 'keyboard'], 'Full Vuexy dashboard provides the complete account identity and action hierarchy.'],
        $id === 'navigation.app-switcher' => ['html/vertical-menu-template-no-customizer/index.html', 'templates/layouts/app-switcher', 'navbar trigger and application list dropdown', ['nav-link', 'dropdown-menu-end', 'avatar', 'badge'], ['normal', 'open', 'closed', 'empty', 'responsive', 'keyboard'], 'Uses the same native navbar dropdown grammar while preserving the Starter App switching contract.'],
        str_starts_with($id, 'auth.') => ['html/vertical-menu-template-no-customizer/auth-login-cover.html', 'auth/'.substr($id, 5), 'split illustration and credentials panel', ['authentication-wrapper', 'authentication-cover', 'auth-cover-bg', 'input-group-merge'], ['normal', 'loading', 'validation-error', 'disabled', 'responsive', 'keyboard'], 'Vuexy cover authentication is the closest complete native reference for credential flows.'],
        $id === 'error.http' => ['html/vertical-menu-template-no-customizer/pages-misc-error.html', 'errors/layout', 'centered status copy, action, and illustration', ['misc-wrapper', 'misc-bg-wrapper', 'btn-primary'], ['normal', 'responsive', 'keyboard'], 'Native miscellaneous error composition keeps status and recovery action prominent.'],
        $id === 'interaction.accordion' => ['html/vertical-menu-template-no-customizer/ui-accordion.html', 'user-management/role-form', 'accordion item with heading trigger and collapsible module controls', ['accordion', 'accordion-item', 'accordion-button', 'accordion-collapse'], ['normal', 'expanded', 'collapsed', 'disabled', 'responsive', 'keyboard'], 'Native accordion groups module permissions without hiding the role identity form.'],
        $id === 'interaction.tabs' => ['html/vertical-menu-template-no-customizer/ui-tabs-pills.html', 'profile/edit-my-profile', 'full-width tab bar followed by one active content region', ['nav-align-top', 'nav-tabs-shadow', 'nav-tabs', 'nav-fill', 'nav-link'], ['normal', 'active', 'disabled', 'responsive', 'keyboard'], 'Vuexy line tabs make profile and settings sections read as navigation without competing with primary actions.'],
        $id === 'interaction.dropdown' => ['html/vertical-menu-template-no-customizer/ui-dropdowns.html', 'templates/layouts/account-menu', 'button trigger and positioned menu', ['dropdown', 'dropdown-toggle', 'dropdown-menu'], ['normal', 'open', 'closed', 'disabled', 'responsive', 'keyboard'], 'Native dropdown structure supports account, App, bulk, and row actions.'],
        $id === 'feedback.toast' => ['html/vertical-menu-template-no-customizer/ui-toasts.html', 'templates/components/toast', 'fixed live region containing dismissible toast surfaces', ['toast-container', 'toast', 'bg-label-success', 'bg-label-danger'], ['success', 'error', 'warning', 'info', 'dismissed', 'responsive', 'keyboard'], 'Vuexy toast colors preserve semantic Livewire feedback without browser dialogs.'],
        $id === 'feedback.alert' => ['html/vertical-menu-template-no-customizer/ui-alerts.html', 'templates/components/alert', 'semantic alert with icon and message', ['alert', 'alert-dismissible', 'alert-primary', 'alert-danger'], ['success', 'error', 'warning', 'info', 'dismissed', 'responsive', 'keyboard'], 'Native alert hierarchy makes persistent status feedback scannable.'],
        str_starts_with($id, 'feedback.') => ['html/vertical-menu-template-no-customizer/ui-modals.html', str_contains($id, 'destructive') ? 'templates/components/danger-password-modal' : 'templates/components/alert-modal', 'backdrop dialog with title, consequence copy, and ordered actions', ['modal', 'modal-dialog', 'modal-content', 'modal-footer'], ['normal', 'open', 'closed', 'loading', 'disabled', 'validation-error', 'responsive', 'keyboard'], 'Vuexy modal structure satisfies explicit confirmation and destructive-action context.'],
        $id === 'form.switch' => ['html/vertical-menu-template-no-customizer/forms-switches.html', 'settings/security-settings', 'labeled Bootstrap switch with supporting copy', ['form-check', 'form-switch', 'form-check-input'], ['checked', 'unchecked', 'disabled', 'focus', 'validation-error', 'responsive', 'keyboard'], 'Native switches represent binary security settings.'],
        $id === 'form.radio' || $id === 'form.checkbox' => ['html/vertical-menu-template-no-customizer/forms-basic-inputs.html', 'user-management/role-form', 'labeled selectable control inside module list', ['form-check', 'form-check-input', 'form-check-label'], ['checked', 'unchecked', 'disabled', 'focus', 'validation-error', 'responsive', 'keyboard'], 'Native Bootstrap controls preserve module selection semantics.'],
        $id === 'form.file' => ['html/vertical-menu-template-no-customizer/forms-basic-inputs.html', 'settings/client-profile', 'hidden file input controlled by a native upload button', ['btn-primary', 'form-control', 'avatar'], ['normal', 'selected', 'loading', 'validation-error', 'disabled', 'responsive', 'keyboard'], 'Vuexy account upload composition keeps preview and actions together.'],
        $id === 'form.select' => ['html/vertical-menu-template-no-customizer/forms-basic-inputs.html', 'user-management/user-form', 'label, native select, help, and validation message', ['form-label', 'form-select', 'is-invalid', 'invalid-feedback'], ['normal', 'focus', 'selected', 'validation-error', 'disabled', 'responsive', 'keyboard'], 'Native select avoids duplicate custom chevrons and retains browser accessibility.'],
        $id === 'form.textarea' => ['html/vertical-menu-template-no-customizer/forms-basic-inputs.html', 'user-management/role-form', 'label, textarea, help, and validation message', ['form-label', 'form-control', 'is-invalid', 'invalid-feedback'], ['normal', 'focus', 'filled', 'validation-error', 'disabled', 'responsive', 'keyboard'], 'Native textarea supports role descriptions with consistent validation.'],
        str_starts_with($id, 'form.') => ['html/vertical-menu-template-no-customizer/forms-basic-inputs.html', 'settings/client-profile', 'label, native control, help, and validation message', ['form-label', 'form-control', 'input-group', 'is-invalid'], ['normal', 'focus', 'filled', 'loading', 'validation-error', 'disabled', 'responsive', 'keyboard'], 'Vuexy native controls style the shared form structure without changing its fields or grouping.'],
        $id === 'table.pagination' => ['html/vertical-menu-template-no-customizer/ui-pagination-breadcrumbs.html', 'powergrid/pagination', 'compact previous, numbered, and next pagination list', ['pagination', 'page-item', 'page-link'], ['normal', 'active', 'first-page', 'last-page', 'disabled', 'responsive', 'keyboard'], 'Native pagination is adapted to Livewire page actions at both table edges.'],
        $id === 'table.per-page' || $id === 'table.record-count' => ['html/vertical-menu-template-no-customizer/tables-datatables-basic.html', 'powergrid/footer', 'three-part footer with per-page control, pagination, and record count', ['form-select-sm', 'pagination-sm', 'text-body-secondary'], ['normal', 'empty', 'single-page', 'many-pages', 'responsive', 'keyboard'], 'DataTables footer is the closest native density and information hierarchy.'],
        $id === 'table.toolbar' || $id === 'table.search' || $id === 'table.bulk-action' => ['html/vertical-menu-template-no-customizer/tables-datatables-basic.html', 'user-management/powergrid/roles-toolbar', 'responsive toolbar with search, filter, and contextual bulk actions', ['input-group', 'form-control', 'btn', 'dropdown-menu'], ['normal', 'empty', 'selected', 'loading', 'disabled', 'responsive', 'keyboard'], 'Native DataTables controls style the shared Starter toolbar composition without changing its placement or actions.'],
        str_starts_with($id, 'table.') => ['html/vertical-menu-template-no-customizer/tables-basic.html', 'powergrid/table-base', 'responsive inner frame containing intrinsic-width table', ['card-datatable', 'table-responsive', 'table', 'table-hover'], ['normal', 'empty', 'loading', 'error', 'sorted', 'filtered', 'selected', 'responsive', 'keyboard'], 'Vuexy table structure owns presentation; practical filter widths own column sizing.'],
        $id === 'identity.avatar' => ['html/vertical-menu-template-no-customizer/ui-badges.html', 'profile/edit-my-profile', 'image or initials within sized circular avatar', ['avatar', 'avatar-xl', 'avatar-initial'], ['image', 'initials', 'loading', 'responsive'], 'Vuexy avatar medallion is reused consistently for identity.'],
        $id === 'status.badge' => ['html/vertical-menu-template-no-customizer/ui-badges.html', 'profile/edit-my-profile', 'compact semantic label badge', ['badge', 'bg-label-success', 'bg-label-warning'], ['success', 'warning', 'danger', 'neutral', 'responsive'], 'Native label badges give status contrast without dominating the page.'],
        $id === 'action.button' => ['html/vertical-menu-template-no-customizer/ui-buttons.html', 'auth/login', 'icon-capable primary or secondary action button', ['btn', 'btn-primary', 'btn-label-secondary', 'btn-icon'], ['normal', 'hover', 'focus', 'loading', 'disabled', 'responsive', 'keyboard'], 'Vuexy buttons provide the action hierarchy used throughout the integration.'],
        $id === 'content.empty-state' => ['html/vertical-menu-template-no-customizer/tables-basic.html', 'user-management/roles', 'centered icon, explanation, and recovery action within owning surface', ['text-center', 'text-body-secondary', 'btn-primary'], ['empty', 'loading', 'error', 'responsive', 'keyboard'], 'Empty state remains inside the component that owns the absent data.'],
        $id === 'content.statistic' => ['html/vertical-menu-template/dashboards-analytics.html', 'settings/settings-index', 'semantic icon medallion, label, and value in a compact card', ['card', 'avatar', 'avatar-initial', 'bg-label-primary'], ['normal', 'zero', 'loading', 'responsive'], 'Owner screenshot and native analytics dashboard establish the requested visual density and contrast.'],
        default => ['html/vertical-menu-template-no-customizer/cards-basic.html', 'settings/settings-index', 'page heading or bordered card surface with native spacing', ['card', 'card-header', 'card-body', 'text-heading'], ['normal', 'empty', 'loading', 'error', 'responsive', 'keyboard'], 'Vuexy cards provide the native content hierarchy without decorating plain App placeholders.'],
    };
    if (! isset($indexed[$reference]) || ! is_file($root.'/'.$views.$view.'.blade.php')) {
        throw new RuntimeException('Missing component evidence: '.$id.' '.$reference);
    }
    $components[] = [
        'id' => $id,
        'references' => [$reference],
        'runtime' => [$views.$view.'.blade.php'],
        'native_structure' => $structure,
        'classes' => $classes,
        'states' => $states,
        'assets' => ['vendor/css/core.css', 'vendor/fonts/iconify-icons.css', 'css/vuexy.css', 'js/vuexy.js'],
        'selection_reason' => $reason,
    ];
}
$runtimeReferences = array_fill_keys(array_unique(array_merge(
    array_merge(...array_column($components, 'references')),
    [
        'html/front-pages-no-customizer/landing-page.html',
        'html/vertical-menu-template-no-customizer/pages-account-settings-account.html',
        'html/vertical-menu-template-no-customizer/pages-account-settings-security.html',
        'html/vertical-menu-template-no-customizer/app-access-roles.html',
        'html/vertical-menu-template-no-customizer/app-user-list.html',
    ],
)), true);
foreach ($sourceIndex['files'] as &$entry) {
    $entry['decision'] = isset($runtimeReferences[$entry['path']]) ? 'runtime-source' : 'indexed-only';
}
unset($entry);
$writeJson($docs.'/source-index.json', $sourceIndex);
$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runtime, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->isLink()) {
        throw new RuntimeException('Invalid runtime entry');
    }
    $target = substr($file->getPathname(), strlen($runtime) + 1);
    $owner = match (true) {
        str_starts_with($target, 'js/') => $views.'templates/layouts/scripts.blade.php',
        $target === 'img/branding/vuexy-mark.svg' => $views.'templates/layouts/brand.blade.php',
        $target === 'img/favicon/favicon.ico' => $views.'templates/layouts/head.blade.php',
        str_starts_with($target, 'img/front-pages/') => $views.'templates/landing.blade.php',
        str_contains($target, 'page-misc-error') => $views.'errors/layout.blade.php',
        str_starts_with($target, 'img/illustrations/') => $views.'templates/layouts/auth.blade.php',
        default => $views.'templates/layouts/head.blade.php',
    };
    $files[$target] = ['source' => 'runtime/'.$target, 'target' => $target, 'sha256' => hash_file('sha256', $file->getPathname()), 'referenced_by' => [$owner]];
}
ksort($files);
$temporary = tempnam(sys_get_temp_dir(), 'vuexy-runtime-');
$zip = new ZipArchive;
if ($zip->open($temporary, ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Cannot create archive');
}
foreach ($files as $entry) {
    $zip->addFile($runtime.'/'.$entry['target'], $entry['source']);
}
$zip->close();
if ($zip->open($temporary, ZipArchive::CHECKCONS) !== true) {
    throw new RuntimeException('Archive consistency failed');
}
foreach ($files as $entry) {
    if (hash('sha256', $zip->getFromName($entry['source'])) !== $entry['sha256']) {
        throw new RuntimeException('Archive entry hash mismatch');
    }
}
$zip->close();
$hash = hash_file('sha256', $temporary);
$writeJson($docs.'/component-manifest.json', ['schema_version' => 1, 'theme' => 'vuexy', 'source' => ['name' => 'Vuexy 3.0.0', 'license' => 'Commercial; owner-confirmed personal/internal use', 'distribution' => 'private'], 'components' => $components]);
$writeJson($docs.'/asset-manifest.json', ['schema_version' => 1, 'theme' => 'vuexy', 'files' => array_values($files)]);
$writeJson($docs.'/source.json', ['schema_version' => 1, 'theme' => 'vuexy', 'provider' => 'local', 'url' => null, 'archive_sha256' => $hash, 'archive_max_bytes' => max(1048576, filesize($temporary) + 1024), 'required_local_path' => 'theme-intake/vuexy', 'license' => 'Commercial; owner confirmed personal/internal use on 2026-09-05. Public redistribution is not authorized.', 'distribution' => 'private', 'html_files' => $sourceIndex['html_files']]);
rename($temporary, $repository.'/vuexy.zip');
file_put_contents($repository.'/VUEXY_SHA256SUMS', $hash."  vuexy.zip\n");
echo count($files).' runtime files verified; archive '.$hash."\n";
