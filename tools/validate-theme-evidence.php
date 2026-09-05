#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$theme = $argv[1] ?? '';

if (! preg_match('/^[a-z0-9][a-z0-9-]*$/', $theme)) {
    fwrite(STDERR, "Usage: php tools/validate-theme-evidence.php <theme-key>\n");
    exit(2);
}

$ledgerPath = $root.'/theme-intake/'.$theme.'/.starter-theme-run/verification-evidence.json';
$contractPath = $root.'/docs/rules/theme-package-contract.json';

if (! is_file($ledgerPath)) {
    fwrite(STDERR, "Missing verification ledger: {$ledgerPath}\n");
    exit(1);
}

try {
    $ledger = json_decode((string) file_get_contents($ledgerPath), true, flags: JSON_THROW_ON_ERROR);
    $contract = json_decode((string) file_get_contents($contractPath), true, flags: JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, 'Invalid JSON: '.$exception->getMessage()."\n");
    exit(1);
}

$errors = [];
$require = static function (bool $condition, string $message) use (&$errors): void {
    if (! $condition) {
        $errors[] = $message;
    }
};
$nonEmptyString = static fn (mixed $value): bool => is_string($value) && trim($value) !== '';
$sha256 = static fn (mixed $value): bool => is_string($value) && preg_match('/^[a-f0-9]{64}$/', $value) === 1;

$require(($ledger['schema_version'] ?? null) === 1, 'Ledger schema_version must be 1.');
$require(($ledger['theme'] ?? null) === $theme, 'Ledger theme must match the requested theme key.');
$require($nonEmptyString($ledger['baseline_theme'] ?? null), 'baseline_theme is required.');
$require($nonEmptyString($ledger['run_id'] ?? null), 'run_id is required.');

foreach (['canonical_package', 'documentation_project', 'template_repository', 'new_theme_host', 'baseline_host'] as $key) {
    $require($nonEmptyString($ledger['paths'][$key] ?? null), "paths.{$key} is required.");
}

$fingerprintKeys = [
    'source_intake_inventory',
    'machine_contract',
    'baseline_structural_views',
    'target_runtime_blade_css_js',
    'asset_manifest',
    'runtime_archive',
];
foreach ($fingerprintKeys as $key) {
    $fingerprint = $ledger['fingerprints'][$key] ?? null;
    $require(is_array($fingerprint), "fingerprints.{$key} is required.");
    $require($sha256($fingerprint['sha256'] ?? null), "fingerprints.{$key}.sha256 must be a SHA-256 value.");
    $require(($fingerprint['current'] ?? null) === true, "fingerprints.{$key} is stale.");
}

$expectedStages = $contract['deterministic_execution']['stage_order'] ?? [];
foreach ($expectedStages as $stage) {
    $gate = $ledger['stages'][$stage] ?? null;
    $require(is_array($gate), "stages.{$stage} is required.");
    $require(($gate['status'] ?? null) === 'pass', "stages.{$stage} must pass.");
    $require(is_array($gate['evidence'] ?? null) && $gate['evidence'] !== [], "stages.{$stage} requires evidence.");
}

$rows = $ledger['page_matrix'] ?? null;
$require(is_array($rows) && $rows !== [], 'page_matrix must contain evidence rows.');
$coveredGroups = [];
$coveredLayouts = [];
$coveredViewports = [];
$coveredStates = [];

foreach (is_array($rows) ? $rows : [] as $index => $row) {
    $prefix = "page_matrix.{$index}";
    $status = $row['status'] ?? null;
    $require(in_array($status, ['pass', 'not_applicable'], true), "{$prefix}.status must be pass or not_applicable.");
    if ($status === 'not_applicable') {
        $require($nonEmptyString($row['not_applicable_reason'] ?? null), "{$prefix} requires a not_applicable_reason.");
    }

    foreach (['page_id', 'page_group', 'route', 'layout', 'viewport', 'state', 'baseline_screenshot', 'target_screenshot'] as $key) {
        $require($nonEmptyString($row[$key] ?? null), "{$prefix}.{$key} is required.");
    }

    $require(is_array($row['vendor_references'] ?? null) && $row['vendor_references'] !== [], "{$prefix}.vendor_references is required.");
    $require(is_array($row['computed_metrics'] ?? null) && $row['computed_metrics'] !== [], "{$prefix}.computed_metrics is required.");
    $require(($row['console_errors'] ?? null) === [], "{$prefix}.console_errors must be empty.");
    $require(($row['network_errors'] ?? null) === [], "{$prefix}.network_errors must be empty.");

    if ($nonEmptyString($row['page_group'] ?? null)) {
        $coveredGroups[] = $row['page_group'];
    }
    if ($nonEmptyString($row['layout'] ?? null)) {
        $coveredLayouts[] = $row['layout'];
    }
    if ($nonEmptyString($row['viewport'] ?? null)) {
        $coveredViewports[] = $row['viewport'];
    }
    if ($nonEmptyString($row['state'] ?? null)) {
        $coveredStates[] = $row['state'];
    }
}

foreach ($contract['browser_matrix']['required_page_groups'] ?? [] as $group) {
    $require(in_array($group, $coveredGroups, true), "Missing browser page group: {$group}.");
}
foreach ($contract['required_layouts'] ?? [] as $layout) {
    $require(in_array($layout, $coveredLayouts, true), "Missing browser layout: {$layout}.");
}
foreach ($contract['browser_matrix']['required_viewports'] ?? [] as $viewport) {
    $require(in_array($viewport, $coveredViewports, true), "Missing browser viewport: {$viewport}.");
}
foreach ($contract['required_interaction_states'] ?? [] as $state) {
    $require(in_array($state, $coveredStates, true), "Missing interaction state: {$state}.");
}

$checks = $ledger['automated_checks'] ?? null;
$require(is_array($checks) && $checks !== [], 'automated_checks must not be empty.');
foreach (is_array($checks) ? $checks : [] as $index => $check) {
    $require($nonEmptyString($check['command'] ?? null), "automated_checks.{$index}.command is required.");
    $require(($check['exit_code'] ?? null) === 0, "automated_checks.{$index}.exit_code must be zero.");
    $require(($check['status'] ?? null) === 'pass', "automated_checks.{$index}.status must pass.");
}

$require(($ledger['completion']['status'] ?? null) === 'pass', 'completion.status must pass.');
$require(($ledger['completion']['known_defects'] ?? null) === [], 'completion.known_defects must be empty.');
$require(($ledger['completion']['skipped_checks'] ?? null) === [], 'completion.skipped_checks must be empty.');

if ($errors !== []) {
    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }
    exit(1);
}

printf(
    "Theme evidence valid: %s (%d browser rows, %d automated checks)\n",
    $theme,
    count($rows),
    count($checks),
);
