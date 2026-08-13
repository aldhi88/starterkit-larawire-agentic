<?php

declare(strict_types=1);

$options = getopt('', ['theme:', 'source:', 'output:']);
$theme = $options['theme'] ?? null;
$source = $options['source'] ?? null;
$output = $options['output'] ?? null;

if (! is_string($theme) || preg_match('/^[a-z0-9][a-z0-9_-]*$/', $theme) !== 1) {
    fwrite(STDERR, "Use --theme=<safe-theme-key>.\n");
    exit(1);
}

if (! is_string($source) || ! is_dir($source)) {
    fwrite(STDERR, "Use --source=<existing-theme-intake-directory>.\n");
    exit(1);
}

if (! is_string($output) || trim($output) === '') {
    fwrite(STDERR, "Use --output=<source-index.json>.\n");
    exit(1);
}

$sourceRoot = realpath($source);

if (! is_string($sourceRoot)) {
    fwrite(STDERR, "Unable to resolve the source directory.\n");
    exit(1);
}

$signalPatterns = [
    'accordion' => '/accordion/i',
    'alert' => '/\balert(?:-|\b)/i',
    'auth' => '/sign[ -]?in|login|password|auth|lock[ -]?screen/i',
    'avatar' => '/\bavatar(?:-|\b)/i',
    'badge-status' => '/\bbadge(?:-|\b)|\bstatus(?:-|\b)/i',
    'button-action' => '/<button|\bbtn(?:-|\b)/i',
    'card-statistic' => '/\bcard(?:-|\b)|statistic|widget/i',
    'dropdown' => '/dropdown/i',
    'empty-error' => '/empty|error-[45]|not found/i',
    'feedback-modal' => '/modal|toast|notification/i',
    'form-control' => '/<form|<input|<select|<textarea|form-control/i',
    'layout-navigation' => '/navbar|sidebar|navigation|layout-/i',
    'pagination' => '/pagination|page-item/i',
    'table' => '/<table|datatable|advanced-table/i',
    'tabs' => '/tab-pane|nav-tabs/i',
    'timeline-activity' => '/timeline|activity|logs/i',
];

$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
);

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo) {
        continue;
    }

    if ($file->isLink()) {
        fwrite(STDERR, "Theme intake may not contain symbolic links: {$file->getPathname()}.\n");
        exit(1);
    }

    if (! $file->isFile() || strtolower($file->getExtension()) !== 'html') {
        continue;
    }

    $contents = file_get_contents($file->getPathname());

    if (! is_string($contents)) {
        fwrite(STDERR, "Unable to read {$file->getPathname()}.\n");
        exit(1);
    }

    $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($sourceRoot) + 1));
    $haystack = $relative."\n".$contents;
    $signals = [];

    foreach ($signalPatterns as $signal => $pattern) {
        if (preg_match($pattern, $haystack) === 1) {
            $signals[] = $signal;
        }
    }

    if ($signals === []) {
        $signals[] = 'uncategorized';
    }

    $files[] = [
        'path' => $relative,
        'sha256' => hash('sha256', $contents),
        'signals' => $signals,
        'decision' => 'indexed-only',
    ];
}

usort($files, static fn (array $left, array $right): int => $left['path'] <=> $right['path']);

if ($files === []) {
    fwrite(STDERR, "The source directory contains no HTML files.\n");
    exit(1);
}

$payload = [
    'schema_version' => 1,
    'theme' => $theme,
    'source_root' => 'theme-intake/'.$theme,
    'html_files' => count($files),
    'files' => $files,
];
$directory = dirname($output);

if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
    fwrite(STDERR, "Unable to create the output directory.\n");
    exit(1);
}

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

if (file_put_contents($output, $json) === false) {
    fwrite(STDERR, "Unable to write the source index.\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Indexed %d HTML files for theme [%s].\n", count($files), $theme));
