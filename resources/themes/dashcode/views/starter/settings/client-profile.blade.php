<div class="dashcode-company-form">
    @unless ($embedded)
        <div class="dashcode-page-heading mb-5" aria-label="Header halaman" data-starter-region="page-header">
            <div>
                <h2 class="page-title">Profil Perusahaan</h2>
                <div class="text-secondary">Perbarui profil perusahaan, logo aplikasi, dan favicon.</div>
            </div>
        </div>
    @endunless

    <form class="{{ $embedded ? '' : 'card' }}" wire:submit="save"
        x-data="{ brandingBusy: false }"
        x-bind:aria-busy="brandingBusy"
        x-on:starter-brand-crop-busy.window="brandingBusy = $event.detail"
        x-on:submit.capture="if (brandingBusy) { $event.preventDefault(); $event.stopImmediatePropagation() }"
    >
        <div class="card-body space-y-6">
            @if(! $embedded)
                <h2 class="card-title mb-4">Pengaturan Perusahaan</h2>
            @endif

            <section class="space-y-4" aria-labelledby="company-logo-heading">
                <h3 id="company-logo-heading" class="card-title starter-form-section-title">Logo Aplikasi</h3>
                <div
                    class="dashcode-upload-row"
                    data-starter-region="logo-field"
                    x-data="StarterTemplate.brandImageCrop(@js($clientLogoPreviewUrl), 'logo', 'clientPhotoUpload')"
                    x-on:starter-client-branding-updated.window="replace($event.detail.logoUrl)"
                >
                    <div>
                        <div class="starter-client-logo-preview" data-client-logo-preview>
                            @include('starter-shared::components.client-logo-preview')
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary mb-0" x-on:click="$refs.fileInput.click()">
                            Ganti Logo
                        </button>
                        <input type="file" x-ref="fileInput" id="client-photo-upload" class="dashcode-visually-hidden @error('clientPhotoUpload') is-invalid @enderror" x-on:change="select($event)" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <div>
                        <button type="button" class="btn btn-ghost-danger" data-starter-modal-open="#delete-client-photo-modal">
                            Hapus Logo
                        </button>
                    </div>
                    <div class="dashcode-upload-feedback">
                        @error('clientPhotoUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                        <div class="text-secondary small mt-2" wire:loading wire:target="clientPhotoUpload">Mengunggah...</div>
                        <div class="text-secondary small mt-2" x-show="decoding" role="status">Mempersiapkan gambar…</div>
                        <div class="text-danger small mt-2" x-show="error" x-text="error" role="alert"></div>
                        @include('starter.templates.components.brand-crop-modal')
                    </div>
                </div>
                <p class="text-secondary small">PNG, JPG, atau WebP hingga 10 MB. Atur crop logo 4:1; hasil PNG 800 × 200 px tanpa latar tambahan.</p>
            </section>

            <section class="space-y-4" aria-labelledby="application-favicon-heading">
                <h3 id="application-favicon-heading" class="card-title starter-form-section-title">Favicon</h3>
                <div
                    class="dashcode-upload-row"
                    data-starter-region="favicon-field"
                    x-data="StarterTemplate.brandImageCrop(@js($clientFaviconPreviewUrl), 'favicon', 'clientFaviconUpload')"
                    x-on:starter-client-branding-updated.window="replace($event.detail.faviconUrl)"
                >
                    <div>
                        <div class="starter-client-logo-preview starter-favicon-preview" data-client-logo-preview>
                            @include('starter-shared::components.client-logo-preview', ['clientLogoPreviewUrl' => $clientFaviconPreviewUrl, 'previewLabel' => 'Pratinjau favicon'])
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-primary mb-0" x-on:click="$refs.fileInput.click()">
                            Ganti Favicon
                        </button>
                        <input type="file" x-ref="fileInput" id="client-favicon-upload" class="dashcode-visually-hidden @error('clientFaviconUpload') is-invalid @enderror" x-on:change="select($event)" accept="image/png,image/jpeg,image/webp">
                    </div>
                    <div>
                        <button type="button" class="btn btn-ghost-danger" data-starter-modal-open="#delete-client-favicon-modal">
                            Hapus Favicon
                        </button>
                    </div>
                    <div class="dashcode-upload-feedback">
                        @error('clientFaviconUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                        <div class="text-secondary small mt-2" wire:loading wire:target="clientFaviconUpload">Mengunggah...</div>
                        <div class="text-secondary small mt-2" x-show="decoding" role="status">Mempersiapkan gambar…</div>
                        <div class="text-danger small mt-2" x-show="error" x-text="error" role="alert"></div>
                        @include('starter.templates.components.brand-crop-modal')
                    </div>
                </div>
                <p class="text-secondary small">PNG, JPG, atau WebP hingga 10 MB. Crop persegi menghasilkan PNG 256 × 256 px untuk tab browser.</p>
            </section>

            <section class="space-y-4" aria-labelledby="company-profile-heading">
                <h3 id="company-profile-heading" class="card-title starter-form-section-title">Profil Perusahaan</h3>
                <div class="dashcode-form-grid dashcode-form-grid-3" data-starter-region="company-details">
                    <div>
                        <label class="form-label">Nama Perusahaan</label>
                        <input type="text" class="form-control @error('clientForm.name') is-invalid @enderror" wire:model.defer="clientForm.name">
                        @error('clientForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label">Nama PIC</label>
                        <input type="text" class="form-control @error('clientForm.pic_name') is-invalid @enderror" wire:model.defer="clientForm.pic_name">
                        @error('clientForm.pic_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div>
                        <label class="form-label">Telepon</label>
                        <input type="text" class="form-control @error('clientForm.phone') is-invalid @enderror" wire:model.defer="clientForm.phone">
                        @error('clientForm.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>

            <section class="space-y-4" aria-labelledby="company-contact-heading">
                <h3 id="company-contact-heading" class="card-title starter-form-section-title">Kontak</h3>
                <div class="dashcode-form-grid dashcode-form-grid-2" data-starter-region="contact-details">
                    <div>
                        <label class="form-label">Email Perusahaan</label>
                        <input type="email" class="form-control @error('clientForm.email') is-invalid @enderror" wire:model.defer="clientForm.email">
                        @error('clientForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>
        </div>

        <div class="{{ $embedded ? 'card-body border-top bg-transparent' : 'card-footer bg-transparent mt-auto' }}" data-starter-region="page-actions">
            <div class="dashcode-actions">
                <button type="submit" class="btn btn-primary" x-bind:disabled="brandingBusy" wire:loading.attr="disabled" wire:target="save">
                    @include('starter.templates.layouts.icon', ['name' => 'check'])
                    Simpan Profil Perusahaan
                </button>
            </div>
        </div>
    </form>

    @include('starter.templates.components.danger-modal', [
        'id' => 'delete-client-photo-modal',
        'title' => 'Hapus logo?',
        'message' => 'Logo saat ini akan diganti dengan logo default.',
        'confirmText' => 'Hapus Logo',
        'confirmAction' => 'resetClientPhoto',
        'dismissOnConfirm' => true,
    ])
    @include('starter.templates.components.danger-modal', [
        'id' => 'delete-client-favicon-modal',
        'title' => 'Hapus favicon?',
        'message' => 'Favicon akan kembali ke ikon default. Logo aplikasi tetap tersimpan.',
        'confirmText' => 'Hapus Favicon',
        'confirmAction' => 'resetClientFavicon',
        'dismissOnConfirm' => true,
    ])
</div>
