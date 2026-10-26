<?php

use Aldhi88\StarterKit\Http\Controllers\Cms\CmsMediaController;
use Aldhi88\StarterKit\Livewire\Cms\Categories;
use Aldhi88\StarterKit\Livewire\Cms\ContentForm;
use Aldhi88\StarterKit\Livewire\Cms\ContentIndex;
use Aldhi88\StarterKit\Livewire\Cms\Dashboard;
use Aldhi88\StarterKit\Livewire\Cms\Documentation;
use Aldhi88\StarterKit\Livewire\Cms\MediaLibrary;
use Aldhi88\StarterKit\Models\Starter\ClientLogin;
use Aldhi88\StarterKit\Services\Starter\NavigationAuthorizedRedirectService;
use Aldhi88\StarterKit\Support\Cms\CmsResource;
use Aldhi88\StarterKit\Support\Starter\StarterNavigation;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    $redirect = request()->query('redirect', url('/'));

    return redirect(StarterNavigation::authLoginUrl(StarterNavigation::isSafeRedirect($redirect) ? $redirect : url('/')));
});

Route::middleware(['auth:web', 'starter.active', 'starter.password-change', 'starter.lock'])->group(function () {
    Route::get('/', function (NavigationAuthorizedRedirectService $redirects) {
        $login = auth()->user();

        return $login instanceof ClientLogin
            ? redirect($redirects->forAppAnchor($login, 'cms'))
            : redirect()->route('auth.login');
    })->name('cms.anchor');

    Route::middleware('starter.authorize')->group(function () {
        Route::livewire('/dashboard', Dashboard::class)->name('cms.dashboard');

        Route::livewire('/media/galleries/albums', ContentIndex::class)
            ->defaults('resource', 'galleries')->name('cms.media.albums.index');

        foreach (CmsResource::cases() as $resource) {
            $path = str_replace('.', '/', substr($resource->routePrefix(), 4));
            $taxonomy = $resource->isCategory() || $resource === CmsResource::MenuCategories;
            $library = in_array($resource, [CmsResource::Media, CmsResource::Galleries], true);
            $indexComponent = $taxonomy ? Categories::class : ($library ? MediaLibrary::class : ContentIndex::class);
            $formComponent = $taxonomy ? Categories::class : ($resource === CmsResource::Media ? MediaLibrary::class : ContentForm::class);
            $mode = $resource === CmsResource::Galleries ? 'gallery' : 'upload';
            $indexRoute = Route::livewire('/'.$path, $indexComponent)
                ->defaults('resource', $resource->value)->name($resource->routePrefix().'.index');
            $createRoute = Route::livewire('/'.$path.'/baru', $formComponent)
                ->defaults('resource', $resource->value)->name($resource->routePrefix().'.create');
            $editRoute = Route::livewire('/'.$path.'/{contentId}/edit', $formComponent)
                ->whereNumber('contentId')->defaults('resource', $resource->value)->name($resource->routePrefix().'.edit');
            if ($library) {
                $indexRoute->defaults('mode', $mode);
            }
            if ($resource === CmsResource::Media) {
                $createRoute->defaults('mode', 'upload');
                $editRoute->defaults('mode', 'upload');
            }
        }
    });
});

Route::domain(config('app.domain'))->group(function () {
    Route::livewire('/cms/documentation', Documentation::class)->name('starter.cms.documentation');
    Route::get('/cms/media/{publicId}', CmsMediaController::class)
        ->whereUlid('publicId')->name('starter.cms.media');
});
