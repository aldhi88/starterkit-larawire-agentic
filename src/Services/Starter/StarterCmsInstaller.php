<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Support\Cms\CmsAppDefinition;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class StarterCmsInstaller
{
    public function __construct(private readonly Filesystem $files, private readonly Migrator $migrator) {}

    public static function enabled(): bool
    {
        return config('apps.cms._starter_cms') === 1 && is_file(base_path('routes/apps/cms.php'));
    }

    /** @return array<string, string> */
    public function plan(): array
    {
        if (is_file(base_path('routes/apps/cms.api.php')) && ! is_file(config_path('apps/cms.php'))) {
            throw new RuntimeException('Target CMS sudah digunakan oleh source lain: routes/apps/cms.api.php');
        }

        $files = [
            config_path('apps/cms.php') => "<?php\n\n// Optional CMS managed by Starterkit Larawire.\nreturn ".var_export(CmsAppDefinition::configuration(), true).";\n",
            base_path('routes/apps/cms.php') => <<<'PHP'
<?php

// Optional CMS managed by Starterkit Larawire.
require \Aldhi88\StarterKit\Support\Starter\StarterPaths::path('routes/cms.php');
PHP.PHP_EOL,
        ];

        foreach ($this->migrations() as $target => $source) {
            $files[$target] = $this->files->get($source);
        }

        foreach (array_keys($files) as $path) {
            if (! $this->files->exists($path)) {
                continue;
            }

            $source = $this->files->get($path);
            $managed = match ($path) {
                config_path('apps/cms.php') => str_contains($source, "'_starter_cms' => 1"),
                base_path('routes/apps/cms.php') => str_contains($source, "StarterPaths::path('routes/cms.php')"),
                default => $source === $files[$path],
            };

            if (! $managed) {
                throw new RuntimeException('Target CMS sudah digunakan oleh source lain: '.str_replace(base_path().'/', '', $path));
            }
        }

        return $files;
    }

    public function checkExistingTables(): void
    {
        // MySQL commits DDL individually. Detect occupied names before running the first CREATE.
        $creates = [
            '2026_10_25_000000_create_cms_tables' => [
                'cms_categories', 'cms_media', 'cms_contents', 'cms_galleries',
                'cms_gallery_media', 'cms_menu_categories', 'cms_menus', 'cms_comments',
            ],
            '2026_10_25_000001_add_cms_rich_content' => ['cms_content_media'],
            '2026_10_25_000002_add_post_category_hierarchy' => ['cms_content_categories'],
        ];
        $ran = $this->migrator->repositoryExists() ? $this->migrator->getRepository()->getRan() : [];
        foreach (array_diff_key($creates, array_flip($ran)) as $migration => $tables) {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    throw new RuntimeException("Tabel [{$table}] sudah ada, tetapi migration [{$migration}] belum tercatat. Periksa tabel tersebut sebelum mengaktifkan CMS.");
                }
            }
        }
    }

    /** @return array<string, string> Host migration paths mapped to their package templates. */
    public function migrations(): array
    {
        $migrations = [];
        foreach ($this->files->glob(StarterPaths::path('installer/templates/cms/*.php')) as $source) {
            $migrations[database_path('migrations/apps/cms/'.basename($source))] = $source;
        }

        if ($migrations === []) {
            throw new RuntimeException('Template migration CMS tidak tersedia.');
        }

        return $migrations;
    }

    /** @return list<string> */
    public function prepare(): array
    {
        $plan = $this->plan();
        $created = [];

        try {
            foreach ($plan as $path => $contents) {
                if ($this->files->exists($path)) {
                    continue;
                }

                $this->files->ensureDirectoryExists(dirname($path));

                if ($this->files->put($path, $contents) === false) {
                    throw new RuntimeException('Tidak dapat menulis source CMS: '.basename($path));
                }

                $created[] = $path;
            }
        } catch (Throwable $exception) {
            $this->files->delete($created);

            throw $exception;
        }

        config(['apps.cms' => require config_path('apps/cms.php')]);
        $this->migrator->path(database_path('migrations/apps/cms'));

        return $created;
    }
}
