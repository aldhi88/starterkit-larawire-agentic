<?php

use Aldhi88\StarterKit\Console\Commands\Starter\ViewsReplaceCommand;
use Aldhi88\StarterKit\Services\Starter\StarterViewOverrideService;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;

beforeEach(function (): void {
    $this->viewOverrideFixture = sys_get_temp_dir().'/starter-view-override-'.bin2hex(random_bytes(8));
    mkdir($this->viewOverrideFixture.'/source/templates/layouts', 0777, true);
    mkdir($this->viewOverrideFixture.'/source/auth', 0777, true);
    file_put_contents($this->viewOverrideFixture.'/source/templates/layouts/auth.blade.php', '<main>package layout v1</main>');
    file_put_contents($this->viewOverrideFixture.'/source/auth/login.blade.php', '<form>package login v1</form>');
    config()->set('starter.theme', 'tabler');

    $root = $this->viewOverrideFixture;
    $this->viewOverrides = new class(new Filesystem, $root) extends StarterViewOverrideService
    {
        public function __construct(Filesystem $files, private readonly string $fixtureRoot)
        {
            parent::__construct($files);
        }

        public function sourceRoot(?string $theme = null): string
        {
            return $this->fixtureRoot.'/source';
        }

        public function overrideThemeRoot(?string $theme = null): string
        {
            return $this->fixtureRoot.'/project';
        }
    };
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->viewOverrideFixture);
});

it('publishes theme views and preserves project customization by default', function (): void {
    $published = $this->viewOverrides->publish();
    $projectLogin = $this->viewOverrideFixture.'/project/starter/auth/login.blade.php';

    expect($published)->toMatchArray(['created' => 2, 'replaced' => 0, 'preserved' => 0, 'backup' => null])
        ->and(file_get_contents($projectLogin))->toBe('<form>package login v1</form>')
        ->and($this->viewOverrides->statuses()['auth/login.blade.php']['status'])->toBe('current');

    file_put_contents($projectLogin, '<form>project customization</form>');
    file_put_contents($this->viewOverrideFixture.'/source/auth/login.blade.php', '<form>package login v2</form>');
    $republished = $this->viewOverrides->publish();

    expect($republished['preserved'])->toBe(1)
        ->and(file_get_contents($projectLogin))->toBe('<form>project customization</form>')
        ->and($this->viewOverrides->statuses()['auth/login.blade.php']['status'])->toBe('conflict');
});

it('previews replacement and backs up custom files before restoring package defaults', function (): void {
    $this->viewOverrides->publish();
    $projectLogin = $this->viewOverrideFixture.'/project/starter/auth/login.blade.php';
    file_put_contents($projectLogin, '<form>project customization</form>');
    file_put_contents($this->viewOverrideFixture.'/source/auth/login.blade.php', '<form>package login v2</form>');

    expect($this->viewOverrides->replacementPlan(['auth/login.blade.php']))
        ->toBe(['auth/login.blade.php' => 'replace']);

    $result = $this->viewOverrides->replace(['auth/login.blade.php']);

    expect($result['replaced'])->toBe(1)
        ->and($result['backup'])->not->toBeNull()
        ->and(is_file($result['backup'].'/starter/auth/login.blade.php'))->toBeTrue()
        ->and(file_get_contents($result['backup'].'/starter/auth/login.blade.php'))->toBe('<form>project customization</form>')
        ->and(file_get_contents($projectLogin))->toBe('<form>package login v2</form>')
        ->and($this->viewOverrides->statuses()['auth/login.blade.php']['status'])->toBe('current');
});

it('rejects paths outside the registered package view set', function (): void {
    $this->viewOverrides->publish(['../../.env']);
})->throws(RuntimeException::class, 'View override tidak dikenal');

it('rejects symlinks before replacing an override target', function (): void {
    $this->viewOverrides->publish(['auth/login.blade.php']);
    $projectLogin = $this->viewOverrideFixture.'/project/starter/auth/login.blade.php';
    $outside = $this->viewOverrideFixture.'/outside.blade.php';
    file_put_contents($outside, '<form>outside</form>');
    unlink($projectLogin);
    symlink($outside, $projectLogin);

    try {
        $this->viewOverrides->publish(['auth/login.blade.php'], true);
    } finally {
        expect(file_get_contents($outside))->toBe('<form>outside</form>');
    }
})->throws(RuntimeException::class, 'Symlink tidak diizinkan');

it('cancels the interactive replacement wizard without changing overrides', function (): void {
    $this->viewOverrides->publish();
    $manifest = $this->viewOverrideFixture.'/project/.starter-views.json';
    $before = hash_file('sha256', $manifest);
    $this->app->instance(StarterViewOverrideService::class, $this->viewOverrides);

    $command = $this->app->make(ViewsReplaceCommand::class);
    $command->setLaravel($this->app);
    $tester = new CommandTester($command);
    $tester->setInputs(['cancel']);

    expect($tester->execute([]))->toBe(0)
        ->and($tester->getDisplay())->toContain('Penggantian dibatalkan')
        ->and(hash_file('sha256', $manifest))->toBe($before);
});
