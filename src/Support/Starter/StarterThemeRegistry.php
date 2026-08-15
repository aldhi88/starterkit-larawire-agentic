<?php

namespace Aldhi88\StarterKit\Support\Starter;

use PowerComponents\LivewirePowerGrid\Themes\Theme;
use RuntimeException;

class StarterThemeRegistry
{
    /** @var list<string> */
    private const REQUIRED_LAYOUTS = ['vertical', 'horizontal'];

    /** @var array<string, array<string, mixed>> */
    private static array $themes = [];

    /** @var array<string, true> */
    private static array $validatedDefinitions = [];

    /** @param array<string, mixed> $definition */
    public static function register(string $key, array $definition): void
    {
        self::assertKey($key);
        self::assertDefinition($key, $definition);
        self::$themes[$key] = $definition;
    }

    public static function flushRegistered(): void
    {
        self::$themes = [];
        self::$validatedDefinitions = [];
    }

    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        $merged = array_replace((array) config('starter.themes', []), self::$themes);
        $themes = [];

        foreach ($merged as $key => $definition) {
            if (! is_string($key) || ! is_array($definition)) {
                throw new RuntimeException('Every starter theme must use a valid string key and array definition.');
            }

            self::assertKey($key);
            self::assertDefinition($key, $definition);
            $themes[$key] = $definition;
        }

        return $themes;
    }

    /** @return array<string, mixed> */
    public static function get(string $key): array
    {
        self::assertKey($key);
        $definition = self::all()[$key] ?? null;

        if (! is_array($definition)) {
            throw new RuntimeException("Unsupported starter theme [{$key}].");
        }

        return $definition;
    }

    public static function path(string $theme, string $key, string $path = ''): string
    {
        $definition = self::get($theme);
        $root = $definition['root'] ?? StarterPaths::root();
        $relative = $definition[$key] ?? null;

        if (! is_string($root) || $root === '' || ! self::isAbsolutePath($root)) {
            throw new RuntimeException("Starter theme [{$theme}] does not define an absolute package root.");
        }

        if (! is_string($relative) || ! self::isSafeRelativePath($relative)) {
            throw new RuntimeException("Starter theme [{$theme}] does not define a safe [{$key}] path.");
        }

        if ($path !== '' && ! self::isSafeRelativePath($path)) {
            throw new RuntimeException("Starter theme [{$theme}] received an unsafe child path.");
        }

        return rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, trim($relative, '/\\'))
            .($path === '' ? '' : DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, ltrim($path, '/\\')));
    }

    private static function assertKey(string $key): void
    {
        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/', $key) !== 1) {
            throw new RuntimeException("Invalid starter theme key [{$key}].");
        }
    }

    /** @param array<string, mixed> $definition */
    private static function assertDefinition(string $key, array $definition): void
    {
        $signature = $key.':'.hash('sha256', serialize($definition));

        if (isset(self::$validatedDefinitions[$signature])) {
            return;
        }

        $label = $definition['label'] ?? null;
        $root = $definition['root'] ?? null;

        if (! is_string($label) || trim($label) === '') {
            throw new RuntimeException("Starter theme [{$key}] must define a label.");
        }

        if (! is_string($root) || ! self::isAbsolutePath($root) || ! is_dir($root)) {
            throw new RuntimeException("Starter theme [{$key}] must define an existing absolute package root.");
        }

        foreach (['views', 'assets', 'docs'] as $pathKey) {
            $relative = $definition[$pathKey] ?? null;

            if (! is_string($relative) || ! self::isSafeRelativePath($relative)) {
                throw new RuntimeException("Starter theme [{$key}] must define a safe [{$pathKey}] directory.");
            }

            if ($pathKey !== 'assets'
                && ! is_dir(rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
                throw new RuntimeException("Starter theme [{$key}] [{$pathKey}] directory was not found.");
            }
        }

        $powerGrid = $definition['powergrid'] ?? null;

        if (! is_string($powerGrid) || ! class_exists($powerGrid) || ! is_a($powerGrid, Theme::class, true)) {
            throw new RuntimeException("Starter theme [{$key}] must define a valid PowerGrid theme class.");
        }

        $layouts = $definition['layouts'] ?? null;

        if (! is_array($layouts)) {
            throw new RuntimeException("Starter theme [{$key}] must define its layouts.");
        }

        $viewsRoot = rtrim($root, '/\\').DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, (string) $definition['views']);

        foreach (self::REQUIRED_LAYOUTS as $layout) {
            $view = $layouts[$layout] ?? null;

            if (! is_string($view) || ! self::isSafeViewName($view)) {
                throw new RuntimeException("Starter theme [{$key}] must define a safe [{$layout}] layout view.");
            }

            $viewFile = $viewsRoot.DIRECTORY_SEPARATOR
                .str_replace('.', DIRECTORY_SEPARATOR, $view).'.blade.php';

            if (! is_file($viewFile)) {
                throw new RuntimeException("Starter theme [{$key}] [{$layout}] layout view was not found.");
            }
        }

        self::assertPackageContract($key, $root, $definition, $viewsRoot);
        self::$validatedDefinitions[$signature] = true;
    }

    /** @param array<string, mixed> $definition */
    private static function assertPackageContract(
        string $key,
        string $root,
        array $definition,
        string $viewsRoot,
    ): void {
        $contract = self::readJson(
            StarterPaths::path('docs/rules/theme-package-contract.json'),
            'starter theme package contract',
        );

        if (($contract['schema_version'] ?? null) !== 1
            || self::stringList($contract['required_layouts'] ?? null, 'required theme layouts') !== self::REQUIRED_LAYOUTS) {
            throw new RuntimeException('Starter theme package contract has an unsupported schema or layout contract.');
        }

        $docsRoot = rtrim($root, '/\\').DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, (string) $definition['docs']);

        foreach (self::stringList($contract['required_docs'] ?? null, 'required theme docs') as $file) {
            if (! self::isSafeRelativePath($file) || ! is_file($docsRoot.DIRECTORY_SEPARATOR.$file)) {
                throw new RuntimeException("Starter theme [{$key}] required docs file [{$file}] was not found.");
            }
        }

        foreach (self::stringList($contract['required_runtime_groups'] ?? null, 'required runtime groups') as $group) {
            if (! self::isSafeRelativePath($group)) {
                throw new RuntimeException("Starter theme contract contains an unsafe runtime group [{$group}].");
            }

            $directory = $viewsRoot.DIRECTORY_SEPARATOR.'starter'.DIRECTORY_SEPARATOR
                .str_replace('/', DIRECTORY_SEPARATOR, $group);

            if (! is_dir($directory)) {
                throw new RuntimeException("Starter theme [{$key}] runtime group [{$group}] was not found.");
            }
        }

        foreach (self::stringList($contract['required_runtime_files'] ?? null, 'required runtime files') as $file) {
            if (! self::isSafeRelativePath($file)
                || ! is_file($viewsRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                throw new RuntimeException("Starter theme [{$key}] runtime file [{$file}] was not found.");
            }
        }

        $sourcePaths = self::assertSourceIndex($key, $docsRoot);
        self::assertSourceMetadata($key, $docsRoot);
        self::assertComponentManifest($key, $root, $docsRoot, $contract, $sourcePaths);
        self::assertAssetManifest($key, $root, $docsRoot);
    }

    /** @return array<string, true> */
    private static function assertSourceIndex(string $key, string $docsRoot): array
    {
        $index = self::readJson($docsRoot.DIRECTORY_SEPARATOR.'source-index.json', "theme [{$key}] source index");
        self::assertManifestHeader($key, $index, 'source index');
        $files = $index['files'] ?? null;
        $htmlFiles = $index['html_files'] ?? null;
        $sourceRoot = $index['source_root'] ?? null;

        if (! is_array($files) || ! array_is_list($files) || $files === []
            || ! is_int($htmlFiles) || $htmlFiles !== count($files)
            || $sourceRoot !== 'theme-intake/'.$key) {
            throw new RuntimeException("Starter theme [{$key}] source index must account for every source HTML file.");
        }

        $paths = [];

        foreach ($files as $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException("Starter theme [{$key}] source index contains an invalid entry.");
            }

            $path = $entry['path'] ?? null;
            $hash = $entry['sha256'] ?? null;
            $signals = $entry['signals'] ?? null;
            $decision = $entry['decision'] ?? null;

            if (! is_string($path) || ! self::isSafeRelativePath($path) || isset($paths[$path])) {
                throw new RuntimeException("Starter theme [{$key}] source index contains an unsafe or duplicate path.");
            }

            if (! is_string($hash) || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
                throw new RuntimeException("Starter theme [{$key}] source index [{$path}] has an invalid SHA-256.");
            }

            self::stringList($signals, "theme [{$key}] source signals");

            if (! in_array($decision, ['curated', 'runtime-source', 'indexed-only'], true)) {
                throw new RuntimeException("Starter theme [{$key}] source index [{$path}] has an invalid decision.");
            }

            $paths[$path] = true;
        }

        return $paths;
    }

    private static function assertSourceMetadata(string $key, string $docsRoot): void
    {
        $source = self::readJson($docsRoot.DIRECTORY_SEPARATOR.'source.json', "theme [{$key}] source metadata");
        $url = $source['url'] ?? null;
        $host = is_string($url) ? strtolower((string) parse_url($url, PHP_URL_HOST)) : '';

        if (($source['schema_version'] ?? null) !== 1
            || ($source['theme'] ?? null) !== $key
            || ($source['provider'] ?? null) !== 'github'
            || ! is_string($url)
            || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ! in_array($host, ['github.com', 'raw.githubusercontent.com'], true)
            || preg_match('/^[a-f0-9]{64}$/', (string) ($source['archive_sha256'] ?? '')) !== 1
            || ! is_int($source['archive_max_bytes'] ?? null)
            || $source['archive_max_bytes'] < 1
            || $source['archive_max_bytes'] > 104857600
            || ($source['required_local_path'] ?? null) !== 'theme-intake/'.$key
            || ! is_string($source['license'] ?? null)
            || trim((string) $source['license']) === ''
            || ($source['distribution'] ?? null) !== 'public') {
            throw new RuntimeException("Starter theme [{$key}] source metadata is incomplete or invalid.");
        }
    }

    /** @param array<string, mixed> $contract */
    private static function assertComponentManifest(
        string $key,
        string $root,
        string $docsRoot,
        array $contract,
        array $sourcePaths,
    ): void {
        $manifest = self::readJson(
            $docsRoot.DIRECTORY_SEPARATOR.'component-manifest.json',
            "theme [{$key}] component manifest",
        );
        self::assertManifestHeader($key, $manifest, 'component manifest');
        $components = $manifest['components'] ?? null;

        if (! is_array($components) || ! array_is_list($components) || $components === []) {
            throw new RuntimeException("Starter theme [{$key}] component manifest is empty.");
        }

        $source = $manifest['source'] ?? null;

        if (! is_array($source)
            || ! is_string($source['name'] ?? null)
            || trim((string) $source['name']) === ''
            || ! is_string($source['license'] ?? null)
            || trim((string) $source['license']) === ''
            || ! in_array($source['distribution'] ?? null, ['public', 'private'], true)) {
            throw new RuntimeException("Starter theme [{$key}] component manifest must document its source, license, and distribution.");
        }

        $componentIds = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                throw new RuntimeException("Starter theme [{$key}] component manifest contains an invalid entry.");
            }

            $id = $component['id'] ?? null;

            if (! is_string($id) || preg_match('/^[a-z0-9][a-z0-9.-]*$/', $id) !== 1 || isset($componentIds[$id])) {
                throw new RuntimeException("Starter theme [{$key}] component manifest contains an invalid or duplicate ID.");
            }

            $references = self::stringList($component['references'] ?? null, "theme [{$key}] component references");
            $runtime = self::stringList($component['runtime'] ?? null, "theme [{$key}] component runtime");
            self::stringList($component['states'] ?? null, "theme [{$key}] component states");

            foreach ($references as $reference) {
                if (! self::isSafeRelativePath($reference) || ! isset($sourcePaths[$reference])) {
                    throw new RuntimeException("Starter theme [{$key}] component [{$id}] reference [{$reference}] is not indexed.");
                }
            }

            foreach ($runtime as $runtimeFile) {
                if (! self::isSafeRelativePath($runtimeFile)
                    || ! is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $runtimeFile))) {
                    throw new RuntimeException("Starter theme [{$key}] component [{$id}] runtime [{$runtimeFile}] was not found.");
                }
            }

            $componentIds[$id] = true;
        }

        foreach (self::stringList($contract['required_components'] ?? null, 'required theme components') as $required) {
            if (! isset($componentIds[$required])) {
                throw new RuntimeException("Starter theme [{$key}] component manifest is missing [{$required}].");
            }
        }
    }

    private static function assertAssetManifest(string $key, string $root, string $docsRoot): void
    {
        $manifest = self::readJson(
            $docsRoot.DIRECTORY_SEPARATOR.'asset-manifest.json',
            "theme [{$key}] asset manifest",
        );
        self::assertManifestHeader($key, $manifest, 'asset manifest');
        $files = $manifest['files'] ?? null;

        if (! is_array($files) || ! array_is_list($files) || $files === []) {
            throw new RuntimeException("Starter theme [{$key}] asset manifest is empty.");
        }

        $manifestPaths = [];

        foreach ($files as $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException("Starter theme [{$key}] asset manifest contains an invalid entry.");
            }

            $source = $entry['source'] ?? null;
            $target = $entry['target'] ?? null;
            $hash = $entry['sha256'] ?? null;
            $owners = $entry['referenced_by'] ?? null;

            if (! is_string($source) || ! self::isSafeRelativePath($source)
                || ! str_starts_with($source, 'runtime/')
                || ! is_string($target) || ! self::isSafeRelativePath($target)
                || isset($manifestPaths[$target])) {
                throw new RuntimeException("Starter theme [{$key}] asset manifest contains an unsafe or duplicate path.");
            }

            if (! is_string($hash) || preg_match('/^[a-f0-9]{64}$/', $hash) !== 1) {
                throw new RuntimeException("Starter theme [{$key}] asset [{$target}] has an invalid SHA-256.");
            }

            foreach (self::stringList($owners, "theme [{$key}] asset owners") as $owner) {
                if (! self::isSafeRelativePath($owner)
                    || ! is_file(rtrim($root, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $owner))) {
                    throw new RuntimeException("Starter theme [{$key}] asset [{$target}] owner [{$owner}] was not found.");
                }
            }

            $manifestPaths[$target] = true;
        }
    }

    /** @param array<string, mixed> $manifest */
    private static function assertManifestHeader(string $key, array $manifest, string $name): void
    {
        if (($manifest['schema_version'] ?? null) !== 1 || ($manifest['theme'] ?? null) !== $key) {
            throw new RuntimeException("Starter theme [{$key}] {$name} has an invalid schema or theme key.");
        }
    }

    /** @return array<string, mixed> */
    private static function readJson(string $path, string $name): array
    {
        $contents = @file_get_contents($path);
        $decoded = is_string($contents) ? json_decode($contents, true) : null;

        if (! is_array($decoded)) {
            throw new RuntimeException(ucfirst($name).' is missing or invalid JSON.');
        }

        return $decoded;
    }

    /** @return list<string> */
    private static function stringList(mixed $value, string $name): array
    {
        if (! is_array($value) || ! array_is_list($value) || $value === []) {
            throw new RuntimeException(ucfirst($name).' must be a non-empty string list.');
        }

        $strings = [];

        foreach ($value as $item) {
            if (! is_string($item) || trim($item) === '') {
                throw new RuntimeException(ucfirst($name).' must be a non-empty string list.');
            }

            $strings[] = $item;
        }

        return $strings;
    }

    private static function isSafeRelativePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);
        $trimmed = trim($normalized, '/');

        return $trimmed !== ''
            && ! self::isAbsolutePath($normalized)
            && preg_match('~(^|/)\.\.?(?:/|$)~', $trimmed) !== 1;
    }

    private static function isSafeViewName(string $view): bool
    {
        return $view !== ''
            && ! str_contains($view, '..')
            && preg_match('/^[a-z0-9][a-z0-9_.-]*$/i', $view) === 1;
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
