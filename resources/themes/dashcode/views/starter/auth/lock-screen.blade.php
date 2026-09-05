<div class="dashcode-auth-form">
    <div class="text-center mb-4" data-starter-region="identity-summary">
        <span class="avatar avatar-xl rounded-circle" style="background-image: url({{ app(\Aldhi88\StarterKit\Services\Starter\StarterContextService::class)->avatarUrl($login) }})"></span>
        <div class="mt-3 h3 mb-1">{{ $login->name }}</div>
        <div class="text-secondary">{{ $login->role?->name ?? 'User' }}</div>
    </div>

    <div class="dashcode-alert dashcode-alert-info" role="status" data-starter-region="context-notice">
        <div class="flex gap-2">
            @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'icon-sm flex-shrink-0 mt-1'])
            <div>
                Sesi login tetap aktif. Masukkan password untuk membuka kembali aplikasi.
            </div>
        </div>
    </div>

    <form class="space-y-4" wire:submit="unlock" autocomplete="on" data-starter-region="credentials-form">
        <div class="mb-3">
            <label class="form-label" for="lock-screen-password">Password</label>
            <div class="relative" x-data="{ visible: false }">
                <input
                    x-bind:type="visible ? 'text' : 'password'"
                    class="form-control !pr-12 @error('password') is-invalid @enderror"
                    id="lock-screen-password"
                    wire:model.defer="password"
                    autofocus
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    @error('password') aria-invalid="true" aria-describedby="lock-screen-password-error" @enderror
                >
                <button
                    type="button"
                    class="absolute right-0 top-1/2 h-full w-9 -translate-y-1/2 border-l border-l-slate-200 flex items-center justify-center text-slate-500"
                    x-on:click="visible = ! visible"
                    x-bind:aria-label="visible ? 'Sembunyikan Password' : 'Tampilkan Password'"
                >
                    <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm'])</span>
                    <span x-show="visible" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm'])</span>
                </button>
            </div>
            @error('password')
                <div id="lock-screen-password-error" class="invalid-feedback block">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-dark block w-full text-center" type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="unlock">
                @include('starter.templates.layouts.icon', ['name' => 'lock', 'class' => 'icon'])
                Buka Aplikasi
            </span>
            <span wire:loading wire:target="unlock">Memeriksa...</span>
        </button>
    </form>

    <form method="POST" action="{{ route('auth.logout') }}" class="text-center mt-3" data-starter-region="secondary-action">
        @csrf
        <button type="submit" class="btn btn-link link-secondary p-0">Logout dan ganti user</button>
    </form>
</div>
