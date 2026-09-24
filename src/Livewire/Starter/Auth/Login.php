<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Auth;

use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
use Aldhi88\StarterKit\Services\Starter\LoginHumanChallengeService;
use Aldhi88\StarterKit\Services\Starter\StarterConfigService;
use Aldhi88\StarterKit\Support\Starter\StarterTheme;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Login')]
class Login extends Component
{
    private StarterConfigService $configs;

    /**
     * @var array{identifier: string, password: string, remember: bool, human_challenge: string}
     */
    public array $form = [
        'identifier' => '',
        'password' => '',
        'remember' => false,
        'human_challenge' => '',
    ];

    public string $redirect = '';

    /** @var array{code: string} */
    public array $otpForm = [
        'code' => '',
    ];

    public bool $otpRequired = false;

    public string $maskedOtpEmail = '';

    public string $otpStatus = '';

    /** @var array{code: string} */
    public array $authenticatorForm = [
        'code' => '',
    ];

    public bool $authenticatorRequired = false;

    public bool $authenticatorRecoveryMode = false;

    public string $humanChallengeImage = '';

    public function boot(StarterConfigService $configs): void
    {
        $this->configs = $configs;
    }

    public function mount(
        AuthLoginService $loginService,
        LoginHumanChallengeService $humanChallenge,
    ): void {
        $this->redirect = request()->query('redirect', '');
        $this->syncPendingFactors($loginService);

        if (! $this->otpRequired && ! $this->authenticatorRequired) {
            $this->humanChallengeImage = $humanChallenge->issue();
        }
    }

    public function authenticate(
        AuthLoginService $loginService,
        LoginHumanChallengeService $humanChallenge,
    ) {
        try {
            $rules = [
                'form.identifier' => ['required', 'string', 'max:255'],
                'form.password' => ['required', 'string', 'max:1024'],
            ];

            if ($humanChallenge->enabled()) {
                $rules['form.human_challenge'] = ['required', 'digits:5'];
            }

            $this->validate($rules, [], [
                'form.identifier' => 'username atau email',
                'form.password' => 'password',
                'form.human_challenge' => 'angka keamanan',
            ]);

            $humanChallenge->verify($this->form['human_challenge']);

            $target = $loginService->attempt(
                username: $this->form['identifier'],
                password: $this->form['password'],
                remember: $this->form['remember'],
                redirect: $this->redirect,
            );
        } catch (ValidationException $exception) {
            $this->form['human_challenge'] = '';
            $this->humanChallengeImage = $humanChallenge->issue();

            throw $exception;
        }

        $this->form['password'] = '';
        $this->form['human_challenge'] = '';

        if ($target === null) {
            $this->syncPendingFactors($loginService);

            if ($this->otpRequired) {
                $this->otpStatus = 'Kode OTP telah dikirim. Periksa inbox email Anda.';
            }

            return null;
        }

        return $this->redirect($target);
    }

    public function verifyOtp(AuthLoginService $loginService)
    {
        try {
            $this->validate([
                'otpForm.code' => ['required', 'digits:6'],
            ], [], [
                'otpForm.code' => 'kode OTP',
            ]);

            $target = $loginService->verifyOtp($this->otpForm['code']);
        } catch (ValidationException $exception) {
            $this->otpForm['code'] = '';
            $this->syncPendingFactors($loginService);

            throw $exception;
        }

        $this->otpForm['code'] = '';

        if ($target === null) {
            $this->syncPendingFactors($loginService);

            return null;
        }

        return $this->redirect($target);
    }

    public function verifyAuthenticator(AuthLoginService $loginService)
    {
        try {
            $this->validate([
                'authenticatorForm.code' => $this->authenticatorRecoveryMode
                    ? ['required', 'string', 'regex:/^[A-Za-z0-9]{4}-?[A-Za-z0-9]{4}$/']
                    : ['required', 'digits:6'],
            ], [], [
                'authenticatorForm.code' => $this->authenticatorRecoveryMode
                    ? 'kode pemulihan'
                    : 'kode authenticator',
            ]);

            $target = $loginService->verifyTwoFactor($this->authenticatorForm['code']);
        } catch (ValidationException $exception) {
            $this->authenticatorForm['code'] = '';
            $this->syncPendingFactors($loginService);

            throw $exception;
        }

        $this->authenticatorForm['code'] = '';

        return $this->redirect($target);
    }

    public function resendOtp(AuthLoginService $loginService): void
    {
        $this->resetErrorBag('otpForm.code');
        $loginService->resendOtp();
        $this->syncPendingFactors($loginService);
        $this->otpForm['code'] = '';
        $this->otpStatus = 'Kode OTP baru telah dikirim.';
    }

    public function cancelOtp(
        AuthLoginService $loginService,
        LoginHumanChallengeService $humanChallenge,
    ): void {
        $loginService->cancelOtp();
        $this->reset(['otpForm', 'otpRequired', 'maskedOtpEmail', 'otpStatus']);
        $this->humanChallengeImage = $humanChallenge->issue();
        $this->resetErrorBag();
    }

    public function cancelTwoFactor(
        AuthLoginService $loginService,
        LoginHumanChallengeService $humanChallenge,
    ): void {
        $loginService->cancelTwoFactor();
        $this->reset([
            'otpForm',
            'otpRequired',
            'maskedOtpEmail',
            'otpStatus',
            'authenticatorForm',
            'authenticatorRequired',
            'authenticatorRecoveryMode',
        ]);
        $this->humanChallengeImage = $humanChallenge->issue();
        $this->resetErrorBag();
    }

    public function toggleAuthenticatorRecoveryMode(): void
    {
        $this->authenticatorRecoveryMode = ! $this->authenticatorRecoveryMode;
        $this->authenticatorForm['code'] = '';
        $this->resetErrorBag('authenticatorForm.code');
    }

    public function refreshHumanChallenge(LoginHumanChallengeService $humanChallenge): void
    {
        $this->form['human_challenge'] = '';
        $this->humanChallengeImage = $humanChallenge->issue();
        $this->resetErrorBag('form.human_challenge');
    }

    public function render()
    {
        return view(StarterTheme::viewName('starter.auth.login'), [
            'rememberMeEnabled' => ! (bool) config('starter.auth.login_otp_enabled', false)
                && $this->configs->boolean('security.remember_me_enabled'),
        ])->layoutData([
            'otpRequired' => $this->otpRequired,
            'authenticatorRequired' => $this->authenticatorRequired,
        ]);
    }

    private function syncPendingFactors(AuthLoginService $loginService): void
    {
        $pending = $loginService->pendingOtp();
        $this->otpRequired = $pending !== null;
        $this->maskedOtpEmail = $pending['masked_email'] ?? '';
        $this->authenticatorRequired = ! $this->otpRequired && $loginService->pendingTwoFactor();

        if (! $this->otpRequired) {
            $this->otpStatus = '';
        }
    }
}
