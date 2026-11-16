<div class="cms-header-actions">
    @include('starter-shared::cms.back-link', ['backUrl' => route($cmsResource->managementIndexRoute()), 'backClass' => 'cms-btn-neutral'])
    <button type="submit" form="cms-content-form-{{ $this->getId() }}" data-starter-region="page-actions" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,upload,editorUploads,finishEditorUploads" x-bind:disabled="cmsBusy">
        @include('starter.cms.action-icon', ['action' => 'save'])
        <span wire:loading.remove wire:target="save">{{ $resource === 'comments' ? 'Simpan moderasi' : 'Simpan '.$cmsResource->singular() }}</span>
        <span wire:loading wire:target="save" role="status">Menyimpan…</span>
    </button>
</div>
