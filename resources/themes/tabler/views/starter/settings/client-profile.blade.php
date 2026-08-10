<div>
    @unless ($embedded)
        <div class="page-header d-print-none mt-0 mb-3" aria-label="Header halaman">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Starter / Pengaturan</div>
                    <h2 class="page-title">Profil Perusahaan</h2>
                </div>
            </div>
        </div>
    @endunless

    <form class="{{ $embedded ? '' : 'card' }}" wire:submit="save">
        <div class="card-body">
            @if(! $embedded)
                <h2 class="mb-4">Pengaturan Perusahaan</h2>
            @endif

            <h3 class="card-title">Logo</h3>
            <div class="row align-items-center">
                <div class="col-auto">
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
                <div class="col-auto">
                    <label class="btn btn-outline-primary mb-0" for="client-photo-upload">
                        Ganti Logo
                    </label>
                    <input type="file" id="client-photo-upload" class="d-none @error('clientPhotoUpload') is-invalid @enderror" wire:model="clientPhotoUpload" accept="image/*">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-ghost-danger" data-bs-toggle="modal" data-bs-target="#delete-client-photo-modal">
                        Hapus Logo
                    </button>
                </div>
                <div class="col-12">
                    @error('clientPhotoUpload') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    <div class="text-secondary small mt-2" wire:loading wire:target="clientPhotoUpload">Mengunggah...</div>
                </div>
            </div>

            <h3 class="card-title mt-4">Profil Perusahaan</h3>
            <div class="row g-3">
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

            <h3 class="card-title mt-4">Kontak</h3>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Email Perusahaan</label>
                    <input type="email" class="form-control @error('clientForm.email') is-invalid @enderror" wire:model.defer="clientForm.email">
                    @error('clientForm.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="{{ $embedded ? 'card-body border-top bg-transparent' : 'card-footer bg-transparent mt-auto' }}">
            <div class="btn-list justify-content-end">
                <button type="submit" class="btn btn-primary">
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
</div>
