<div class="dashcode-auth-form">
    <form class="space-y-4" wire:submit="authenticate" autocomplete="on" data-starter-region="credentials-form">
        <div>
            <label class="block capitalize form-label" for="username">Username atau Email</label>
            <input type="text" class="form-control py-2 @error('form.identifier') is-invalid @enderror" id="username" name="username" wire:model.defer="form.identifier" placeholder="Contoh: superuser atau nama@perusahaan.com" autofocus autocomplete="username" autocapitalize="none" spellcheck="false">
            @error('form.identifier')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="block capitalize form-label" for="password">Password</label>
            <input type="password" class="form-control py-2 @error('form.password') is-invalid @enderror" id="password" wire:model.defer="form.password" placeholder="Masukkan password" autocomplete="current-password">
            @error('form.password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if ($rememberMeEnabled)
            <div class="flex">
                <label class="flex items-center gap-2 cursor-pointer" for="remember">
                    <input type="checkbox" class="h-4 w-4 flex-none" id="remember" wire:model.defer="form.remember">
                    <span class="text-slate-500 text-sm leading-6">Ingat saya di perangkat ini</span>
                </label>
            </div>
        @endif

        <div>
            <button class="btn btn-dark block w-full text-center" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Login</span>
                <span wire:loading>Memproses...</span>
            </button>
        </div>
    </form>

    <div class="text-center text-secondary mt-3" data-starter-region="secondary-help">Hubungi administrator jika lupa password.</div>
</div>
