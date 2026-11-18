<?php

namespace Aldhi88\StarterKit\Http\Controllers\Starter\Auth;

use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Support\Starter\GoogleLogin;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleLoginController
{
    public function start(Request $request, AuthLoginService $auth): RedirectResponse
    {
        abort_unless(GoogleLogin::enabled(), 404);
        $auth->cancelTwoFactor();
        $request->session()->forget(['starter.google_link', 'starter.google_login_id']);
        $state = Str::random(64);
        $verifier = Str::random(80);
        $redirect = $request->query('redirect');
        $request->session()->put('starter.google_flow', [
            'state' => $state, 'verifier' => $verifier, 'expires' => now()->addMinutes(10)->timestamp,
            'redirect' => is_string($redirect) && StarterNavigation::isSafeRedirect($redirect) ? $redirect : null,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('starter.google.client_id'), 'redirect_uri' => StarterNavigation::authUrl('google/callback'),
            'response_type' => 'code', 'scope' => 'openid email', 'state' => $state, 'prompt' => 'select_account',
            'code_challenge_method' => 'S256', 'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        ]));
    }

    public function callback(Request $request, AuthLoginService $auth): RedirectResponse
    {
        $flow = $request->session()->pull('starter.google_flow');
        abort_unless(GoogleLogin::enabled(), 404);
        $failure = fn () => redirect(StarterNavigation::authLoginUrl())->with('google_notice', 'Login Google belum berhasil. Coba lagi atau gunakan password.');
        $state = $request->query('state');
        $code = $request->query('code');
        if (! is_array($flow) || $flow['expires'] < now()->timestamp || ! is_string($state)
            || ! hash_equals($flow['state'], $state) || ! is_string($code) || $code === '') {
            return $failure();
        }

        try {
            $tokens = Http::asForm()->timeout(10)->withoutRedirecting()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $flow['verifier'],
                'client_id' => config('starter.google.client_id'), 'client_secret' => config('starter.google.client_secret'),
                'redirect_uri' => StarterNavigation::authUrl('google/callback'),
            ]);
            $token = $tokens->json('access_token');
            if (! $tokens->successful() || ! is_string($token) || $token === '') {
                return $failure();
            }
            $user = Http::withToken($token)->timeout(10)->withoutRedirecting()->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (ConnectionException) {
            return $failure();
        }

        $subject = $user->json('sub');
        $email = $user->json('email');
        if (! $user->successful() || ! is_string($subject) || $subject === '' || $user->json('email_verified') !== true
            || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $failure();
        }

        $auth->cancelTwoFactor();
        $request->session()->forget('starter.google_login_id');
        $hash = hash('sha256', $subject);
        $login = ClientLogin::where('google_subject_hash', $hash)->first();
        $loginUrl = StarterNavigation::authLoginUrl($flow['redirect']);
        if (! $login) {
            // Ownership of the local account must be proven before its first association.
            $request->session()->put('starter.google_link', [
                'email' => strtolower($email), 'hash' => $hash, 'expires' => now()->addMinutes(10)->timestamp,
            ]);

            return redirect($loginUrl)->with('google_notice', 'Untuk menautkan akun Google, masuk dengan password akun LaraWire yang memakai alamat email yang sama.');
        }

        try {
            $target = $auth->attemptGoogle($login, $flow['redirect']);
        } catch (ValidationException) {
            return $failure();
        }

        return redirect($target ?? $loginUrl);
    }
}
