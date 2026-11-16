                @include('starter.cms.category-picker', ['categories' => $postCategoryTree, 'field' => 'form.category_ids', 'pickerId' => 'cms-form-categories'])
                <details class="cms-post-category-create mt-3">
                    <summary>Buat kategori baru</summary>
                    <div class="vstack gap-2 pt-3">
                        <div>
                            <label class="form-label" for="cms-new-category-name">Nama kategori</label>
                            <input id="cms-new-category-name" type="text" class="form-control form-control-sm @error('newCategoryName') is-invalid @enderror" wire:model.defer="newCategoryName" maxlength="255" placeholder="Contoh: Berita Daerah" x-on:keydown.enter.prevent="$wire.createPostCategory()">
                            @error('newCategoryName')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="cms-new-category-parent">Kategori induk <span class="fw-normal text-secondary">(opsional)</span></label>
                            <select id="cms-new-category-parent" class="form-select form-select-sm @error('newCategoryParentId') is-invalid @enderror" wire:model.defer="newCategoryParentId">
                                <option value="">Kategori utama</option>
                                @foreach ($postCategoryTree as $category)
                                    <option value="{{ $category['id'] }}">{{ $category['path'] }}</option>
                                @endforeach
                            </select>
                            @error('newCategoryParentId')<div class="invalid-feedback d-block" role="alert">{{ $message }}</div>@enderror
                        </div>
                        <button type="button" class="btn btn-sm align-self-start" wire:click="createPostCategory" wire:loading.attr="disabled" wire:target="createPostCategory">Buat dan pilih</button>
                    </div>
                </details>
                @error('form.category_ids')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
                @error('form.category_ids.*')<div class="text-danger small" role="alert">{{ $message }}</div>@enderror
