<?php

namespace Aldhi88\StarterKit\Livewire\Starter\Auth;

use Aldhi88\StarterKit\Services\Starter\AuthLoginService;
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
     * @var array{identifier: string, password: string, remember: bool}
     */
    public array $form = [
        'identifier' => '',
        'password' => '',
        'remember' => false,
    ];

    public string $redirect = '';

    /** @var array{code: string} */
    public array $otpForm = [
        'code' => '',
    ];

    public bool $otpRequired = false;

    public string $maskedOtpEmail = '';

    public string $otpStatus = '';

    public function boot(StarterConfigService $configs): void
    {
        $this->configs = $configs;
    }

    public function mount(AuthLoginService $loginService): void
    {
        $this->redirect = request()->query('redirect', '');
        $this->syncPendingOtp($loginService);
    }

    public function authenticate(AuthLoginService $loginService)
    {
        try {
            $this->validate([
                'form.identifier' => ['required', 'string', 'max:255'],
                'form.password' => ['required', 'string', 'max:1024'],
            ], [], [
                'form.identifier' => 'username atau email',
                'form.password' => 'password',
            ]);

            $target = $loginService->attempt(
                username: $this->form['identifier'],
                password: $this->form['password'],
                remember: $this->form['remember'],
                redirect: $this->redirect,
            );
        } catch (ValidationException $exception) {
            $this->form['password'] = '';

            throw $exception;
        }

        $this->form['password'] = '';

        if ($target === null) {
            $this->syncPendingOtp($loginService);
            $this->otpStatus = 'Kode OTP telah dikirim. Periksa inbox email Anda.';

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
            $this->syncPendingOtp($loginService);

            throw $exception;
        }

        $this->otpForm['code'] = '';

        return $this->redirect($target);
    }

    public function resendOtp(AuthLoginService $loginService): void
    {
        $this->resetErrorBag('otpForm.code');
        $loginService->resendOtp();
        $this->syncPendingOtp($loginService);
        $this->otpForm['code'] = '';
        $this->otpStatus = 'Kode OTP baru telah dikirim.';
    }

    public function cancelOtp(AuthLoginService $loginService): void
    {
        $loginService->cancelOtp();
        $this->reset(['otpForm', 'otpRequired', 'maskedOtpEmail', 'otpStatus']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view(StarterTheme::viewName('starter.auth.login'), [
            'rememberMeEnabled' => ! (bool) config('starter.auth.login_otp_enabled', false)
                && $this->configs->boolean('security.remember_me_enabled'),
        ])->layoutData([
            'otpRequired' => $this->otpRequired,
        ]);
    }

    private function syncPendingOtp(AuthLoginService $loginService): void
    {
        $pending = $loginService->pendingOtp();
        $this->otpRequired = $pending !== null;
        $this->maskedOtpEmail = $pending['masked_email'] ?? '';

        if (! $this->otpRequired) {
            $this->otpStatus = '';
        }
    }
}
