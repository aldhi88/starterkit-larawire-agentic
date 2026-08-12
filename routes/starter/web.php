<?php

use Aldhi88\StarterKit\Http\Controllers\Starter\Auth\LogoutController;
use Aldhi88\StarterKit\Livewire\Starter\Auth\Login;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::get('/', function (Request $request, NavigationAuthorizedRedirectService $redirects) {
        $redirect = $request->query('redirect');
        $login = auth()->user();

        if ($login instanceof ClientLogin) {
            return redirect($redirects->forLogin($login, $redirect));
        }

        return redirect(StarterNavigation::authLoginUrl(StarterNavigation::isSafeRedirect($redirect) ? $redirect : null));
    })->name('home');

    Route::livewire('/login', Login::class)
        ->middleware('guest')
        ->name('login');

    Route::post('/logout', LogoutController::class)
        ->middleware('auth:web')
        ->name('logout');
});
