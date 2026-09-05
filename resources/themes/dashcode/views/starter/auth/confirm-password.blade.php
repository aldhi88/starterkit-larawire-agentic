<div class="dashcode-auth-form">
    <div class="text-center mb-4" data-starter-region="identity-summary">
        <div class="starter-auth-mark mx-auto">
            @include('starter.templates.layouts.icon', ['name' => 'shield-lock', 'class' => 'icon'])
        </div>
        <h2 class="mt-3 mb-1">Konfirmasi Password</h2>
        <div class="text-secondary">Verifikasi diperlukan sebelum membuka pengaturan sensitif.</div>
    </div>

    <div class="dashcode-alert dashcode-alert-info" role="status" data-starter-region="context-notice">
        <div class="flex gap-2">
            @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'icon-sm flex-shrink-0 mt-1'])
            <div>Konfirmasi ini berlaku sementara selama session aktif.</div>
        </div>
    </div>

    <form class="space-y-4" wire:submit="confirm" autocomplete="on" data-starter-region="credentials-form">
        <div class="mb-3">
            <label class="form-label" for="confirm-password">Password</label>
            <div class="relative" x-data="{ visible: false }">
                <input
                    x-bind:type="visible ? 'text' : 'password'"
                    class="form-control !pr-12 @error('password') is-invalid @enderror"
                    id="confirm-password"
                    wire:model.defer="password"
                    autofocus
                    autocomplete="current-password"
                    placeholder="Masukkan password saat ini"
                    @error('password') aria-invalid="true" aria-describedby="confirm-password-error" @enderror
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
                <div id="confirm-password-error" class="invalid-feedback block">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-dark block w-full text-center" type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="confirm">
                @include('starter.templates.layouts.icon', ['name' => 'shield-check', 'class' => 'icon'])
                Lanjutkan
            </span>
            <span wire:loading wire:target="confirm">Memverifikasi...</span>
        </button>
    </form>

    <a href="{{ $cancelUrl }}" class="btn btn-link link-secondary mt-2 block w-full text-center" data-starter-region="secondary-action">
        Batal
    </a>
</div>
