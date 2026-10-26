<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Support\Cms\CmsAppDefinition;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
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
        $files = [
            config_path('apps/cms.php') => "<?php\n\n// Optional CMS managed by Starterkit Larawire.\nreturn ".var_export(CmsAppDefinition::configuration(), true).";\n",
            base_path('routes/apps/cms.php') => <<<'PHP'
<?php

// Optional CMS managed by Starterkit Larawire.
require \Aldhi88\StarterKit\Support\Starter\StarterPaths::path('routes/cms.php');
PHP.PHP_EOL,
        ];

        $migrations = $this->files->glob(StarterPaths::path('installer/templates/cms/*.php'));

        if ($migrations === []) {
            throw new RuntimeException('Template migration CMS tidak tersedia.');
        }

        foreach ($migrations as $migration) {
            $files[database_path('migrations/apps/cms/'.basename($migration))] = $this->files->get($migration);
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
