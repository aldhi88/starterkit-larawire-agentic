<?php

use Aldhi88\StarterKit\Installation\FreshLaravelChecker;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;

beforeEach(function (): void {
    $this->fixture = sys_get_temp_dir().'/starterkit-checker-'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($this->fixture);

    $files = [
        'bootstrap/app.php' => <<<'PHP'
<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void { //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*'));
    })->create();
PHP,
        'bootstrap/providers.php' => <<<'PHP'
<?php
use App\Providers\AppServiceProvider;
return [AppServiceProvider::class];
PHP,
        'routes/web.php' => <<<'PHP'
<?php
use Illuminate\Support\Facades\Route;
Route::get('/', function () { return view('welcome'); });
PHP,
        'routes/console.php' => <<<'PHP'
<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
Artisan::command('inspire', function () { $this->comment(Inspiring::quote()); })->purpose('Display an inspiring quote');
PHP,
        'app/Http/Controllers/Controller.php' => <<<'PHP'
<?php
namespace App\Http\Controllers;
abstract class Controller {
    //
}
PHP,
        'app/Providers/AppServiceProvider.php' => <<<'PHP'
<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider {
    public function register(): void {
        //
    }
    public function boot(): void {
        //
    }
}
PHP,
        'app/Models/User.php' => <<<'PHP'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Foundation\Auth\User as Authenticatable;
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable {
    protected function casts(): array { return ['email_verified_at' => 'datetime', 'password' => 'hashed']; }
}
PHP,
        'resources/views/welcome.blade.php' => '<h1>Laravel</h1>',
        'database/migrations/0001_01_01_000000_create_users_table.php' => "<?php Schema::create('users', fn () => null); Schema::create('password_reset_tokens', fn () => null); Schema::create('sessions', fn () => null);",
        'database/migrations/0001_01_01_000001_create_cache_table.php' => "<?php Schema::create('cache', fn () => null); Schema::create('cache_locks', fn () => null);",
        'database/migrations/0001_01_01_000002_create_jobs_table.php' => "<?php Schema::create('jobs', fn () => null); Schema::create('job_batches', fn () => null); Schema::create('failed_jobs', fn () => null);",
    ];

    foreach ($files as $relative => $contents) {
        File::ensureDirectoryExists(dirname($this->fixture.'/'.$relative));
        File::put($this->fixture.'/'.$relative, $contents.PHP_EOL);
    }
});

afterEach(function (): void {
    File::deleteDirectory($this->fixture);
});

it('accepts a supported fresh Laravel source tree', function (): void {
    expect((new FreshLaravelChecker($this->fixture))->sourceFindings())->toBe([]);
});

it('accepts the current fresh Laravel JSON exception callback', function (): void {
    $path = $this->fixture.'/bootstrap/app.php';
    File::put($path, str_replace(
        "\$request->is('api/*')",
        "\$request->is('api/*') || \$request->expectsJson()",
        (string) File::get($path),
    ));

    expect((new FreshLaravelChecker($this->fixture, '13.25.0'))->sourceFindings())->toBe([]);
});

it('checks only the selected mysql database for existing tables', function (): void {
    $schema = Mockery::mock(Builder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('getTableListing')
            ->once()
            ->with('target_database')
            ->andReturn([]);
    });
    $connection = Mockery::mock(Connection::class, function (MockInterface $mock) use ($schema): void {
        $mock->shouldReceive('getPdo')->once()->andReturn(new PDO('sqlite::memory:'));
        $mock->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $mock->shouldReceive('getDatabaseName')->once()->andReturn('target_database');
        $mock->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
    });
    DB::shouldReceive('connection')->twice()->andReturn($connection);

    $report = (new FreshLaravelChecker($this->fixture))->inspectDatabase();

    expect($report->migrationsHaveRun)->toBeFalse()
        ->and($report->isFresh())->toBeTrue();
});

it('rejects Laravel versions below the documented minimum', function (): void {
    $findings = (new FreshLaravelChecker($this->fixture, '13.7.9'))->sourceFindings();

    expect($findings)->toContain(
        'Versi Laravel 13.7.9 belum memenuhi versi minimum 13.8.0.',
    );
});

it('rejects application source even when default files remain', function (): void {
    File::put($this->fixture.'/routes/web.php', <<<'PHP'
<?php
use Illuminate\Support\Facades\Route;
Route::get('/', function () { return view('welcome'); });
Route::get('/reports', fn () => 'custom');
PHP);
    File::ensureDirectoryExists($this->fixture.'/app/Models');
    File::put($this->fixture.'/app/Models/Invoice.php', '<?php class Invoice {}');

    $findings = (new FreshLaravelChecker($this->fixture))->sourceFindings();

    expect($findings)
        ->toContain('routes/web.php sudah memiliki route aplikasi.')
        ->and(implode(' ', $findings))->toContain('Models/Invoice.php');
});
