<div>
    <div class="alert alert-info" role="status" data-starter-region="context-notice">
        <div class="d-flex gap-2">
            @include('starter.templates.layouts.icon', ['name' => 'info-circle', 'class' => 'icon-sm flex-shrink-0 mt-1'])
            <div>Konfirmasi ini berlaku sementara selama session aktif.</div>
        </div>
    </div>

    <form wire:submit="confirm" autocomplete="on" data-starter-region="credentials-form">
        <div class="mb-3">
            <label class="form-label" for="confirm-password">Password</label>
            <div class="input-group input-group-flat" x-data="{ visible: false }">
                <input
                    x-bind:type="visible ? 'text' : 'password'"
                    class="form-control @error('password') is-invalid @enderror"
                    id="confirm-password"
                    wire:model.defer="password"
                    autofocus
                    autocomplete="current-password"
                    placeholder="Masukkan password saat ini"
                    @error('password') aria-invalid="true" aria-describedby="confirm-password-error" @enderror
                >
                <button
                    type="button"
                    class="input-group-text"
                    x-on:click="visible = ! visible"
                    x-bind:aria-label="visible ? 'Sembunyikan Password' : 'Tampilkan Password'"
                >
                    <span x-show="! visible">@include('starter.templates.layouts.icon', ['name' => 'eye', 'class' => 'icon-sm'])</span>
                    <span x-show="visible" x-cloak>@include('starter.templates.layouts.icon', ['name' => 'eye-off', 'class' => 'icon-sm'])</span>
                </button>
            </div>
            @error('password')
                <div id="confirm-password-error" class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-primary w-100" type="submit" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="confirm">
                @include('starter.templates.layouts.icon', ['name' => 'shield-check', 'class' => 'icon'])
                Lanjutkan
            </span>
            <span wire:loading wire:target="confirm">Memverifikasi...</span>
        </button>
    </form>

    <a href="{{ $cancelUrl }}" class="btn btn-link link-secondary w-100 mt-2" data-starter-region="secondary-action">
        Batal
    </a>
</div>
