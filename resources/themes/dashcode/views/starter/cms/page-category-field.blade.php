@include('starter.cms.fields.input', ['field' => 'category_id', 'label' => 'Kategori Halaman', 'type' => 'select', 'choices' => $categoryOptions, 'emptyLabel' => 'Pilih kategori', 'required' => true])
<details class="cms-post-category-create mt-3">
    <summary>Buat kategori baru</summary>
    <div class="pt-2">
        <label class="form-label" for="cms-new-page-category">Nama kategori</label>
        <input id="cms-new-page-category" type="text" class="form-control w-full" wire:model.defer="newCategoryName" maxlength="255" autocomplete="off" wire:keydown.enter.prevent="createPageCategory">
        @error('newCategoryName')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
        <button type="button" class="btn cms-btn-neutral btn-sm mt-2" wire:click="createPageCategory" wire:loading.attr="disabled" wire:target="createPageCategory">Buat dan pilih</button>
    </div>
</details>
