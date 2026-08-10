<?php

namespace Altekno\StarterKit\Console\Commands\Starter;

use Altekno\StarterKit\Models\Starter\Client;
use Altekno\StarterKit\Models\Starter\ClientLogin;
use Altekno\StarterKit\Models\Starter\ClientRole;
use Altekno\StarterKit\Rules\Starter\StarterPasswordRules;
use Altekno\StarterKit\Services\Starter\AuditLogService;
use Altekno\StarterKit\Services\Starter\StarterDeploymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SetupCommand extends Command
{
    protected $signature = 'starter:setup
        {--company= : Internal company/client name}
        {--email= : Superuser notification email}
        {--username= : Superuser username}
        {--reset-password : Replace the existing Superuser password}';

    protected $description = 'Prepare the first deployment, client, Superuser, database, registry, and assets';

    public function __construct(
        private readonly AuditLogService $auditLogs,
    ) {
        parent::__construct();
    }

    public function handle(StarterDeploymentService $deployment): int
    {
        if ($deployment->prepare($this, ensureApplicationKey: true) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $username = str($this->option('username') ?: config('starter.superuser.username'))->lower()->trim()->toString();
        $email = str($this->option('email') ?: config('starter.superuser.email'))->lower()->trim()->toString();
        $companyName = trim((string) ($this->option('company') ?: config('app.name')));
        $existingLogin = ClientLogin::query()->where('username', $username)->first();
        $password = null;

        if (! $existingLogin || $this->option('reset-password')) {
            $password = $this->password();
        }

        try {
            Validator::make([
                'username' => $username,
                'email' => $email,
            ], [
                'username' => ['required', 'alpha_dash:ascii', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
            ])->validate();
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        DB::transaction(function () use ($companyName, $email, $username, $password, $existingLogin): void {
            $client = Client::query()->firstOrCreate([], [
                'name' => $companyName,
                'email' => $email,
                'pic_name' => 'Developer',
                'account_status' => 'approved',
                'approved_at' => now(),
            ]);

            if ($client->account_status !== 'approved') {
                $client->forceFill(['account_status' => 'approved', 'approved_at' => now()])->save();
            }

            $role = ClientRole::query()->updateOrCreate([
                'code' => 'superuser',
            ], [
                'name' => 'Superuser',
                'desc' => 'Role bawaan developer dengan akses penuh ke seluruh module.',
                'is_system' => true,
            ]);

            $role->mods()->detach();

            $login = $existingLogin ?? new ClientLogin;
            $login->forceFill([
                'client_role_id' => $role->id,
                'name' => $login->name ?: 'Superuser',
                'username' => $username,
                'email' => $email,
                'email_verified_at' => $login->email_verified_at ?: now(),
                'status' => 'active',
                'must_change_password' => false,
            ]);

            if ($password !== null) {
                $login->password = $password;
                $login->password_changed_at = now();
                $login->remember_token = Str::random(60);
                $login->auth_version = $login->exists
                    ? max(1, (int) $login->auth_version) + 1
                    : 1;
            }

            $login->save();
        });

        if ($password !== null && $existingLogin instanceof ClientLogin) {
            $this->auditLogs->recordSecurityEvent(
                'auth.password_reset_by_setup',
                'Password Superuser direset melalui setup',
                target: $existingLogin->fresh(),
                metadata: ['reason' => 'setup_reset'],
            );
        }

        if ($this->call('starter:sync', [
            '--force' => true,
            '--prepared' => true,
        ]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->info("Private starter ready. Login username: {$username}");

        return self::SUCCESS;
    }

    private function password(): string
    {
        $password = (string) config('starter.superuser.password');

        if ($password === '' && app()->environment(['local', 'development', 'testing'])) {
            $password = 'superuser123';
        }

        if ($password === '' && $this->input->isInteractive()) {
            $password = (string) $this->secret('Superuser password');
        }

        if ($password === '') {
            $this->fail('STARTER_SUPERUSER_PASSWORD is required when creating or resetting Superuser outside local/development/testing.');
        }

        if (in_array($password, ['superuser123', 'rahasia123'], true)) {
            if (! app()->environment(['local', 'development', 'testing'])) {
                $this->fail('The development Superuser password cannot be used outside local/development/testing.');
            }

            $this->warn('Using the local-only default password. Configure STARTER_SUPERUSER_PASSWORD before deployment.');

            return $password;
        }

        try {
            Validator::make(['password' => $password], ['password' => StarterPasswordRules::rules()])->validate();
        } catch (ValidationException $exception) {
            $this->fail(collect($exception->errors())->flatten()->implode(' '));
        }

        return $password;
    }
}
