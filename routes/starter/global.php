<?php

use Altekno\StarterKit\Http\Controllers\Starter\Auth\TouchSessionActivityController;
use Altekno\StarterKit\Livewire\Starter\Auth\ConfirmPassword;
use Altekno\StarterKit\Livewire\Starter\Auth\LockScreen;
use Altekno\StarterKit\Livewire\Starter\Logs\ActivityLogIndex;
use Altekno\StarterKit\Livewire\Starter\Profile\EditMyProfile;
use Altekno\StarterKit\Livewire\Starter\Settings\SettingsIndex;
use Altekno\StarterKit\Livewire\Starter\UserManagement\RoleForm;
use Altekno\StarterKit\Livewire\Starter\UserManagement\UserForm;
use Illuminate\Support\Facades\Route;

Route::livewire('/confirm-password', ConfirmPassword::class)
    ->middleware(['auth:web', 'starter.active', 'starter.password-change', 'starter.lock'])
    ->name('password.confirm');

Route::name('starter.')->middleware(['auth:web', 'starter.active'])->group(function (): void {
    Route::livewire('/lock-screen', LockScreen::class)->name('lock-screen');

    Route::post('/session/activity', TouchSessionActivityController::class)
        ->middleware('starter.password-change')
        ->name('session.activity');
});

Route::name('starter.')
    ->middleware(['auth:web', 'starter.active', 'starter.password-change', 'starter.lock'])
    ->group(function (): void {
        Route::livewire('/profile/edit', EditMyProfile::class)->name('profile.edit');

        Route::livewire('/activity-logs', ActivityLogIndex::class)
            ->middleware('starter.logs')
            ->name('logs.index');

        Route::livewire('/settings', SettingsIndex::class)
            ->middleware(['starter.admin', 'password.confirm'])
            ->name('settings');

        Route::prefix('settings/roles')
            ->name('settings.roles.')
            ->middleware(['starter.admin', 'password.confirm'])
            ->group(function (): void {
                Route::livewire('/create', RoleForm::class)->name('create');
                Route::livewire('/{roleId}/edit', RoleForm::class)->name('edit');
            });

        Route::prefix('user-management')
            ->name('user-management.')
            ->middleware(['starter.admin', 'password.confirm'])
            ->group(function (): void {
                Route::get('/roles', fn () => redirect()->route('starter.settings', ['section' => 'roles']))->name('roles');
                Route::livewire('/users/create', UserForm::class)->name('users.create');
                Route::livewire('/users/{userLoginId}/edit', UserForm::class)->name('users.edit');
                Route::get('/users', fn () => redirect()->route('starter.settings', ['section' => 'users']))->name('users');
            });

        Route::get('/client-profile', fn () => redirect()->route('starter.settings', ['section' => 'company']))
            ->middleware(['starter.admin', 'password.confirm'])
            ->name('client-profile');
    });
