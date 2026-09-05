<?php

declare(strict_types=1);

// Owner-only maintenance: rebuild a prepared runtime archive and its hashes.
$root = dirname(__DIR__);
$theme = strtolower((string) ($argv[1] ?? ''));

if (! preg_match('/^[a-z0-9-]+$/', $theme)) {
    throw new InvalidArgumentException('Usage: php tools/package-theme-runtime.php <theme-key>');
}

$runtime = $root.'/theme-intake/'.$theme.'/runtime';
$docs = $root.'/docs/template/'.$theme;
$repository = dirname($root).'/starterkit-larawire-agentic-template';
$manifestPath = $docs.'/asset-manifest.json';
$sourcePath = $docs.'/source.json';

foreach ([$runtime, $docs, $repository] as $directory) {
    if (! is_dir($directory)) {
        throw new RuntimeException('Required directory is missing: '.$directory);
    }
}

$previous = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
$previousByTarget = [];

foreach ($previous['files'] ?? [] as $entry) {
    $previousByTarget[(string) $entry['target']] = $entry;
}

$viewRoot = $root.'/resources/themes/'.$theme.'/views';
$directReferences = static function (string $target) use ($root, $theme, $viewRoot): array {
    $matches = [];
    $needle = 'assets/'.$theme.'/'.$target;
    $views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewRoot, FilesystemIterator::SKIP_DOTS));

    foreach ($views as $view) {
        if ($view->isFile() && str_ends_with($view->getFilename(), '.blade.php') && str_contains((string) file_get_contents($view->getPathname()), $needle)) {
            $matches[] = substr($view->getPathname(), strlen($root) + 1);
        }
    }

    sort($matches);

    return $matches;
};

$renamedReferences = static function (string $target) use ($directReferences, $previousByTarget, $theme): array {
    $direct = $directReferences($target);

    if ($direct !== []) {
        return $direct;
    }

    if ($target === 'css/'.$theme.'.css') {
        return array_values(array_unique(array_merge(
            $previousByTarget['css/starter-theme.css']['referenced_by'] ?? [],
            $previousByTarget['css/custom.css']['referenced_by'] ?? [],
            $previousByTarget[$target]['referenced_by'] ?? [],
        )));
    }

    if ($target === 'js/'.$theme.'.js') {
        return array_values(array_unique(array_merge(
            $previousByTarget['js/starter-theme.js']['referenced_by'] ?? [],
            $previousByTarget[$target]['referenced_by'] ?? [],
        )));
    }

    return $previousByTarget[$target]['referenced_by'] ?? [];
};

$files = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runtime, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (! $file->isFile() || $file->isLink()) {
        throw new RuntimeException('Runtime entries must be regular files.');
    }

    $target = substr($file->getPathname(), strlen($runtime) + 1);
    $references = $renamedReferences($target);

    if ($references === []) {
        throw new RuntimeException('Runtime file has no recorded owner: '.$target);
    }

    $files[$target] = [
        'source' => 'runtime/'.$target,
        'target' => $target,
        'sha256' => hash_file('sha256', $file->getPathname()),
        'referenced_by' => $references,
    ];
}

ksort($files);
$temporary = tempnam(sys_get_temp_dir(), $theme.'-runtime-');
$zip = new ZipArchive;

if ($zip->open($temporary, ZipArchive::OVERWRITE) !== true) {
    throw new RuntimeException('Cannot create runtime archive.');
}

foreach ($files as $entry) {
    $zip->addFile($runtime.'/'.$entry['target'], $entry['source']);
}

$zip->close();

if ($zip->open($temporary, ZipArchive::CHECKCONS) !== true) {
    throw new RuntimeException('Archive consistency check failed.');
}

foreach ($files as $entry) {
    if (hash('sha256', (string) $zip->getFromName($entry['source'])) !== $entry['sha256']) {
        throw new RuntimeException('Archive entry hash mismatch: '.$entry['source']);
    }
}

$zip->close();
$archiveHash = hash_file('sha256', $temporary);
$archivePath = $repository.'/'.$theme.'.zip';

if (! rename($temporary, $archivePath)) {
    throw new RuntimeException('Cannot replace runtime archive.');
}

$source = json_decode((string) file_get_contents($sourcePath), true, flags: JSON_THROW_ON_ERROR);
$source['archive_sha256'] = $archiveHash;
$source['archive_max_bytes'] = max((int) ($source['archive_max_bytes'] ?? 0), filesize($archivePath) + 1024);

file_put_contents($manifestPath, json_encode([
    'schema_version' => 1,
    'theme' => $theme,
    'files' => array_values($files),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
file_put_contents($sourcePath, json_encode($source, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

$checksumPath = $repository.'/'.($theme === 'vuexy' ? 'VUEXY_SHA256SUMS' : 'SHA256SUMS');
$checksumLines = is_file($checksumPath) ? file($checksumPath, FILE_IGNORE_NEW_LINES) : [];
$replacement = $archiveHash.'  '.$theme.'.zip';
$replaced = false;

foreach ($checksumLines as &$line) {
    if (str_ends_with($line, '  '.$theme.'.zip')) {
        $line = $replacement;
        $replaced = true;
    }
}
unset($line);

if (! $replaced) {
    $checksumLines[] = $replacement;
}

file_put_contents($checksumPath, implode("\n", array_filter($checksumLines))."\n");
echo count($files).' runtime files verified; archive '.$archiveHash."\n";
