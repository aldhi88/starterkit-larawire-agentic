<fieldset class="cms-status-options">
    <legend class="form-label">Status publikasi</legend>
    <div class="cms-status-grid">
        @foreach (['draft' => ['Draf', 'Belum ditampilkan'], 'published' => ['Terbit', 'Sesuai waktu terbit']] as $value => [$label, $hint])
            <label class="{{ $dash ? 'border border-slate-200 dark:border-slate-600 rounded' : 'border rounded' }} cms-status-option" :class="$wire.form.status === @js($value) ? '{{ $dash ? 'text-primary-500' : 'text-primary' }}' : ''">
                <input type="radio" name="cms-publication-{{ $this->getId() }}" value="{{ $value }}" wire:model.defer="form.status" class="{{ $dash ? 'rounded-full' : 'form-check-input' }}">
                <span><strong>{{ $label }}</strong><small>{{ $hint }}</small></span>
            </label>
        @endforeach
    </div>
    @error('form.status')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
</fieldset>
