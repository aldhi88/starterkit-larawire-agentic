<?php

use Altekno\StarterKit\Installation\FreshLaravelChecker;
use Illuminate\Support\Facades\File;

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
