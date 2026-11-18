<?php

use Aldhi88\StarterKit\Contracts\Starter\ClientInterface;
use Aldhi88\StarterKit\Contracts\Starter\ClientLoginInterface;
use Aldhi88\StarterKit\Http\Controllers\Starter\Auth\GoogleLoginController;
use Aldhi88\StarterKit\Models\Starter\Client;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Models\Starter\ClientRole;
use Aldhi88\StarterKit\Repositories\Starter\ClientLoginRepository;
use Aldhi88\StarterKit\Repositories\Starter\ClientRepository;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Services\Starter\LoginOtpMailService;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Support\Starter\StarterPaths;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('g', 32)), 'app.domain' => 'localhost',
        'starter.google' => ['enabled' => true, 'client_id' => 'client', 'client_secret' => 'secret'],
        'auth.providers.users.model' => ClientLogin::class]);
    foreach (['2026_07_27_185232_create_starter_identity_tables.php', '2026_11_18_000000_add_google_subject_to_starter_client_logins.php'] as $file) {
        (require StarterPaths::path('database/migrations/starter/'.$file))->up();
    }
    app()->bind(ClientInterface::class, ClientRepository::class);
    app()->bind(ClientLoginInterface::class, ClientLoginRepository::class);
    $this->mock(LoginOtpMailService::class);
    $configs = Mockery::mock(StarterConfigService::class);
    $configs->allows('integer')->andReturn(5);
    app()->instance(StarterConfigService::class, $configs);
    $redirects = Mockery::mock(NavigationAuthorizedRedirectService::class);
    $redirects->allows('forLogin')->andReturn('/dashboard');
    app()->instance(NavigationAuthorizedRedirectService::class, $redirects);
    Client::create(['name' => 'Campus', 'account_status' => 'approved']);
    $role = ClientRole::create(['code' => 'staff', 'name' => 'Staff']);
    $this->login = ClientLogin::create(['client_role_id' => $role->id, 'username' => 'staff', 'name' => 'Staff',
        'email' => 'staff@example.test', 'password' => 'local-password']);
    Route::middleware('web')->group(function () {
        Route::get('/auth/google', [GoogleLoginController::class, 'start']);
        Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback']);
        Route::post('/password-test', fn (AuthLoginService $auth) => redirect($auth->attempt('staff', 'local-password')));
    });
    Http::preventStrayRequests();
});

function googleCallbackUrl($test): string
{
    $response = $test->get('/auth/google')->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    expect($query['code_challenge_method'])->toBe('S256');

    return '/auth/google/callback?'.http_build_query(['code' => 'provider-code', 'state' => $query['state']]);
}

function fakeGoogleIdentity(bool $verified = true): void
{
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-token']),
        'openidconnect.googleapis.com/v1/userinfo' => Http::response(['sub' => 'subject', 'email' => 'staff@example.test', 'email_verified' => $verified]),
    ]);
}

it('links after local authentication and subsequently logs in without marking the password confirmed', function () {
    fakeGoogleIdentity();
    $this->get(googleCallbackUrl($this))->assertRedirect();
    $this->assertGuest();
    expect($this->login->fresh()->google_subject_hash)->toBeNull();
    $this->post('/password-test')->assertRedirect('/dashboard');
    expect($this->login->fresh()->google_subject_hash)->toBe(hash('sha256', 'subject'));
    Auth::logout();
    $this->get(googleCallbackUrl($this))->assertRedirect('/dashboard')->assertSessionMissing('auth.password_confirmed_at');
    $this->assertAuthenticatedAs($this->login);
});

it('rejects disabled callbacks and invalid or consumed state before contacting Google', function () {
    Http::fake();
    $url = googleCallbackUrl($this);
    $this->get('/auth/google/callback?state=wrong&code=x')->assertRedirect();
    $this->get($url)->assertRedirect();
    config(['starter.google.enabled' => false]);
    $this->get('/auth/google')->assertNotFound();
    $this->get($url)->assertNotFound();
    Http::assertNothingSent();
    $this->assertGuest();
});

it('rejects unverified emails and refuses a linked inactive account', function (bool $verified) {
    fakeGoogleIdentity($verified);
    $this->login->forceFill(['google_subject_hash' => hash('sha256', 'subject'), 'status' => $verified ? 'inactive' : 'active'])->save();
    $this->get(googleCallbackUrl($this))->assertRedirect()->assertSessionMissing('starter.google_link');
    $this->assertGuest();
})->with([true, false]);

it('requires the enrolled authenticator after a linked Google login', function () {
    fakeGoogleIdentity();
    config(['starter.auth.login_two_factor_enabled' => true]);
    (require StarterPaths::path('database/migrations/starter/2026_09_23_000000_add_two_factor_authentication_to_starter_client_logins.php'))->up();
    $this->login->forceFill(['google_subject_hash' => hash('sha256', 'subject'), 'two_factor_secret' => 'placeholder', 'two_factor_confirmed_at' => now()])->save();
    $this->get(googleCallbackUrl($this))->assertRedirect()->assertSessionHas('starter.login_two_factor.login_id', $this->login->id);
    $this->assertGuest();
});
