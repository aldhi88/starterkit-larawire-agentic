<div class="dashcode-company-form">
    @unless ($embedded)
        <div class="dashcode-page-heading" aria-label="Header halaman" data-starter-region="page-header">
            <div>
                <div class="page-pretitle">Starter / Pengaturan</div>
                <h2 class="page-title">Profil Perusahaan</h2>
            </div>
        </div>
    @endunless

    <form class="{{ $embedded ? '' : 'card' }}" wire:submit="save">
        <div class="card-body space-y-6">
            @if(! $embedded)
                <h2 class="mb-4">Pengaturan Perusahaan</h2>
            @endif

            <section class="space-y-4" aria-labelledby="company-logo-heading">
                <h3 id="company-logo-heading" class="card-title">Logo</h3>
                <div class="dashcode-upload-row" data-starter-region="logo-field">
                    <div>
                        <div class="starter-client-logo-preview" data-client-logo-preview>
                            @if ($clientLogoPreviewUrl)
                                <img
                                    src="{{ $clientLogoPreviewUrl }}"
                                    class="starter-client-logo-preview-image"
                                    alt="Pratinjau logo {{ $clientForm['name'] ?: 'perusahaan' }}"
                                >
                            @else
                                <span class="starter-client-logo-placeholder">{{ $clientInitials }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <label class="btn btn-outline-primary mb-0" for="client-photo-upload">
                            Ganti Logo
                        </label>
                        <input type="file" id="client-photo-upload" class="dashcode-visually-hidden @error('clientPhotoUpload') is-invalid @enderror" wire:model="clientPhotoUpload" accept="image/*">
                    </div>
                    <div>
                        <button type="button" class="btn btn-ghost-danger" data-starter-modal-open="#delete-client-photo-modal">
                            Hapus Logo
                        </button>
                    </div>
                    <div class="dashcode-upload-feedback">
                        @error('clientPhotoUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                        <div class="text-secondary small mt-2" wire:loading wire:target="clientPhotoUpload">Mengunggah...</div>
                    </div>
                </div>
            </section>

            <section class="space-y-4" aria-labelledby="company-profile-heading">
                <h3 id="company-profile-heading" class="card-title">Profil Perusahaan</h3>
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
                <h3 id="company-contact-heading" class="card-title">Kontak</h3>
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
                <button type="submit" class="btn btn-primary">
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
</div>
