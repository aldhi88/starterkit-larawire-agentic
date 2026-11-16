<div class="cms-card-items" x-data="{ itemErrors: @js($errors->toArray()) }" wire:key="card-items-{{ md5(json_encode($errors->toArray())) }}" x-bind:hidden="!isCollection && !Object.keys(itemErrors).some(key => key.startsWith('form.card_data.items'))">
    <div class="cms-card-items-heading" :class="{'cms-collection-start': !items.length}">
        <div class="cms-collection-symbol" x-bind:hidden="items.length > 0" aria-hidden="true">
            @foreach ($presetPresentation as $key => $icon)
                <span x-bind:hidden="preset !== @js($key)">@include('starter.templates.layouts.icon', ['name' => $icon, 'class' => 'icon'])</span>
            @endforeach
        </div>
        <div><h3 class="card-title"><span x-text="'Daftar ' + itemLabel"></span> <span class="cms-item-count" x-bind:hidden="!items.length" x-text="items.length"></span></h3><p class="form-hint">Susun hingga 50 item sesuai urutan tampil.</p></div>
        <button type="button" class="btn btn-sm btn-outline-primary" @click="addItem()" :disabled="items.length >= 50" data-cms-add-card-item x-text="'Tambah ' + itemLabel + (items.length ? '' : ' pertama')"></button>
    </div>
    @error('form.card_data.items')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
    <div class="cms-card-item-undo" x-bind:hidden="!removedItem" role="status"><span>Item dihapus dari daftar.</span><button type="button" class="btn btn-sm btn-link" :disabled="items.length >= 50" @click="undoRemove()">Urungkan</button></div>
    <div class="cms-card-item-list" x-data="window.StarterCms.cardItemSorting()">
        <template x-for="(item, index) in items" :key="item.key">
            <section class="cms-card-item {{ $dash ? 'border border-slate-200 dark:border-slate-600 rounded' : 'border rounded' }}" :data-cms-card-item="item.key" :data-drop-edge="item.key === dropKey ? dropEdge : null" :class="{'is-dragged':item.key === draggingKey}">
                <div class="cms-card-item-header">
                    <span class="cms-card-grip" @pointerdown.prevent="startSort($event, item.key)" @pointermove="trackSort($event)" @pointerup="finishSort($event, true)" @pointercancel="finishSort($event, false)" @lostpointercapture="finishSort($event, false)" x-bind:hidden="items.length < 2" title="Seret untuk mengubah urutan" aria-hidden="true"></span>
                    <button type="button" class="cms-card-item-toggle" @click="openItem = openItem === item.key ? null : item.key" :aria-expanded="(openItem === item.key).toString()" :aria-controls="'cms-card-item-body-' + item.key">
                        <template x-if="itemImage(item)?.url"><img class="cms-card-item-avatar" :src="itemImage(item).url" alt=""></template>
                        <span class="cms-card-item-number" x-bind:hidden="Boolean(itemImage(item)?.url)" aria-hidden="true" x-text="itemMark(item, index)"></span>
                        <span class="cms-card-item-summary"><strong x-text="itemTitle(item, index)"></strong><small x-text="itemDescription(item)"></small></span>
                        @include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon'])
                    </button>
                    <div class="cms-card-item-actions">
                        <button type="button" class="btn btn-sm cms-quiet-action" @click="moveItem(index, -1)" :disabled="index === 0" :aria-label="'Naikkan ' + itemTitle(item, index)">@include('starter.templates.layouts.icon', ['name' => 'chevron-up', 'class' => 'icon'])</button>
                        <button type="button" class="btn btn-sm cms-quiet-action" @click="moveItem(index, 1)" :disabled="index === items.length - 1" :aria-label="'Turunkan ' + itemTitle(item, index)">@include('starter.templates.layouts.icon', ['name' => 'chevron-down', 'class' => 'icon'])</button>
                        <button type="button" class="btn btn-sm cms-quiet-action cms-item-remove" @click="removeItem(index)" :aria-label="'Hapus ' + itemTitle(item, index)">@include('starter.templates.layouts.icon', ['name' => 'trash', 'class' => 'icon'])</button>
                    </div>
                </div>
                <div class="cms-card-item-body cms-authoring-stack" :data-preset="preset" :id="'cms-card-item-body-' + item.key" x-bind:hidden="openItem !== item.key && !Object.keys(itemErrors).some(key => key.startsWith('form.card_data.items.' + index + '.'))">
                    <div class="cms-card-assets">
                        @foreach (['image_id' => 'Gambar', 'logo_id' => 'Logo', 'thumbnail_id' => 'Thumbnail'] as $itemField => $itemLabel)
                            <div x-bind:hidden="!itemVisible(@js($itemField)) && !itemErrors['form.card_data.items.' + index + '.{{ $itemField }}']">
                                <div class="form-label" x-text="itemFieldLabel(@js($itemField), @js($itemLabel))">{{ $itemLabel }}</div>
                                @include('starter-shared::cms.asset-field', [
                                    'assetField' => 'card_data.items', 'assetId' => '', 'assetDynamicId' => "'cms-item-' + item.key + '-".$itemField."'",
                                    'assetLabel' => $itemLabel, 'assetValue' => 'selected[item.'.$itemField.']',
                                    'assetChoose' => "chooseItemMedia(item.key, '".$itemField."')", 'assetRemove' => 'item.'.$itemField.' = null',
                                ])
                                <div class="text-danger" role="alert" x-text="itemErrors['form.card_data.items.' + index + '.{{ $itemField }}']?.join(' ')"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="cms-card-detail-grid">
                        @foreach ($fields + ['body' => ['Isi lengkap', 'textarea', 'Tulis isi atau jawaban…', 10000]] as $itemField => $definition)
                            @include('starter-shared::cms.card-item-field')
                        @endforeach
                    </div>
                </div>
            </section>
        </template>
    </div>
</div>
