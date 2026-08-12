<?php

namespace Aldhi88\StarterKit\Services\Starter;

use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Rules\Starter\StarterPasswordRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StarterIdentityService
{
    public const SUPERUSER_USERNAME = 'superuser';

    public function initialized(): bool
    {
        return Client::query()->exists() && ClientLogin::query()
            ->where('username', self::SUPERUSER_USERNAME)
            ->exists();
    }

    /** @throws ValidationException */
    public function create(
        string $company,
        string $email,
        string $password,
        bool $strongPassword = true,
    ): ClientLogin {
        $credentials = Validator::make([
            'company' => trim($company),
            'email' => str($email)->lower()->trim()->toString(),
            'password' => $password,
        ], [
            'company' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => $strongPassword
                ? StarterPasswordRules::rules()
                : StarterPasswordRules::localBootstrapRules(),
        ], [], [
            'company' => 'nama perusahaan/client',
            'email' => 'email Superuser',
            'password' => 'password Superuser',
        ])->validate();

        return DB::transaction(function () use ($credentials): ClientLogin {
            $client = Client::query()->firstOrCreate([], [
                'name' => $credentials['company'],
                'email' => $credentials['email'],
                'pic_name' => 'Developer',
                'account_status' => 'approved',
                'approved_at' => now(),
            ]);

            if ($client->getAttribute('account_status') !== 'approved') {
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

            return ClientLogin::query()->create([
                'client_role_id' => $role->id,
                'name' => 'Superuser',
                'username' => self::SUPERUSER_USERNAME,
                'email' => $credentials['email'],
                'email_verified_at' => now(),
                'password' => $credentials['password'],
                'status' => 'active',
                'must_change_password' => false,
                'password_changed_at' => now(),
                'remember_token' => Str::random(60),
                'auth_version' => 1,
            ]);
        });
    }
}
