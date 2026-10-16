<div>
    @unless ($embedded)
        <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman" data-starter-region="page-header">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Profil Perusahaan</h2>
                    <div class="text-secondary">Perbarui profil perusahaan, logo aplikasi, dan favicon.</div>
                </div>
            </div>
        </div>
    @endunless

    <form class="{{ $embedded ? '' : 'card' }}" wire:submit="save"
        x-data="{ brandingBusy: false }"
        x-bind:aria-busy="brandingBusy"
        x-on:starter-brand-crop-busy.window="brandingBusy = $event.detail"
        x-on:submit.capture="if (brandingBusy) { $event.preventDefault(); $event.stopImmediatePropagation() }"
    >
        <div class="card-body">
            @if(! $embedded)
                <h2 class="card-title mb-4">Pengaturan Perusahaan</h2>
            @endif

            <h3 class="card-title starter-form-section-title">Logo Aplikasi</h3>
            <div
                class="row align-items-center"
                data-starter-region="logo-field"
                x-data="StarterTemplate.brandImageCrop(@js($clientLogoPreviewUrl), 'logo', 'clientPhotoUpload')"
                x-on:starter-client-branding-updated.window="replace($event.detail.logoUrl)"
            >
                <div class="col-auto">
                    <div class="starter-client-logo-preview" data-client-logo-preview>
                        @include('starter-shared::components.client-logo-preview')
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-primary mb-0" x-on:click="$refs.fileInput.click()">
                        Ganti Logo
                    </button>
                    <input type="file" x-ref="fileInput" id="client-photo-upload" class="d-none @error('clientPhotoUpload') is-invalid @enderror" x-on:change="select($event)" accept="image/png,image/jpeg,image/webp">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-label-secondary" data-starter-modal-open="#delete-client-photo-modal">
                        Hapus Logo
                    </button>
                </div>
                <div class="col-12">
                    @error('clientPhotoUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    <div class="text-secondary small mt-2" wire:loading wire:target="clientPhotoUpload">Mengunggah...</div>
                    <div class="text-secondary small mt-2" x-show="decoding" role="status">Mempersiapkan gambar…</div>
                    <div class="text-danger small mt-2" x-show="error" x-text="error" role="alert"></div>
                    @include('starter.templates.components.brand-crop-modal')
                </div>
            </div>

            <p class="text-secondary small mt-2">PNG, JPG, atau WebP hingga 10 MB. Atur crop logo 4:1; hasil PNG 800 × 200 px tanpa latar tambahan.</p>

            <h3 class="card-title starter-form-section-title mt-4">Favicon</h3>
            <div
                class="row align-items-center"
                data-starter-region="favicon-field"
                x-data="StarterTemplate.brandImageCrop(@js($clientFaviconPreviewUrl), 'favicon', 'clientFaviconUpload')"
                x-on:starter-client-branding-updated.window="replace($event.detail.faviconUrl)"
            >
                <div class="col-auto">
                    <div class="starter-client-logo-preview starter-favicon-preview" data-client-logo-preview>
                        @include('starter-shared::components.client-logo-preview', ['clientLogoPreviewUrl' => $clientFaviconPreviewUrl, 'previewLabel' => 'Pratinjau favicon'])
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-primary mb-0" x-on:click="$refs.fileInput.click()">
                        Ganti Favicon
                    </button>
                    <input type="file" x-ref="fileInput" id="client-favicon-upload" class="d-none @error('clientFaviconUpload') is-invalid @enderror" x-on:change="select($event)" accept="image/png,image/jpeg,image/webp">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-label-secondary" data-starter-modal-open="#delete-client-favicon-modal">
                        Hapus Favicon
                    </button>
                </div>
                <div class="col-12">
                    @error('clientFaviconUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    <div class="text-secondary small mt-2" wire:loading wire:target="clientFaviconUpload">Mengunggah...</div>
                    <div class="text-secondary small mt-2" x-show="decoding" role="status">Mempersiapkan gambar…</div>
                    <div class="text-danger small mt-2" x-show="error" x-text="error" role="alert"></div>
                    @include('starter.templates.components.brand-crop-modal')
                </div>
            </div>

            <p class="text-secondary small mt-2">PNG, JPG, atau WebP hingga 10 MB. Crop persegi menghasilkan PNG 256 × 256 px untuk tab browser.</p>

            <h3 class="card-title starter-form-section-title mt-4">Profil Perusahaan</h3>
            <div class="row g-3" data-starter-region="company-details">
                <div class="col-md-4">
                    <label class="form-label">Nama Perusahaan</label>
                    <input type="text" class="form-control @error('clientForm.name') is-invalid @enderror" wire:model.defer="clientForm.name">
                    @error('clientForm.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Nama PIC</label>
                    <input type="text" class="form-control @error('clientForm.pic_name') is-invalid @enderror" wire:model.defer="clientForm.pic_name">
                    @error('clientForm.pic_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Telepon</label>
                    <input type="text" class="form-control @error('clientForm.phone') is-invalid @enderror" wire:model.defer="clientForm.phone">
                    @error('clientForm.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <h3 class="card-title starter-form-section-title mt-4">Kontak</h3>
            <div class="row g-3" data-starter-region="contact-details">
                <div class="col-md-6">
                    <label class="form-label">Email Perusahaan</label>
                    <input type="email" class="form-control @error('clientForm.email') is-invalid @enderror" wire:model.defer="clientForm.email">
                    @error('clientForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="{{ $embedded ? 'card-body border-top bg-transparent' : 'card-footer bg-transparent mt-auto' }}" data-starter-region="page-actions">
            <div class="btn-list justify-content-end">
                <button type="submit" class="btn btn-primary" x-bind:disabled="brandingBusy" wire:loading.attr="disabled" wire:target="save">
                    @include('starter.templates.layouts.icon', ['name' => 'check', 'class' => 'me-1'])
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
