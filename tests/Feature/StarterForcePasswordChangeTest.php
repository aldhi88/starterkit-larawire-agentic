<?php

use Aldhi88\StarterKit\Http\Middleware\Starter\StarterForcePasswordChange;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

function forcePasswordChangeRequest(string $method, string $uri, string $routeName): Request
{
    $request = Request::create($uri, $method);
    $route = new RoutingRoute([$method], $uri, fn () => null);
    $route->name($routeName);
    $request->setRouteResolver(fn (): RoutingRoute => $route);

    $login = new ClientLogin;
    $login->forceFill([
        'id' => 2,
        'username' => 'temporary-password-user',
        'must_change_password' => true,
    ]);
    $request->setUserResolver(fn (): ClientLogin => $login);

    return $request;
}

it('allows session activity heartbeats while a password change is required', function (): void {
    $request = forcePasswordChangeRequest('POST', '/session/activity', 'starter.session.activity');

    $response = (new StarterForcePasswordChange)->handle(
        $request,
        fn () => response()->noContent(),
    );

    expect($response->getStatusCode())->toBe(204);
});

it('keeps redirecting other routes to profile security while a password change is required', function (): void {
    Route::get('/profile/edit-test', fn () => null)->name('starter.profile.edit');
    $request = forcePasswordChangeRequest('GET', '/dashboard', 'hr.dashboard');

    $response = (new StarterForcePasswordChange)->handle(
        $request,
        fn () => response('unreachable'),
    );

    expect($response->isRedirect(route('starter.profile.edit', ['tab' => 'security'])))->toBeTrue();
});
