<?php

use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Symfony\Component\Process\Process;

it('validates direct browser observations from memory while retaining failure gates', function (string $scenario, string $error): void {
    $contract = json_decode(file_get_contents(StarterPaths::path('docs/rules/theme-package-contract.json')), true, flags: JSON_THROW_ON_ERROR);
    $ledger = [
        'schema_version' => 2,
        'theme' => 'example',
        'baseline_theme' => 'tabler',
        'run_id' => 'validator-regression',
        'paths' => array_fill_keys(['canonical_package', 'documentation_project', 'template_repository', 'new_theme_host', 'baseline_host'], '/synthetic'),
        'fingerprints' => [],
        'stages' => array_fill_keys($contract['deterministic_execution']['stage_order'], ['status' => 'pass', 'evidence' => ['Synthetic validator fixture']]),
        'page_matrix' => [],
        'automated_checks' => [['command' => 'synthetic-check', 'exit_code' => 0, 'status' => 'pass']],
        'completion' => ['status' => 'pass', 'known_defects' => [], 'skipped_checks' => []],
    ];
    foreach ($contract['deterministic_execution']['required_fingerprints'] as $key) {
        $ledger['fingerprints'][str_replace('-', '_', $key)] = ['sha256' => str_repeat('a', 64), 'current' => true];
    }
    foreach ($contract['browser_matrix']['required_page_groups'] as $group) {
        foreach ($contract['required_layouts'] as $layout) {
            foreach ($contract['browser_matrix']['required_viewports'] as $viewport) {
                foreach ($contract['required_interaction_states'] as $state) {
                    $ledger['page_matrix'][] = [
                        'page_id' => 'synthetic-page', 'page_group' => $group, 'route' => '/synthetic',
                        'layout' => $layout, 'viewport' => $viewport, 'state' => $state,
                        'browser_observation' => 'Synthetic direct inspection result; no screenshot file.',
                        'vendor_references' => ['synthetic-reference'], 'computed_metrics' => ['root_overflow' => 0],
                        'console_errors' => [], 'network_errors' => [], 'status' => 'pass',
                    ];
                }
            }
        }
    }

    match ($scenario) {
        'missing-observation' => $ledger['page_matrix'][0]['browser_observation'] = '',
        'missing-metrics' => $ledger['page_matrix'][0]['computed_metrics'] = [],
        'failed-command' => $ledger['automated_checks'][0]['exit_code'] = 1,
        'stale-input' => $ledger['fingerprints']['machine_contract']['current'] = false,
        'blocked-stage' => $ledger['stages']['4-browser']['status'] = 'blocked',
        default => null,
    };

    $process = new Process([PHP_BINARY, StarterPaths::path('tools/validate-theme-evidence.php'), 'example', '-']);
    $process->setInput(json_encode($ledger, JSON_THROW_ON_ERROR));
    $process->run();

    expect($process->getExitCode())->toBe($error === '' ? 0 : 1);
    if ($error !== '') {
        expect($process->getErrorOutput())->toContain($error);
    } else {
        expect($process->getOutput())->toContain('Theme evidence valid: example');
    }
})->with([
    ['valid', ''],
    ['missing-observation', 'browser_observation is required'],
    ['missing-metrics', 'computed_metrics is required'],
    ['failed-command', 'exit_code must be zero'],
    ['stale-input', 'machine_contract is stale'],
    ['blocked-stage', '4-browser must pass'],
]);
